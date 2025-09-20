<?php
/**
 * Webhook Receiver per Minecraft Plugin
 * Riceve notifiche dal plugin Minecraft e restituisce i comandi da eseguire
 */

require_once 'config.php';

// Imposta l'header JSON per la risposta
header('Content-Type: application/json');

// Controlla se la richiesta è POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Metodo non consentito. Usa POST.'
    ]);
    exit;
}

// Leggi il body della richiesta
$raw_body = file_get_contents('php://input');
$request_data = json_decode($raw_body, true);

// Valida i dati ricevuti
if (!$request_data || !isset($request_data['server_id']) || !isset($request_data['player_name']) || !isset($request_data['timestamp'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Dati mancanti o invalidi.'
    ]);
    exit;
}

try {
    $server_id = intval($request_data['server_id']);
    $player_name = sanitize($request_data['player_name']);
    
    // Recupera la configurazione webhook per il server
    $stmt = $pdo->prepare("SELECT * FROM sl_webhooks WHERE server_id = ? AND is_active = 1");
    $stmt->execute([$server_id]);
    $webhook = $stmt->fetch();
    
    if (!$webhook) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Webhook non configurato o disattivato per questo server.'
        ]);
        exit;
    }
    
    // Verifica la firma HMAC se presente
    $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
    if ($signature) {
        $expected_signature = 'sha256=' . hash_hmac('sha256', $raw_body, $webhook['webhook_secret']);
        if (!hash_equals($expected_signature, $signature)) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Firma webhook non valida.'
            ]);
            exit;
        }
    }
    
    // Verifica che il voto esista nel database
    if (isset($request_data['vote_id'])) {
        $vote_stmt = $pdo->prepare("SELECT id FROM sl_votes WHERE id = ? AND server_id = ? AND user_id IN (SELECT id FROM sl_users WHERE minecraft_nick = ?)");
        $vote_stmt->execute([$request_data['vote_id'], $server_id, $player_name]);
        if (!$vote_stmt->fetch()) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Voto non trovato nel database.'
            ]);
            exit;
        }
    }
    
    // Processa il template dei comandi
    $command_template = $webhook['command_template'];
    $commands = [];
    
    if (!empty($command_template)) {
        // Sostituisci il placeholder {player} con il nome del giocatore
        $processed_template = str_replace('{player}', $player_name, $command_template);
        
        // Dividi i comandi per riga
        $lines = explode("\n", $processed_template);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $commands[] = $line;
            }
        }
    }
    
    // Prepara la risposta di successo
    $response = [
        'success' => true,
        'message' => 'Webhook processato con successo',
        'player' => $player_name,
        'commands' => $commands,
        'timestamp' => date('c')
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Errore nel webhook: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore interno del server.'
    ]);
}