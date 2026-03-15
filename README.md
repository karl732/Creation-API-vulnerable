## Application PHP volontairement vulnérable

Cette mini-application est destinée **uniquement à des fins de tests** sur les vulnérabilités web (SQL injections, contrôle d'accès, élévation de privilèges, etc.).
Pour pouvoir déployer et tester en toute sécurité mon application, j'ai d'abord créé une machine virtuelle sur VM Ware Workstation Pro, c'est pourquoi je recommande d'en faire de même.

### Fichiers principaux

- `config.php` : connexion non sécurisée à la base MySQL `bankingtraining` (adapter login/mot de passe).
- `index.php` : formulaire de connexion (identifiant / mot de passe).
- `login.php` : authentification vulnérable (SQL par concaténation, mots de passe en clair).
- `dashboard.php` : affichage et manipulation de données d'utilisateurs et de comptes, avec multiples failles de contrôle d'accès.
- `edit_account.php` / `delete_account.php` : modification / suppression de comptes sans aucune vérification.
- `make_admin.php` : élévation de privilèges via simple paramètre / formulaire.
- `logout.php` : déconnexion simple.
- `logger.php` : module de journalisation (tentatives de connexion, actions sensibles). Fichier de log : `www/logs/app.log`.

### Journalisation (logs)

Les actions suivantes sont enregistrées dans `www/logs/app.log` (une ligne JSON par événement) :

- **Connexions** : succès et échecs (LOGIN_SUCCESS, LOGIN_FAILURE) pour les trois formulaires (vulnérable, sécurisé, haché) — aucun mot de passe n’est loggé.
- **Actions sensibles** : suppression de compte (DELETE_ACCOUNT), modification de solde (UPDATE_BALANCE), changement de mot de passe (CHANGE_PASSWORD), inscription (REGISTER), déconnexion (LOGOUT).

Chaque entrée contient : date/heure, type d’action, message, `user_id` / `username` (si session), IP, User-Agent. En Docker, le dossier `www/logs/` doit être accessible en écriture (création automatique si les permissions le permettent).

**Visualiser les logs :**

- **Dans le navigateur** : ouvrir **http://localhost:8080/view_logs.php**. Un tableau affiche tous les événements (plus récents en premier), avec des filtres par type (connexions réussies/échouées, déconnexion, suppression de compte, etc.).
- **En ligne de commande** (sur l'hôte, depuis le dossier du projet) :
  - Voir tout le fichier : `type www\logs\app.log` (Windows) ou `cat www/logs/app.log` (Linux/Mac).
  - Dernières lignes : `Get-Content www\logs\app.log -Tail 20` (PowerShell) ou `tail -n 20 www/logs/app.log` (Linux/Mac).
- **Depuis le conteneur Docker** : `docker compose exec app cat /var/www/html/logs/app.log` (ou `tail -n 50 /var/www/html/logs/app.log`).

### Exigences de vulnérabilité respectées

- **Requêtes SQL par concaténation de chaînes** : aucune requête préparée.
- **Aucune validation des entrées côté serveur** : entrées `$_GET`/`$_POST` utilisées directement dans les requêtes.
- **Mots de passe en clair** : colonne `password` lue/écrite telle quelle.
- **HTTP uniquement** : aucune redirection HTTPS ou configuration de sécurité.

### Exemple de schéma SQL minimal

```sql
CREATE DATABASE IF NOT EXISTS bankingtraining;
USE bankingtraining;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'user'
);

CREATE TABLE accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  balance DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

INSERT INTO users (username, password, role) VALUES
('user1', 'password1', 'user'),
('user2', 'password2', 'user'),
('admin', 'adminpass', 'admin');

INSERT INTO accounts (user_id, balance) VALUES
(1, 100.00),
(1, 2500.50),
(2, 500.00),
(3, 999999.99);
```

### Tests possibles

- **Contourner l'authentification (SQL injection login bypass)**  
  La requête exige **exactement 1 ligne** (`mysqli_num_rows === 1`). Un simple `' OR '1'='1` dans le mot de passe renverrait toutes les lignes et échouerait. Utiliser l’une des méthodes suivantes :
  - **Méthode 1 – Commenter le reste (recommandé)**  
    - **Identifiant :** `alice'#`  
    - **Mot de passe :** n’importe quoi (ex. `x`)  
    La requête devient : `SELECT * FROM users WHERE username = 'alice'` (tout après `#` est ignoré). Une seule ligne est renvoyée → connexion en tant qu’alice.
  - **Méthode 2 – Bypass via le champ mot de passe**  
    - **Identifiant :** `alice`  
    - **Mot de passe :** `' OR username='alice' AND '1'='1`  
    La condition ne reste vraie que pour la ligne d’alice → une seule ligne renvoyée.
- **Accéder aux données d'autres utilisateurs** : saisir manuellement dans l'URL `dashboard.php?user_id=2` (ou un autre ID). Les liens directs vers d'autres utilisateurs ont été retirés dans les deux versions.
- **Modifier / supprimer les données d'autres utilisateurs** : en version vulnérable, modifier l'URL ou l'`id` pour accéder à la modification/suppression d'un compte d'un autre utilisateur.
- **Actions d'un autre rôle (admin)** : en version vulnérable, accéder à `make_admin.php` (formulaire du tableau de bord si connecté en admin) pour promouvoir un utilisateur.

N'utilisez jamais cette application sur un environnement de production ou accessible au public.

---

## Connexion sécurisée (correctifs contre les injections SQL)

Sur la page d’accueil, une seconde section **« Banque connexion – Sécurisation contre les injections SQL »** propose un formulaire avec les correctifs suivants.

### Correctifs mis en œuvre

1. **Validation systématique des entrées côté serveur** (`login_secure.php`)  
   - **Type** : identifiant et mot de passe traités comme chaînes, trim appliqué.  
   - **Longueur max** : identifiant ≤ 200 caractères, mot de passe ≤ 72 (aligné sur les limites courantes).  
   - **Format** : identifiant limité à lettres (dont accentuées), chiffres, point, tiret, underscore (regex `[\p{L}\p{N}._-]+`).  
   - **Caractères spéciaux** : rejet des null bytes dans le mot de passe.  
   - **Messages d’erreur** : messages explicites pour les erreurs de validation ; message générique « Identifiant ou mot de passe invalide » en cas d’échec d’authentification (pas d’énumération d’utilisateurs).

2. **Requêtes préparées (prepared statements)**  
   - La requête SQL utilise des paramètres (`?`) et `mysqli_stmt_bind_param()` : les entrées ne sont jamais concaténées au SQL.  
   - Les tentatives d’injection (`' OR '1'='1`, `alice'#`, etc.) sont traitées comme des chaînes littérales et ne modifient pas la requête.

3. **Contrôle d'accès (version sécurisée uniquement)**  
   - **Accès aux données d'autres utilisateurs** : en connexion sécurisée, le paramètre `user_id` dans l'URL est ignoré ; seul l'utilisateur connecté voit ses propres données. Toute tentative d'accès à `dashboard.php?user_id=X` (X ≠ soi-même) redirige vers son propre tableau de bord.  
   - **Modification / suppression de comptes** : en connexion sécurisée, seuls les comptes dont `user_id` correspond à l'utilisateur connecté peuvent être modifiés ou supprimés ; sinon redirection avec message « Accès refusé à ce compte ».  
   - **Actions admin** : en connexion sécurisée, seul l'utilisateur dont l'identifiant est `admin` peut utiliser le formulaire de promotion (`make_admin.php`) ; les autres reçoivent « Action réservée aux administrateurs ».

4. **Vérification que les attaques ne fonctionnent plus**  
   - **Bypass par commentaire** : identifiant `alice'#` → rejeté par la validation de format (caractère `'` non autorisé).  
   - **Bypass par OR** : mot de passe `' OR '1'='1` → passé en paramètre lié, aucun effet SQL ; message générique.  
   - **Chaînes trop longues** : rejet par la validation de longueur.  
   - **Accès aux données / comptes d'autrui** : bloqué côté serveur en mode sécurisé.  
   - Connexion valide uniquement avec identifiant et mot de passe corrects (ex. alice / pink).

5. **Affichage des données d'autres utilisateurs**  
   Les liens du type « Afficher les données de l'utilisateur 1 / 2 / 3 » ont été supprimés **dans les deux versions** (vulnérable et sécurisée). En version vulnérable, l'accès aux données d'autres utilisateurs reste possible en saisissant manuellement `dashboard.php?user_id=X` dans l'URL.

### Justification des choix (réduction de la surface d’attaque)

| Choix | Effet |
|------|--------|
| **Requêtes préparées** | Séparation stricte entre structure SQL et données : les entrées ne peuvent plus être interprétées comme du code SQL, ce qui supprime les injections SQL classiques. |
| **Validation type / longueur** | Réduit les risques de dépassement et impose un format attendu ; les payloads d’injection contiennent souvent des guillemets ou caractères spéciaux, rejetés par le format identifiant. |
| **Format identifiant restreint** | Limite les caractères autorisés (pas de `'`, `"`, `\`, espaces, etc.), ce qui bloque beaucoup de payloads sans empêcher les identifiants légitimes (lettres accentuées, point, tiret, underscore). |
| **Message d’erreur générique à l’authentification** | Évite de révéler si l’identifiant existe en base, limitant l’énumération d’utilisateurs. |
| **Longueur max mot de passe (72)** | Alignée sur les bonnes pratiques (ex. bcrypt) et évite des entrées démesurées. |

En relançant les attaques de l’étape 2 sur le formulaire **« Connexion sécurisée »**, elles ne permettent plus de contourner l’authentification ; seules des identifiants/mots de passe valides permettent de se connecter.

---

## Encryptage de l’ID et du mot de passe

Une troisième section sur la page d’accueil, **« Encryptage de l'ID et du mot de passe »**, reprend toutes les mesures de la connexion sécurisée (validation, requêtes préparées, contrôle d’accès) et ajoute un **stockage sécurisé des mots de passe** et des processus adaptés.

### Correctifs spécifiques

- **Stockage par hachage** : les mots de passe sont stockés avec `password_hash(..., PASSWORD_BCRYPT, ['cost' => 12])` (sel automatique, coût adapté). Aucun mot de passe en clair en base.
- **Inscription** (`register_hashed.php`) : création de compte dans la table `users_hashed` avec mot de passe haché ; validation identique à la section sécurisée.
- **Connexion** (`login_secure_hash.php`) : vérification avec `password_verify()` sur la colonne `password_hash` ; aucun mot de passe en clair utilisé ni loggé.
- **Modification du mot de passe** (`change_password_hashed.php`) : formulaire « Modifier le mot de passe » (lien sur le tableau de bord) ; vérification de l’ancien mot de passe avec `password_verify`, enregistrement du nouveau avec `password_hash`.
- **Aucun affichage ni log en clair** : dans le tableau de bord, pour les utilisateurs connectés via cette section, le mot de passe n’est jamais affiché (message du type « stocké de manière sécurisée (hachage, jamais affiché en clair) »). Les mots de passe ne sont pas écrits dans les journaux applicatifs.

### Fichiers et données

- Table **`users_hashed`** : `id`, `username`, `password_hash`, `role`. Créée dans `banktraining.sql`.
- **`seed_hashed_users.php`** : insère au premier usage les comptes de test `admin` (PULL) et `alice` (pink) avec des hachages bcrypt, sans jamais stocker ni afficher les mots de passe en clair.

### Consulter les données (utilisateurs test et nouveaux inscrits)

Les données sont stockées dans la base **bankingtraining**, table **users_hashed**, persistée dans le volume Docker **mysql_data**. Pour afficher identifiants et mots de passe hachés :

**Depuis le projet (avec les conteneurs démarrés) :**

```bash
docker compose exec db mysql -uroot -proot bankingtraining -e "SELECT id, username, password_hash, role FROM users_hashed;"
```

Vous verrez les colonnes `id`, `username` (identifiant en clair), `password_hash` (chaîne bcrypt, ex. `$2y$10$...`), `role`. Les mots de passe ne sont jamais en clair.

**Connexion MySQL interactive (pour d’autres requêtes) :**

```bash
docker compose exec db mysql -uroot -proot bankingtraining
```

Puis en SQL : `SELECT * FROM users_hashed;` ou toute autre requête.

---

## Certificats pour HTTPS

Le dossier **`certs/`** contient la clé privée et le certificat X.509 utilisés pour activer HTTPS sur le serveur (tests ou déploiement sécurisé).

### Activation d’HTTPS (Apache + SSL)

HTTPS est activé via **mod_ssl** et un VirtualHost dédié. Les certificats du dossier `certs/` sont montés dans le conteneur en lecture seule.

- **HTTP** : `http://localhost:8080` (port 80 dans le conteneur).
- **HTTPS** : `https://localhost:8443` (port 443 dans le conteneur).

Après `docker compose up -d` (et si `certs/server.key` et `certs/server.crt` existent), l’application est accessible en HTTPS sur le port **8443**. Le navigateur affichera un avertissement pour un certificat auto-signé ; accepter l’exception pour continuer.

### Fichiers attendus

- **`server.key`** : clé privée (à ne jamais partager ni commiter dans git).
- **`server.crt`** : certificat X.509 (public).

Pour régénérer un certificat auto-signé (depuis la racine du projet) :

```bash
cd certs
openssl req -x509 -newkey rsa:2048 -keyout server.key -out server.crt -days 365 -nodes -subj "/CN=localhost/O=Banking Training/C=FR"
```

### Vérifier que le certificat est valide et cohérent

**1. Contenu du certificat (dates, sujet, émetteur)**

```bash
openssl x509 -in certs/server.crt -text -noout
```

Vérifier : dates « Not Before » / « Not After », sujet (CN=…), signature.

**2. Vérifier que le certificat est bien formé**

```bash
openssl x509 -in certs/server.crt -noout
```

Aucune erreur affichée = certificat valide syntaxiquement.

**3. Vérifier que la clé et le certificat correspondent**

```bash
# Empreinte du modulus du certificat
openssl x509 -noout -modulus -in certs/server.crt | openssl md5

# Empreinte du modulus de la clé privée
openssl rsa -noout -modulus -in certs/server.key | openssl md5
```

Les deux empreintes (MD5) doivent être **identiques**. Si oui, la paire clé/certificat est cohérente.

**4. Tester un serveur HTTPS (une fois HTTPS activé sur Apache/Nginx)**

- **Navigateur** : ouvrir `https://localhost:8443` (ou le port HTTPS configuré). Avec un certificat auto-signé, le navigateur affichera un avertissement ; accepter l’exception pour confirmer que la connexion TLS fonctionne (cadenas affiché après acceptation).
- **Ligne de commande** (connexion TLS sans vérifier le nom d’hôte, pour test) :

  ```bash
  openssl s_client -connect localhost:8443 -servername localhost
  ```

  Vous devez voir la chaîne de certificats et « Verify return code: 0 » (ou un code indiquant que seul le nom/CA pose problème, ce qui est normal pour un auto-signé). `Ctrl+C` pour quitter.

En résumé : si les commandes **1 à 3** ne renvoient pas d’erreur et que les empreintes en **3** sont identiques, le certificat est bien formé et associé à la clé. Le test **4** confirme que le serveur utilise correctement cette paire pour HTTPS.

### Dépannage : « Unable to connect » / errno 111 (connexion refusée)

**Où lancez-vous le navigateur ou la commande ?**

- **Depuis votre PC (Windows)** alors que Docker tourne sur la **VM** : `localhost` désigne votre PC, pas la VM. Aucun service n’écoute sur le port 8443 de votre PC → connexion refusée.
  - **À faire** : utilisez l’**IP de la VM** (ex. `192.168.x.x` ou celle affichée dans VMware). Ex. : `https://192.168.55.101:8443` (remplacer par l’IP réelle de votre VM).
  - Vérifiez que le pare-feu de la VM autorise le port **8443** (entrant).
- **Depuis la VM** (navigateur ou terminal sur la VM) : `https://localhost:8443` est correct. Si ça échoue, passez aux vérifications ci-dessous.

**Vérifications sur la VM (dossier du projet)**

1. **Conteneur en marche et ports mappés**
   ```bash
   docker compose ps
   ```
   Vous devez voir le port `0.0.0.0:8443->443/tcp` pour le service `app`.

2. **Certificats présents** (obligatoire pour qu’Apache écoute sur 443)
   ```bash
   dir certs\server.crt certs\server.key
   ```
   Les deux fichiers doivent exister dans le dossier `certs/` à la racine du projet (même niveau que `docker-compose.yml`).

3. **Apache écoute bien sur 443 dans le conteneur**
   ```bash
   docker compose exec app bash -c "ss -tlnp | grep 443"
   ```
   Vous devez voir une ligne avec `:443`. Si rien n’apparaît, le VirtualHost SSL n’a pas démarré (souvent certificats absents ou erreur au chargement).

4. **Logs Apache (erreurs SSL)**
   ```bash
   docker compose logs app
   ```
   Ou depuis le conteneur : `docker compose exec app cat /var/log/apache2/error.log`. Chercher des messages du type `SSLCertificateFile` ou `Cannot load SSL certificate`.

**À propos de 172.18.0.3** : cette adresse est l’IP du conteneur **à l’intérieur du réseau Docker**. Elle n’est utilisable que depuis un autre conteneur (ex. `http://172.18.0.3/`). Depuis la VM ou votre PC, il faut utiliser **localhost** (depuis la VM) ou **l’IP de la VM** (depuis le PC), avec les ports mappés **8080** (HTTP) et **8443** (HTTPS).

---

## Explorer la base sans MySQL Workbench sur la VM

Vous utilisez MySQL Workbench sur votre ordinateur principal ; sur la machine virtuelle, vous pouvez soit vous connecter depuis le PC, soit utiliser la ligne de commande.

### Option 1 : MySQL Workbench sur votre PC → connexion à la base sur la VM

Le port MySQL **3306** est exposé par Docker sur la VM. Depuis votre ordinateur principal :

1. Notez **l’adresse IP de la VM** (ex. `192.168.x.x` ou l’IP affichée dans votre outil de virtualisation).
2. Dans MySQL Workbench : **Connexion MySQL** → **Configurer une connexion** (ou nouvelle connexion).
3. Paramètres :
   - **Hostname :** adresse IP de la VM (ex. `192.168.1.100`)
   - **Port :** `3306`
   - **Username :** `root`
   - **Password :** `root` (cliquez sur « Stocker dans le trousseau » si besoin)
4. **Test connexion**, puis **OK**. Ouvrez la connexion.
5. Dans le panneau de gauche, sélectionnez le schéma **`bankingtraining`** (double-clic ou clic droit → « Set as Default Schema »).
6. Vous pouvez exécuter vos requêtes comme d’habitude (ex. `SELECT * FROM users_hashed;`).

**À vérifier si la connexion échoue :** pare-feu de la VM autorisant le port 3306, réseau (VM et PC sur le même réseau ou port forwarding configuré).

### Option 2 : Sur la VM, client MySQL en ligne de commande (aucune installation)

Le conteneur **db** contient déjà le client MySQL. Depuis la VM, dans le dossier du projet :

**Mode interactif (comme un « mini Workbench » en terminal) :**

```bash
docker compose exec db mysql -uroot -proot bankingtraining
```

Vous obtenez un prompt `mysql>`. Exemples de commandes :

- `SHOW TABLES;` — lister les tables
- `SELECT * FROM users_hashed;` — voir les utilisateurs et mots de passe hachés
- `SELECT * FROM users;` — table utilisateurs (version vulnérable)
- `DESCRIBE users_hashed;` — structure de la table
- `exit` ou `quit` — quitter

**Une requête sans entrer en mode interactif :**

```bash
docker compose exec db mysql -uroot -proot bankingtraining -e "VOTRE_REQUETE_SQL"
```

Exemple :  
`docker compose exec db mysql -uroot -proot bankingtraining -e "SELECT id, username, role FROM users_hashed;"`

