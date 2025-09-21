<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

require_once 'config.php';

// Log per debug
error_log("=== CHECK PENDING VOTES REQUEST ===");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request data: " . file_get_contents('php://input'));

try {
    // Verifica connessione database
    $check_stmt = $pdo->prepare("SELECT 1");
    $check_stmt->execute();
    error_log("Database connection: OK");
    
    // Ricevi i dati JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception("Dati JSON non validi");
    }
    
    $player_name = isset($input['player_name']) ? trim($input['player_name']) : '';
    $server_id = isset($input['server_id']) ? intval($input['server_id']) : 0;
    $license_key = isset($input['license_key']) ? $input['license_key'] : '';
    
    if (empty($player_name) || (empty($server_id) && empty($license_key))) {
        throw new Exception("Parametri mancanti o non validi");
    }
    
    // Pulisci il nome del giocatore
    $player_name = preg_replace('/[^a-zA-Z0-9_]/', '', $player_name);
    
    // Controlla se ci sono voti non riscossi per questo giocatore
    if (!empty($license_key)) {
        // Usa la licenza per trovare il server
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as pending_count 
            FROM sl_vote_codes vc
            LEFT JOIN sl_reward_logs srl ON vc.id = srl.vote_code_id
            JOIN sl_server_licenses sl ON vc.server_id = sl.server_id
            WHERE vc.player_name = ? 
            AND sl.license_key = ? 
            AND sl.is_active = 1
            AND vc.status = 'pending' 
            AND vc.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND srl.id IS NULL
        ");
        $stmt->execute([$player_name, $license_key]);
    } else {
        // Fallback all'ID server
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as pending_count 
            FROM sl_vote_codes vc
            LEFT JOIN sl_reward_logs srl ON vc.id = srl.vote_code_id
            WHERE vc.player_name = ? 
            AND vc.server_id = ? 
            AND vc.status = 'pending' 
            AND vc.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND srl.id IS NULL
        ");
        $stmt->execute([$player_name, $server_id]);
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $has_pending = $result['pending_count'] > 0;
    
    error_log("Player: $player_name, Server: $server_id, Pending votes: " . $result['pending_count']);
    
    echo json_encode([
        'success' => true,
        'has_pending' => $has_pending,
        'pending_count' => $result['pending_count']
    ]);
    
} catch (Exception $e) {
    error_log("Errore in check_pending_votes.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'error' => 'Errore durante il controllo voti pendenti',
        'debug_message' => $e->getMessage()
    ]);
}