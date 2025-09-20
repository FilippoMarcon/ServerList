<?php
/**
 * Sistema di Votazione
 * Voting System
 * Gestisce i voti degli utenti per i server
 */

require_once 'config.php';

// Imposta l'header JSON per la risposta
header('Content-Type: application/json');

// Controlla se la richiesta è POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Metodo non consentito.'
    ]);
    exit;
}

// Controlla se l'utente è loggato
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Devi essere loggato per votare.'
    ]);
    exit;
}

// Ottieni i dati dalla richiesta
$server_id = isset($_POST['server_id']) ? (int)$_POST['server_id'] : 0;
$action = isset($_POST['action']) ? sanitize($_POST['action']) : '';

// Validazione input
if ($server_id === 0 || $action !== 'vote') {
    echo json_encode([
        'success' => false,
        'message' => 'Dati invalidi.'
    ]);
    exit;
}

try {
    // Inizia una transazione
    $pdo->beginTransaction();
    
    // Controlla se il server esiste
    $stmt = $pdo->prepare("SELECT id FROM sl_servers WHERE id = ?");
    $stmt->execute([$server_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Server non trovato.');
    }
    
    $user_id = $_SESSION['user_id'];
    
    // NUOVO SISTEMA: Controlla se l'utente ha già votato QUALSIASI server oggi
    $stmt = $pdo->prepare("SELECT v.server_id, s.nome, v.data_voto 
                          FROM sl_votes v 
                          JOIN sl_servers s ON v.server_id = s.id 
                          WHERE v.user_id = ? 
                          AND DATE(v.data_voto) = CURDATE() 
                          ORDER BY v.data_voto DESC 
                          LIMIT 1");
    $stmt->execute([$user_id]);
    $today_vote = $stmt->fetch();
    
    if ($today_vote) {
        // L'utente ha già votato oggi
        $voted_server_name = $today_vote['nome'];
        $vote_time = date('H:i', strtotime($today_vote['data_voto']));
        
        // Calcola il tempo fino a mezzanotte
        $now = new DateTime();
        $midnight = new DateTime('tomorrow midnight');
        $time_until_midnight = $midnight->diff($now);
        
        $hours = $time_until_midnight->h;
        $minutes = $time_until_midnight->i;
        
        throw new Exception("Hai già votato oggi per '{$voted_server_name}' alle {$vote_time}. Potrai votare di nuovo tra {$hours}h {$minutes}m (a mezzanotte).");
    }
    
    // Inserisci il voto
    $stmt = $pdo->prepare("INSERT INTO sl_votes (server_id, user_id, data_voto) VALUES (?, ?, NOW())");
    $stmt->execute([$server_id, $user_id]);
    
    // Aggiorna il contatore voti nella vista (opzionale, la vista si aggiorna automaticamente)
    // Ma possiamo anche aggiornare una cache se necessario
    
    // Commit della transazione
    $pdo->commit();
    
    // Invia webhook se configurato
    sendVoteWebhook($server_id, $_SESSION['minecraft_nick'], $pdo->lastInsertId());
    
    // Prepara la risposta di successo
    $response = [
        'success' => true,
        'message' => 'Voto registrato con successo!',
        'vote_time' => date('Y-m-d H:i:s'),
        'user_nick' => $_SESSION['minecraft_nick']
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback in caso di errore
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Funzione per ottenere lo stato di voto di un utente per un server specifico
 * Usata per verifiche aggiuntive o API
 */
function getUserVoteStatus($user_id, $server_id = null) {
    global $pdo;
    
    try {
        // Controlla se l'utente ha votato oggi (qualsiasi server)
        $stmt = $pdo->prepare("SELECT v.server_id, s.nome, v.data_voto 
                              FROM sl_votes v 
                              JOIN sl_servers s ON v.server_id = s.id 
                              WHERE v.user_id = ? 
                              AND DATE(v.data_voto) = CURDATE() 
                              ORDER BY v.data_voto DESC 
                              LIMIT 1");
        $stmt->execute([$user_id]);
        $today_vote = $stmt->fetch();
        
        if ($today_vote) {
            // Calcola il tempo fino a mezzanotte
            $now = new DateTime();
            $midnight = new DateTime('tomorrow midnight');
            $time_until_midnight = $midnight->diff($now);
            $time_remaining = ($time_until_midnight->h * 3600) + ($time_until_midnight->i * 60) + $time_until_midnight->s;
            
            return [
                'can_vote' => false,
                'voted_today' => true,
                'voted_server_id' => $today_vote['server_id'],
                'voted_server_name' => $today_vote['nome'],
                'last_vote_time' => $today_vote['data_voto'],
                'time_remaining' => $time_remaining,
                'next_vote_time' => $midnight->format('Y-m-d H:i:s')
            ];
        } else {
            return [
                'can_vote' => true,
                'voted_today' => false,
                'last_vote_time' => null,
                'time_remaining' => 0,
                'next_vote_time' => null
            ];
        }
        
    } catch (PDOException $e) {
        return [
            'can_vote' => false,
            'error' => 'Errore nel controllo del voto'
        ];
    }
}

/**
 * Funzione per ottenere le statistiche di voto di un server
 */
function getServerVoteStats($server_id) {
    global $pdo;
    
    try {
        // Voti totali
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_votes FROM sl_votes WHERE server_id = ?");
        $stmt->execute([$server_id]);
        $total_votes = $stmt->fetchColumn();
        
        // Voti di oggi
        $stmt = $pdo->prepare("SELECT COUNT(*) as today_votes FROM sl_votes 
                              WHERE server_id = ? AND DATE(data_voto) = CURDATE()");
        $stmt->execute([$server_id]);
        $today_votes = $stmt->fetchColumn();
        
        // Voti questa settimana
        $stmt = $pdo->prepare("SELECT COUNT(*) as week_votes FROM sl_votes 
                              WHERE server_id = ? AND data_voto >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $stmt->execute([$server_id]);
        $week_votes = $stmt->fetchColumn();
        
        // Voti questo mese
        $stmt = $pdo->prepare("SELECT COUNT(*) as month_votes FROM sl_votes 
                              WHERE server_id = ? AND data_voto >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stmt->execute([$server_id]);
        $month_votes = $stmt->fetchColumn();
        
        return [
            'total_votes' => (int)$total_votes,
            'today_votes' => (int)$today_votes,
            'week_votes' => (int)$week_votes,
            'month_votes' => (int)$month_votes
        ];
        
    } catch (PDOException $e) {
        return [
            'total_votes' => 0,
            'today_votes' => 0,
            'week_votes' => 0,
            'month_votes' => 0,
            'error' => 'Errore nel recupero delle statistiche'
        ];
    }
}

/**
 * Funzione per inviare webhook quando un utente vota
 */
function sendVoteWebhook($server_id, $player_name, $vote_id) {
    global $pdo;
    
    try {
        // Recupera la configurazione webhook per il server
        $stmt = $pdo->prepare("SELECT * FROM sl_webhooks WHERE server_id = ? AND is_active = 1");
        $stmt->execute([$server_id]);
        $webhook = $stmt->fetch();
        
        if (!$webhook || empty($webhook['webhook_url'])) {
            return false; // Nessun webhook configurato o disattivato
        }
        
        // Prepara il payload
        $payload = [
            'server_id' => $server_id,
            'player_name' => $player_name,
            'timestamp' => date('c'), // ISO 8601 format
            'vote_id' => $vote_id
        ];
        
        $payload_json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        
        // Calcola la firma HMAC
        $signature = hash_hmac('sha256', $payload_json, $webhook['webhook_secret']);
        
        // Prepara le headers
        $headers = [
            'Content-Type: application/json',
            'X-Webhook-Signature: sha256=' . $signature,
            'User-Agent: MinecraftServerList/1.0'
        ];
        
        // Inizializza cURL
        $ch = curl_init($webhook['webhook_url']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload_json,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        // Esegui la richiesta
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Log del webhook (indipendentemente dal risultato)
        $log_stmt = $pdo->prepare("INSERT INTO sl_webhook_logs (webhook_id, server_id, player_name, payload, response_code, response_body, success, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $success = ($http_code >= 200 && $http_code < 300);
        $log_stmt->execute([
            $webhook['id'],
            $server_id,
            $player_name,
            $payload_json,
            $http_code,
            $response,
            $success ? 1 : 0
        ]);
        
        return $success;
        
    } catch (Exception $e) {
        // Log dell'errore
        error_log("Errore nell'invio webhook: " . $e->getMessage());
        return false;
    }
}

/**
 * Funzione per ottenere la classifica dei server per voti
 */
function getServerRanking($limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT s.id, s.nome, s.ip, s.versione, s.logo_url, 
                                      COALESCE(v.vote_count, 0) as vote_count 
                               FROM sl_servers s 
                               LEFT JOIN server_votes_count v ON s.id = v.server_id 
                               ORDER BY vote_count DESC 
                               LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        return [];
    }
}