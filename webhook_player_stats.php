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

// Controlla se sono passati almeno 5 minuti dall'ultimo salvataggio
$last_save_file = sys_get_temp_dir() . '/blocksy_webhook_last_save.txt';
$min_interval = 300; // 5 minuti = 300 secondi

if (file_exists($last_save_file)) {
    $last_save = (int)file_get_contents($last_save_file);
    $elapsed = time() - $last_save;
    
    if ($elapsed < $min_interval) {
        $wait = $min_interval - $elapsed;
        echo "⏳ Troppo presto! Ultimo salvataggio: " . round($elapsed / 60, 1) . " minuti fa\n";
        echo "⏰ Riprova tra: " . round($wait / 60, 1) . " minuti\n";
        echo "\n✓ Webhook chiamato correttamente (ma non eseguito)\n";
        exit(0);
    }
}

// Aggiorna timestamp
file_put_contents($last_save_file, time());

require_once 'config.php';

/**
 * Query diretta al server Minecraft usando il protocollo Server List Ping
 * Più affidabile di API esterne
 */
function queryMinecraftServer($address, $timeout = 3) {
    // Separa IP e porta
    $parts = explode(':', $address);
    $host = $parts[0];
    $port = isset($parts[1]) ? (int)$parts[1] : 25565;
    
    try {
        // Crea socket
        $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$socket) {
            return false;
        }
        
        // Imposta timeout
        stream_set_timeout($socket, $timeout);
        
        // Handshake packet (Protocol 47 = 1.8.x, ma funziona per tutte le versioni moderne)
        $data = "\x00"; // Packet ID
        $data .= "\x2F"; // Protocol version (47)
        $data .= pack('c', strlen($host)) . $host; // Server address
        $data .= pack('n', $port); // Server port
        $data .= "\x01"; // Next state (1 = status)
        
        // Invia handshake
        $handshake = pack('c', strlen($data)) . $data;
        fwrite($socket, $handshake);
        
        // Request packet
        fwrite($socket, "\x01\x00");
        
        // Leggi risposta
        $length = unpack('c', fread($socket, 1))[1];
        if ($length < 1) {
            fclose($socket);
            return false;
        }
        
        // Leggi packet ID
        $packetId = unpack('c', fread($socket, 1))[1];
        if ($packetId !== 0x00) {
            fclose($socket);
            return false;
        }
        
        // Leggi lunghezza JSON
        $jsonLength = 0;
        $shift = 0;
        do {
            $byte = unpack('c', fread($socket, 1))[1];
            $jsonLength |= ($byte & 0x7F) << $shift;
            $shift += 7;
        } while ($byte & 0x80);
        
        // Leggi JSON
        $json = fread($socket, $jsonLength);
        fclose($socket);
        
        // Parse JSON
        $data = json_decode($json, true);
        if ($data && isset($data['players']['online'])) {
            return (int)$data['players']['online'];
        }
        
        return false;
        
    } catch (Exception $e) {
        return false;
    }
}

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
        
        // Prova prima con query diretta al server, poi fallback su API
        $player_count = 0;
        $status = 'offline';
        
        // Metodo 1: Query diretta al server Minecraft (più affidabile)
        $direct_result = queryMinecraftServer($server['ip']);
        if ($direct_result !== false) {
            $player_count = $direct_result;
            $status = 'online';
            echo "✓ $status ($player_count players) [direct]\n";
        } else {
            // Metodo 2: Fallback su API mcsrvstat.us
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
            curl_close($ch);
            
            if ($http_code === 200 && $response) {
                $data = json_decode($response, true);
                if ($data && isset($data['online']) && $data['online']) {
                    $player_count = $data['players']['online'] ?? 0;
                    $status = 'online';
                    echo "✓ $status ($player_count players) [api]\n";
                } else {
                    echo "✗ offline\n";
                    $errors++;
                }
            } else {
                echo "✗ api error\n";
                $errors++;
            }
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
    
    // Aggregazione giornaliera (oggi e ieri)
    echo "\nAggregazione giornaliera...\n";
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    $aggregated = 0;
    foreach ([$today, $yesterday] as $date) {
        $stmt = $pdo->query("SELECT DISTINCT server_id FROM sl_player_stats WHERE DATE(recorded_at) = '$date'");
        $servers_date = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($servers_date as $sid) {
            $stmt = $pdo->prepare("SELECT MAX(player_count) as max_players FROM sl_player_stats WHERE server_id = ? AND DATE(recorded_at) = ?");
            $stmt->execute([$sid, $date]);
            $max = $stmt->fetch()['max_players'] ?? 0;
            
            $stmt = $pdo->prepare("INSERT INTO sl_player_stats_daily (server_id, date, max_players) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE max_players = GREATEST(max_players, ?)");
            $stmt->execute([$sid, $date, $max, $max]);
            $aggregated++;
        }
    }
    echo "Record aggregati: $aggregated\n";
    
    // Pulizia vecchi dati (mantieni solo ultimi 2 giorni)
    $deleted = $pdo->exec("DELETE FROM sl_player_stats WHERE DATE(recorded_at) < DATE_SUB(CURDATE(), INTERVAL 2 DAY)");
    echo "Record vecchi eliminati: $deleted\n";
    
    echo "\n✓ Completato: " . date('Y-m-d H:i:s') . "\n";
    
} catch (PDOException $e) {
    echo "\n✗ Errore fatale: " . $e->getMessage() . "\n";
    exit(1);
}
