<?php
/**
 * Script per aggiungere automaticamente le colonne mancanti alla tabella sl_servers
 * ESEGUI QUESTO FILE UNA SOLA VOLTA per sistemare il database
 */

require_once 'config.php';

// Verifica che l'utente sia admin
if (!isLoggedIn() || !isAdmin()) {
    die("Accesso negato. Solo gli amministratori possono eseguire questo script.");
}

echo "<h2>Fix Colonne Tabella sl_servers</h2>";
echo "<p>Aggiunta automatica delle colonne mancanti...</p>";

$columns_to_add = [
    [
        'name' => 'website_url',
        'sql' => "ALTER TABLE sl_servers ADD COLUMN website_url VARCHAR(255) NULL",
        'description' => 'URL del sito web del server'
    ],
    [
        'name' => 'shop_url',
        'sql' => "ALTER TABLE sl_servers ADD COLUMN shop_url VARCHAR(255) NULL",
        'description' => 'URL dello shop del server'
    ],
    [
        'name' => 'discord_url',
        'sql' => "ALTER TABLE sl_servers ADD COLUMN discord_url VARCHAR(255) NULL",
        'description' => 'URL del server Discord'
    ],
    [
        'name' => 'telegram_url',
        'sql' => "ALTER TABLE sl_servers ADD COLUMN telegram_url VARCHAR(255) NULL",
        'description' => 'URL del canale Telegram'
    ],
    [
        'name' => 'modalita',
        'sql' => "ALTER TABLE sl_servers ADD COLUMN modalita JSON NULL",
        'description' => 'Modalità di gioco (JSON array)'
    ],
    [
        'name' => 'staff_list',
        'sql' => "ALTER TABLE sl_servers ADD COLUMN staff_list JSON NULL",
        'description' => 'Lista staff del server (JSON)'
    ],
    [
        'name' => 'social_links',
        'sql' => "ALTER TABLE sl_servers ADD COLUMN social_links TEXT NULL",
        'description' => 'Link social aggiuntivi (JSON)'
    ],
    [
        'name' => 'in_costruzione',
        'sql' => "ALTER TABLE sl_servers ADD COLUMN in_costruzione TINYINT(1) DEFAULT 0",
        'description' => 'Flag server in costruzione'
    ],
    [
        'name' => 'votifier_host',
        'sql' => "ALTER TABLE sl_servers ADD COLUMN votifier_host VARCHAR(255) NULL",
        'description' => 'Host Votifier per invio voti'
    ],
    [
        'name' => 'votifier_port',
        'sql' => "ALTER TABLE sl_servers ADD COLUMN votifier_port INT DEFAULT 8192",
        'description' => 'Porta Votifier'
    ],
    [
        'name' => 'votifier_key',
        'sql' => "ALTER TABLE sl_servers ADD COLUMN votifier_key TEXT NULL",
        'description' => 'Chiave pubblica RSA Votifier'
    ],
    [
        'name' => 'data_aggiornamento',
        'sql' => "ALTER TABLE sl_servers ADD COLUMN data_aggiornamento TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP",
        'description' => 'Data ultimo aggiornamento'
    ]
];

$results = [];

foreach ($columns_to_add as $column) {
    try {
        $pdo->exec($column['sql']);
        $results[] = [
            'name' => $column['name'],
            'status' => 'success',
            'message' => 'Colonna aggiunta con successo',
            'description' => $column['description']
        ];
    } catch (PDOException $e) {
        // Verifica se l'errore è perché la colonna esiste già
        if (strpos($e->getMessage(), 'Duplicate column') !== false || 
            strpos($e->getMessage(), 'already exists') !== false) {
            $results[] = [
                'name' => $column['name'],
                'status' => 'exists',
                'message' => 'Colonna già esistente',
                'description' => $column['description']
            ];
        } else {
            $results[] = [
                'name' => $column['name'],
                'status' => 'error',
                'message' => $e->getMessage(),
                'description' => $column['description']
            ];
        }
    }
}

// Mostra i risultati
echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%; margin-top: 20px;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Colonna</th><th>Descrizione</th><th>Stato</th><th>Messaggio</th>";
echo "</tr>";

$success_count = 0;
$exists_count = 0;
$error_count = 0;

foreach ($results as $result) {
    $color = '';
    switch ($result['status']) {
        case 'success':
            $color = '#d4edda';
            $icon = '✓';
            $success_count++;
            break;
        case 'exists':
            $color = '#fff3cd';
            $icon = '○';
            $exists_count++;
            break;
        case 'error':
            $color = '#f8d7da';
            $icon = '✗';
            $error_count++;
            break;
    }
    
    echo "<tr style='background: $color;'>";
    echo "<td><strong>" . htmlspecialchars($result['name']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($result['description']) . "</td>";
    echo "<td style='text-align: center;'><strong>$icon</strong></td>";
    echo "<td>" . htmlspecialchars($result['message']) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<div style='margin-top: 20px; padding: 15px; background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 5px;'>";
echo "<h3>Riepilogo:</h3>";
echo "<ul>";
echo "<li><strong style='color: green;'>Colonne aggiunte:</strong> $success_count</li>";
echo "<li><strong style='color: orange;'>Colonne già esistenti:</strong> $exists_count</li>";
echo "<li><strong style='color: red;'>Errori:</strong> $error_count</li>";
echo "</ul>";

if ($error_count === 0) {
    echo "<p style='color: green; font-weight: bold;'>✓ Tutte le colonne sono state sistemate correttamente!</p>";
    echo "<p>Ora puoi modificare i server sia da profile.php che da admin.php senza problemi.</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ Alcuni errori sono stati riscontrati. Controlla i messaggi sopra.</p>";
}

echo "</div>";

echo "<hr>";
echo "<p><a href='/test_server_columns.php'>→ Verifica colonne</a> | <a href='/admin'>← Torna all'admin</a> | <a href='/profile'>← Torna al profilo</a></p>";
?>
