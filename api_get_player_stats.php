<?php
/**
 * API per recuperare statistiche player
 * Supporta 3 periodi: today, 30days, 12months
 */
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['server_id'])) {
    echo json_encode(['success' => false, 'error' => 'Server ID mancante']);
    exit;
}

$server_id = (int)$_GET['server_id'];
$period = $_GET['period'] ?? 'today';

try {
    $stats = [];
    
    if ($period === 'today') {
        // Statistiche di oggi (ogni 10 minuti)
        $stmt = $pdo->prepare("
            SELECT player_count, recorded_at
            FROM sl_player_stats
            WHERE server_id = ?
            AND DATE(recorded_at) = CURDATE()
            ORDER BY recorded_at ASC
        ");
        $stmt->execute([$server_id]);
        $stats = $stmt->fetchAll();
        
    } elseif ($period === '30days') {
        // Ultimi 30 giorni (max giornaliero)
        $stmt = $pdo->prepare("
            SELECT max_players as player_count, date as recorded_at
            FROM sl_player_stats_daily
            WHERE server_id = ?
            AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ORDER BY date ASC
        ");
        $stmt->execute([$server_id]);
        $stats = $stmt->fetchAll();
        
    } elseif ($period === '12months') {
        // Ultimi 12 mesi (max mensile)
        $stmt = $pdo->prepare("
            SELECT max_players as player_count, 
                   CONCAT(year_month, '-01') as recorded_at
            FROM sl_player_stats_monthly
            WHERE server_id = ?
            AND year_month >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 12 MONTH), '%Y-%m')
            ORDER BY year_month ASC
        ");
        $stmt->execute([$server_id]);
        $stats = $stmt->fetchAll();
    }
    
    echo json_encode([
        'success' => true,
        'period' => $period,
        'stats' => $stats
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Errore database']);
}