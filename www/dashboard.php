<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Veuillez vous connecter");
    exit;
}

$requestedUserId = isset($_GET['user_id']) ? $_GET['user_id'] : $_SESSION['user_id'];

$isSecureOrHashed = !empty($_SESSION['logged_via_secure']) || !empty($_SESSION['logged_via_secure_hash']);

// Version sécurisée / hachage : n'afficher que ses propres données
if ($isSecureOrHashed && (int) $requestedUserId !== (int) $_SESSION['user_id']) {
    $requestedUserId = $_SESSION['user_id'];
    header("Location: dashboard.php");
    exit;
}

if (!empty($_SESSION['logged_via_secure_hash'])) {
    $stmtUser = mysqli_prepare($conn, "SELECT id, username, role FROM users_hashed WHERE id = ?");
    mysqli_stmt_bind_param($stmtUser, "i", $requestedUserId);
    mysqli_stmt_execute($stmtUser);
    $resultUser = mysqli_stmt_get_result($stmtUser);
    $userData = mysqli_fetch_assoc($resultUser);
    mysqli_stmt_close($stmtUser);
} else {
    $sqlUser = "SELECT * FROM users WHERE id = " . (int) $requestedUserId;
    $resultUser = mysqli_query($conn, $sqlUser);
    if (!$resultUser) {
        die("Erreur SQL (user) : " . mysqli_error($conn));
    }
    $userData = mysqli_fetch_assoc($resultUser);
}

$sqlAccounts = "SELECT * FROM accounts WHERE user_id = " . (int) $requestedUserId;
$resultAccounts = mysqli_query($conn, $sqlAccounts);

if (!$resultAccounts) {
    die("Erreur SQL (accounts) : " . mysqli_error($conn));
}

$accountsList = [];
while ($row = mysqli_fetch_assoc($resultAccounts)) {
    $accountsList[] = $row;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tableau de bord — Banque</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f1419;
            --surface: #1a2332;
            --surface-hover: #243044;
            --border: #2d3a4d;
            --text: #e6edf3;
            --text-muted: #8b9eb5;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --success: #22c55e;
            --error: #ef4444;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 4px 24px rgba(0,0,0,0.25);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }
        .header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .header h1 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .user-badge {
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        .user-badge strong { color: var(--text); }
        .tag {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 500;
            background: rgba(59, 130, 246, 0.2);
            color: var(--accent);
        }
        .main {
            max-width: 900px;
            margin: 0 auto;
            padding: 1.5rem;
        }
        .alert {
            padding: 0.75rem 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .alert-error { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
        .alert-success { background: rgba(34, 197, 94, 0.15); color: #86efac; border: 1px solid rgba(34, 197, 94, 0.3); }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.25rem;
            box-shadow: var(--shadow);
        }
        .card h2 {
            margin: 0 0 1rem 0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
        }
        .user-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
        }
        .user-item:last-child { border-bottom: none; }
        .user-item dt { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.2rem; }
        .user-item dd { margin: 0; font-weight: 500; }
        .accounts-table {
            width: 100%;
            border-collapse: collapse;
        }
        .accounts-table th,
        .accounts-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        .accounts-table th {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .accounts-table tr:last-child td { border-bottom: none; }
        .accounts-table tr:hover td { background: var(--surface-hover); }
        .balance { font-weight: 600; font-variant-numeric: tabular-nums; color: var(--success); }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s, color 0.15s;
        }
        .btn-primary { background: var(--accent); color: white; }
        .btn-primary:hover { background: var(--accent-hover); color: white; }
        .btn-ghost {
            background: transparent;
            color: var(--text-muted);
        }
        .btn-ghost:hover { background: var(--surface-hover); color: var(--text); }
        .btn-danger { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
        .btn-danger:hover { background: rgba(239, 68, 68, 0.35); color: #fca5a5; }
        .actions-cell { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .admin-form {
            display: flex;
            gap: 0.75rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .admin-form label { font-size: 0.875rem; color: var(--text-muted); }
        .admin-form input {
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text);
            font-family: inherit;
            font-size: 0.9rem;
        }
        .nav-links { display: flex; gap: 0.75rem; flex-wrap: wrap; }
        .nav-links a { color: var(--text-muted); text-decoration: none; font-size: 0.875rem; }
        .nav-links a:hover { color: var(--accent); }
        .empty-state { color: var(--text-muted); font-size: 0.9rem; padding: 1rem 0; }
    </style>
</head>
<body>
    <header class="header">
        <h1>Tableau de bord</h1>
        <div class="header-right">
            <span class="user-badge">
                <strong><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></strong>
                · <?php echo htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8'); ?>
                <?php if (!empty($_SESSION['logged_via_secure'])): ?><span class="tag">Sécurisé</span><?php endif; ?>
                <?php if (!empty($_SESSION['logged_via_secure_hash'])): ?><span class="tag">Haché</span><?php endif; ?>
            </span>
            <div class="nav-links">
                <?php if (!empty($_SESSION['logged_via_secure_hash'])): ?>
                    <a href="change_password_hashed.php">Mot de passe</a>
                <?php endif; ?>
                <a href="view_logs.php">Journal</a>
                <a href="logout.php">Déconnexion</a>
            </div>
        </div>
    </header>

    <main class="main">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="card">
            <h2>Profil (ID <?php echo (int) $requestedUserId; ?>)</h2>
            <?php if ($userData): ?>
                <dl class="user-grid">
                    <div class="user-item">
                        <dt>Identifiant</dt>
                        <dd><?php echo htmlspecialchars($userData['username'], ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <div class="user-item">
                        <dt>Rôle</dt>
                        <dd><?php echo htmlspecialchars($userData['role'], ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <div class="user-item">
                        <dt>Mot de passe</dt>
                        <dd><?php echo $isSecureOrHashed ? '•••••••• (stockage sécurisé)' : htmlspecialchars($userData['password'] ?? '', ENT_QUOTES, 'UTF-8') . ' (en clair)'; ?></dd>
                    </div>
                </dl>
            <?php else: ?>
                <p class="empty-state">Aucun utilisateur trouvé pour cet ID.</p>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Comptes bancaires</h2>
            <?php if (!empty($accountsList)): ?>
                <table class="accounts-table">
                    <thead>
                        <tr>
                            <th>Compte</th>
                            <th>Solde</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accountsList as $account): ?>
                        <tr>
                            <td>#<?php echo (int) $account['id']; ?></td>
                            <td><span class="balance"><?php echo htmlspecialchars(number_format((float) $account['balance'], 2, ',', ' ')); ?> €</span></td>
                            <td>
                                <div class="actions-cell">
                                    <a href="edit_account.php?id=<?php echo (int) $account['id']; ?>&user_id=<?php echo (int) $requestedUserId; ?>" class="btn btn-ghost">Modifier</a>
                                    <a href="delete_account.php?id=<?php echo (int) $account['id']; ?>&user_id=<?php echo (int) $requestedUserId; ?>" class="btn btn-danger" onclick="return confirm('Supprimer ce compte ?');">Supprimer</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty-state">Aucun compte lié.</p>
            <?php endif; ?>
        </section>

        <?php if ($_SESSION['username'] === 'admin'): ?>
        <section class="card">
            <h2>Administration</h2>
            <form method="post" action="make_admin.php" class="admin-form">
                <div>
                    <label for="target_user_id">ID utilisateur à promouvoir admin</label>
                    <input type="text" id="target_user_id" name="target_user_id" placeholder="ex. 2">
                </div>
                <button type="submit" class="btn btn-primary">Promouvoir</button>
            </form>
        </section>
        <?php endif; ?>
    </main>
</body>
</html>

