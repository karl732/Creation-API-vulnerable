<?php
/**
 * Seed initial pour la table users_hashed.
 * Insère admin (PULL) et alice (pink) avec hachage bcrypt (sel + coût 12).
 * À exécuter une fois après création de la table (ou appelé automatiquement si vide).
 * Les mots de passe ne sont jamais loggés ni affichés.
 */
require_once "config.php";

$count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users_hashed"));
if ((int) $count[0] > 0) {
    return;
}

$cost = 12; // Coût bcrypt adapté (2^12 itérations)
$adminHash = password_hash('PULL', PASSWORD_BCRYPT, ['cost' => $cost]);
$aliceHash = password_hash('pink', PASSWORD_BCRYPT, ['cost' => $cost]);

$stmt = mysqli_prepare($conn, "INSERT INTO users_hashed (username, password_hash, role) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sss", $u, $h, $r);

$u = 'admin'; $h = $adminHash; $r = 'Admin';
mysqli_stmt_execute($stmt);
$u = 'alice'; $h = $aliceHash; $r = 'Customer';
mysqli_stmt_execute($stmt);

mysqli_stmt_close($stmt);
