<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configurazione database
$host = 'localhost';
$dbname = 'votifier';
$username = 'root';
$password = '';

// Ricevi i dati JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['username']) || !isset($input['server_id'])) {
    echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
    exit;
}

$username = $input['username'];
$serverId = $input['server_id'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Controlla se il giocatore ha voti pendenti
    $stmt = $pdo->prepare("SELECT COUNT(*) as pending_votes FROM votes WHERE username = ? AND server_id = ? AND used = 0");
    $stmt->execute([$username, $serverId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $hasPendingVotes = $result['pending_votes'] > 0;

    echo json_encode([
        'success' => true,
        'has_pending_votes' => $hasPendingVotes,
        'pending_count' => (int)$result['pending_votes']
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Errore database: ' . $e->getMessage()]);
}
?>