<?php
/**
 * Test API key disattivata
 */

require_once '../../config.php';

header('Content-Type: application/json');

$api_key = isset($_GET['apiKey']) ? trim($_GET['apiKey']) : '';

if (empty($api_key)) {
    http_response_code(401);
    echo json_encode(['error' => 'API key mancante']);
    exit;
}

try {
    // Query diretta senza cache
    $stmt = $pdo->prepare("SELECT id, nome, api_key_active FROM sl_servers WHERE api_key = ? LIMIT 1");
    $stmt->execute([$api_key]);
    $server = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$server) {
        http_response_code(403);
        echo json_encode(['error' => 'API key non valida']);
        exit;
    }
    
    // Debug info
    $debug = [
        'server_id' => $server['id'],
        'server_name' => $server['nome'],
        'api_key_active_raw' => $server['api_key_active'],
        'api_key_active_type' => gettype($server['api_key_active']),
        'api_key_active_int' => (int)$server['api_key_active'],
        'is_null' => $server['api_key_active'] === null,
        'is_zero' => (int)$server['api_key_active'] === 0,
        'is_one' => (int)$server['api_key_active'] === 1,
    ];
    
    // Calcola se è attiva
    $is_active = ($server['api_key_active'] === null) ? true : ((int)$server['api_key_active'] === 1);
    
    $debug['calculated_is_active'] = $is_active;
    $debug['should_block'] = !$is_active;
    
    if (!$is_active) {
        http_response_code(403);
        echo json_encode([
            'error' => 'API key disattivata',
            'debug' => $debug
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'API key attiva',
        'debug' => $debug
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Errore: ' . $e->getMessage()]);
}
