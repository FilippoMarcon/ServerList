<?php
/**
 * Test Port Connectivity
 * Verifica se una porta è raggiungibile
 */

require_once 'config.php';

// Solo admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
    exit;
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$host = $input['host'] ?? '';
$port = (int)($input['port'] ?? 0);

if (empty($host) || $port <= 0) {
    echo json_encode(['success' => false, 'error' => 'Host e porta richiesti']);
    exit;
}

$start = microtime(true);

// Prova a connettersi
$stream = @stream_socket_client(
    "tcp://{$host}:{$port}",
    $errno,
    $errstr,
    5
);

$time = round((microtime(true) - $start) * 1000);

if (!$stream) {
    echo json_encode([
        'success' => false,
        'error' => "$errstr (Codice: $errno)",
        'time' => $time
    ]);
    exit;
}

// Prova a leggere il banner
stream_set_timeout($stream, 3);
$banner = fread($stream, 64);
fclose($stream);

echo json_encode([
    'success' => true,
    'banner' => trim($banner),
    'time' => $time
]);
