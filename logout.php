<?php
/**
 * Logout Script
 * Script di disconnessione
 */

require_once 'config.php';

// Distrugge tutte le variabili di sessione
$_SESSION = array();

// Se è stato usato un cookie di sessione, eliminalo
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Distrugge la sessione
session_destroy();

// Reindirizza alla homepage con messaggio di successo
$_SESSION['success_message'] = 'Logout effettuato con successo!';
redirect('index.php');