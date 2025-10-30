<?php
/**
 * API per recuperare eventi prossimi
 */
require_once 'config.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT e.*, s.nome as server_name, s.id as server_id, s.logo_url
        FROM sl_server_events e 
        JOIN sl_servers s ON e.server_id = s.id 
        WHERE e.is_active = 1 
        AND s.is_active = 1 
        AND e.event_date >= CURDATE() 
        AND e.event_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY e.event_date ASC, e.event_time ASC 
        LIMIT 5
    ");
    $events = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'events' => $events
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Errore del database']);
}
