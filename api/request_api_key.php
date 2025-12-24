<?php
/**
 * API per richiedere una API key per un server
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Verifica autenticazione
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Leggi dati POST
$data = json_decode(file_get_contents('php://input'), true);
$server_id = isset($data['server_id']) ? (int)$data['server_id'] : 0;

if ($server_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Server ID non valido']);
    exit;
}

try {
    // Crea tabella richieste se non esiste
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sl_api_key_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            server_id INT NOT NULL,
            user_id INT NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (server_id) REFERENCES sl_servers(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES sl_users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_server_request (server_id)
        )
    ");
    
    // Verifica che il server appartenga all'utente
    $stmt = $pdo->prepare("SELECT id, nome, api_key FROM sl_servers WHERE id = ? AND owner_id = ?");
    $stmt->execute([$server_id, $user_id]);
    $server = $stmt->fetch();
    
    if (!$server) {
        echo json_encode(['success' => false, 'message' => 'Server non trovato o non hai i permessi']);
        exit;
    }
    
    // Verifica che non abbia già una API key
    if ($server['api_key']) {
        echo json_encode(['success' => false, 'message' => 'Il server ha già una API key']);
        exit;
    }
    
    // Verifica che non ci sia già una richiesta pending
    $stmt = $pdo->prepare("SELECT id, status FROM sl_api_key_requests WHERE server_id = ?");
    $stmt->execute([$server_id]);
    $existing_request = $stmt->fetch();
    
    if ($existing_request) {
        if ($existing_request['status'] === 'pending') {
            echo json_encode(['success' => false, 'message' => 'Hai già una richiesta in attesa per questo server']);
            exit;
        } else {
            // Aggiorna richiesta esistente
            $stmt = $pdo->prepare("UPDATE sl_api_key_requests SET status = 'pending', updated_at = NOW() WHERE server_id = ?");
            $stmt->execute([$server_id]);
        }
    } else {
        // Crea nuova richiesta
        $stmt = $pdo->prepare("INSERT INTO sl_api_key_requests (server_id, user_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$server_id, $user_id]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Richiesta inviata con successo'
    ]);
    
} catch (PDOException $e) {
    error_log("Errore richiesta API key: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore del server']);
}
