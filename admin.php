<?php
/**
 * Modern Admin Dashboard
 * Comprehensive server management system
 */

require_once 'config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'stats':
            // Get real-time statistics
            try {
                $stats = [];
                
                // Total servers
                $stmt = $pdo->query("SELECT COUNT(*) FROM sl_servers");
                $stats['total_servers'] = $stmt->fetchColumn();
                
                // Active servers (voted in last 7 days)
                $stmt = $pdo->query("SELECT COUNT(DISTINCT server_id) FROM sl_votes WHERE data_voto >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                $stats['active_servers'] = $stmt->fetchColumn();
                
                // Total users
                $stmt = $pdo->query("SELECT COUNT(*) FROM sl_users");
                $stats['total_users'] = $stmt->fetchColumn();
                
                // Total votes
                $stmt = $pdo->query("SELECT COUNT(*) FROM sl_votes");
                $stats['total_votes'] = $stmt->fetchColumn();
                
                // Today's votes
                $stmt = $pdo->query("SELECT COUNT(*) FROM sl_votes WHERE DATE(data_voto) = CURDATE()");
                $stats['today_votes'] = $stmt->fetchColumn();
                
                // New users today
                $stmt = $pdo->query("SELECT COUNT(*) FROM sl_users WHERE DATE(data_registrazione) = CURDATE()");
                $stats['today_users'] = $stmt->fetchColumn();
                
                // Top server by votes
                $stmt = $pdo->query("SELECT s.nome, COUNT(v.id) as votes FROM sl_servers s LEFT JOIN sl_votes v ON s.id = v.server_id GROUP BY s.id ORDER BY votes DESC LIMIT 1");
                $top_server = $stmt->fetch();
                $stats['top_server'] = $top_server ? $top_server['nome'] : 'N/A';
                $stats['top_server_votes'] = $top_server ? $top_server['votes'] : 0;
                
                echo json_encode($stats);
            } catch (PDOException $e) {
                echo json_encode(['error' => 'Database error']);
            }
            exit;
            
        case 'recent_activity':
            // Get recent activity
            try {
                $stmt = $pdo->query("
                    SELECT 'vote' as type, s.nome as server_name, u.minecraft_nick as user_nick, v.data_voto as timestamp
                    FROM sl_votes v 
                    JOIN sl_servers s ON v.server_id = s.id 
                    JOIN sl_users u ON v.user_id = u.id 
                    WHERE v.data_voto >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    UNION ALL
                    SELECT 'user' as type, '' as server_name, minecraft_nick as user_nick, data_registrazione as timestamp
                    FROM sl_users 
                    WHERE data_registrazione >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ORDER BY timestamp DESC 
                    LIMIT 10
                ");
                $activity = $stmt->fetchAll();
                echo json_encode($activity);
            } catch (PDOException $e) {
                echo json_encode(['error' => 'Database error']);
            }
            exit;
            
        case 'user_list':
            // Get user list with filters
            try {
                $search = $_GET['search'] ?? '';
                $role = $_GET['role'] ?? '';
                $status = $_GET['status'] ?? '';
                $date = $_GET['date'] ?? '';
                
                $where_conditions = [];
                $params = [];
                
                if (!empty($search)) {
                    $where_conditions[] = "(u.minecraft_nick LIKE ? OR u.email LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }
                
                if ($role === 'admin') {
                    $where_conditions[] = "u.is_admin = 1";
                } elseif ($role === 'user') {
                    $where_conditions[] = "u.is_admin = 0";
                }
                
                if ($date === 'today') {
                    $where_conditions[] = "DATE(u.data_registrazione) = CURDATE()";
                } elseif ($date === 'week') {
                    $where_conditions[] = "u.data_registrazione >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                } elseif ($date === 'month') {
                    $where_conditions[] = "u.data_registrazione >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                }
                
                $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
                
                // Get users with server and vote counts
                $sql = "
                    SELECT u.*, 
                           COUNT(DISTINCT s.id) as server_count,
                           COUNT(DISTINCT v.id) as vote_count
                    FROM sl_users u
                    LEFT JOIN sl_servers s ON u.id = s.user_id
                    LEFT JOIN sl_votes v ON u.id = v.user_id
                    $where_clause
                    GROUP BY u.id
                    ORDER BY u.data_registrazione DESC
                    LIMIT 50
                ";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $users = $stmt->fetchAll();
                
                // Get stats
                $stats_sql = "
                    SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN ultimo_accesso >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as active,
                        SUM(is_admin) as admins,
                        SUM(CASE WHEN DATE(data_registrazione) = CURDATE() THEN 1 ELSE 0 END) as new_today
                    FROM sl_users
                ";
                $stats_stmt = $pdo->query($stats_sql);
                $stats = $stats_stmt->fetch();
                
                echo json_encode([
                    'users' => $users,
                    'stats' => $stats,
                    'pagination' => ['current_page' => 1, 'total_pages' => 1]
                ]);
            } catch (PDOException $e) {
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'user_details':
            $user_id = intval($_GET['id'] ?? 0);
            if ($user_id > 0) {
                try {
                    $stmt = $pdo->prepare("
                        SELECT u.*, 
                               COUNT(DISTINCT s.id) as server_count,
                               COUNT(DISTINCT v.id) as vote_count
                        FROM sl_users u
                        LEFT JOIN sl_servers s ON u.id = s.user_id
                        LEFT JOIN sl_votes v ON u.id = v.user_id
                        WHERE u.id = ?
                        GROUP BY u.id
                    ");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                    
                    if ($user) {
                        echo json_encode($user);
                    } else {
                        echo json_encode(['error' => 'Utente non trovato']);
                    }
                } catch (PDOException $e) {
                    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
                }
            } else {
                echo json_encode(['error' => 'ID utente non valido']);
            }
            exit;
            
        case 'get_user':
            $user_id = intval($_GET['id'] ?? 0);
            if ($user_id > 0) {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM sl_users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                    
                    if ($user) {
                        echo json_encode($user);
                    } else {
                        echo json_encode(['error' => 'Utente non trovato']);
                    }
                } catch (PDOException $e) {
                    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
                }
            } else {
                echo json_encode(['error' => 'ID utente non valido']);
            }
            exit;
    }
}

// Handle user management POST requests
if (isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['ajax']) {
        case 'add_user':
            $minecraft_nick = sanitize($_POST['minecraft_nick'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            
            if (empty($minecraft_nick) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'error' => 'Tutti i campi sono obbligatori']);
                exit;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Email non valida']);
                exit;
            }
            
            try {
                // Check if user already exists
                $stmt = $pdo->prepare("SELECT id FROM sl_users WHERE minecraft_nick = ? OR email = ?");
                $stmt->execute([$minecraft_nick, $email]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'Utente già esistente']);
                    exit;
                }
                
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $is_admin = ($role === 'admin') ? 1 : 0;
                
                $stmt = $pdo->prepare("
                    INSERT INTO sl_users (minecraft_nick, email, password, is_admin, data_registrazione) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$minecraft_nick, $email, $hashed_password, $is_admin]);
                
                echo json_encode(['success' => true]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'Errore database: ' . $e->getMessage()]);
            }
            exit;
            
        case 'edit_user':
            $user_id = intval($_POST['id'] ?? 0);
            $minecraft_nick = sanitize($_POST['minecraft_nick'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            
            if ($user_id <= 0 || empty($minecraft_nick) || empty($email)) {
                echo json_encode(['success' => false, 'error' => 'Dati non validi']);
                exit;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Email non valida']);
                exit;
            }
            
            try {
                // Check if another user has the same nick/email
                $stmt = $pdo->prepare("SELECT id FROM sl_users WHERE (minecraft_nick = ? OR email = ?) AND id != ?");
                $stmt->execute([$minecraft_nick, $email, $user_id]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'Nick o email già in uso']);
                    exit;
                }
                
                $is_admin = ($role === 'admin') ? 1 : 0;
                
                if (!empty($password)) {
                    // Update with new password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        UPDATE sl_users 
                        SET minecraft_nick = ?, email = ?, password = ?, is_admin = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$minecraft_nick, $email, $hashed_password, $is_admin, $user_id]);
                } else {
                    // Update without changing password
                    $stmt = $pdo->prepare("
                        UPDATE sl_users 
                        SET minecraft_nick = ?, email = ?, is_admin = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$minecraft_nick, $email, $is_admin, $user_id]);
                }
                
                echo json_encode(['success' => true]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'Errore database: ' . $e->getMessage()]);
            }
            exit;
            
        case 'delete_user':
            $user_id = intval($_POST['user_id'] ?? 0);
            if ($user_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID utente non valido']);
                exit;
            }
            
            try {
                // Don't allow deleting yourself
                if ($user_id == $_SESSION['user_id']) {
                    echo json_encode(['success' => false, 'error' => 'Non puoi eliminare il tuo account']);
                    exit;
                }
                
                $stmt = $pdo->prepare("DELETE FROM sl_users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Utente non trovato']);
                }
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'Errore database: ' . $e->getMessage()]);
            }
            exit;
            
        case 'toggle_user_role':
            $user_id = intval($_POST['user_id'] ?? 0);
            if ($user_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID utente non valido']);
                exit;
            }
            
            try {
                // Don't allow changing your own role
                if ($user_id == $_SESSION['user_id']) {
                    echo json_encode(['success' => false, 'error' => 'Non puoi modificare il tuo ruolo']);
                    exit;
                }
                
                // Get current role and toggle it
                $stmt = $pdo->prepare("SELECT is_admin FROM sl_users WHERE id = ?");
                $stmt->execute([$user_id]);
                $current_role = $stmt->fetchColumn();
                
                if ($current_role !== false) {
                    $new_role = $current_role ? 0 : 1;
                    $stmt = $pdo->prepare("UPDATE sl_users SET is_admin = ? WHERE id = ?");
                    $stmt->execute([$new_role, $user_id]);
                    
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Utente non trovato']);
                }
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'Errore database: ' . $e->getMessage()]);
            }
            exit;
            
        case 'bulk_delete_users':
            $user_ids = explode(',', $_POST['user_ids'] ?? '');
            $user_ids = array_filter(array_map('intval', $user_ids));
            
            if (empty($user_ids)) {
                echo json_encode(['success' => false, 'error' => 'Nessun utente selezionato']);
                exit;
            }
            
            // Remove current user from the list
            $user_ids = array_filter($user_ids, function($id) {
                return $id != $_SESSION['user_id'];
            });
            
            if (empty($user_ids)) {
                echo json_encode(['success' => false, 'error' => 'Non puoi eliminare il tuo account']);
                exit;
            }
            
            try {
                $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
                $stmt = $pdo->prepare("DELETE FROM sl_users WHERE id IN ($placeholders)");
                $stmt->execute($user_ids);
                
                $affected = $stmt->rowCount();
                echo json_encode(['success' => true, 'affected' => $affected]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'Errore database: ' . $e->getMessage()]);
            }
            exit;
            
        case 'bulk_toggle_admin':
            $user_ids = explode(',', $_POST['user_ids'] ?? '');
            $user_ids = array_filter(array_map('intval', $user_ids));
            
            if (empty($user_ids)) {
                echo json_encode(['success' => false, 'error' => 'Nessun utente selezionato']);
                exit;
            }
            
            // Remove current user from the list
            $user_ids = array_filter($user_ids, function($id) {
                return $id != $_SESSION['user_id'];
            });
            
            if (empty($user_ids)) {
                echo json_encode(['success' => false, 'error' => 'Non puoi modificare il tuo ruolo']);
                exit;
            }
            
            try {
                $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
                $stmt = $pdo->prepare("UPDATE sl_users SET is_admin = NOT is_admin WHERE id IN ($placeholders)");
                $stmt->execute($user_ids);
                
                $affected = $stmt->rowCount();
                echo json_encode(['success' => true, 'affected' => $affected]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'Errore database: ' . $e->getMessage()]);
            }
            exit;
            
        case 'toggle_license_status':
            $license_id = intval($_POST['license_id'] ?? 0);
            if ($license_id > 0) {
                try {
                    // Get current status
                    $stmt = $pdo->prepare("SELECT is_active FROM sl_server_licenses WHERE id = ?");
                    $stmt->execute([$license_id]);
                    $current_status = $stmt->fetchColumn();
                    
                    if ($current_status !== false) {
                        // Toggle status
                        $new_status = $current_status ? 0 : 1;
                        $stmt = $pdo->prepare("UPDATE sl_server_licenses SET is_active = ? WHERE id = ?");
                        $stmt->execute([$new_status, $license_id]);
                        
                        echo json_encode([
                            'success' => true,
                            'message' => $new_status ? 'Licenza attivata con successo' : 'Licenza disattivata con successo'
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Licenza non trovata']);
                    }
                } catch (PDOException $e) {
                    echo json_encode(['success' => false, 'message' => 'Errore database: ' . $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'ID licenza non valido']);
            }
            exit;
            
        case 'revoke_license':
            $license_id = intval($_POST['license_id'] ?? 0);
            if ($license_id > 0) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM sl_server_licenses WHERE id = ?");
                    $stmt->execute([$license_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(['success' => true, 'message' => 'Licenza revocata con successo']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Licenza non trovata']);
                    }
                } catch (PDOException $e) {
                    echo json_encode(['success' => false, 'message' => 'Errore database: ' . $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'ID licenza non valido']);
            }
            exit;
            
        case 'bulk_revoke_licenses':
            $license_ids = explode(',', $_POST['license_ids'] ?? '');
            $license_ids = array_filter(array_map('intval', $license_ids));
            
            if (!empty($license_ids)) {
                try {
                    $placeholders = str_repeat('?,', count($license_ids) - 1) . '?';
                    $stmt = $pdo->prepare("DELETE FROM sl_server_licenses WHERE id IN ($placeholders)");
                    $stmt->execute($license_ids);
                    
                    $deleted_count = $stmt->rowCount();
                    echo json_encode([
                        'success' => true,
                        'message' => "$deleted_count licenze revocate con successo"
                    ]);
                } catch (PDOException $e) {
                    echo json_encode(['success' => false, 'message' => 'Errore database: ' . $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Nessuna licenza selezionata']);
            }
            exit;
            
        case 'get_user_details':
            $user_id = $_POST['user_id'] ?? 0;
            
            // Get user details with statistics
            $stmt = $pdo->prepare("
                SELECT u.*, 
                       COUNT(DISTINCT s.id) as owned_servers,
                       COUNT(DISTINCT v.id) as total_votes,
                       COUNT(DISTINCT CASE WHEN DATE(v.data_voto) = CURDATE() THEN v.id END) as today_votes,
                       MAX(v.data_voto) as last_vote
                FROM sl_users u
                LEFT JOIN sl_servers s ON u.id = s.proprietario_id
                LEFT JOIN sl_votes v ON u.id = v.user_id
                WHERE u.id = ?
                GROUP BY u.id
            ");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Get user's servers
                $stmt = $pdo->prepare("SELECT nome, ip, porta, attivo FROM sl_servers WHERE proprietario_id = ?");
                $stmt->execute([$user_id]);
                $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'user' => $user,
                    'servers' => $servers
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
            }
            exit;
            
        case 'toggle_user_role':
            $user_id = $_POST['user_id'] ?? 0;
            
            // Prevent self-demotion
            if ($user_id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Non puoi modificare il tuo stesso ruolo']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE sl_users SET is_admin = NOT is_admin WHERE id = ?");
            $stmt->execute([$user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Ruolo utente aggiornato']);
            exit;
            
        case 'delete_user':
            $user_id = $_POST['user_id'] ?? 0;
            
            // Prevent self-deletion
            if ($user_id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Non puoi eliminare il tuo stesso account']);
                exit;
            }
            
            try {
                $pdo->beginTransaction();
                
                // Delete user's votes
                $stmt = $pdo->prepare("DELETE FROM sl_votes WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Delete user's servers (or transfer ownership if needed)
                $stmt = $pdo->prepare("DELETE FROM sl_servers WHERE proprietario_id = ?");
                $stmt->execute([$user_id]);
                
                // Delete user
                $stmt = $pdo->prepare("DELETE FROM sl_users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Utente eliminato con successo']);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Errore nell\'eliminazione dell\'utente']);
            }
            exit;
            
        case 'bulk_delete_users':
            $user_ids = explode(',', $_POST['user_ids'] ?? '');
            $user_ids = array_filter(array_map('intval', $user_ids));
            
            // Remove current user from the list
            $user_ids = array_filter($user_ids, function($id) {
                return $id != $_SESSION['user_id'];
            });
            
            if (empty($user_ids)) {
                echo json_encode(['success' => false, 'message' => 'Nessun utente valido selezionato']);
                exit;
            }
            
            try {
                $pdo->beginTransaction();
                
                $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
                
                // Delete votes
                $stmt = $pdo->prepare("DELETE FROM sl_votes WHERE user_id IN ($placeholders)");
                $stmt->execute($user_ids);
                
                // Delete servers
                $stmt = $pdo->prepare("DELETE FROM sl_servers WHERE proprietario_id IN ($placeholders)");
                $stmt->execute($user_ids);
                
                // Delete users
                $stmt = $pdo->prepare("DELETE FROM sl_users WHERE id IN ($placeholders)");
                $stmt->execute($user_ids);
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Utenti eliminati con successo']);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Errore nell\'eliminazione degli utenti']);
            }
            exit;
            
        case 'bulk_toggle_admin':
            $user_ids = explode(',', $_POST['user_ids'] ?? '');
            $user_ids = array_filter(array_map('intval', $user_ids));
            
            // Remove current user from the list
            $user_ids = array_filter($user_ids, function($id) {
                return $id != $_SESSION['user_id'];
            });
            
            if (empty($user_ids)) {
                echo json_encode(['success' => false, 'message' => 'Nessun utente valido selezionato']);
                exit;
            }
            
            $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
            $stmt = $pdo->prepare("UPDATE sl_users SET is_admin = NOT is_admin WHERE id IN ($placeholders)");
            $stmt->execute($user_ids);
            
            echo json_encode(['success' => true, 'message' => 'Ruoli aggiornati con successo']);
            exit;
    }
}

// Handle form submissions
$action = isset($_GET['action']) ? sanitize($_GET['action']) : 'dashboard';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'add_server':
            $nome = sanitize($_POST['nome'] ?? '');
            $ip = sanitize($_POST['ip'] ?? '');
            $versione = sanitize($_POST['versione'] ?? '');
            $tipo_server = $_POST['tipo_server'] ?? 'Java & Bedrock';
            // Validazione tipo_server
            $allowed_types = ['Java', 'Bedrock', 'Java & Bedrock'];
            if (!in_array($tipo_server, $allowed_types)) {
                $tipo_server = 'Java & Bedrock';
            }
            $descrizione = sanitizeQuillContent($_POST['descrizione'] ?? '');
            $banner_url = sanitize($_POST['banner_url'] ?? '');
            $logo_url = sanitize($_POST['logo_url'] ?? '');
            $modalita = isset($_POST['modalita']) ? $_POST['modalita'] : [];
            $modalita_json = json_encode(array_values($modalita));
            
            if (empty($nome) || empty($ip) || empty($versione)) {
                $error = 'Nome, IP e Versione sono campi obbligatori.';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO sl_servers (nome, ip, versione, tipo_server, descrizione, banner_url, logo_url, modalita, is_active, data_inserimento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 2, NOW())");
                    $stmt->execute([$nome, $ip, $versione, $tipo_server, $descrizione, $banner_url, $logo_url, $modalita_json]);
                    $message = 'Server aggiunto con successo! Il server è in attesa di approvazione.';
                } catch (PDOException $e) {
                    $error = 'Errore durante l\'aggiunta del server.';
                }
            }
            break;
            
        case 'edit_server':
            $server_id = (int)($_POST['server_id'] ?? 0);
            $nome = sanitize($_POST['nome'] ?? '');
            $ip = sanitize($_POST['ip'] ?? '');
            $versione = sanitize($_POST['versione'] ?? '');
            $tipo_server = sanitize($_POST['tipo_server'] ?? 'Java & Bedrock');
            $descrizione = sanitize($_POST['descrizione'] ?? '');
            $banner_url = sanitize($_POST['banner_url'] ?? '');
            $logo_url = sanitize($_POST['logo_url'] ?? '');
            
            if ($server_id === 0 || empty($nome) || empty($ip) || empty($versione)) {
                $error = 'Tutti i campi obbligatori devono essere compilati.';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE sl_servers SET nome = ?, ip = ?, versione = ?, tipo_server = ?, descrizione = ?, banner_url = ?, logo_url = ? WHERE id = ?");
                    $stmt->execute([$nome, $ip, $versione, $tipo_server, $descrizione, $banner_url, $logo_url, $server_id]);
                    $message = 'Server modificato con successo!';
                } catch (PDOException $e) {
                    $error = 'Errore durante la modifica del server.';
                }
            }
            break;
            
        case 'delete_server':
            $server_id = (int)($_POST['server_id'] ?? 0);
            
            if ($server_id === 0) {
                $error = 'ID server non valido.';
            } else {
                try {
                    // Delete associated votes first
                    $stmt = $pdo->prepare("DELETE FROM sl_votes WHERE server_id = ?");
                    $stmt->execute([$server_id]);
                    
                    // Delete server
                    $stmt = $pdo->prepare("DELETE FROM sl_servers WHERE id = ?");
                    $stmt->execute([$server_id]);
                    
                    $message = 'Server eliminato con successo!';
                } catch (PDOException $e) {
                    $error = 'Errore durante l\'eliminazione del server.';
                }
            }
            break;
            
        case 'toggle_server_status':
            $server_id = (int)($_POST['server_id'] ?? 0);
            $status = (int)($_POST['status'] ?? 0);
            
            if ($server_id === 0) {
                echo json_encode(['success' => false, 'error' => 'ID server non valido.']);
                exit;
            }
            
            try {
                $stmt = $pdo->prepare("UPDATE sl_servers SET attivo = ? WHERE id = ?");
                $stmt->execute([$status, $server_id]);
                
                $statusText = $status ? 'attivato' : 'disattivato';
                echo json_encode(['success' => true, 'message' => "Server $statusText con successo!"]);
                exit;
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'Errore durante l\'operazione.']);
                exit;
            }
            break;
            
        case 'delete_server':
            $server_id = (int)($_POST['server_id'] ?? 0);
            
            if ($server_id === 0) {
                echo json_encode(['success' => false, 'error' => 'ID server non valido.']);
                exit;
            }
            
            try {
                // Delete associated votes first
                $stmt = $pdo->prepare("DELETE FROM sl_votes WHERE server_id = ?");
                $stmt->execute([$server_id]);
                
                // Delete server
                $stmt = $pdo->prepare("DELETE FROM sl_servers WHERE id = ?");
                $stmt->execute([$server_id]);
                
                echo json_encode(['success' => true, 'message' => 'Server eliminato con successo!']);
                exit;
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'Errore durante l\'eliminazione del server.']);
                exit;
            }
            break;
            
        case 'bulk_activate':
        case 'bulk_deactivate':
        case 'bulk_delete':
            $server_ids = json_decode($_POST['server_ids'] ?? '[]', true);
            
            if (empty($server_ids) || !is_array($server_ids)) {
                echo json_encode(['success' => false, 'error' => 'Nessun server selezionato.']);
                exit;
            }
            
            try {
                $placeholders = str_repeat('?,', count($server_ids) - 1) . '?';
                
                switch ($action) {
                    case 'bulk_activate':
                        $stmt = $pdo->prepare("UPDATE sl_servers SET attivo = 1 WHERE id IN ($placeholders)");
                        $stmt->execute($server_ids);
                        $message = count($server_ids) . ' server attivati con successo!';
                        break;
                        
                    case 'bulk_deactivate':
                        $stmt = $pdo->prepare("UPDATE sl_servers SET attivo = 0 WHERE id IN ($placeholders)");
                        $stmt->execute($server_ids);
                        $message = count($server_ids) . ' server disattivati con successo!';
                        break;
                        
                    case 'bulk_delete':
                        // Delete associated votes first
                        $stmt = $pdo->prepare("DELETE FROM sl_votes WHERE server_id IN ($placeholders)");
                        $stmt->execute($server_ids);
                        
                        // Delete servers
                        $stmt = $pdo->prepare("DELETE FROM sl_servers WHERE id IN ($placeholders)");
                        $stmt->execute($server_ids);
                        $message = count($server_ids) . ' server eliminati con successo!';
                        break;
                }
                
                echo json_encode(['success' => true, 'message' => $message]);
                exit;
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'Errore durante l\'operazione bulk.']);
                exit;
            }
            break;
    }
}

$page_title = "Admin Dashboard";
include 'header.php';
?>

<div class="admin-dashboard">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 col-md-4">
                <div class="admin-sidebar">
                    <div class="text-center mb-4">
                        <h4 class="text-primary mb-1">Admin Panel</h4>
                        <small class="text-secondary">Benvenuto, <?php echo htmlspecialchars($_SESSION['minecraft_nick']); ?></small>
                    </div>
                    
                    <nav class="admin-nav">
                        <a href="admin.php?action=dashboard" class="admin-nav-item <?php echo $action === 'dashboard' ? 'active' : ''; ?>">
                            <i class="bi bi-speedometer2"></i>
                            Dashboard
                        </a>
                        <a href="admin.php?action=users" class="admin-nav-item <?php echo $action === 'users' ? 'active' : ''; ?>">
                            <i class="bi bi-people"></i>
                            Gestione Utenti
                        </a>
                        <a href="admin.php?action=votes" class="admin-nav-item <?php echo $action === 'votes' ? 'active' : ''; ?>">
                            <i class="bi bi-bar-chart"></i>
                            Statistiche Voti
                        </a>
                        
                        <hr class="my-3" style="border-color: var(--border-color);">
                        
                        <a href="index.php" class="admin-nav-item">
                            <i class="bi bi-house"></i>
                            Vai al Sito
                        </a>
                        <a href="logout.php" class="admin-nav-item text-danger">
                            <i class="bi bi-box-arrow-right"></i>
                            Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9 col-md-8">
                <div class="admin-content">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i><?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($action === 'dashboard'): ?>
                        <!-- Dashboard Content -->
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="bi bi-speedometer2"></i>
                                Dashboard Overview
                            </h2>
                            <button class="btn btn-outline-primary btn-sm" onclick="refreshStats()">
                                <i class="bi bi-arrow-clockwise"></i> Aggiorna
                            </button>
                        </div>
                        
                        <!-- Statistics Cards -->
                        <div class="stats-grid" id="statsGrid">
                            <div class="stat-card">
                                <div class="stat-icon text-primary">
                                    <i class="bi bi-server"></i>
                                </div>
                                <div class="stat-number" id="totalServers">-</div>
                                <div class="stat-label">Server Totali</div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon text-success">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div class="stat-number" id="activeServers">-</div>
                                <div class="stat-label">Server Attivi</div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon text-info">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div class="stat-number" id="totalUsers">-</div>
                                <div class="stat-label">Utenti Registrati</div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon text-warning">
                                    <i class="bi bi-bar-chart"></i>
                                </div>
                                <div class="stat-number" id="totalVotes">-</div>
                                <div class="stat-label">Voti Totali</div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon text-danger">
                                    <i class="bi bi-calendar-day"></i>
                                </div>
                                <div class="stat-number" id="todayVotes">-</div>
                                <div class="stat-label">Voti Oggi</div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon text-purple">
                                    <i class="bi bi-person-plus"></i>
                                </div>
                                <div class="stat-number" id="todayUsers">-</div>
                                <div class="stat-label">Nuovi Utenti Oggi</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <!-- Recent Activity -->
                            <div class="col-lg-8">
                                <div class="dashboard-section">
                                    <div class="section-header">
                                        <h3 class="section-title">
                                            <i class="bi bi-activity"></i>
                                            Attività Recente
                                        </h3>
                                    </div>
                                    <div id="recentActivity">
                                        <div class="text-center text-secondary">
                                            <i class="bi bi-hourglass-split"></i> Caricamento...
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Quick Actions -->
                            <div class="col-lg-4">
                                <div class="dashboard-section">
                                    <div class="section-header">
                                        <h3 class="section-title">
                                            <i class="bi bi-lightning"></i>
                                            Azioni Rapide
                                        </h3>
                                    </div>
                                    <div class="quick-actions">
                                        <a href="admin.php?action=add_server" class="quick-action-btn">
                                            <i class="bi bi-plus-circle"></i>
                                            Aggiungi Server
                                        </a>
                                        <a href="admin.php?action=servers" class="quick-action-btn">
                                            <i class="bi bi-list"></i>
                                            Lista Server
                                        </a>
                                        <a href="admin.php?action=users" class="quick-action-btn">
                                            <i class="bi bi-people"></i>
                                            Gestisci Utenti
                                        </a>
                                        <a href="admin_rewards.php" class="quick-action-btn">
                                            <i class="bi bi-gift"></i>
                                            Ricompense
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Top Server -->
                                <div class="dashboard-section">
                                    <div class="section-header">
                                        <h3 class="section-title">
                                            <i class="bi bi-trophy"></i>
                                            Server Top
                                        </h3>
                                    </div>
                                    <div class="text-center">
                                        <div class="stat-number text-warning" id="topServerVotes">-</div>
                                        <div class="stat-label" id="topServerName">Caricamento...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        




                        



                    <?php elseif ($action === 'users'): ?>
                        <!-- User Management Section -->
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="bi bi-people"></i>
                                Gestione Utenti
                            </h2>
                            <div class="section-actions">
                                <button class="btn btn-outline-primary btn-sm" onclick="refreshUserList()">
                                    <i class="bi bi-arrow-clockwise"></i> Aggiorna
                                </button>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                    <i class="bi bi-plus-circle"></i> Aggiungi Utente
                                </button>
                            </div>
                        </div>

                        <!-- User Statistics -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-icon text-primary">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <div class="stat-number" id="totalUsersCount">-</div>
                                    <div class="stat-label">Utenti Totali</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-icon text-success">
                                        <i class="bi bi-person-check"></i>
                                    </div>
                                    <div class="stat-number" id="activeUsersCount">-</div>
                                    <div class="stat-label">Utenti Attivi</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-icon text-warning">
                                        <i class="bi bi-shield-check"></i>
                                    </div>
                                    <div class="stat-number" id="adminUsersCount">-</div>
                                    <div class="stat-label">Amministratori</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-icon text-info">
                                        <i class="bi bi-calendar-plus"></i>
                                    </div>
                                    <div class="stat-number" id="newUsersToday">-</div>
                                    <div class="stat-label">Nuovi Oggi</div>
                                </div>
                            </div>
                        </div>

                        <!-- User Filters -->
                        <div class="dashboard-section mb-4">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <i class="bi bi-funnel"></i>
                                    Filtri
                                </h3>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Cerca Utente</label>
                                    <input type="text" class="form-control" id="userSearchInput" placeholder="Nome utente o email...">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Ruolo</label>
                                    <select class="form-select" id="userRoleFilter">
                                        <option value="">Tutti i ruoli</option>
                                        <option value="admin">Amministratori</option>
                                        <option value="user">Utenti</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Stato</label>
                                    <select class="form-select" id="userStatusFilter">
                                        <option value="">Tutti gli stati</option>
                                        <option value="active">Attivi</option>
                                        <option value="inactive">Inattivi</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Data Registrazione</label>
                                    <input type="date" class="form-control" id="userDateFilter">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-primary" onclick="applyUserFilters()">
                                            <i class="bi bi-search"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" onclick="clearUserFilters()">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User Table -->
                        <div class="dashboard-section">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <i class="bi bi-list"></i>
                                    Lista Utenti
                                </h3>
                                <div class="section-actions">
                                    <div class="bulk-actions" id="userBulkActions" style="display: none;">
                                        <span class="text-muted me-2">Azioni per <span id="selectedUsersCount">0</span> utenti:</span>
                                        <button class="btn btn-warning btn-sm" onclick="bulkToggleAdminUsers()">
                                            <i class="bi bi-shield"></i> Cambia Ruolo
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="bulkDeleteUsers()">
                                            <i class="bi bi-trash"></i> Elimina
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input type="checkbox" id="selectAllUsers" onchange="toggleAllUserSelection()">
                                            </th>
                                            <th>Avatar</th>
                                            <th>Utente</th>
                                            <th>Email</th>
                                            <th>Ruolo</th>
                                            <th>Registrazione</th>
                                            <th>Ultimo Accesso</th>
                                            <th>Server</th>
                                            <th>Voti</th>
                                            <th>Azioni</th>
                                        </tr>
                                    </thead>
                                    <tbody id="userTableBody">
                                        <tr>
                                            <td colspan="10" class="text-center py-4">
                                                <i class="bi bi-hourglass-split"></i> Caricamento utenti...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <nav aria-label="User pagination" class="mt-3">
                                <ul class="pagination justify-content-center" id="userPagination">
                                    <!-- Pagination will be generated by JavaScript -->
                                </ul>
                            </nav>
                        </div>

                    <?php else: ?>
                        <!-- Other sections placeholder -->
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="bi bi-tools"></i>
                                Sezione in Sviluppo
                            </h2>
                        </div>
                        
                        <div class="text-center text-secondary py-5">
                            <i class="bi bi-gear" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <h4>Funzionalità in Arrivo</h4>
                            <p>Questa sezione sarà disponibile presto con nuove funzionalità avanzate.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus"></i>
                    Aggiungi Nuovo Utente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addUserForm">
                    <div class="mb-3">
                        <label class="form-label">Nome Utente Minecraft</label>
                        <input type="text" class="form-control" id="newUserMinecraftNick" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="newUserEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" id="newUserPassword" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ruolo</label>
                        <select class="form-select" id="newUserRole" required>
                            <option value="user">Utente</option>
                            <option value="admin">Amministratore</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" onclick="addNewUser()">
                    <i class="bi bi-plus-circle"></i> Aggiungi Utente
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-gear"></i>
                    Modifica Utente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="editUserId">
                    <div class="mb-3">
                        <label class="form-label">Nome Utente Minecraft</label>
                        <input type="text" class="form-control" id="editUserMinecraftNick" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="editUserEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nuova Password (lascia vuoto per non modificare)</label>
                        <input type="password" class="form-control" id="editUserPassword">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ruolo</label>
                        <select class="form-select" id="editUserRole" required>
                            <option value="user">Utente</option>
                            <option value="admin">Amministratore</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" onclick="saveUserChanges()">
                    <i class="bi bi-check-circle"></i> Salva Modifiche
                </button>
            </div>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-circle"></i>
                    Dettagli Utente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <div class="text-center">
                    <i class="bi bi-hourglass-split"></i> Caricamento...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Dashboard -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load initial stats
    refreshStats();
    loadRecentActivity();
    
    // Auto-refresh every 30 seconds
    setInterval(refreshStats, 30000);
    setInterval(loadRecentActivity, 60000);
});

function refreshStats() {
    fetch('admin.php?ajax=stats')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error loading stats:', data.error);
                return;
            }
            
            document.getElementById('totalServers').textContent = data.total_servers || '0';
            document.getElementById('activeServers').textContent = data.active_servers || '0';
            document.getElementById('totalUsers').textContent = data.total_users || '0';
            document.getElementById('totalVotes').textContent = data.total_votes || '0';
            document.getElementById('todayVotes').textContent = data.today_votes || '0';
            document.getElementById('todayUsers').textContent = data.today_users || '0';
            document.getElementById('topServerName').textContent = data.top_server || 'N/A';
            document.getElementById('topServerVotes').textContent = data.top_server_votes || '0';
        })
        .catch(error => {
            console.error('Error fetching stats:', error);
        });
}

function loadRecentActivity() {
    fetch('admin.php?ajax=recent_activity')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error loading activity:', data.error);
                return;
            }
            
            const container = document.getElementById('recentActivity');
            if (data.length === 0) {
                container.innerHTML = '<div class="text-center text-secondary"><i class="bi bi-inbox"></i> Nessuna attività recente</div>';
                return;
            }
            
            container.innerHTML = data.map(item => {
                const isVote = item.type === 'vote';
                const iconClass = isVote ? 'activity-vote' : 'activity-user';
                const icon = isVote ? 'bi-bar-chart' : 'bi-person-plus';
                const text = isVote 
                    ? `${item.user_nick} ha votato per ${item.server_name}`
                    : `${item.user_nick} si è registrato`;
                const time = new Date(item.timestamp).toLocaleString('it-IT');
                
                return `
                    <div class="activity-item">
                        <div class="activity-icon ${iconClass}">
                            <i class="bi ${icon}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-primary fw-medium">${text}</div>
                            <small class="text-secondary">${time}</small>
                        </div>
                    </div>
                `;
            }).join('');
        })
        .catch(error => {
            console.error('Error fetching activity:', error);
        });
}

// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 5000);
}



// Initialize all functions when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Existing initialization
    refreshStats();
    loadRecentActivity();
    setInterval(refreshStats, 30000);
    setInterval(loadRecentActivity, 60000);
    
    // New server management initialization
    initializeServerFilters();
    initializeBulkActions();
    initializeFormPreview();
    
    // License management initialization
    initializeLicenseFilters();
    initializeLicenseBulkActions();
    
    // User management initialization
    if (window.location.search.includes('action=users')) {
        refreshUserList();
        initializeUserFilters();
        initializeUserBulkActions();
    }
});

// User Management Functions
function refreshUserList() {
    fetch('admin.php?ajax=user_list')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showToast('Errore nel caricamento utenti: ' + data.error, 'danger');
                return;
            }
            
            updateUserStats(data.stats);
            updateUserTable(data.users);
            updateUserPagination(data.pagination);
        })
        .catch(error => {
            console.error('Error fetching users:', error);
            showToast('Errore di connessione', 'danger');
        });
}

function updateUserStats(stats) {
    document.getElementById('totalUsersCount').textContent = stats.total || '0';
    document.getElementById('activeUsersCount').textContent = stats.active || '0';
    document.getElementById('adminUsersCount').textContent = stats.admins || '0';
    document.getElementById('newUsersToday').textContent = stats.new_today || '0';
}

function updateUserTable(users) {
    const tbody = document.getElementById('userTableBody');
    
    if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4"><i class="bi bi-inbox"></i> Nessun utente trovato</td></tr>';
        return;
    }
    
    tbody.innerHTML = users.map(user => {
        const roleClass = user.is_admin ? 'text-warning' : 'text-primary';
        const roleIcon = user.is_admin ? 'bi-shield-check' : 'bi-person';
        const roleText = user.is_admin ? 'Admin' : 'Utente';
        const lastLogin = user.ultimo_accesso ? new Date(user.ultimo_accesso).toLocaleDateString('it-IT') : 'Mai';
        const registrationDate = new Date(user.data_registrazione).toLocaleDateString('it-IT');
        
        return `
            <tr>
                <td>
                    <input type="checkbox" class="user-checkbox" value="${user.id}">
                </td>
                <td>
                    <img src="${AVATAR_API}/${user.minecraft_nick}/32" alt="${user.minecraft_nick}" 
                         class="rounded" width="32" height="32" loading="lazy">
                </td>
                <td>
                    <div class="fw-medium">${user.minecraft_nick}</div>
                    <small class="text-secondary">ID: ${user.id}</small>
                </td>
                <td>${user.email}</td>
                <td>
                    <span class="${roleClass}">
                        <i class="bi ${roleIcon}"></i> ${roleText}
                    </span>
                </td>
                <td>${registrationDate}</td>
                <td>${lastLogin}</td>
                <td>
                    <span class="badge bg-primary">${user.server_count || 0}</span>
                </td>
                <td>
                    <span class="badge bg-success">${user.vote_count || 0}</span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="viewUserDetails(${user.id})" title="Dettagli">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-warning" onclick="editUser(${user.id})" title="Modifica">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-${user.is_admin ? 'secondary' : 'warning'}" 
                                onclick="toggleUserRole(${user.id})" title="${user.is_admin ? 'Rimuovi Admin' : 'Rendi Admin'}">
                            <i class="bi bi-shield${user.is_admin ? '-slash' : '-check'}"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteUser(${user.id})" title="Elimina">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
    
    updateUserBulkActionsVisibility();
}

function addNewUser() {
    const formData = {
        minecraft_nick: document.getElementById('newUserMinecraftNick').value,
        email: document.getElementById('newUserEmail').value,
        password: document.getElementById('newUserPassword').value,
        role: document.getElementById('newUserRole').value
    };
    
    fetch('admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            ajax: 'add_user',
            ...formData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Utente aggiunto con successo', 'success');
            bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
            document.getElementById('addUserForm').reset();
            refreshUserList();
        } else {
            showToast('Errore: ' + data.error, 'danger');
        }
    })
    .catch(error => {
        console.error('Error adding user:', error);
        showToast('Errore di connessione', 'danger');
    });
}

function editUser(userId) {
    fetch(`admin.php?ajax=get_user&id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showToast('Errore: ' + data.error, 'danger');
                return;
            }
            
            document.getElementById('editUserId').value = data.id;
            document.getElementById('editUserMinecraftNick').value = data.minecraft_nick;
            document.getElementById('editUserEmail').value = data.email;
            document.getElementById('editUserRole').value = data.is_admin ? 'admin' : 'user';
            
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        })
        .catch(error => {
            console.error('Error fetching user:', error);
            showToast('Errore di connessione', 'danger');
        });
}

function saveUserChanges() {
    const formData = {
        id: document.getElementById('editUserId').value,
        minecraft_nick: document.getElementById('editUserMinecraftNick').value,
        email: document.getElementById('editUserEmail').value,
        password: document.getElementById('editUserPassword').value,
        role: document.getElementById('editUserRole').value
    };
    
    fetch('admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            ajax: 'edit_user',
            ...formData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Utente modificato con successo', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
            refreshUserList();
        } else {
            showToast('Errore: ' + data.error, 'danger');
        }
    })
    .catch(error => {
        console.error('Error editing user:', error);
        showToast('Errore di connessione', 'danger');
    });
}

function viewUserDetails(userId) {
    fetch(`admin.php?ajax=user_details&id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showToast('Errore: ' + data.error, 'danger');
                return;
            }
            
            const content = `
                <div class="row">
                    <div class="col-md-4 text-center">
                        <img src="${AVATAR_API}/${data.minecraft_nick}/128" alt="${data.minecraft_nick}" 
                             class="rounded mb-3" width="128" height="128">
                        <h5>${data.minecraft_nick}</h5>
                        <span class="badge bg-${data.is_admin ? 'warning' : 'primary'} mb-3">
                            <i class="bi bi-${data.is_admin ? 'shield-check' : 'person'}"></i>
                            ${data.is_admin ? 'Amministratore' : 'Utente'}
                        </span>
                    </div>
                    <div class="col-md-8">
                        <h6>Informazioni Account</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Email:</strong></td><td>${data.email}</td></tr>
                            <tr><td><strong>Registrazione:</strong></td><td>${new Date(data.data_registrazione).toLocaleString('it-IT')}</td></tr>
                            <tr><td><strong>Ultimo Accesso:</strong></td><td>${data.ultimo_accesso ? new Date(data.ultimo_accesso).toLocaleString('it-IT') : 'Mai'}</td></tr>
                        </table>
                        
                        <h6 class="mt-3">Statistiche</h6>
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center p-2 bg-light rounded">
                                    <div class="h4 text-primary mb-0">${data.server_count || 0}</div>
                                    <small>Server Posseduti</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-2 bg-light rounded">
                                    <div class="h4 text-success mb-0">${data.vote_count || 0}</div>
                                    <small>Voti Totali</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('userDetailsContent').innerHTML = content;
            new bootstrap.Modal(document.getElementById('userDetailsModal')).show();
        })
        .catch(error => {
            console.error('Error fetching user details:', error);
            showToast('Errore di connessione', 'danger');
        });
}

function toggleUserRole(userId) {
    if (!confirm('Sei sicuro di voler cambiare il ruolo di questo utente?')) return;
    
    fetch('admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            ajax: 'toggle_user_role',
            user_id: userId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Ruolo utente modificato con successo', 'success');
            refreshUserList();
        } else {
            showToast('Errore: ' + data.error, 'danger');
        }
    })
    .catch(error => {
        console.error('Error toggling user role:', error);
        showToast('Errore di connessione', 'danger');
    });
}

function deleteUser(userId) {
    if (!confirm('Sei sicuro di voler eliminare questo utente? Questa azione non può essere annullata.')) return;
    
    fetch('admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            ajax: 'delete_user',
            user_id: userId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Utente eliminato con successo', 'success');
            refreshUserList();
        } else {
            showToast('Errore: ' + data.error, 'danger');
        }
    })
    .catch(error => {
        console.error('Error deleting user:', error);
        showToast('Errore di connessione', 'danger');
    });
}

function initializeUserFilters() {
    const searchInput = document.getElementById('userSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(applyUserFilters, 500));
    }
}

function applyUserFilters() {
    const search = document.getElementById('userSearchInput')?.value || '';
    const role = document.getElementById('userRoleFilter')?.value || '';
    const status = document.getElementById('userStatusFilter')?.value || '';
    const date = document.getElementById('userDateFilter')?.value || '';
    
    const params = new URLSearchParams({
        ajax: 'user_list',
        search,
        role,
        status,
        date
    });
    
    fetch(`admin.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showToast('Errore nel filtro: ' + data.error, 'danger');
                return;
            }
            updateUserTable(data.users);
            updateUserPagination(data.pagination);
        })
        .catch(error => {
            console.error('Error applying filters:', error);
            showToast('Errore di connessione', 'danger');
        });
}

function clearUserFilters() {
    document.getElementById('userSearchInput').value = '';
    document.getElementById('userRoleFilter').value = '';
    document.getElementById('userStatusFilter').value = '';
    document.getElementById('userDateFilter').value = '';
    refreshUserList();
}

function initializeUserBulkActions() {
    const selectAll = document.getElementById('selectAllUsers');
    if (selectAll) {
        selectAll.addEventListener('change', toggleAllUserSelection);
    }
}

function toggleAllUserSelection() {
    const selectAll = document.getElementById('selectAllUsers');
    const checkboxes = document.querySelectorAll('.user-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateUserBulkActionsVisibility();
}

function updateUserBulkActionsVisibility() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    const bulkActions = document.getElementById('userBulkActions');
    const selectedCount = document.getElementById('selectedUsersCount');
    
    if (checkboxes.length > 0) {
        bulkActions.style.display = 'block';
        selectedCount.textContent = checkboxes.length;
    } else {
        bulkActions.style.display = 'none';
    }
}

function bulkToggleAdminUsers() {
    const selectedIds = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        showToast('Seleziona almeno un utente', 'warning');
        return;
    }
    
    if (!confirm(`Sei sicuro di voler cambiare il ruolo di ${selectedIds.length} utenti?`)) return;
    
    fetch('admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            ajax: 'bulk_toggle_admin',
            user_ids: selectedIds.join(',')
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(`Ruolo modificato per ${data.affected} utenti`, 'success');
            refreshUserList();
        } else {
            showToast('Errore: ' + data.error, 'danger');
        }
    })
    .catch(error => {
        console.error('Error bulk toggling admin:', error);
        showToast('Errore di connessione', 'danger');
    });
}

function bulkDeleteUsers() {
    const selectedIds = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        showToast('Seleziona almeno un utente', 'warning');
        return;
    }
    
    if (!confirm(`Sei sicuro di voler eliminare ${selectedIds.length} utenti? Questa azione non può essere annullata.`)) return;
    
    fetch('admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            ajax: 'bulk_delete_users',
            user_ids: selectedIds.join(',')
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(`${data.affected} utenti eliminati con successo`, 'success');
            refreshUserList();
        } else {
            showToast('Errore: ' + data.error, 'danger');
        }
    })
    .catch(error => {
        console.error('Error bulk deleting users:', error);
        showToast('Errore di connessione', 'danger');
    });
}

function updateUserPagination(pagination) {
    // Implementation for pagination if needed
    const paginationContainer = document.getElementById('userPagination');
    if (paginationContainer && pagination) {
        // Add pagination logic here if needed
    }
}

// Utility function for debouncing
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Add event listeners for user checkboxes
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('user-checkbox')) {
        updateUserBulkActionsVisibility();
    }
});

</script>

<?php include 'footer.php'; ?>