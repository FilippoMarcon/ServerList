<?php
/**
 * Sezione Gestione API Keys per admin.php
 * Sostituisce completamente la gestione licenze
 */

function include_api_keys() {
    global $pdo;
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    
    // Statistiche API Keys
    $stats = [];
    try {
        $stats['total_servers'] = $pdo->query("SELECT COUNT(*) FROM sl_servers WHERE is_