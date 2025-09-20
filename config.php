<?php
/**
 * Configurazione database e impostazioni generali
 * Database configuration and general settings
 */

// Configurazione database MySQL
$servername = "phpmyadmin.namedhosting.com";
$username = "user_5907";
$password = "JyLYLLB3D0Bvh68MaYgn0RYS3RDMtIkpA0o7fPOOEzg";
$dbname = "site_5907";

// Opzioni PDO per la connessione sicura
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Creazione connessione PDO
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
} catch (PDOException $e) {
    // Gestione errore di connessione
    die("Connessione al database fallita: " . $e->getMessage());
}

// Configurazione sessione
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurazione fuso orario
date_default_timezone_set('Europe/Rome');

// Costanti utili
define('SITE_NAME', 'Minecraft Server List');
define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]");
define('AVATAR_API', 'https://minotar.net/avatar');

// Funzioni di utilità

/**
 * Sanitizza l'input per prevenire XSS
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Verifica se l'utente è loggato
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Verifica se l'utente è admin
 */
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Reindirizza a una pagina specifica
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Mostra messaggi di errore/successo
 */
function showMessage($message, $type = 'info') {
    $alert_class = $type === 'error' ? 'danger' : ($type === 'success' ? 'success' : 'info');
    return "<div class='alert alert-$alert_class alert-dismissible fade show' role='alert'>
            $message
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
}

/**
 * Ottieni avatar Minecraft
 */
function getMinecraftAvatar($nickname, $size = 64) {
    return AVATAR_API . "/" . urlencode($nickname) . "/$size.png";
}

/**
 * Controllo voto giornaliero
 */
function canVote($user_id, $server_id, $pdo) {
    $stmt = $pdo->prepare("SELECT data_voto FROM sl_votes WHERE user_id = ? AND server_id = ? ORDER BY data_voto DESC LIMIT 1");
    $stmt->execute([$user_id, $server_id]);
    $last_vote = $stmt->fetch();
    
    if ($last_vote) {
        $last_vote_time = strtotime($last_vote['data_voto']);
        $current_time = time();
        $hours_diff = ($current_time - $last_vote_time) / 3600;
        return $hours_diff >= 24;
    }
    
    return true;
}

?>