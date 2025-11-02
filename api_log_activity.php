<?php
/**
 * Sistema di logging attività
 */
require_once 'config.php';

// Crea tabella se non esiste
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS sl_activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action_type VARCHAR(50) NOT NULL,
        entity_type VARCHAR(50),
        entity_id INT,
        old_values TEXT,
        new_values TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_id),
        INDEX(action_type),
        INDEX(entity_type),
        INDEX(entity_id),
        INDEX(created_at)
    )");
} catch (PDOException $e) {
    error_log("Errore creazione tabella activity_logs: " . $e->getMessage());
}

/**
 * Funzione per loggare un'attività
 */
function logActivity($action_type, $entity_type = null, $entity_id = null, $old_values = null, $new_values = null) {
    global $pdo;
    
    try {
        $user_id = $_SESSION['user_id'] ?? null;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO sl_activity_logs 
            (user_id, action_type, entity_type, entity_id, old_values, new_values, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $action_type,
            $entity_type,
            $entity_id,
            $old_values ? json_encode($old_values) : null,
            $new_values ? json_encode($new_values) : null,
            $ip_address,
            $user_agent
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Errore log activity: " . $e->getMessage());
        return false;
    }
}
