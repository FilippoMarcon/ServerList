<?php
/**
 * API per ottenere lo stato del server Minecraft (giocatori online, ping, ecc.)
 * Usa l'API pubblica di mcsrvstat.us
 */

header('Content-Type: application/json');

$server_ip = $_GET['ip'] ?? '';

if (empty($server_ip)) {
    echo json_encode([
        'success' => false,
        'error' => 'IP server mancante'
    ]);
    exit;
}

// Rimuovi porta se presente per l'API
$ip_parts = explode(':', $server_ip);
$clean_ip = $ip_parts[0];

// Usa l'API di mcsrvstat.us (gratuita e affidabile)
$api_url = "https://api.mcsrvstat.us/3/" . urlencode($clean_ip);

// Usa cURL per la richiesta
$ch = curl_init($api_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_USERAGENT => 'Blocksy-ServerList/1.0 (https://blocksy.it)'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$response) {
    echo json_encode([
        'success' => false,
        'error' => 'Impossibile contattare il server',
        'online' => false
    ]);
    exit;
}

$data = json_decode($response, true);

if (!$data || !isset($data['online'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Risposta API non valida',
        'online' => false
    ]);
    exit;
}

// Prepara la risposta
$result = [
    'success' => true,
    'online' => $data['online'],
    'players' => [
        'online' => $data['players']['online'] ?? 0,
        'max' => $data['players']['max'] ?? 0
    ],
    'version' => $data['version'] ?? 'Sconosciuta',
    'motd' => $data['motd']['clean'] ?? [],
    'icon' => $data['icon'] ?? null
];

echo json_encode($result);
