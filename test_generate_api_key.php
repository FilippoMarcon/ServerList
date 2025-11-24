<?php
/**
 * Script veloce per generare API key di test
 * Usa questo se non riesci ad accedere al pannello admin
 */

require_once 'config.php';

// Genera API key
$api_key = bin2hex(random_bytes(32));

echo "===========================================\n";
echo "  API KEY GENERATA\n";
echo "===========================================\n\n";
echo "API Key: " . $api_key . "\n\n";
echo "Copia questa chiave nel config.yml del plugin:\n\n";
echo "api-key: \"" . $api_key . "\"\n\n";
echo "===========================================\n\n";

// Mostra lista server
try {
    $stmt = $pdo->query("SELECT id, nome, ip FROM sl_servers ORDER BY id");
    $servers = $stmt->fetchAll();
    
    if (empty($servers)) {
        echo "Nessun server trovato nel database.\n";
    } else {
        echo "Server disponibili:\n";
        foreach ($servers as $server) {
            echo "  ID: {$server['id']} - {$server['nome']} ({$server['ip']})\n";
        }
        echo "\n";
        
        // Chiedi quale server aggiornare
        echo "Inserisci l'ID del server da aggiornare (o premi INVIO per saltare): ";
        $server_id = trim(fgets(STDIN));
        
        if (!empty($server_id) && is_numeric($server_id)) {
            $stmt = $pdo->prepare("UPDATE sl_servers SET api_key = ? WHERE id = ?");
            $stmt->execute([$api_key, $server_id]);
            
            echo "\n✓ API key salvata per il server ID $server_id\n";
            echo "\nOra puoi usare questa key nel config.yml del plugin!\n";
        } else {
            echo "\nAPI key NON salvata nel database.\n";
            echo "Puoi salvarla manualmente o usare admin_generate_api_key.php\n";
        }
    }
} catch (Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}

echo "\n===========================================\n";
?>
