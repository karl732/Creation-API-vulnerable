<?php
/**
 * Connexion sécurisée contre les injections SQL et les entrées malformées.
 *
 * Choix de conception :
 * - Requêtes préparées (prepared statements) : les données sont envoyées séparément
 *   de la structure SQL, ce qui rend l'injection SQL impossible (pas de concaténation).
 * - Validation côté serveur : type, longueur max, format attendu pour réduire la surface
 *   d'attaque (rejet des chaînes anormalement longues ou contenant des caractères
 *   de contrôle / métacaractères SQL).
 * - Message d'échec générique : "Identifiant ou mot de passe invalide" pour ne pas
 *   révéler si l'identifiant existe (évite l'énumération d'utilisateurs).
 */
session_start();
require_once "config.php";
require_once "logger.php";

// Constantes de validation (cohérentes avec le schéma et bonnes pratiques)
const USERNAME_MAX_LENGTH = 200;
const PASSWORD_MAX_LENGTH = 72;
const USERNAME_FORMAT_REGEX = '/^[\p{L}\p{N}._-]+$/u'; // Lettres (dont accentuées), chiffres, . _ -

$errors = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$username = isset($_POST['username_secure']) ? trim((string) $_POST['username_secure']) : '';
$password = isset($_POST['password_secure']) ? (string) $_POST['password_secure'] : '';

// --- Validation des entrées côté serveur ---

// Type et présence
if ($username === '') {
    $errors[] = "L'identifiant est obligatoire.";
}
if ($password === '') {
    $errors[] = "Le mot de passe est obligatoire.";
}

// Longueur maximale
if (strlen($username) > USERNAME_MAX_LENGTH) {
    $errors[] = "L'identifiant ne doit pas dépasser " . USERNAME_MAX_LENGTH . " caractères.";
}
if (strlen($password) > PASSWORD_MAX_LENGTH) {
    $errors[] = "Le mot de passe ne doit pas dépasser " . PASSWORD_MAX_LENGTH . " caractères.";
}

// Format attendu (caractères autorisés pour l'identifiant)
if ($username !== '' && !preg_match(USERNAME_FORMAT_REGEX, $username)) {
    $errors[] = "L'identifiant ne doit contenir que des lettres, chiffres, points, tirets et underscores.";
}

// Caractères spéciaux / caractères de contrôle dans le mot de passe (optionnel : rejeter les null bytes)
if (strpos($password, "\0") !== false) {
    $errors[] = "Caractères non autorisés dans le mot de passe.";
}

if (!empty($errors)) {
    $_SESSION['secure_validation_errors'] = $errors;
    header("Location: index.php#connexion-securisee");
    exit;
}

// --- Authentification par requête préparée (plus d'injection SQL possible) ---

$stmt = mysqli_prepare($conn, "SELECT id, username, role FROM users WHERE username = ? AND password = ?");
if (!$stmt) {
    $_SESSION['secure_validation_errors'] = ["Erreur technique. Veuillez réessayer."];
    header("Location: index.php#connexion-securisee");
    exit;
}

mysqli_stmt_bind_param($stmt, "ss", $username, $password);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

if ($result && mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_via_secure'] = true;
    unset($_SESSION['secure_validation_errors']);
    app_log('LOGIN_SUCCESS', 'Connexion réussie (formulaire sécurisé)', ['username' => $user['username']]);
    header("Location: dashboard.php?user_id=" . $user['id']);
    exit;
}

app_log('LOGIN_FAILURE', 'Tentative de connexion échouée (formulaire sécurisé)', ['username' => $username]);
// Échec : message générique (pas d'énumération d'utilisateurs)
$_SESSION['secure_validation_errors'] = ["Identifiant ou mot de passe invalide."];
header("Location: index.php#connexion-securisee");
exit;
