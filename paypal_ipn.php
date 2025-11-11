<?php
/**
 * PayPal IPN (Instant Payment Notification) Handler
 * Gestisce i pagamenti delle sponsorizzazioni
 */

require_once 'config.php';

header('Content-Type: application/json');

// Verifica che sia una richiesta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Verifica CSRF
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Verifica che l'utente sia loggato
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Variabile per tracciare se abbiamo iniziato una transazione
$transaction_started = false;

try {
    // Recupera dati dal form
    $server_id = (int)($_POST['server_id'] ?? 0);
    $plan_days = (int)($_POST['plan_days'] ?? 0);
    $plan_price = (float)($_POST['plan_price'] ?? 0);
    $paypal_order_id = $_POST['paypal_order_id'] ?? '';
    $paypal_payer_id = $_POST['paypal_payer_id'] ?? '';
    
    // Validazione
    if ($server_id <= 0 || $plan_days <= 0 || $plan_price <= 0 || empty($paypal_order_id)) {
        throw new Exception('Dati non validi');
    }
    
    // Verifica che il server appartenga all'utente
    $stmt = $pdo->prepare("SELECT id, nome FROM sl_servers WHERE id = ? AND owner_id = ? AND is_active = 1");
    $stmt->execute([$server_id, $user_id]);
    $server = $stmt->fetch();
    
    if (!$server) {
        throw new Exception('Server non trovato o non autorizzato');
    }
    
    // Verifica che l'ordine PayPal non sia già stato processato
    $stmt = $pdo->prepare("SELECT id FROM sl_sponsor_payments WHERE paypal_order_id = ?");
    $stmt->execute([$paypal_order_id]);
    if ($stmt->fetch()) {
        throw new Exception('Pagamento già processato');
    }
    
    // Calcola data scadenza in ore (1 giorno = 24 ore)
    $hours = $plan_days * 24;
    $expires_at = date('Y-m-d H:i:s', strtotime("+$hours hours"));
    
    // Inizia transazione
    $pdo->beginTransaction();
    $transaction_started = true;
    
    // Salva il pagamento
    $stmt = $pdo->prepare("
        INSERT INTO sl_sponsor_payments 
        (server_id, user_id, amount, currency, plan_days, paypal_order_id, paypal_payer_id, payment_status, created_at) 
        VALUES (?, ?, ?, 'EUR', ?, ?, ?, 'completed', NOW())
    ");
    $stmt->execute([$server_id, $user_id, $plan_price, $plan_days, $paypal_order_id, $paypal_payer_id]);
    
    // Attiva o aggiorna la sponsorizzazione
    $stmt = $pdo->prepare("
        INSERT INTO sl_sponsored_servers (server_id, priority, is_active, created_at, expires_at) 
        VALUES (?, 1, 1, NOW(), ?) 
        ON DUPLICATE KEY UPDATE 
            is_active = 1,
            expires_at = IF(expires_at > NOW(), DATE_ADD(expires_at, INTERVAL ? HOUR), ?),
            priority = 1
    ");
    $stmt->execute([$server_id, $expires_at, $hours, $expires_at]);
    
    $pdo->commit();
    $transaction_started = false; // Transazione completata
    
    // Log attività DOPO il commit per evitare interferenze
    if (file_exists(__DIR__ . '/api_log_activity.php')) {
        try {
            require_once __DIR__ . '/api_log_activity.php';
            logActivity('sponsor_activated', 'server', $server_id, null, [
                'plan_days' => $plan_days,
                'amount' => $plan_price,
                'expires_at' => $expires_at
            ]);
        } catch (Exception $logError) {
            error_log("Errore durante log attività: " . $logError->getMessage());
        }
    }
    
    // Invia email di conferma (opzionale)
    // TODO: Implementare invio email
    
    echo json_encode([
        'success' => true,
        'message' => 'Sponsorizzazione attivata con successo',
        'server_name' => $server['nome'],
        'expires_at' => $expires_at
    ]);
    
} catch (Exception $e) {
    // Rollback solo se abbiamo effettivamente iniziato una transazione E c'è ancora una transazione attiva
    if ($transaction_started && $pdo->inTransaction()) {
        try {
            $pdo->rollBack();
        } catch (PDOException $rollbackError) {
            error_log("Errore durante rollback: " . $rollbackError->getMessage());
        }
    }
    
    error_log("Errore PayPal IPN: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
