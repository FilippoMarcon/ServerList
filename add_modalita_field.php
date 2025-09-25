<?php
require_once 'config.php';

try {
    // Aggiunge il campo modalità alla tabella sl_servers
    $sql1 = "ALTER TABLE `sl_servers` ADD COLUMN `modalita` TEXT DEFAULT NULL COMMENT 'Modalità di gioco del server (JSON array)'";
    $pdo->exec($sql1);
    echo "Campo 'modalita' aggiunto con successo alla tabella sl_servers.\n";
    
    // Aggiorna i server esistenti con modalità di default
    $sql2 = "UPDATE `sl_servers` SET `modalita` = '[\"Survival\", \"Adventure\"]' WHERE `modalita` IS NULL";
    $result = $pdo->exec($sql2);
    echo "Aggiornati $result server con modalità di default.\n";
    
    echo "Operazione completata con successo!\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Il campo 'modalita' esiste già nella tabella.\n";
        
        // Aggiorna comunque i server che non hanno modalità
        try {
            $sql2 = "UPDATE `sl_servers` SET `modalita` = '[\"Survival\", \"Adventure\"]' WHERE `modalita` IS NULL OR `modalita` = ''";
            $result = $pdo->exec($sql2);
            echo "Aggiornati $result server con modalità di default.\n";
        } catch (PDOException $e2) {
            echo "Errore nell'aggiornamento: " . $e2->getMessage() . "\n";
        }
    } else {
        echo "Errore: " . $e->getMessage() . "\n";
    }
}
?>