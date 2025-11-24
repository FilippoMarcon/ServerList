<?php
/**
 * Votifier Webhook
 * Riceve i voti da Votifier tramite il plugin Blocksy
 */

require_once 'config.php';

header('Content-Type: application/json');

// Verifica che sia una richiesta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Leggi i dati JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

// Log della richiesta
error_log("Votifier Webhook - Dati ricevuti: " . $input);

// Verifica i campi obbligatori
$action = $data['action'] ?? '';
$license_key = $data['license_key'] ?? '';
$player_name = $data['player_name'] ?? '';
$service_name = $data['service_name'] ?? '';
$address = $data['address'] ?? '';
$timestamp = $data['timestamp'] ?? 0;

if ($action !== 'votifier_vote') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

if (empty($license_key) || empty($player_name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    // Trova il server dalla licenza
    $stmt = $pdo->prepare("SELECT id, nome FROM sl_servers WHERE license_key = ? AND is_active = 1");
    $stmt->execute([$license_key]);
    $server = $stmt->fetch();
    
    if (!$server) {
        error_log("Votifier Webhook - Server non trovato per licenza: $license_key");
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Server not found']);
        exit;
    }
    
    $server_id = $server['id'];
    
    // Trova l'utente dal nickname Minecraft
    $stmt = $pdo->prepare("SELECT id FROM sl_users WHERE minecraft_nick = ?");
    $stmt->execute([$player_name]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Crea un utente temporaneo se non esiste
        $stmt = $pdo->prepare("INSERT INTO sl_users (minecraft_nick, created_at) VALUES (?, NOW())");
        $stmt->execute([$player_name]);
        $user_id = $pdo->lastInsertId();
    } else {
        $user_id = $user['id'];
    }
    
    // Verifica se ha già votato oggi
    $stmt = $pdo->prepare("
        SELECT id FROM sl_votes 
        WHERE user_id = ? AND server_id = ? AND DATE(data_voto) = CURDATE()
    ");
    $stmt->execute([$user_id, $server_id]);
    
    if ($stmt->fetch()) {
        error_log("Votifier Webhook - $player_name ha già votato oggi per server $server_id");
        echo json_encode([
            'success' => true,
            'message' => 'Vote already registered today',
            'already_voted' => true
        ]);
        exit;
    }
    
    // Registra il voto
    $pdo->beginTransaction();
    
    // Inserisci il voto
    $stmt = $pdo->prepare("
        INSERT INTO sl_votes (server_id, user_id, data_voto, ip_address) 
        VALUES (?, ?, NOW(), ?)
    ");
    $stmt->execute([$server_id, $user_id, $_SERVER['REMOTE_ADDR'] ?? 'votifier']);
    $vote_id = $pdo->lastInsertId();
    
    // Genera codice voto
    $vote_code = strtoupper(substr(md5(uniqid($vote_id, true)), 0, 4) . '-' . 
                            substr(md5(uniqid($vote_id, true)), 4, 4) . '-' . 
                            substr(md5(uniqid($vote_id, true)), 8, 4));
    
    $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    // Inserisci il codice voto
    $stmt = $pdo->prepare("
        INSERT INTO sl_vote_codes (vote_id, server_id, user_id, code, expires_at, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$vote_id, $server_id, $user_id, $vote_code, $expires_at]);
    
    $pdo->commit();
    
    // Log attività
    if (file_exists(__DIR__ . '/api_log_activity.php')) {
        require_once __DIR__ . '/api_log_activity.php';
        logActivity('vote_received_votifier', 'vote', $vote_id, null, [
            'server_id' => $server_id,
            'player_name' => $player_name,
            'service_name' => $service_name,
            'vote_code' => $vote_code
        ]);
    }
    
    error_log("Votifier Webhook - Voto registrato: $player_name per server $server_id, codice: $vote_code");
    
    echo json_encode([
        'success' => true,
        'message' => 'Vote registered successfully',
        'vote_id' => $vote_id,
        'vote_code' => $vote_code,
        'server_name' => $server['nome']
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Votifier Webhook - Errore: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}
