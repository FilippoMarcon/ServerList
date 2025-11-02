<?php
if (!isset($pdo)) {
    require_once 'config.php';
}

// Crea tabelle se non esistono
try {
    // Tabella per statistiche dettagliate (ogni 10 minuti, solo giorno corrente)
    $pdo->exec("CREATE TABLE IF NOT EXISTS sl_player_stats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL,
        player_count INT NOT NULL,
        recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(server_id),
        INDEX(recorded_at)
    )");
    
    // Tabella per statistiche giornaliere aggregate (max del giorno)
    $pdo->exec("CREATE TABLE IF NOT EXISTS sl_player_stats_daily (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL,
        date DATE NOT NULL,
        max_players INT NOT NULL,
        UNIQUE KEY unique_server_date (server_id, date),
        INDEX(server_id),
        INDEX(date)
    )");
} catch (PDOException $e) {
    error_log("Errore creazione tabelle player_stats: " . $e->getMessage());
    exit(1);
}

// Recupera tutti i server attivi
try {
    $stmt = $pdo->query("SELECT id, ip FROM sl_servers WHERE is_active = 1");
    $servers = $stmt->fetchAll();
    
    $saved = 0;
    foreach ($servers as $server) {
        // Usa l'API per ottenere il player count reale
        $ch = curl_init("https://api.mcsrvstat.us/3/" . urlencode($server['ip']));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Blocksy-ServerList/1.0 (https://blocksy.it)'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $player_count = 0;
        if ($http_code === 200 && $response) {
            $data = json_decode($response, true);
            if ($data && isset($data['online']) && $data['online']) {
                $player_count = $data['players']['online'] ?? 0;
            }
        }
        
        // Salva statistica
        $stmt = $pdo->prepare("INSERT INTO sl_player_stats (server_id, player_count) VALUES (?, ?)");
        $stmt->execute([$server['id'], $player_count]);
        $saved++;
    }
    
    // Pulizia: elimina record più vecchi di 2 giorni
    $pdo->exec("DELETE FROM sl_player_stats WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 2 DAY)");
    
    // Aggregazione giornaliera automatica
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $stmt = $pdo->query("SELECT DISTINCT server_id FROM sl_player_stats WHERE DATE(recorded_at) = '$yesterday'");
    $servers_yesterday = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($servers_yesterday as $sid) {
        $stmt = $pdo->prepare("SELECT MAX(player_count) as max_players FROM sl_player_stats WHERE server_id = ? AND DATE(recorded_at) = ?");
        $stmt->execute([$sid, $yesterday]);
        $max = $stmt->fetch()['max_players'] ?? 0;
        
        $stmt = $pdo->prepare("INSERT INTO sl_player_stats_daily (server_id, date, max_players) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE max_players = ?");
        $stmt->execute([$sid, $yesterday, $max, $max]);
    }
    
    // Elimina dati dettagliati di ieri dopo aggregazione
    $pdo->exec("DELETE FROM sl_player_stats WHERE DATE(recorded_at) = '$yesterday'");
    
} catch (PDOException $e) {
    error_log("Errore salvataggio player stats: " . $e->getMessage());
}
