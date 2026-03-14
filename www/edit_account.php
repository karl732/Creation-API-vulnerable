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

// Version sécurisée / hachage : n'autoriser que la modification de ses propres comptes
if (!empty($_SESSION['logged_via_secure']) || !empty($_SESSION['logged_via_secure_hash'])) {
    $check = mysqli_query($conn, "SELECT user_id FROM accounts WHERE id = " . (int) $accountId . " AND user_id = " . (int) $_SESSION['user_id']);
    if (!$check || mysqli_num_rows($check) === 0) {
        header("Location: dashboard.php?error=" . urlencode("Accès refusé à ce compte"));
        exit;
    }
    mysqli_free_result($check);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newBalance = $_POST['balance'];

    $sqlUpdate = "UPDATE accounts SET balance = " . $newBalance . " WHERE id = " . $accountId;
    $resultUpdate = mysqli_query($conn, $sqlUpdate);

    if (!$resultUpdate) {
        die("Erreur SQL (update) : " . mysqli_error($conn));
    }

    app_log('UPDATE_BALANCE', 'Modification du solde d\'un compte', ['account_id' => (int) $accountId, 'new_balance' => $newBalance]);

    $redirectSecure = !empty($_SESSION['logged_via_secure']) || !empty($_SESSION['logged_via_secure_hash']);
    $redirectUserId = $redirectSecure ? $_SESSION['user_id'] : (isset($_GET['user_id']) ? $_GET['user_id'] : $_SESSION['user_id']);
    header("Location: dashboard.php" . ($redirectSecure ? "" : "?user_id=" . $redirectUserId));
    exit;
}

$sqlAccount = "SELECT * FROM accounts WHERE id = " . $accountId;
$resultAccount = mysqli_query($conn, $sqlAccount);

if (!$resultAccount) {
    die("Erreur SQL (select account) : " . mysqli_error($conn));
}

$account = mysqli_fetch_assoc($resultAccount);

if (!$account) {
    die("Compte introuvable.");
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier compte (Vulnérable)</title>
</head>
<body>
    <h1>Modifier le compte <?php echo $account['id']; ?></h1>

    <form method="post">
        <label for="balance">Nouveau solde :</label>
        <input type="text" id="balance" name="balance" value="<?php echo $account['balance']; ?>">
        <button type="submit">Enregistrer</button>
    </form>

    <p><a href="dashboard.php<?php echo (!empty($_SESSION['logged_via_secure']) || !empty($_SESSION['logged_via_secure_hash'])) ? '' : '?user_id=' . (isset($_GET['user_id']) ? (int)$_GET['user_id'] : (int)$_SESSION['user_id']); ?>">Retour au tableau de bord</a></p>
</body>
</html>

