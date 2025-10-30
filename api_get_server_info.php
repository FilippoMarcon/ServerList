<?php
/**
 * API per recuperare informazioni del server per il modal eventi
 */
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['server_id'])) {
    echo json_encode(['error' => 'Server ID mancante']);
    exit;
}

$server_id = (int)$_GET['server_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            id, nome, ip, tipo_server, descrizione, logo_url, 
            website_url, discord_url, telegram_url, social_links
        FROM sl_servers 
        WHERE id = ? AND is_active = 1
    ");
    $stmt->execute([$server_id]);
    $server = $stmt->fetch();
    
    if (!$server) {
        echo json_encode(['error' => 'Server non trovato']);
        exit;
    }
    
    // Decodifica social links
    $social_links = [];
    if (!empty($server['social_links'])) {
        $decoded = json_decode($server['social_links'], true);
        if (is_array($decoded)) {
            $social_links = $decoded;
        }
    }
    
    // Aggiungi Discord e Telegram ai social links se presenti
    if (!empty($server['discord_url'])) {
        array_unshift($social_links, ['title' => 'Discord', 'url' => $server['discord_url']]);
    }
    if (!empty($server['telegram_url'])) {
        array_unshift($social_links, ['title' => 'Telegram', 'url' => $server['telegram_url']]);
    }
    if (!empty($server['website_url'])) {
        array_unshift($social_links, ['title' => 'Sito Web', 'url' => $server['website_url']]);
    }
    
    echo json_encode([
        'success' => true,
        'server' => [
            'id' => $server['id'],
            'nome' => $server['nome'],
            'ip' => $server['ip'],
            'tipo_server' => $server['tipo_server'],
            'descrizione' => $server['descrizione'],
            'logo_url' => $server['logo_url'],
            'social_links' => $social_links
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Errore del database']);
}
