<?php
/**
 * Script di migrazione per aggiungere il campo in_costruzione
 */
require_once 'config.php';

try {
    // Aggiungi campo in_costruzione
    $pdo->exec("ALTER TABLE sl_servers ADD COLUMN in_costruzione TINYINT(1) DEFAULT 0");
    echo "Campo in_costruzione aggiunto con successo!\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Campo in_costruzione già esistente.\n";
    } else {
        echo "Errore: " . $e->getMessage() . "\n";
    }
}

echo "Migrazione completata.\n";
