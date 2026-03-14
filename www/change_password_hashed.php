<?php
/**
 * Modification du mot de passe (utilisateurs avec hachage).
 * Vérification de l'ancien mot de passe via password_verify, stockage du nouveau via password_hash.
 * Aucun mot de passe en clair stocké ni affiché.
 */
session_start();
require_once "config.php";
require_once "logger.php";

const PASSWORD_MAX_LENGTH = 72;
const PASSWORD_MIN_LENGTH = 8;
const BCRYPT_COST = 12;

if (!isset($_SESSION['user_id']) || empty($_SESSION['logged_via_secure_hash'])) {
    header("Location: index.php?error=Accès non autorisé");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['new_password_confirm'] ?? '');

    if ($current === '') {
        $errors[] = "Le mot de passe actuel est obligatoire.";
    }
    if ($new === '') {
        $errors[] = "Le nouveau mot de passe est obligatoire.";
    }
    if (strlen($new) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Le nouveau mot de passe doit contenir au moins " . PASSWORD_MIN_LENGTH . " caractères.";
    }
    if (strlen($new) > PASSWORD_MAX_LENGTH) {
        $errors[] = "Le nouveau mot de passe ne doit pas dépasser " . PASSWORD_MAX_LENGTH . " caractères.";
    }
    if ($new !== $confirm) {
        $errors[] = "Les deux nouveaux mots de passe ne correspondent pas.";
    }
    if (strpos($new, "\0") !== false || strpos($current, "\0") !== false) {
        $errors[] = "Caractères non autorisés.";
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT password_hash FROM users_hashed WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($row && password_verify($current, $row['password_hash'])) {
            $newHash = password_hash($new, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            $up = mysqli_prepare($conn, "UPDATE users_hashed SET password_hash = ? WHERE id = ?");
            mysqli_stmt_bind_param($up, "si", $newHash, $_SESSION['user_id']);
            mysqli_stmt_execute($up);
            mysqli_stmt_close($up);
            app_log('CHANGE_PASSWORD', 'Mot de passe modifié (utilisateur haché)');
            header("Location: dashboard.php?msg=" . urlencode("Mot de passe modifié."));
            exit;
        }
        $errors[] = "Mot de passe actuel incorrect.";
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier le mot de passe</title>
</head>
<body>
    <h1>Modifier le mot de passe</h1>
    <?php if (!empty($errors)): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $e): ?>
                <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form method="post">
        <label for="current_password">Mot de passe actuel :</label>
        <input type="password" id="current_password" name="current_password" maxlength="72" autocomplete="current-password"><br><br>
        <label for="new_password">Nouveau mot de passe :</label>
        <input type="password" id="new_password" name="new_password" maxlength="72" autocomplete="new-password"><br><br>
        <label for="new_password_confirm">Confirmer le nouveau mot de passe :</label>
        <input type="password" id="new_password_confirm" name="new_password_confirm" maxlength="72" autocomplete="new-password"><br><br>
        <button type="submit">Enregistrer</button>
    </form>
    <p><a href="dashboard.php">Retour au tableau de bord</a></p>
</body>
</html>
