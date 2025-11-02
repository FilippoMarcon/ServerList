<?php
/**
 * Test Votifier Connection
 * Endpoint per testare la connessione Votifier dall'admin panel
 */

require_once 'config.php';

// Solo admin possono testare
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
    exit;
}

header('Content-Type: application/json');

// Leggi i dati dalla richiesta
$input = json_decode(file_get_contents('php://input'), true);

$host = $input['host'] ?? '';
$port = isset($input['port']) ? (int)$input['port'] : 8192;
$publicKey = $input['key'] ?? '';

if (empty($host) || empty($publicKey)) {
    echo json_encode([
        'success' => false,
        'error' => 'Host e chiave pubblica sono obbligatori'
    ]);
    exit;
}

try {
    require_once 'Votifier.php';
    
    $votifier = new Votifier($host, $port, $publicKey);
    $result = $votifier->testConnection();
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
