<?php
/**
 * Inscription avec stockage sécurisé du mot de passe (bcrypt, sel et coût).
 * Validation identique à la section sécurisée ; aucun mot de passe en clair stocké ni loggé.
 */
session_start();
require_once "config.php";
require_once "logger.php";

const USERNAME_MAX_LENGTH = 200;
const PASSWORD_MAX_LENGTH = 72;
const PASSWORD_MIN_LENGTH = 8;
const USERNAME_FORMAT_REGEX = '/^[\p{L}\p{N}._-]+$/u';
const BCRYPT_COST = 12;

$errors = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php#encryptage");
    exit;
}

$username = isset($_POST['username_hashed']) ? trim((string) $_POST['username_hashed']) : '';
$password = isset($_POST['password_hashed']) ? (string) $_POST['password_hashed'] : '';
$password_confirm = isset($_POST['password_hashed_confirm']) ? (string) $_POST['password_hashed_confirm'] : '';
$role = isset($_POST['role']) && in_array($_POST['role'], ['Customer', 'Analyst'], true) ? $_POST['role'] : 'Customer';

if ($username === '') {
    $errors[] = "L'identifiant est obligatoire.";
}
if (strlen($username) > USERNAME_MAX_LENGTH) {
    $errors[] = "L'identifiant ne doit pas dépasser " . USERNAME_MAX_LENGTH . " caractères.";
}
if ($username !== '' && !preg_match(USERNAME_FORMAT_REGEX, $username)) {
    $errors[] = "L'identifiant ne doit contenir que des lettres, chiffres, points, tirets et underscores.";
}
if ($password === '') {
    $errors[] = "Le mot de passe est obligatoire.";
}
if (strlen($password) < PASSWORD_MIN_LENGTH) {
    $errors[] = "Le mot de passe doit contenir au moins " . PASSWORD_MIN_LENGTH . " caractères.";
}
if (strlen($password) > PASSWORD_MAX_LENGTH) {
    $errors[] = "Le mot de passe ne doit pas dépasser " . PASSWORD_MAX_LENGTH . " caractères.";
}
if ($password !== $password_confirm) {
    $errors[] = "Les deux mots de passe ne correspondent pas.";
}
if (strpos($password, "\0") !== false) {
    $errors[] = "Caractères non autorisés dans le mot de passe.";
}

if (!empty($errors)) {
    $_SESSION['hashed_validation_errors'] = $errors;
    header("Location: index.php#encryptage");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id FROM users_hashed WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$r = mysqli_stmt_get_result($stmt);
if (mysqli_fetch_assoc($r)) {
    mysqli_stmt_close($stmt);
    $_SESSION['hashed_validation_errors'] = ["Cet identifiant est déjà utilisé."];
    header("Location: index.php#encryptage");
    exit;
}
mysqli_stmt_close($stmt);

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
$stmt = mysqli_prepare($conn, "INSERT INTO users_hashed (username, password_hash, role) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sss", $username, $hash, $role);
if (!mysqli_stmt_execute($stmt)) {
    $_SESSION['hashed_validation_errors'] = ["Erreur lors de l'inscription."];
    header("Location: index.php#encryptage");
    exit;
}
mysqli_stmt_close($stmt);

app_log('REGISTER', 'Nouvelle inscription (formulaire haché)', ['username' => $username, 'role' => $role]);
unset($_SESSION['hashed_validation_errors']);
$_SESSION['hashed_register_ok'] = "Compte créé. Vous pouvez vous connecter.";
header("Location: index.php#encryptage");
exit;
