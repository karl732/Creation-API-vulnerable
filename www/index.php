<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php?user_id=" . $_SESSION['user_id']);
    exit;
}

$secureErrors = $_SESSION['secure_validation_errors'] ?? [];
$hashedErrors = $_SESSION['hashed_validation_errors'] ?? [];
$hashedOk = $_SESSION['hashed_register_ok'] ?? '';
unset($_SESSION['secure_validation_errors'], $_SESSION['hashed_validation_errors'], $_SESSION['hashed_register_ok']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — Banque</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f1419;
            --surface: #1a2332;
            --surface-hover: #243044;
            --border: #2d3a4d;
            --text: #e6edf3;
            --text-muted: #8b9eb5;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --success: #22c55e;
            --error: #ef4444;
            --warn: #eab308;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 4px 24px rgba(0,0,0,0.25);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }
        .page {
            max-width: 720px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }
        .hero {
            text-align: center;
            margin-bottom: 2rem;
        }
        .hero h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .hero p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        .alert {
            padding: 0.75rem 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .alert-error ul { margin: 0.25rem 0 0 1.25rem; padding: 0; }
        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            color: #86efac;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
        }
        .card h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .card .subtitle {
            margin: 0 0 1.25rem 0;
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        .card.badge-warn {
            border-left: 3px solid var(--warn);
        }
        .card.badge-secure {
            border-left: 3px solid var(--success);
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.35rem;
            color: var(--text-muted);
        }
        .form-group input,
        .form-group select {
            width: 100%;
            max-width: 320px;
            padding: 0.6rem 0.85rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text);
            font-family: inherit;
            font-size: 0.95rem;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }
        .btn {
            display: inline-block;
            padding: 0.6rem 1.25rem;
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s, color 0.15s;
        }
        .btn-primary {
            background: var(--accent);
            color: white;
        }
        .btn-primary:hover {
            background: var(--accent-hover);
            color: white;
        }
        .footer-links {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            text-align: center;
        }
        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.875rem;
        }
        .footer-links a:hover {
            color: var(--accent);
        }
    </style>
</head>
<body>
    <div class="page">
        <header class="hero">
            <h1>Banque — Connexion</h1>
            <p>Application de démonstration (formulaires vulnérable et sécurisés)</p>
        </header>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <!-- Formulaire vulnérable -->
        <section class="card badge-warn" id="connexion-vulnerable">
            <h2>Connexion (formulaire vulnérable)</h2>
            <p class="subtitle">Requêtes SQL par concaténation — à des fins de test d’injection SQL uniquement.</p>
            <form method="post" action="login.php">
                <div class="form-group">
                    <label for="username">Identifiant</label>
                    <input type="text" id="username" name="username" autocomplete="username" placeholder="ex. alice">
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary">Se connecter</button>
            </form>
        </section>

        <!-- Connexion sécurisée -->
        <section class="card badge-secure" id="connexion-securisee">
            <h2>Connexion sécurisée (anti-injection SQL)</h2>
            <p class="subtitle">Validation des entrées et requêtes préparées. Les injections sont rejetées.</p>
            <?php if (!empty($secureErrors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($secureErrors as $err): ?>
                            <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="post" action="login_secure.php">
                <div class="form-group">
                    <label for="username_secure">Identifiant</label>
                    <input type="text" id="username_secure" name="username_secure" maxlength="200" autocomplete="username" placeholder="ex. alice">
                </div>
                <div class="form-group">
                    <label for="password_secure">Mot de passe</label>
                    <input type="password" id="password_secure" name="password_secure" maxlength="72" autocomplete="current-password" placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary">Se connecter (sécurisé)</button>
            </form>
        </section>

        <!-- Inscription + Connexion hachée -->
        <section class="card badge-secure" id="encryptage">
            <h2>Encryptage (mot de passe haché)</h2>
            <p class="subtitle">Inscription et connexion avec bcrypt. Aucun mot de passe en clair stocké ni affiché.</p>
            <?php if ($hashedOk): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($hashedOk, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if (!empty($hashedErrors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($hashedErrors as $err): ?>
                            <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <h3 style="margin: 1.25rem 0 0.75rem 0; font-size: 1rem;">Inscription</h3>
            <form method="post" action="register_hashed.php" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label for="reg_username_hashed">Identifiant</label>
                    <input type="text" id="reg_username_hashed" name="username_hashed" maxlength="200" autocomplete="username" placeholder="nouveau compte">
                </div>
                <div class="form-group">
                    <label for="reg_password_hashed">Mot de passe</label>
                    <input type="password" id="reg_password_hashed" name="password_hashed" maxlength="72" autocomplete="new-password" placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label for="reg_password_hashed_confirm">Confirmer le mot de passe</label>
                    <input type="password" id="reg_password_hashed_confirm" name="password_hashed_confirm" maxlength="72" autocomplete="new-password" placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label for="reg_role">Rôle</label>
                    <select id="reg_role" name="role">
                        <option value="Customer">Customer</option>
                        <option value="Analyst">Analyst</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">S'inscrire</button>
            </form>

            <h3 style="margin: 1.25rem 0 0.75rem 0; font-size: 1rem;">Connexion (haché)</h3>
            <form method="post" action="login_secure_hash.php">
                <div class="form-group">
                    <label for="username_hashed">Identifiant</label>
                    <input type="text" id="username_hashed" name="username_hashed" maxlength="200" autocomplete="username" placeholder="ex. alice">
                </div>
                <div class="form-group">
                    <label for="password_hashed">Mot de passe</label>
                    <input type="password" id="password_hashed" name="password_hashed" maxlength="72" autocomplete="current-password" placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary">Se connecter (haché)</button>
            </form>
        </section>

        <footer class="footer-links">
            <a href="view_logs.php">Voir le journal des événements (tentatives de connexion, actions sensibles)</a>
        </footer>
    </div>
</body>
</html>
