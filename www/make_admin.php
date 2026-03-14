<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Veuillez vous connecter");
    exit;
}

// Version sécurisée / hachage : seuls les utilisateurs "admin" peuvent promouvoir
if ((!empty($_SESSION['logged_via_secure']) || !empty($_SESSION['logged_via_secure_hash'])) && $_SESSION['username'] !== 'admin') {
    header("Location: dashboard.php?error=" . urlencode("Action réservée aux administrateurs"));
    exit;
}

if (!isset($_POST['target_user_id'])) {
    die("ID utilisateur cible manquant.");
}

$targetUserId = $_POST['target_user_id'];

$sql = "UPDATE users SET role = 'admin' WHERE id = " . $targetUserId;
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Erreur SQL (make admin) : " . mysqli_error($conn));
}

header("Location: dashboard.php");
exit;

