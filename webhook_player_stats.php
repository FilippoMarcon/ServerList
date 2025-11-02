<?php
/**
 * Webhook semplice per salvare player stats
 * Può essere chiamato da qualsiasi servizio esterno
 * 
 * URL: https://blocksy.it/webhook_player_stats.php
 */

// Previeni timeout
set_time_limit(300); // 5 minuti max
ini_set('max_execution_time', 300);

header('Content-Type: text/plain; charset=utf-8');
echo "=== BLOCKSY PLAYER STATS WEBHOOK ===\n";
echo "Inizio: " . date('Y-m-d H:i:s') . "\n\n";

require_once 'config.php';

// Crea tabelle se non esistono
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS sl_player_stats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL,
        player_count INT NOT NULL,
        recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(server_id),
        INDEX(recorded_at)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS sl_player_stats_daily (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL,
        date DATE NOT NULL,
        max_players INT NOT NULL,
        UNIQUE KEY unique_server_date (server_id, date),
        INDEX(server_id),
        INDEX(date)
    )");
    
    echo "✓ Tabelle verificate\n\n";
} catch (PDOException $e) {
    echo "✗ Errore tabelle: " . $e->getMessage() . "\n";
    exit(1);
}

// Recupera tutti i server attivi
try {
    $stmt = $pdo->query("SELECT id, nome, ip FROM sl_servers WHERE is_active = 1");
    $servers = $stmt->fetchAll();
    
    echo "Server attivi: " . count($servers) . "\n\n";
    
    if (empty($servers)) {
        echo "Nessun server da processare.\n";
        exit(0);
    }
    
    $saved = 0;
    $errors = 0;
    
    foreach ($servers as $server) {
        echo "Processing: {$server['nome']} ({$server['ip']})... ";
        flush();
        
        // Chiama API con timeout breve
        $ch = curl_init("https://api.mcsrvstat.us/3/" . urlencode($server['ip']));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Blocksy-ServerList/1.0 (https://blocksy.it)',
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        $player_count = 0;
        $status = 'offline';
        
        if ($http_code === 200 && $response) {
            $data = json_decode($response, true);
            if ($data && isset($data['online']) && $data['online']) {
                $player_count = $data['players']['online'] ?? 0;
                $status = 'online';
            }
        } elseif ($curl_error) {
            echo "CURL Error: $curl_error ";
            $errors++;
        }
        
        // Salva nel database
        try {
            $stmt = $pdo->prepare("INSERT INTO sl_player_stats (server_id, player_count) VALUES (?, ?)");
            $stmt->execute([$server['id'], $player_count]);
            echo "✓ $status ($player_count players)\n";
            $saved++;
        } catch (PDOException $e) {
            echo "✗ DB Error: " . $e->getMessage() . "\n";
            $errors++;
        }
        
        // Piccola pausa per non sovraccaricare l'API
        usleep(100000); // 0.1 secondi
    }
    
    echo "\n";
    echo "Salvati: $saved\n";
    echo "Errori: $errors\n";
    
    // Pulizia vecchi dati
    $deleted = $pdo->exec("DELETE FROM sl_player_stats WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 2 DAY)");
    echo "Record vecchi eliminati: $deleted\n";
    
    echo "\n✓ Completato: " . date('Y-m-d H:i:s') . "\n";
    
} catch (PDOException $e) {
    echo "\n✗ Errore fatale: " . $e->getMessage() . "\n";
    exit(1);
}
