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
define('SITE_NAME', 'Blocksy');
define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]");
define('AVATAR_API', 'https://mc-heads.net/avatar');

// Configurazione reCAPTCHA Google
define('RECAPTCHA_SITE_KEY', '6Lcm188rAAAAAK0x_JWgJjNii5XY6rkoqPA-i7fJ'); // Sostituisci con la tua Site Key
define('RECAPTCHA_SECRET_KEY', '6Lcm188rAAAAAHlpFg9bYpicG-FspVf6Gq50QJ4r'); // Sostituisci con la tua Secret Key

// Funzioni di utilità

/**
 * Sanitizza l'input per prevenire XSS
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitizza il contenuto HTML di Quill preservando la formattazione sicura
 */
function sanitizeQuillContent($data) {
    // Lista di tag HTML sicuri permessi da Quill
    $allowed_tags = '<p><br><strong><b><em><i><u><ol><ul><li><h1><h2><h3><h4><h5><h6><blockquote><a>';
    
    // Rimuove tag non sicuri ma preserva quelli di formattazione
    $cleaned = strip_tags(trim($data), $allowed_tags);
    
    // Sanitizza gli attributi dei link per sicurezza
    $cleaned = preg_replace('/<a\s+[^>]*href\s*=\s*["\']([^"\']*)["\'][^>]*>/i', '<a href="$1" target="_blank" rel="noopener noreferrer">', $cleaned);
    
    // Rimuove attributi pericolosi
    $cleaned = preg_replace('/<(\w+)\s+[^>]*?(on\w+|javascript:|data:|style=)[^>]*?>/i', '<$1>', $cleaned);
    
    return $cleaned;
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
    return isset($_SESSION['is_admin']) && ($_SESSION['is_admin'] === true || $_SESSION['is_admin'] === 1 || $_SESSION['is_admin'] === '1');
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
    return AVATAR_API . "/" . urlencode($nickname);
}

/**
 * Controllo voto giornaliero - NUOVO SISTEMA: un voto al giorno per utente
 */
function canVote($user_id, $server_id, $pdo) {
    // Controlla se l'utente ha già votato oggi (qualsiasi server)
    $stmt = $pdo->prepare("SELECT data_voto FROM sl_votes WHERE user_id = ? AND DATE(data_voto) = CURDATE() LIMIT 1");
    $stmt->execute([$user_id]);
    $today_vote = $stmt->fetch();
    
    // Se ha già votato oggi, non può votare
    return !$today_vote;
}

/**
 * Ottieni informazioni sul voto giornaliero dell'utente
 */
function getUserDailyVoteInfo($user_id, $pdo) {
    $stmt = $pdo->prepare("SELECT v.server_id, s.nome, v.data_voto 
                          FROM sl_votes v 
                          JOIN sl_servers s ON v.server_id = s.id 
                          WHERE v.user_id = ? 
                          AND DATE(v.data_voto) = CURDATE() 
                          ORDER BY v.data_voto DESC 
                          LIMIT 1");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

?>