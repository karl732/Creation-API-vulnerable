<?php
// Connexion MySQL (même style que sqlinjections/db_config.php)
// En Docker : host = 'db', en local : host = 'localhost'
$DBHOST = getenv('DB_HOST') ?: 'localhost';
$DBUSER = getenv('DB_USER') ?: 'root';
$DBPASS = getenv('DB_PASS') ?: '';
$DBNAME = getenv('DB_NAME') ?: 'bankingtraining';

$conn = mysqli_connect($DBHOST, $DBUSER, $DBPASS, $DBNAME);

if (mysqli_connect_errno()) {
    die("Erreur de connexion à la base de données : " . mysqli_connect_error());
}
