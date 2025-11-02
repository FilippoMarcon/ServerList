<?php
// Avvio sessione e configurazione database
session_start();

$servername = "phpmyadmin.namedhosting.com";
$username = "user_6909";
$password = "AIsxFxqH-d0DW5BxPQN9nkwpf5_yACano3z6hER8nD8";
$dbname = "site_6909";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Connessione fallita: " . $e->getMessage());
}

/**
 * Configurazione database e impostazioni generali
 * Database configuration and general settings
 */

// Configurazione database MySQL
$servername = "phpmyadmin.namedhosting.com";
$username = "user_6909";
$password = "AIsxFxqH-d0DW5BxPQN9nkwpf5_yACano3z6hER8nD8";
$dbname = "site_6909";

// Opzioni PDO per la connessione sicura
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Creazione connessione PDO
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
    // Allinea timezone della sessione MySQL con quella PHP (Europe/Rome)
    try {
        $tz = new DateTimeZone('Europe/Rome');
        $dt = new DateTime('now', $tz);
        $offsetSeconds = $tz->getOffset($dt);
        $hours = intdiv($offsetSeconds, 3600);
        $mins = intdiv($offsetSeconds % 3600, 60);
        $offsetStr = sprintf('%+03d:%02d', $hours, $mins);
        $pdo->exec("SET time_zone = '{$offsetStr}'");
    } catch (Exception $e) {
        // Ignora eventuali errori di impostazione timezone
    }
} catch (PDOException $e) {
    // Gestione errore di connessione
    die("Connessione al database fallita: " . $e->getMessage());
}

// Configurazione sessione (allineata al backup)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gestione Remember Me Token
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    try {
        $token = $_COOKIE['remember_token'];
        $stmt = $pdo->prepare("
            SELECT rt.user_id, u.minecraft_nick, u.is_admin 
            FROM sl_remember_tokens rt
            JOIN sl_users u ON rt.user_id = u.id
            WHERE rt.token = ? AND rt.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Login automatico
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['minecraft_nick'] = $user['minecraft_nick'];
            $_SESSION['is_admin'] = $user['is_admin'];
        } else {
            // Token scaduto o non valido, rimuovi cookie
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
    } catch (PDOException $e) {
        error_log("Errore remember token: " . $e->getMessage());
    }
}

// Configurazione fuso orario
date_default_timezone_set('Europe/Rome');

// Costanti utili
define('SITE_NAME', 'Blocksy');
define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]");
define('AVATAR_API', 'https://mc-heads.net/avatar');
define('ENABLE_DEV_RESET_LINK_DISPLAY', true); // Mostra link reset in dev

// Configurazione reCAPTCHA Google
define('RECAPTCHA_SITE_KEY', '6Lcm188rAAAAAK0x_JWgJjNii5XY6rkoqPA-i7fJ'); // Sostituisci con la tua Site Key
define('RECAPTCHA_SECRET_KEY', '6Lcm188rAAAAAHlpFg9bYpicG-FspVf6Gq50QJ4r'); // Sostituisci con la tua Secret Key

// Migrazioni automatiche
try {
    // Aggiungi campo in_costruzione se non esiste
    $pdo->exec("ALTER TABLE sl_servers ADD COLUMN in_costruzione TINYINT(1) DEFAULT 0");
} catch (PDOException $e) {
    // Ignora se il campo esiste già
}

try {
    // Crea tabella user_bans se non esiste
    $pdo->exec("CREATE TABLE IF NOT EXISTS sl_user_bans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        reason TEXT,
        banned_by INT NOT NULL,
        banned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active TINYINT(1) DEFAULT 1,
        INDEX(user_id),
        INDEX(is_active)
    )");
} catch (PDOException $e) {
    error_log("Errore creazione tabella user_bans: " . $e->getMessage());
}

try {
    // Crea tabella messages se non esiste
    $pdo->exec("CREATE TABLE IF NOT EXISTS sl_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        from_user_id INT,
        to_user_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        parent_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(to_user_id),
        INDEX(is_read),
        INDEX(parent_id),
        INDEX(created_at)
    )");
    // Aggiungi parent_id se non esiste
    $pdo->exec("ALTER TABLE sl_messages ADD COLUMN parent_id INT NULL");
} catch (PDOException $e) {
    // Ignora se già esiste
}

try {
    // Aggiungi campi Votifier alla tabella servers
    $pdo->exec("ALTER TABLE sl_servers ADD COLUMN votifier_host VARCHAR(255) NULL");
    $pdo->exec("ALTER TABLE sl_servers ADD COLUMN votifier_port INT DEFAULT 8192");
    $pdo->exec("ALTER TABLE sl_servers ADD COLUMN votifier_key TEXT NULL");
} catch (PDOException $e) {
    // Ignora se già esistono
}

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
    $allowed_tags = '<p><br><strong><b><em><i><u><ol><ul><li><h1><h2><h3><h4><h5><h6><blockquote><a><span><div>';
    
    // Rimuove tag non sicuri ma preserva quelli di formattazione
    $cleaned = strip_tags(trim($data), $allowed_tags);
    
    // Sanitizza gli attributi dei link per sicurezza
    $cleaned = preg_replace('/<a\s+[^>]*href\s*=\s*["\']([^"\']*)["\'][^>]*>/i', '<a href="$1" target="_blank" rel="noopener noreferrer">', $cleaned);
    
    // Rimuove solo attributi pericolosi ma preserva style e class per Quill
    $cleaned = preg_replace('/<(\w+)\s+[^>]*?(on\w+|javascript:|data:)[^>]*?>/i', '<$1>', $cleaned);
    
    // Sanitizza gli attributi style per permettere solo proprietà CSS sicure
    $cleaned = preg_replace_callback('/style\s*=\s*["\']([^"\']*)["\']/', function($matches) {
        $style = $matches[1];
        // Permette solo proprietà CSS sicure di Quill
        $safe_properties = [
            'color', 'background-color', 'font-size', 'font-weight', 'font-style', 
            'text-decoration', 'text-align', 'line-height', 'margin', 'padding',
            'border', 'border-color', 'border-width', 'border-style'
        ];
        
        $safe_style = '';
        $declarations = explode(';', $style);
        foreach ($declarations as $declaration) {
            $parts = explode(':', $declaration, 2);
            if (count($parts) === 2) {
                $property = trim($parts[0]);
                $value = trim($parts[1]);
                
                // Verifica se la proprietà è sicura
                if (in_array($property, $safe_properties)) {
                    // Rimuove caratteri pericolosi dal valore
                    $value = preg_replace('/[<>"\']/', '', $value);
                    if (!empty($value)) {
                        $safe_style .= $property . ': ' . $value . '; ';
                    }
                }
            }
        }
        
        return !empty($safe_style) ? 'style="' . trim($safe_style) . '"' : '';
    }, $cleaned);
    
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
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    }
    // Fallback se gli headers sono già stati inviati
    echo '<script>window.location.href=' . json_encode($url) . ';</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES) . '"></noscript>';
    exit();
}

/**
 * CSRF Token utilities
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfInput() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
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
    // Ottiene l'ultimo voto dell'utente e verifica se è nello stesso giorno locale
    $stmt = $pdo->prepare("SELECT data_voto FROM sl_votes WHERE user_id = ? ORDER BY data_voto DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $last = $stmt->fetch();
    if (!$last) return true;
    
    // Converte timestamp DB (assunto UTC) in timezone locale e confronta con oggi locale
    $dt = new DateTime($last['data_voto'], new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
    return ($dt->format('Y-m-d') !== (new DateTime())->format('Y-m-d'));
}

/**
 * Ottieni informazioni sul voto giornaliero dell'utente
 */
function getUserDailyVoteInfo($user_id, $pdo) {
    $stmt = $pdo->prepare("SELECT v.server_id, s.nome, v.data_voto 
                          FROM sl_votes v 
                          JOIN sl_servers s ON v.server_id = s.id 
                          WHERE v.user_id = ? 
                          ORDER BY v.data_voto DESC 
                          LIMIT 1");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if (!$row) return false;
    
    // Converte su fuso locale e restituisce solo se è 'oggi' locale
    $dt = new DateTime($row['data_voto'], new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
    if ($dt->format('Y-m-d') === (new DateTime())->format('Y-m-d')) {
        return $row;
    }
    return false;
}

/**
 * Assicura tabella reset password
 */
function ensurePasswordResetTable($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS sl_password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        UNIQUE (token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

/**
 * Assicura tabelle per verifica Minecraft
 */
function ensureVerificationTables($pdo) {
    // Codici di verifica generati dal plugin, validi 5 minuti
    $pdo->exec("CREATE TABLE IF NOT EXISTS sl_verification_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NULL,
        license_key VARCHAR(64) NULL,
        player_nick VARCHAR(32) NOT NULL,
        code VARCHAR(32) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        consumed_at DATETIME NULL,
        UNIQUE KEY uniq_code (code),
        INDEX idx_player (player_nick),
        INDEX idx_exp (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Collegamento univoco tra utente del sito e nickname Minecraft
    $pdo->exec("CREATE TABLE IF NOT EXISTS sl_minecraft_links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        minecraft_nick VARCHAR(32) NOT NULL,
        verified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user (user_id),
        UNIQUE KEY uniq_mcnick (minecraft_nick),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Assicura che sl_users permetta duplicati sul campo minecraft_nick
    try {
        // Rimuovi UNIQUE se presente
        $pdo->exec("ALTER TABLE sl_users DROP INDEX minecraft_nick");
    } catch (Exception $e) {
        // Ignora se l'indice non esiste o non è rimovibile
    }
    try {
        // Aggiungi un indice non univoco per performance
        $pdo->exec("CREATE INDEX idx_sl_users_mcnick ON sl_users(minecraft_nick)");
    } catch (Exception $e) {
        // Ignora se già esiste
    }
}

// Inizializza risorsa necessaria
ensurePasswordResetTable($pdo);
ensureVerificationTables($pdo);

// Salva player stats quando qualcuno visita il sito
if (php_sapi_name() !== 'cli') {
    $last_save_file = sys_get_temp_dir() . '/blocksy_last_save.txt';
    $should_save = false;
    
    if (file_exists($last_save_file)) {
        $last_save = (int)file_get_contents($last_save_file);
        if ((time() - $last_save) >= 300) { // 5 minuti = 300 secondi
            $should_save = true;
        }
    } else {
        $should_save = true;
    }
    
    if ($should_save && rand(1, 100) <= 100) { // 100% probabilità - esegui sempre
        file_put_contents($last_save_file, time());
        register_shutdown_function(function() {
            global $pdo;
            @include_once __DIR__ . '/cron_save_player_stats.php';
        });
    }
}

?>