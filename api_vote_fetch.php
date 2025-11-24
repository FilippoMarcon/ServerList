<?php
/**
 * API Endpoint per recuperare voti pendenti
 * Simile a MinecraftITALIA /vote/fetch
 * 
 * Usato dal plugin Blocksy per fare polling dei voti
 */

require_once 'config.php';

header('Content-Type: application/json');

// Verifica API key
$api_key = isset($_GET['apiKey']) ? trim($_GET['apiKey']) : '';

if (empty($api_key)) {
    http_response_code(401);
    echo json_encode(['error' => 'API key mancante']);
    exit;
}

try {
    // Verifica che l'API key corrisponda a un server
    $stmt = $pdo->prepare("SELECT id, nome FROM sl_servers WHERE api_key = ? LIMIT 1");
    $stmt->execute([$api_key]);
    $server = $stmt->fetch();
    
    if (!$server) {
        http_response_code(403);
        echo json_encode(['error' => 'API key non valida']);
        exit;
    }
    
    $server_id = $server['id'];
    
    // Recupera voti pendenti (non ancora processati dal plugin)
    $stmt = $pdo->prepare("
        SELECT 
            v.id,
            v.server_id as serverId,
            ml.minecraft_nick as username,
            v.data_voto as timestamp
        FROM sl_votes v
        JOIN sl_minecraft_links ml ON v.user_id = ml.user_id
        WHERE v.server_id = ? 
        AND v.processed = 0
        ORDER BY v.data_voto ASC
        LIMIT 100
    ");
    $stmt->execute([$server_id]);
    $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatta i voti nel formato atteso dal plugin
    $formatted_votes = [];
    foreach ($votes as $vote) {
        $formatted_votes[] = [
            'id' => (int)$vote['id'],
            'serverId' => (int)$vote['serverId'],
            'username' => $vote['username'],
            'timestamp' => $vote['timestamp']
        ];
    }
    
    // Marca i voti come processati
    if (!empty($votes)) {
        $vote_ids = array_column($votes, 'id');
        $placeholders = implode(',', array_fill(0, count($vote_ids), '?'));
        $stmt = $pdo->prepare("UPDATE sl_votes SET processed = 1 WHERE id IN ($placeholders)");
        $stmt->execute($vote_ids);
    }
    
    echo json_encode($formatted_votes);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Errore server: ' . $e->getMessage()]);
    error_log("API vote/fetch error: " . $e->getMessage());
}
