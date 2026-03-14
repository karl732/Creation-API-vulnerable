<?php
session_start();
require_once "config.php";
require_once "logger.php";

if (!isset($_POST['username']) || !isset($_POST['password'])) {
    header("Location: index.php?error=Identifiants manquants");
    exit;
}

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT * FROM users WHERE username = '" . $username . "' AND password = '" . $password . "'";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Erreur SQL : " . mysqli_error($conn));
}

if (mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    app_log('LOGIN_SUCCESS', 'Connexion réussie (formulaire vulnérable)', ['username' => $user['username']]);

    header("Location: dashboard.php?user_id=" . $user['id']);
    exit;
} else {
    app_log('LOGIN_FAILURE', 'Tentative de connexion échouée (formulaire vulnérable)', ['username' => $username]);
    header("Location: index.php?error=Identifiant ou mot de passe invalide");
    exit;
}

