<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

// Pulisce codici scaduti
try {
    $now = date('Y-m-d H:i:s');
    $stmtDel = $pdo->prepare("DELETE FROM sl_verification_codes WHERE expires_at < ?");
    $stmtDel->execute([$now]);
} catch (Exception $e) {
    // ignore
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Metodo non consentito']);
    exit;
}

$input = $_POST;
$playerNick = '';
if (isset($input['player_nick'])) {
    $playerNick = sanitize($input['player_nick']);
} elseif (isset($input['player_name'])) {
    $playerNick = sanitize($input['player_name']);
}

if ($playerNick === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Parametro mancante: player_name/player_nick']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM sl_verification_codes WHERE player_nick = ? AND consumed_at IS NULL AND expires_at > NOW()");
    $stmt->execute([$playerNick]);
    $count = (int)$stmt->fetchColumn();
    echo json_encode(['ok' => true, 'count' => $count]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Errore server']);
}