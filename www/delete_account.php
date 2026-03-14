<?php
session_start();
require_once "config.php";
require_once "logger.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Veuillez vous connecter");
    exit;
}

if (!isset($_GET['id'])) {
    die("ID de compte manquant.");
}

$accountId = $_GET['id'];

// Version sécurisée / hachage : n'autoriser que la suppression de ses propres comptes
if (!empty($_SESSION['logged_via_secure']) || !empty($_SESSION['logged_via_secure_hash'])) {
    $check = mysqli_query($conn, "SELECT user_id FROM accounts WHERE id = " . (int) $accountId . " AND user_id = " . (int) $_SESSION['user_id']);
    if (!$check || mysqli_num_rows($check) === 0) {
        header("Location: dashboard.php?error=" . urlencode("Accès refusé à ce compte"));
        exit;
    }
    mysqli_free_result($check);
}

$sqlDelete = "DELETE FROM accounts WHERE id = " . $accountId;
$resultDelete = mysqli_query($conn, $sqlDelete);

if (!$resultDelete) {
    die("Erreur SQL (delete) : " . mysqli_error($conn));
}

app_log('DELETE_ACCOUNT', 'Suppression de compte bancaire', ['account_id' => (int) $accountId]);

$redirectSecure = !empty($_SESSION['logged_via_secure']) || !empty($_SESSION['logged_via_secure_hash']);
$redirectUrl = $redirectSecure ? "dashboard.php" : "dashboard.php?user_id=" . (isset($_GET['user_id']) ? $_GET['user_id'] : $_SESSION['user_id']);
header("Location: " . $redirectUrl);
exit;

