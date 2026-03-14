<?php
/**
 * Journalisation des actions sensibles et des tentatives de connexion.
 * Ne jamais enregistrer de mots de passe.
 *
 * Fichier de log : www/logs/app.log (créé automatiquement si possible).
 * Sous Docker avec volume monté : s'assurer que le dossier logs/ est accessible en écriture
 * (ex. chmod 777 www/logs sur l'hôte ou création du dossier dans le Dockerfile).
 */

if (!function_exists('app_log')) {
    /**
     * Enregistre une entrée dans le journal applicatif.
     *
     * @param string $action   Type d'action (ex. LOGIN_SUCCESS, LOGIN_FAILURE, DELETE_ACCOUNT, UPDATE_BALANCE).
     * @param string $message  Message lisible.
     * @param array  $context  Données additionnelles (jamais de mot de passe). Sera sérialisé en JSON.
     */
    function app_log($action, $message, array $context = [])
    {
        $logDir = __DIR__ . '/logs';
        $logFile = $logDir . '/app.log';

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $user = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        $username = isset($_SESSION['username']) ? (string) $_SESSION['username'] : '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 200) : '';
        $ts = date('Y-m-d\TH:i:sP');

        $line = [
            'time'     => $ts,
            'action'   => $action,
            'message'  => $message,
            'user_id'  => $user,
            'username' => $username,
            'ip'       => $ip,
            'ua'       => $ua,
        ];
        if (!empty($context)) {
            $line['context'] = $context;
        }

        $json = json_encode($line, JSON_UNESCAPED_UNICODE) . "\n";

        if (@file_put_contents($logFile, $json, FILE_APPEND | LOCK_EX) === false) {
            error_log('[app_log] Impossible d\'écrire dans ' . $logFile . ' : ' . $message);
        }
    }
}
