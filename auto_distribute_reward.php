<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

require_once 'config.php';

// Log per debug
error_log("=== AUTO DISTRIBUTE REWARD REQUEST ===");
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
    
    // Trova il primo voto non riscosso per questo giocatore
    if (!empty($license_key)) {
        // Usa la licenza per trovare il server
        $stmt = $pdo->prepare("
            SELECT vc.*, s.nome as server_name, s.id as server_id
            FROM sl_vote_codes vc
            JOIN sl_servers s ON vc.server_id = s.id
            JOIN sl_server_licenses sl ON s.id = sl.server_id
            LEFT JOIN sl_reward_logs srl ON vc.id = srl.vote_code_id
            WHERE vc.player_name = ? 
            AND sl.license_key = ? 
            AND sl.is_active = 1
            AND vc.status = 'pending' 
            AND vc.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND srl.id IS NULL
            ORDER BY vc.created_at ASC
            LIMIT 1
        ");
        $stmt->execute([$player_name, $license_key]);
    } else {
        // Fallback all'ID server
        $stmt = $pdo->prepare("
            SELECT vc.*, s.nome as server_name, s.id as server_id
            FROM sl_vote_codes vc
            JOIN sl_servers s ON vc.server_id = s.id
            LEFT JOIN sl_reward_logs srl ON vc.id = srl.vote_code_id
            WHERE vc.player_name = ? 
            AND vc.server_id = ? 
            AND vc.status = 'pending' 
            AND vc.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND srl.id IS NULL
            ORDER BY vc.created_at ASC
            LIMIT 1
        ");
        $stmt->execute([$player_name, $server_id]);
    }
    $vote_code = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vote_code) {
        echo json_encode([
            'success' => false,
            'error' => 'Nessun voto non riscosso trovato per questo giocatore'
        ]);
        return;
    }
    
    // Segna il codice come usato
    $update_stmt = $pdo->prepare("UPDATE sl_vote_codes SET status = 'used', used_at = NOW() WHERE id = ?");
    $update_stmt->execute([$vote_code['id']]);
    
    // Elimina tutti i codici pending precedenti dello stesso utente per lo stesso server
    $delete_stmt = $pdo->prepare("
        DELETE FROM sl_vote_codes 
        WHERE vote_id IN (
            SELECT v.id FROM sl_votes v 
            WHERE v.user_id = ? 
            AND v.server_id = ? 
            AND v.id != ?
        )
        AND status = 'pending'
    ");
    $delete_stmt->execute([$vote_code['user_id'], $vote_code['server_id'], $vote_code['vote_id']]);
    
    // Log della ricompensa (usa il server_id corretto dal risultato)
    $actual_server_id = $vote_code['server_id'];
    $log_stmt = $pdo->prepare("
        INSERT INTO sl_reward_logs (vote_code_id, server_id, user_id, minecraft_nick, commands_executed, reward_status) 
        VALUES (?, ?, ?, ?, ?, 'success')
    ");
    
    $log_stmt->execute([
        $vote_code['id'], 
        $actual_server_id, 
        $vote_code['user_id'], 
        $player_name, 
        json_encode(['auto_distributed' => true])
    ]);
    
    error_log("Auto-distributed reward for player: $player_name, code: " . $vote_code['code'] . ", server_id: " . $actual_server_id . ", license_key: " . ($license_key ?: 'none'));
    
    echo json_encode([
        'success' => true,
        'message' => 'Ricompensa automatica distribuita con successo!',
        'player' => $player_name,
        'server' => $vote_code['server_name'],
        'vote_time' => $vote_code['created_at'],
        'rewards' => [
            [
                'code' => $vote_code['code'],
                'type' => 'vote_reward'
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Errore in auto_distribute_reward.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'error' => 'Errore durante la distribuzione automatica della ricompensa',
        'debug_message' => $e->getMessage()
    ]);
}