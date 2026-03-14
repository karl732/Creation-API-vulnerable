<?php
/**
 * Visualisation des logs applicatifs (tentatives de connexion, actions sensibles).
 * À usage formation uniquement — en production, restreindre l'accès (ex. admin, IP).
 */
session_start();
require_once "config.php";

$logFile = __DIR__ . '/logs/app.log';
$filterAction = isset($_GET['action']) ? trim((string) $_GET['action']) : '';
$lines = [];

if (is_file($logFile) && is_readable($logFile)) {
    $content = file_get_contents($logFile);
    foreach (explode("\n", $content) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $decoded = json_decode($line, true);
        if (!is_array($decoded)) continue;
        if ($filterAction !== '' && ($decoded['action'] ?? '') !== $filterAction) continue;
        $lines[] = $decoded;
    }
    $lines = array_reverse($lines); // plus récents en premier
}

$actions = [
    'LOGIN_SUCCESS' => 'Connexion réussie',
    'LOGIN_FAILURE' => 'Tentative de connexion échouée',
    'LOGOUT' => 'Déconnexion',
    'DELETE_ACCOUNT' => 'Suppression de compte',
    'UPDATE_BALANCE' => 'Modification de solde',
    'CHANGE_PASSWORD' => 'Changement de mot de passe',
    'REGISTER' => 'Inscription',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Journal des événements</title>
    <style>
        body { font-family: sans-serif; margin: 1em 2em; background: #f5f5f5; }
        h1 { color: #333; }
        .filters { margin: 1em 0; }
        .filters a { display: inline-block; margin-right: 0.5em; padding: 0.3em 0.6em; background: #ddd; border-radius: 4px; text-decoration: none; color: #333; }
        .filters a:hover, .filters a.active { background: #4a90d9; color: white; }
        table { border-collapse: collapse; width: 100%; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th, td { border: 1px solid #ddd; padding: 0.5em 0.75em; text-align: left; font-size: 0.9em; }
        th { background: #333; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .action-success { color: #2e7d32; }
        .action-failure { color: #c62828; }
        .action-sensitive { color: #d84315; }
        .time { white-space: nowrap; }
        .context { font-size: 0.85em; color: #555; }
        .back { margin-top: 1em; }
        .back a { color: #4a90d9; }
        .empty { padding: 1em; color: #666; }
    </style>
</head>
<body>
    <h1>Journal des événements</h1>
    <p>Tentatives de connexion et actions sensibles enregistrées par l'application.</p>

    <div class="filters">
        <strong>Filtrer par type :</strong>
        <a href="view_logs.php" class="<?php echo $filterAction === '' ? 'active' : ''; ?>">Tous</a>
        <?php foreach ($actions as $code => $label): ?>
            <a href="view_logs.php?action=<?php echo urlencode($code); ?>" class="<?php echo $filterAction === $code ? 'active' : ''; ?>"><?php echo htmlspecialchars($label); ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($lines)): ?>
        <p class="empty">Aucun événement enregistré<?php echo $filterAction ? ' pour ce filtre' : ''; ?>. Le fichier <code>logs/app.log</code> est vide ou inaccessible.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date / Heure</th>
                    <th>Action</th>
                    <th>Message</th>
                    <th>Utilisateur (session)</th>
                    <th>IP</th>
                    <th>Détails (context)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lines as $row):
                    $action = $row['action'] ?? '';
                    $class = '';
                    if (strpos($action, 'SUCCESS') !== false) $class = 'action-success';
                    elseif (strpos($action, 'FAILURE') !== false) $class = 'action-failure';
                    elseif (in_array($action, ['DELETE_ACCOUNT', 'UPDATE_BALANCE', 'CHANGE_PASSWORD', 'REGISTER'], true)) $class = 'action-sensitive';
                    $context = $row['context'] ?? [];
                ?>
                <tr>
                    <td class="time"><?php echo htmlspecialchars($row['time'] ?? '-'); ?></td>
                    <td class="<?php echo $class; ?>"><?php echo htmlspecialchars($actions[$action] ?? $action); ?></td>
                    <td><?php echo htmlspecialchars($row['message'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars(($row['username'] ?? '') ?: (isset($row['user_id']) ? 'id=' . (int)$row['user_id'] : '-')); ?></td>
                    <td><?php echo htmlspecialchars($row['ip'] ?? '-'); ?></td>
                    <td class="context"><?php echo !empty($context) ? htmlspecialchars(json_encode($context, JSON_UNESCAPED_UNICODE)) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p class="back"><a href="index.php">← Retour à l'accueil</a></p>
</body>
</html>
