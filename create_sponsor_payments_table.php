<?php
/**
 * Script per creare la tabella dei pagamenti sponsorizzazioni
 * Esegui questo file UNA VOLTA
 */

require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    die("Accesso negato.");
}

echo "<h2>Creazione Tabella Pagamenti Sponsorizzazioni</h2>";

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sl_sponsor_payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            server_id INT NOT NULL,
            user_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'EUR',
            plan_days INT NOT NULL,
            paypal_order_id VARCHAR(100) NOT NULL,
            paypal_payer_id VARCHAR(100),
            payment_status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_order (paypal_order_id),
            INDEX(server_id),
            INDEX(user_id),
            INDEX(payment_status),
            FOREIGN KEY (server_id) REFERENCES sl_servers(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES sl_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    echo "<p style='color: green;'>✓ Tabella sl_sponsor_payments creata con successo!</p>";
    
    // Verifica struttura
    $stmt = $pdo->query("DESCRIBE sl_sponsor_payments");
    $columns = $stmt->fetchAll();
    
    echo "<h3>Struttura Tabella:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p><a href='/sponsor_payment.php'>→ Vai alla pagina sponsorizzazioni</a> | <a href='/admin'>← Torna all'admin</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Errore: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
