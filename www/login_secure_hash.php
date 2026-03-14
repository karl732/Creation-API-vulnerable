<?php
/**
 * Connexion avec stockage sécurisé des mots de passe (hachage bcrypt).
 * Reprend les correctifs de la section "Sécurisation contre les injections SQL"
 * et ajoute : authentification par password_verify(), aucun mot de passe en clair.
 * Les mots de passe ne sont jamais loggés ni affichés.
 */
session_start();
require_once "config.php";
require_once "logger.php";

include_once __DIR__ . '/seed_hashed_users.php';

const USERNAME_MAX_LENGTH = 200;
const PASSWORD_MAX_LENGTH = 72;
const USERNAME_FORMAT_REGEX = '/^[\p{L}\p{N}._-]+$/u';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$username = isset($_POST['username_hashed']) ? trim((string) $_POST['username_hashed']) : '';
$password = isset($_POST['password_hashed']) ? (string) $_POST['password_hashed'] : '';

if ($username === '') {
    $errors[] = "L'identifiant est obligatoire.";
}
if ($password === '') {
    $errors[] = "Le mot de passe est obligatoire.";
}
if (strlen($username) > USERNAME_MAX_LENGTH) {
    $errors[] = "L'identifiant ne doit pas dépasser " . USERNAME_MAX_LENGTH . " caractères.";
}
if (strlen($password) > PASSWORD_MAX_LENGTH) {
    $errors[] = "Le mot de passe ne doit pas dépasser " . PASSWORD_MAX_LENGTH . " caractères.";
}
if ($username !== '' && !preg_match(USERNAME_FORMAT_REGEX, $username)) {
    $errors[] = "L'identifiant ne doit contenir que des lettres, chiffres, points, tirets et underscores.";
}
if (strpos($password, "\0") !== false) {
    $errors[] = "Caractères non autorisés dans le mot de passe.";
}

if (!empty($errors)) {
    $_SESSION['hashed_validation_errors'] = $errors;
    header("Location: index.php#encryptage");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id, username, role, password_hash FROM users_hashed WHERE username = ?");
if (!$stmt) {
    $_SESSION['hashed_validation_errors'] = ["Erreur technique. Veuillez réessayer."];
    header("Location: index.php#encryptage");
    exit;
}
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($user && password_verify($password, $user['password_hash'])) {
    unset($user['password_hash']);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_via_secure_hash'] = true;
    unset($_SESSION['hashed_validation_errors']);
    app_log('LOGIN_SUCCESS', 'Connexion réussie (formulaire haché)', ['username' => $user['username']]);
    header("Location: dashboard.php");
    exit;
}

app_log('LOGIN_FAILURE', 'Tentative de connexion échouée (formulaire haché)', ['username' => $username]);
$_SESSION['hashed_validation_errors'] = ["Identifiant ou mot de passe invalide."];
header("Location: index.php#encryptage");
exit;
