<?php
session_start();
require_once "logger.php";
app_log('LOGOUT', 'Déconnexion');
session_unset();
session_destroy();
header("Location: index.php");
exit;

