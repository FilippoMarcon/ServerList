<?php
/**
 * Script di debug per verificare lo stato delle API keys
 */

require_once '../config.php';

header('Content-Type: application/json');

$api_key = isset($_GET['apiKey']) ? trim($_GET['apiKey']) : '';

if (empty($api_key)) {
    echo json_encode(['error' => 'API key mancante']);
    exit;
}

try {
    // Verifica se la colonna esiste
    $columns = $pdo->query("SHOW COLUMNS FROM sl_servers LIKE 'api_key_active'")->fetchAll();
    $column_exists = !empty($columns);
    
    // Query per verificare lo stato
    $stmt = $pdo->prepare("SELECT id, nome, api_key, api_key_active FROM sl_servers WHERE api_key = ? LIMIT 1");
    $stmt->execute([$api_key]);
    $server = $stmt->fetch();
    
    if (!$server) {
        echo json_encode([
            'found' => false,
            'message' => 'API key non trovata nel database'
        ]);
        exit;
    }
    
    echo json_encode([
        'found' => true,
        'column_exists' => $column_exists,
        'server_id' => $server['id'],
        'server_name' => $server['nome'],
        'api_key_active' => $server['api_key_active'],
        'api_key_active_type' => gettype($server['api_key_active']),
        'is_active' => (int)$server['api_key_active'] === 1,
        'is_disabled' => (int)$server['api_key_active'] === 0,
        'message' => 'API key trovata'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
