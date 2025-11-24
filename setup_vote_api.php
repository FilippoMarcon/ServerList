<?php
/**
 * Setup per il sistema di voti API (stile MinecraftITALIA)
 * Esegui questo file una volta per configurare il database
 */

require_once 'config.php';

echo "=== Setup Vote API System ===\n\n";

try {
    // 1. Aggiungi colonna 'processed' alla tabella votes
    echo "1. Aggiunta colonna 'processed' a sl_votes...\n";
    try {
        $pdo->exec("ALTER TABLE sl_votes ADD COLUMN processed TINYINT(1) DEFAULT 0");
        echo "   ✓ Colonna 'processed' aggiunta\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   - Colonna 'processed' già esistente\n";
        } else {
            throw $e;
        }
    }
    
    // 2. Aggiungi colonna 'api_key' alla tabella servers
    echo "\n2. Aggiunta colonna 'api_key' a sl_servers...\n";
    try {
        $pdo->exec("ALTER TABLE sl_servers ADD COLUMN api_key VARCHAR(64) NULL");
        echo "   ✓ Colonna 'api_key' aggiunta\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   - Colonna 'api_key' già esistente\n";
        } else {
            throw $e;
        }
    }
    
    // 3. Crea indice per performance
    echo "\n3. Creazione indice per performance...\n";
    try {
        $pdo->exec("CREATE INDEX idx_votes_processed ON sl_votes(server_id, processed)");
        echo "   ✓ Indice creato\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "   - Indice già esistente\n";
        } else {
            throw $e;
        }
    }
    
    // 4. Genera API keys per i server esistenti che non ne hanno una
    echo "\n4. Generazione API keys per i server...\n";
    $stmt = $pdo->query("SELECT id, nome FROM sl_servers WHERE api_key IS NULL OR api_key = ''");
    $servers = $stmt->fetchAll();
    
    foreach ($servers as $server) {
        $api_key = bin2hex(random_bytes(32)); // 64 caratteri hex
        $update = $pdo->prepare("UPDATE sl_servers SET api_key = ? WHERE id = ?");
        $update->execute([$api_key, $server['id']]);
        echo "   ✓ API key generata per '{$server['nome']}': $api_key\n";
    }
    
    if (empty($servers)) {
        echo "   - Tutti i server hanno già una API key\n";
    }
    
    echo "\n=== Setup completato con successo! ===\n\n";
    echo "Endpoint API: " . SITE_URL . "/api/vote/fetch.php?apiKey=YOUR_API_KEY\n";
    echo "\nProssimi passi:\n";
    echo "1. Configura il plugin Blocksy con l'API key del tuo server\n";
    echo "2. Il plugin farà polling ogni X secondi per recuperare i voti\n";
    echo "3. I voti verranno automaticamente processati e inviati a NuVotifier\n\n";
    
} catch (Exception $e) {
    echo "\n✗ ERRORE: " . $e->getMessage() . "\n";
    exit(1);
}
