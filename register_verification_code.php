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
$license = isset($input['license_key']) ? trim($input['license_key']) : null;
$serverId = isset($input['server_id']) ? (int)$input['server_id'] : null;
// Accetta sia 'player_nick' che 'player_name' dal plugin
$playerNick = '';
if (isset($input['player_nick'])) {
    $playerNick = trim($input['player_nick']);
} elseif (isset($input['player_name'])) {
    $playerNick = trim($input['player_name']);
}
$code = isset($input['code']) ? trim($input['code']) : '';

if ($playerNick === '' || $code === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Parametri mancanti: player_nick o code']);
    exit;
}

// Lunghezze ragionevoli
if (strlen($playerNick) > 32 || strlen($code) > 32) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Lunghezza parametri non valida']);
    exit;
}

// Verifica unicità del codice
$stmt = $pdo->prepare("SELECT id FROM sl_verification_codes WHERE code = ? LIMIT 1");
$stmt->execute([$code]);
if ($stmt->fetch()) {
    // Rinnova scadenza e aggiorna player
    $now = date('Y-m-d H:i:s');
$expires = date('Y-m-d H:i:s', time() + 300);
$stmtUp = $pdo->prepare("UPDATE sl_verification_codes SET player_nick = ?, server_id = ?, license_key = ?, created_at = ?, expires_at = ?, consumed_at = NULL WHERE code = ?");
$stmtUp->execute([$playerNick, $serverId, $license, $now, $expires, $code]);
    echo json_encode(['ok' => true, 'renewed' => true]);
    exit;
}

// Inserisce nuovo codice valido 5 minuti
$expires = date('Y-m-d H:i:s', time() + 300);
$stmtIns = $pdo->prepare("INSERT INTO sl_verification_codes (server_id, license_key, player_nick, code, expires_at) VALUES (?, ?, ?, ?, ?)");
$stmtIns->execute([$serverId, $license, $playerNick, $code, $expires]);

echo json_encode(['ok' => true]);