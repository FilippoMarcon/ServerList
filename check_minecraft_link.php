<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Metodo non consentito']);
    exit;
}

$input = $_POST;
$player = '';
if (isset($input['player_nick'])) {
    $player = sanitize($input['player_nick']);
} elseif (isset($input['player_name'])) {
    $player = sanitize($input['player_name']);
}

if ($player === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Parametro mancante: player_name/player_nick']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT user_id, minecraft_nick FROM sl_minecraft_links WHERE minecraft_nick = ? LIMIT 1");
    $stmt->execute([$player]);
    $row = $stmt->fetch();
    if ($row) {
        echo json_encode(['ok' => true, 'verified' => true, 'minecraft_nick' => $row['minecraft_nick']]);
    } else {
        echo json_encode(['ok' => true, 'verified' => false]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Errore server']);
}