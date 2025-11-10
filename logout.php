<?php
/**
 * Logout Script
 * Script di disconnessione
 */

require_once 'config.php';

// Elimina il token "remember me" dal database se esiste
if (isset($_COOKIE['remember_token'])) {
    try {
        $token = $_COOKIE['remember_token'];
        $stmt = $pdo->prepare("DELETE FROM sl_remember_tokens WHERE token = ?");
        $stmt->execute([$token]);
    } catch (PDOException $e) {
        error_log("Errore eliminazione remember token: " . $e->getMessage());
    }
    
    // Elimina il cookie
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    unset($_COOKIE['remember_token']);
}

// Elimina eventuali altri cookie di autenticazione
if (isset($_COOKIE['user_id'])) {
    setcookie('user_id', '', time() - 3600, '/', '', true, true);
    unset($_COOKIE['user_id']);
}

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

// Riavvia una nuova sessione pulita per il messaggio
session_start();
$_SESSION['success_message'] = 'Logout effettuato con successo!';

// Reindirizza alla homepage
redirect('/');