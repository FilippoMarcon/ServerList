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
    }
}

// Handle license management AJAX requests
if (isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['ajax']) {
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
            $tipo_server = sanitize($_POST['tipo_server'] ?? 'Java & Bedrock');
            $descrizione = sanitize($_POST['descrizione'] ?? '');
            $banner_url = sanitize($_POST['banner_url'] ?? '');
            $logo_url = sanitize($_POST['logo_url'] ?? '');
            
            if (empty($nome) || empty($ip) || empty($versione)) {
                $error = 'Nome, IP e Versione sono campi obbligatori.';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO sl_servers (nome, ip, versione, tipo_server, descrizione, banner_url, logo_url, data_inserimento) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$nome, $ip, $versione, $tipo_server, $descrizione, $banner_url, $logo_url]);
                    $message = 'Server aggiunto con successo!';
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

<!-- Admin Dashboard Styles -->
<style>
.admin-dashboard {
    background: var(--primary-bg);
    min-height: 100vh;
    padding: 2rem 0;
}

.admin-sidebar {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 1.5rem;
    height: fit-content;
    position: sticky;
    top: 2rem;
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.admin-content {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 2rem;
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    min-height: 600px;
}

.admin-nav-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    margin: 0.25rem 0;
    border-radius: 10px;
    color: var(--text-secondary);
    text-decoration: none;
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.admin-nav-item:hover {
    background: var(--hover-bg);
    color: var(--text-primary);
    transform: translateX(4px);
}

.admin-nav-item.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: var(--accent-purple);
}

.admin-nav-item i {
    width: 20px;
    margin-right: 0.75rem;
    text-align: center;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--gradient-primary);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.stat-label {
    color: var(--text-secondary);
    font-size: 0.875rem;
    font-weight: 500;
}

.stat-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
    opacity: 0.7;
}

.dashboard-section {
    background: var(--secondary-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.section-title {
    color: var(--text-primary);
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    margin: 0.5rem 0;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    transition: all 0.2s ease;
}

.activity-item:hover {
    background: var(--hover-bg);
    transform: translateX(4px);
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.1rem;
}

.activity-vote {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.activity-user {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1.5rem;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 10px;
    color: var(--text-primary);
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 500;
}

.quick-action-btn:hover {
    background: var(--hover-bg);
    border-color: var(--accent-purple);
    transform: translateY(-2px);
    color: var(--text-primary);
}

.quick-action-btn i {
    margin-right: 0.5rem;
    font-size: 1.1rem;
}

@media (max-width: 768px) {
    .admin-dashboard {
        padding: 1rem 0;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .admin-sidebar {
        margin-bottom: 1.5rem;
        position: static;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
}
</style>

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
                        <a href="admin.php?action=servers" class="admin-nav-item <?php echo $action === 'servers' ? 'active' : ''; ?>">
                            <i class="bi bi-server"></i>
                            Gestione Server
                        </a>
                        <a href="admin.php?action=users" class="admin-nav-item <?php echo $action === 'users' ? 'active' : ''; ?>">
                            <i class="bi bi-people"></i>
                            Gestione Utenti
                        </a>
                        <a href="admin.php?action=votes" class="admin-nav-item <?php echo $action === 'votes' ? 'active' : ''; ?>">
                            <i class="bi bi-bar-chart"></i>
                            Statistiche Voti
                        </a>
                        <a href="admin.php?action=licenses" class="admin-nav-item <?php echo $action === 'licenses' ? 'active' : ''; ?>">
                            <i class="bi bi-key"></i>
                            Gestione Licenze
                        </a>
                        <a href="admin.php?action=sponsored" class="admin-nav-item <?php echo $action === 'sponsored' ? 'active' : ''; ?>">
                            <i class="bi bi-star"></i>
                            Server Sponsorizzati
                        </a>
                        <a href="admin_rewards.php" class="admin-nav-item">
                            <i class="bi bi-gift"></i>
                            Ricompense
                        </a>
                        <a href="admin_reward_logs.php" class="admin-nav-item">
                            <i class="bi bi-clock-history"></i>
                            Log Ricompense
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
                        
                    <?php elseif ($action === 'servers'): ?>
                        <!-- Server Management -->
                        <?php
                        // Get all servers with statistics
                        $stmt = $pdo->query("
                            SELECT s.*, 
                                   u.minecraft_nick as owner_nick,
                                   COUNT(v.id) as total_votes,
                                   COUNT(CASE WHEN DATE(v.data_voto) = CURDATE() THEN 1 END) as today_votes,
                                   MAX(v.data_voto) as last_vote
                            FROM sl_servers s 
                            LEFT JOIN sl_users u ON s.owner_id = u.id
                            LEFT JOIN sl_votes v ON s.id = v.server_id 
                            GROUP BY s.id 
                            ORDER BY total_votes DESC, s.nome ASC
                        ");
                        $servers = $stmt->fetchAll();
                        ?>
                        
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="bi bi-server"></i>
                                Gestione Server (<?php echo count($servers); ?>)
                            </h2>
                            <div class="section-actions">
                                <button class="btn btn-outline-secondary btn-sm" onclick="refreshServerList()">
                                    <i class="bi bi-arrow-clockwise"></i> Aggiorna
                                </button>
                                <a href="admin.php?action=add_server" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Aggiungi Server
                                </a>
                            </div>
                        </div>
                        
                        <!-- Server Filters -->
                        <div class="server-filters mb-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text" class="form-control" id="serverSearch" placeholder="Cerca server...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="serverTypeFilter">
                                        <option value="">Tutti i tipi</option>
                                        <option value="Java">Java</option>
                                        <option value="Bedrock">Bedrock</option>
                                        <option value="Java & Bedrock">Java & Bedrock</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="serverStatusFilter">
                                        <option value="">Tutti gli stati</option>
                                        <option value="1">Attivi</option>
                                        <option value="0">Disattivati</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-danger" onclick="clearFilters()">
                                        <i class="bi bi-x-circle"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bulk Actions -->
                        <div class="bulk-actions mb-3" style="display: none;">
                            <div class="d-flex align-items-center gap-3">
                                <span class="text-secondary">
                                    <span id="selectedCount">0</span> server selezionati
                                </span>
                                <button class="btn btn-outline-success btn-sm" onclick="bulkActivate()">
                                    <i class="bi bi-check-circle"></i> Attiva
                                </button>
                                <button class="btn btn-outline-warning btn-sm" onclick="bulkDeactivate()">
                                    <i class="bi bi-pause-circle"></i> Disattiva
                                </button>
                                <button class="btn btn-outline-danger btn-sm" onclick="bulkDelete()">
                                    <i class="bi bi-trash"></i> Elimina
                                </button>
                            </div>
                        </div>
                        
                        <!-- Servers Table -->
                        <div class="servers-table-container">
                            <div class="table-responsive">
                                <table class="table table-hover" id="serversTable">
                                    <thead>
                                        <tr>
                                            <th width="40">
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                            </th>
                                            <th width="80">Logo</th>
                                            <th>Server</th>
                                            <th>IP</th>
                                            <th>Tipo</th>
                                            <th>Owner</th>
                                            <th>Voti</th>
                                            <th>Oggi</th>
                                            <th>Stato</th>
                                            <th width="150">Azioni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($servers as $server): ?>
                                        <tr data-server-id="<?php echo $server['id']; ?>" 
                                            data-server-type="<?php echo $server['tipo_server']; ?>"
                                            data-server-status="<?php echo $server['is_active']; ?>">
                                            <td>
                                                <input type="checkbox" class="form-check-input server-checkbox" 
                                                       value="<?php echo $server['id']; ?>">
                                            </td>
                                            <td>
                                                <img src="<?php echo htmlspecialchars($server['logo_url'] ?: 'https://via.placeholder.com/64x64?text=?'); ?>" 
                                                     alt="Logo" class="server-logo-small" width="40" height="40">
                                            </td>
                                            <td>
                                                <div class="server-info">
                                                    <strong><?php echo htmlspecialchars($server['nome']); ?></strong>
                                                    <?php if (!$server['is_active']): ?>
                                                        <span class="badge bg-warning ms-2">Disattivato</span>
                                                    <?php endif; ?>
                                                    <br>
                                                    <small class="text-secondary">
                                                        Aggiunto: <?php echo date('d/m/Y', strtotime($server['data_inserimento'])); ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <code class="server-ip"><?php echo htmlspecialchars($server['ip']); ?></code>
                                                <br>
                                                <small class="text-secondary"><?php echo htmlspecialchars($server['versione']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $server['tipo_server'] === 'Java' ? 'primary' : ($server['tipo_server'] === 'Bedrock' ? 'success' : 'info'); ?>">
                                                    <?php echo $server['tipo_server']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($server['owner_nick']): ?>
                                                    <span class="text-primary"><?php echo htmlspecialchars($server['owner_nick']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-secondary">Nessuno</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $server['total_votes']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $server['today_votes'] > 0 ? 'success' : 'secondary'; ?>">
                                                    <?php echo $server['today_votes']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $server['is_active'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $server['is_active'] ? 'Attivo' : 'Disattivo'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="server.php?id=<?php echo $server['id']; ?>" 
                                                       class="btn btn-outline-primary" title="Visualizza">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <button class="btn btn-outline-warning" 
                                                            onclick="editServer(<?php echo $server['id']; ?>)" title="Modifica">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-outline-<?php echo $server['is_active'] ? 'warning' : 'success'; ?>" 
                                                            onclick="toggleServerStatus(<?php echo $server['id']; ?>, <?php echo $server['is_active'] ? 0 : 1; ?>)" 
                                                            title="<?php echo $server['is_active'] ? 'Disattiva' : 'Attiva'; ?>">
                                                        <i class="bi bi-<?php echo $server['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="deleteServer(<?php echo $server['id']; ?>, '<?php echo htmlspecialchars($server['nome']); ?>')" 
                                                            title="Elimina">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <?php if (empty($servers)): ?>
                        <div class="text-center text-secondary py-5">
                            <i class="bi bi-server" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <h4>Nessun Server</h4>
                            <p>Non ci sono server nel database. Aggiungi il primo server!</p>
                            <a href="admin.php?action=add_server" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Aggiungi Server
                            </a>
                        </div>
                        <?php endif; ?>
                        
                    <?php elseif ($action === 'add_server' || $action === 'edit_server'): ?>
                        <!-- Add/Edit Server Form -->
                        <?php
                        $editing = $action === 'edit_server';
                        $server_data = null;
                        
                        if ($editing && isset($_GET['id'])) {
                            $stmt = $pdo->prepare("SELECT * FROM sl_servers WHERE id = ?");
                            $stmt->execute([$_GET['id']]);
                            $server_data = $stmt->fetch();
                            
                            if (!$server_data) {
                                echo '<div class="alert alert-danger">Server non trovato!</div>';
                                $editing = false;
                            }
                        }
                        ?>
                        
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="bi bi-<?php echo $editing ? 'pencil' : 'plus-circle'; ?>"></i>
                                <?php echo $editing ? 'Modifica Server' : 'Aggiungi Nuovo Server'; ?>
                            </h2>
                            <a href="admin.php?action=servers" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Torna alla Lista
                            </a>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-body">
                                        <form id="serverForm" method="POST">
                                            <input type="hidden" name="action" value="<?php echo $editing ? 'edit_server' : 'add_server'; ?>">
                                            <?php if ($editing): ?>
                                                <input type="hidden" name="server_id" value="<?php echo $server_data['id']; ?>">
                                            <?php endif; ?>
                                            
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="nome" class="form-label">Nome Server *</label>
                                                    <input type="text" class="form-control" id="nome" name="nome" 
                                                           value="<?php echo $editing ? htmlspecialchars($server_data['nome']) : ''; ?>" 
                                                           required maxlength="100">
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <label for="ip" class="form-label">IP Server *</label>
                                                    <input type="text" class="form-control" id="ip" name="ip" 
                                                           value="<?php echo $editing ? htmlspecialchars($server_data['ip']) : ''; ?>" 
                                                           required maxlength="255">
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <label for="versione" class="form-label">Versione</label>
                                                    <input type="text" class="form-control" id="versione" name="versione" 
                                                           value="<?php echo $editing ? htmlspecialchars($server_data['versione']) : ''; ?>" 
                                                           maxlength="50" placeholder="es. 1.20.4">
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <label for="tipo_server" class="form-label">Tipo Server</label>
                                                    <select class="form-select" id="tipo_server" name="tipo_server">
                                                        <option value="Java & Bedrock" <?php echo ($editing && $server_data['tipo_server'] === 'Java & Bedrock') ? 'selected' : ''; ?>>Java & Bedrock</option>
                                                        <option value="Java" <?php echo ($editing && $server_data['tipo_server'] === 'Java') ? 'selected' : ''; ?>>Java</option>
                                                        <option value="Bedrock" <?php echo ($editing && $server_data['tipo_server'] === 'Bedrock') ? 'selected' : ''; ?>>Bedrock</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <label for="owner_id" class="form-label">Proprietario</label>
                                                    <select class="form-select" id="owner_id" name="owner_id">
                                                        <option value="">Nessuno</option>
                                                        <?php
                                                        $users_stmt = $pdo->query("SELECT id, minecraft_nick FROM sl_users ORDER BY minecraft_nick");
                                                        while ($user = $users_stmt->fetch()):
                                                        ?>
                                                            <option value="<?php echo $user['id']; ?>" 
                                                                    <?php echo ($editing && $server_data['owner_id'] == $user['id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($user['minecraft_nick']); ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-12">
                                                    <label for="descrizione" class="form-label">Descrizione</label>
                                                    <textarea class="form-control" id="descrizione" name="descrizione" 
                                                              rows="4" maxlength="1000"><?php echo $editing ? htmlspecialchars($server_data['descrizione']) : ''; ?></textarea>
                                                    <div class="form-text">Massimo 1000 caratteri</div>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <label for="logo_url" class="form-label">URL Logo</label>
                                                    <input type="url" class="form-control" id="logo_url" name="logo_url" 
                                                           value="<?php echo $editing ? htmlspecialchars($server_data['logo_url']) : ''; ?>" 
                                                           maxlength="500">
                                                    <div class="form-text">Dimensioni consigliate: 100x100px</div>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <label for="banner_url" class="form-label">URL Banner</label>
                                                    <input type="url" class="form-control" id="banner_url" name="banner_url" 
                                                           value="<?php echo $editing ? htmlspecialchars($server_data['banner_url']) : ''; ?>" 
                                                           maxlength="500">
                                                    <div class="form-text">Dimensioni consigliate: 468x60px</div>
                                                </div>
                                                
                                                <?php if ($editing): ?>
                                                <div class="col-md-6">
                                                    <label for="is_active" class="form-label">Stato</label>
                                                    <select class="form-select" id="is_active" name="is_active">
                                                        <option value="1" <?php echo $server_data['is_active'] ? 'selected' : ''; ?>>Attivo</option>
                                                        <option value="0" <?php echo !$server_data['is_active'] ? 'selected' : ''; ?>>Disattivato</option>
                                                    </select>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="mt-4">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-<?php echo $editing ? 'check' : 'plus'; ?>"></i>
                                                    <?php echo $editing ? 'Aggiorna Server' : 'Aggiungi Server'; ?>
                                                </button>
                                                <a href="admin.php?action=servers" class="btn btn-outline-secondary ms-2">
                                                    Annulla
                                                </a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="bi bi-eye"></i> Anteprima
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="serverPreview" class="server-preview">
                                            <div class="preview-logo">
                                                <img id="previewLogo" src="https://via.placeholder.com/100x100?text=Logo" 
                                                     alt="Logo" width="100" height="100">
                                            </div>
                                            <h5 id="previewName">Nome Server</h5>
                                            <p id="previewIP" class="text-secondary">server.example.com</p>
                                            <p id="previewDescription" class="small">Descrizione del server...</p>
                                            <div class="preview-badges">
                                                <span id="previewType" class="badge bg-primary">Java & Bedrock</span>
                                                <span id="previewVersion" class="badge bg-secondary">1.20.4</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    <?php elseif ($action === 'licenses'): ?>
                        <!-- License Management -->
                        <?php
                        // Get all licenses with server information
                        $stmt = $pdo->query("
                            SELECT sl.*, s.nome as server_name, s.ip as server_ip, 
                                   u.minecraft_nick as owner_nick
                            FROM sl_server_licenses sl
                            JOIN sl_servers s ON sl.server_id = s.id
                            LEFT JOIN sl_users u ON s.owner_id = u.id
                            ORDER BY sl.created_at DESC
                        ");
                        $licenses = $stmt->fetchAll();
                        
                        // Get servers without licenses
                        $stmt = $pdo->query("
                            SELECT s.id, s.nome, s.ip, u.minecraft_nick as owner_nick
                            FROM sl_servers s
                            LEFT JOIN sl_users u ON s.owner_id = u.id
                            LEFT JOIN sl_server_licenses sl ON s.id = sl.server_id
                            WHERE sl.server_id IS NULL
                            ORDER BY s.nome ASC
                        ");
                        $servers_without_licenses = $stmt->fetchAll();
                        ?>
                        
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="bi bi-key"></i>
                                Gestione Licenze (<?php echo count($licenses); ?>)
                            </h2>
                            <div class="section-actions">
                                <button class="btn btn-outline-secondary btn-sm" onclick="refreshLicenseList()">
                                    <i class="bi bi-arrow-clockwise"></i> Aggiorna
                                </button>
                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#generateLicenseModal">
                                    <i class="bi bi-plus-circle"></i> Genera Licenza
                                </button>
                            </div>
                        </div>
                        
                        <!-- License Statistics -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-icon bg-primary">
                                        <i class="bi bi-key"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number"><?php echo count($licenses); ?></div>
                                        <div class="stat-label">Licenze Totali</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-icon bg-success">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number"><?php echo count(array_filter($licenses, function($l) { return $l['is_active']; })); ?></div>
                                        <div class="stat-label">Licenze Attive</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-icon bg-warning">
                                        <i class="bi bi-server"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number"><?php echo count($servers_without_licenses); ?></div>
                                        <div class="stat-label">Server Senza Licenza</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-icon bg-info">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number"><?php echo array_sum(array_column($licenses, 'usage_count')); ?></div>
                                        <div class="stat-label">Utilizzi Totali</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                         
                         <!-- Servers without licenses alert -->
                         <?php if (!empty($servers_without_licenses)): ?>
                         <div class="alert alert-warning mb-4">
                             <h5><i class="bi bi-exclamation-triangle"></i> Server senza licenza (<?php echo count($servers_without_licenses); ?>)</h5>
                             <div class="row">
                                 <?php foreach ($servers_without_licenses as $server): ?>
                                 <div class="col-md-6 mb-2">
                                     <div class="d-flex justify-content-between align-items-center">
                                         <span><strong><?php echo htmlspecialchars($server['nome']); ?></strong> (<?php echo htmlspecialchars($server['ip']); ?>)</span>
                                         <button class="btn btn-sm btn-outline-primary" onclick="generateLicenseForServer(<?php echo $server['id']; ?>)">
                                             <i class="bi bi-key"></i> Genera
                                         </button>
                                     </div>
                                 </div>
                                 <?php endforeach; ?>
                             </div>
                         </div>
                         <?php endif; ?>
                         
                         <!-- License Filters -->
                         <div class="card mb-4">
                             <div class="card-body">
                                 <div class="row g-3">
                                     <div class="col-md-4">
                                         <label class="form-label">Cerca licenza</label>
                                         <input type="text" class="form-control" id="licenseSearch" placeholder="Nome server, IP, proprietario...">
                                     </div>
                                     <div class="col-md-3">
                                         <label class="form-label">Stato</label>
                                         <select class="form-select" id="licenseStatusFilter">
                                             <option value="">Tutti gli stati</option>
                                             <option value="active">Attive</option>
                                             <option value="inactive">Inattive</option>
                                         </select>
                                     </div>
                                     <div class="col-md-3">
                                         <label class="form-label">Ordinamento</label>
                                         <select class="form-select" id="licenseSortFilter">
                                             <option value="created_desc">Data creazione (più recenti)</option>
                                             <option value="created_asc">Data creazione (più vecchie)</option>
                                             <option value="usage_desc">Utilizzi (maggiori)</option>
                                             <option value="usage_asc">Utilizzi (minori)</option>
                                         </select>
                                     </div>
                                     <div class="col-md-2">
                                         <label class="form-label">&nbsp;</label>
                                         <div class="d-grid">
                                             <button class="btn btn-outline-secondary" onclick="clearLicenseFilters()">
                                                 <i class="bi bi-x-circle"></i> Reset
                                             </button>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>
                         
                         <!-- License Table -->
                         <div class="card">
                             <div class="card-header d-flex justify-content-between align-items-center">
                                 <h5 class="mb-0">Elenco Licenze</h5>
                                 <div class="d-flex gap-2">
                                     <button class="btn btn-outline-danger btn-sm" onclick="bulkRevokeLicenses()" id="bulkRevokeBtn" style="display: none;">
                                         <i class="bi bi-x-circle"></i> Revoca Selezionate
                                     </button>
                                 </div>
                             </div>
                             <div class="table-responsive">
                                 <table class="table table-hover mb-0" id="licensesTable">
                                     <thead class="table-light">
                                         <tr>
                                             <th width="40">
                                                 <input type="checkbox" class="form-check-input" id="selectAllLicenses">
                                             </th>
                                             <th>Server</th>
                                             <th>Proprietario</th>
                                             <th>Licenza</th>
                                             <th>Stato</th>
                                             <th>Utilizzi</th>
                                             <th>Ultimo Uso</th>
                                             <th>Creata</th>
                                             <th width="120">Azioni</th>
                                         </tr>
                                     </thead>
                                     <tbody>
                                         <?php foreach ($licenses as $license): ?>
                                         <tr data-license-id="<?php echo $license['id']; ?>" data-server-name="<?php echo htmlspecialchars($license['server_name']); ?>" data-owner="<?php echo htmlspecialchars($license['owner_nick'] ?? 'N/A'); ?>" data-status="<?php echo $license['is_active'] ? 'active' : 'inactive'; ?>">
                                             <td>
                                                 <input type="checkbox" class="form-check-input license-checkbox" value="<?php echo $license['id']; ?>">
                                             </td>
                                             <td>
                                                 <div>
                                                     <strong><?php echo htmlspecialchars($license['server_name']); ?></strong>
                                                     <br><small class="text-muted"><?php echo htmlspecialchars($license['server_ip']); ?></small>
                                                 </div>
                                             </td>
                                             <td><?php echo htmlspecialchars($license['owner_nick'] ?? 'N/A'); ?></td>
                                             <td>
                                                 <div class="d-flex align-items-center">
                                                     <code class="license-key" style="cursor: pointer; user-select: none;" onclick="toggleLicenseVisibility(this)" data-license="<?php echo $license['license_key']; ?>">
                                                         <?php echo str_repeat('*', 20) . substr($license['license_key'], -4); ?>
                                                     </code>
                                                     <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyLicense('<?php echo $license['license_key']; ?>')" title="Copia licenza">
                                                         <i class="bi bi-clipboard"></i>
                                                     </button>
                                                 </div>
                                             </td>
                                             <td>
                                                 <?php if ($license['is_active']): ?>
                                                     <span class="badge bg-success">Attiva</span>
                                                 <?php else: ?>
                                                     <span class="badge bg-secondary">Inattiva</span>
                                                 <?php endif; ?>
                                             </td>
                                             <td>
                                                 <span class="badge bg-info"><?php echo $license['usage_count']; ?></span>
                                             </td>
                                             <td>
                                                 <?php if ($license['last_used_at']): ?>
                                                     <small><?php echo date('d/m/Y H:i', strtotime($license['last_used_at'])); ?></small>
                                                 <?php else: ?>
                                                     <small class="text-muted">Mai usata</small>
                                                 <?php endif; ?>
                                             </td>
                                             <td>
                                                 <small><?php echo date('d/m/Y', strtotime($license['created_at'])); ?></small>
                                             </td>
                                             <td>
                                                 <div class="btn-group btn-group-sm">
                                                     <button class="btn btn-outline-primary" onclick="regenerateLicense(<?php echo $license['id']; ?>)" title="Rigenera">
                                                         <i class="bi bi-arrow-repeat"></i>
                                                     </button>
                                                     <button class="btn btn-outline-warning" onclick="toggleLicenseStatus(<?php echo $license['id']; ?>)" title="<?php echo $license['is_active'] ? 'Disattiva' : 'Attiva'; ?>">
                                                         <i class="bi bi-<?php echo $license['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                     </button>
                                                     <button class="btn btn-outline-danger" onclick="revokeLicense(<?php echo $license['id']; ?>)" title="Revoca">
                                                         <i class="bi bi-trash"></i>
                                                     </button>
                                                 </div>
                                             </td>
                                         </tr>
                                         <?php endforeach; ?>
                                     </tbody>
                                 </table>
                             </div>
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

<!-- Generate License Modal -->
<div class="modal fade" id="generateLicenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Genera Nuova Licenza</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="generateLicenseForm">
                    <div class="mb-3">
                        <label class="form-label">Seleziona Server</label>
                        <select class="form-select" id="serverSelect" required>
                            <option value="">Scegli un server...</option>
                            <?php
                            // Get all servers for the dropdown
                            $stmt = $pdo->query("
                                SELECT s.id, s.nome, s.ip, u.minecraft_nick as owner_nick
                                FROM sl_servers s
                                LEFT JOIN sl_users u ON s.owner_id = u.id
                                ORDER BY s.nome ASC
                            ");
                            $all_servers = $stmt->fetchAll();
                            foreach ($all_servers as $server):
                            ?>
                            <option value="<?php echo $server['id']; ?>">
                                <?php echo htmlspecialchars($server['nome']); ?> (<?php echo htmlspecialchars($server['ip']); ?>) - <?php echo htmlspecialchars($server['owner_nick'] ?? 'N/A'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Nota:</strong> Se il server ha già una licenza, verrà sostituita con una nuova.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-success" onclick="generateNewLicense()">Genera Licenza</button>
            </div>
        </div>
    </div>
</div>
                        




                    
                </div>
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

// Server Management Functions
function refreshServerList() {
    location.reload();
}

function editServer(serverId) {
    window.location.href = `admin.php?action=edit_server&id=${serverId}`;
}

function toggleServerStatus(serverId, newStatus) {
    if (confirm(`Sei sicuro di voler ${newStatus ? 'attivare' : 'disattivare'} questo server?`)) {
        const formData = new FormData();
        formData.append('action', 'toggle_server_status');
        formData.append('server_id', serverId);
        formData.append('status', newStatus);
        
        fetch('admin.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                refreshServerList();
            } else {
                showToast(data.error || 'Errore durante l\'operazione', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Errore di connessione', 'danger');
        });
    }
}

function deleteServer(serverId, serverName) {
    if (confirm(`Sei sicuro di voler eliminare il server "${serverName}"?\n\nQuesta azione eliminerà anche tutti i voti associati e non può essere annullata.`)) {
        const formData = new FormData();
        formData.append('action', 'delete_server');
        formData.append('server_id', serverId);
        
        fetch('admin.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                refreshServerList();
            } else {
                showToast(data.error || 'Errore durante l\'eliminazione', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Errore di connessione', 'danger');
        });
    }
}

// Filtering Functions
function initializeServerFilters() {
    const searchInput = document.getElementById('serverSearch');
    const typeFilter = document.getElementById('serverTypeFilter');
    const statusFilter = document.getElementById('serverStatusFilter');
    
    if (searchInput) {
        searchInput.addEventListener('input', filterServers);
    }
    if (typeFilter) {
        typeFilter.addEventListener('change', filterServers);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', filterServers);
    }
}

function filterServers() {
    const searchTerm = document.getElementById('serverSearch')?.value.toLowerCase() || '';
    const typeFilter = document.getElementById('serverTypeFilter')?.value || '';
    const statusFilter = document.getElementById('serverStatusFilter')?.value || '';
    
    const rows = document.querySelectorAll('#serversTable tbody tr');
    
    rows.forEach(row => {
        const serverName = row.querySelector('.server-info strong')?.textContent.toLowerCase() || '';
        const serverIP = row.querySelector('.server-ip')?.textContent.toLowerCase() || '';
        const serverType = row.dataset.serverType || '';
        const serverStatus = row.dataset.serverStatus || '';
        
        const matchesSearch = serverName.includes(searchTerm) || serverIP.includes(searchTerm);
        const matchesType = !typeFilter || serverType === typeFilter;
        const matchesStatus = !statusFilter || serverStatus === statusFilter;
        
        if (matchesSearch && matchesType && matchesStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function clearFilters() {
    document.getElementById('serverSearch').value = '';
    document.getElementById('serverTypeFilter').value = '';
    document.getElementById('serverStatusFilter').value = '';
    filterServers();
}

// Bulk Actions
function initializeBulkActions() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const serverCheckboxes = document.querySelectorAll('.server-checkbox');
    const bulkActionsDiv = document.querySelector('.bulk-actions');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            serverCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionsVisibility();
        });
    }
    
    serverCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionsVisibility);
    });
}

function updateBulkActionsVisibility() {
    const checkedBoxes = document.querySelectorAll('.server-checkbox:checked');
    const bulkActionsDiv = document.querySelector('.bulk-actions');
    const selectedCountSpan = document.getElementById('selectedCount');
    
    if (checkedBoxes.length > 0) {
        bulkActionsDiv.style.display = 'block';
        selectedCountSpan.textContent = checkedBoxes.length;
    } else {
        bulkActionsDiv.style.display = 'none';
    }
}

function getSelectedServerIds() {
    const checkedBoxes = document.querySelectorAll('.server-checkbox:checked');
    return Array.from(checkedBoxes).map(checkbox => checkbox.value);
}

function bulkActivate() {
    const serverIds = getSelectedServerIds();
    if (serverIds.length === 0) return;
    
    if (confirm(`Attivare ${serverIds.length} server selezionati?`)) {
        bulkAction('bulk_activate', serverIds);
    }
}

function bulkDeactivate() {
    const serverIds = getSelectedServerIds();
    if (serverIds.length === 0) return;
    
    if (confirm(`Disattivare ${serverIds.length} server selezionati?`)) {
        bulkAction('bulk_deactivate', serverIds);
    }
}

function bulkDelete() {
    const serverIds = getSelectedServerIds();
    if (serverIds.length === 0) return;
    
    if (confirm(`ATTENZIONE: Eliminare definitivamente ${serverIds.length} server selezionati?\n\nQuesta azione eliminerà anche tutti i voti associati e non può essere annullata.`)) {
        bulkAction('bulk_delete', serverIds);
    }
}

function bulkAction(action, serverIds) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('server_ids', JSON.stringify(serverIds));
    
    fetch('admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            refreshServerList();
        } else {
            showToast(data.error || 'Errore durante l\'operazione', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Errore di connessione', 'danger');
    });
}

// Form Preview Functions
function initializeFormPreview() {
    const form = document.getElementById('serverForm');
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('input', updatePreview);
        input.addEventListener('change', updatePreview);
    });
    
    // Initial preview update
    updatePreview();
}

function updatePreview() {
    const nome = document.getElementById('nome')?.value || 'Nome Server';
    const ip = document.getElementById('ip')?.value || 'server.example.com';
    const descrizione = document.getElementById('descrizione')?.value || 'Descrizione del server...';
    const logoUrl = document.getElementById('logo_url')?.value || 'https://via.placeholder.com/100x100?text=Logo';
    const tipoServer = document.getElementById('tipo_server')?.value || 'Java & Bedrock';
    const versione = document.getElementById('versione')?.value || '1.20.4';
    
    // Update preview elements
    document.getElementById('previewName').textContent = nome;
    document.getElementById('previewIP').textContent = ip;
    document.getElementById('previewDescription').textContent = descrizione;
    document.getElementById('previewType').textContent = tipoServer;
    document.getElementById('previewVersion').textContent = versione;
    
    // Update logo with error handling
    const logoImg = document.getElementById('previewLogo');
    logoImg.onerror = function() {
        this.src = 'https://via.placeholder.com/100x100?text=Logo';
    };
    logoImg.src = logoUrl;
    
    // Update type badge color
    const typeBadge = document.getElementById('previewType');
    typeBadge.className = 'badge bg-' + (tipoServer === 'Java' ? 'primary' : (tipoServer === 'Bedrock' ? 'success' : 'info'));
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
    initializeUserFilters();
    initializeUserBulkActions();
});

// License Management Functions
function refreshLicenseList() {
    location.reload();
}

function generateNewLicense() {
    const serverId = document.getElementById('serverSelect').value;
    if (!serverId) {
        showToast('Seleziona un server', 'warning');
        return;
    }
    
    fetch('generate_server_license.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=generate&server_id=' + serverId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Licenza generata con successo!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('generateLicenseModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Errore nella generazione della licenza', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Errore di connessione', 'error');
    });
}

function generateLicenseForServer(serverId) {
    fetch('generate_server_license.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=generate&server_id=' + serverId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Licenza generata con successo!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Errore nella generazione della licenza', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Errore di connessione', 'error');
    });
}

function regenerateLicense(licenseId) {
    if (!confirm('Sei sicuro di voler rigenerare questa licenza? La licenza attuale diventerà inutilizzabile.')) {
        return;
    }
    
    fetch('generate_server_license.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=regenerate&license_id=' + licenseId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Licenza rigenerata con successo!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Errore nella rigenerazione della licenza', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Errore di connessione', 'error');
    });
}

function toggleLicenseStatus(licenseId) {
    fetch('admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'ajax=toggle_license_status&license_id=' + licenseId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Errore nel cambio stato licenza', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Errore di connessione', 'error');
    });
}

function revokeLicense(licenseId) {
    if (!confirm('Sei sicuro di voler revocare questa licenza? Questa azione non può essere annullata.')) {
        return;
    }
    
    fetch('admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'ajax=revoke_license&license_id=' + licenseId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Licenza revocata con successo!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Errore nella revoca della licenza', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Errore di connessione', 'error');
    });
}

function toggleLicenseVisibility(element) {
    const licenseKey = element.getAttribute('data-license');
    const isHidden = element.textContent.includes('*');
    
    if (isHidden) {
        element.textContent = licenseKey;
        setTimeout(() => {
            element.textContent = '*'.repeat(20) + licenseKey.slice(-4);
        }, 3000);
    } else {
        element.textContent = '*'.repeat(20) + licenseKey.slice(-4);
    }
}

function copyLicense(licenseKey) {
    navigator.clipboard.writeText(licenseKey).then(() => {
        showToast('Licenza copiata negli appunti!', 'success');
    }).catch(() => {
        showToast('Errore nella copia della licenza', 'error');
    });
}

function initializeLicenseFilters() {
    const searchInput = document.getElementById('licenseSearch');
    const statusFilter = document.getElementById('licenseStatusFilter');
    const sortFilter = document.getElementById('licenseSortFilter');
    
    if (searchInput) {
        searchInput.addEventListener('input', applyLicenseFilters);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', applyLicenseFilters);
    }
    if (sortFilter) {
        sortFilter.addEventListener('change', applyLicenseFilters);
    }
}

function applyLicenseFilters() {
    const searchTerm = document.getElementById('licenseSearch')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('licenseStatusFilter')?.value || '';
    const sortFilter = document.getElementById('licenseSortFilter')?.value || 'created_desc';
    
    const table = document.getElementById('licensesTable');
    if (!table) return;
    
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    
    // Filter rows
    const filteredRows = rows.filter(row => {
        const serverName = row.getAttribute('data-server-name')?.toLowerCase() || '';
        const owner = row.getAttribute('data-owner')?.toLowerCase() || '';
        const status = row.getAttribute('data-status') || '';
        
        const matchesSearch = !searchTerm || serverName.includes(searchTerm) || owner.includes(searchTerm);
        const matchesStatus = !statusFilter || status === statusFilter;
        
        return matchesSearch && matchesStatus;
    });
    
    // Sort rows
    filteredRows.sort((a, b) => {
        switch (sortFilter) {
            case 'created_asc':
                return new Date(a.cells[7].textContent) - new Date(b.cells[7].textContent);
            case 'created_desc':
                return new Date(b.cells[7].textContent) - new Date(a.cells[7].textContent);
            case 'usage_asc':
                return parseInt(a.cells[5].textContent) - parseInt(b.cells[5].textContent);
            case 'usage_desc':
                return parseInt(b.cells[5].textContent) - parseInt(a.cells[5].textContent);
            default:
                return 0;
        }
    });
    
    // Hide all rows
    rows.forEach(row => row.style.display = 'none');
    
    // Show filtered rows
    filteredRows.forEach(row => row.style.display = '');
}

function clearLicenseFilters() {
    document.getElementById('licenseSearch').value = '';
    document.getElementById('licenseStatusFilter').value = '';
    document.getElementById('licenseSortFilter').value = 'created_desc';
    applyLicenseFilters();
}

function initializeLicenseBulkActions() {
    const selectAllCheckbox = document.getElementById('selectAllLicenses');
    const licenseCheckboxes = document.querySelectorAll('.license-checkbox');
    const bulkRevokeBtn = document.getElementById('bulkRevokeBtn');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            licenseCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateLicenseBulkButtons();
        });
    }
    
    licenseCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateLicenseBulkButtons);
    });
}

function updateLicenseBulkButtons() {
    const checkedBoxes = document.querySelectorAll('.license-checkbox:checked');
    const bulkRevokeBtn = document.getElementById('bulkRevokeBtn');
    
    if (bulkRevokeBtn) {
        bulkRevokeBtn.style.display = checkedBoxes.length > 0 ? 'inline-block' : 'none';
    }
}

function bulkRevokeLicenses() {
    const checkedBoxes = document.querySelectorAll('.license-checkbox:checked');
    const licenseIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    if (licenseIds.length === 0) {
        showToast('Seleziona almeno una licenza', 'warning');
        return;
    }
    
    if (!confirm(`Sei sicuro di voler revocare ${licenseIds.length} licenze? Questa azione non può essere annullata.`)) {
        return;
    }
    
    fetch('admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'ajax=bulk_revoke_licenses&license_ids=' + licenseIds.join(',')
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(`${licenseIds.length} licenze revocate con successo!`, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Errore nella revoca delle licenze', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Errore di connessione', 'error');
    });
}

// User Management Functions
function refreshUserList() {
    location.reload();
}

function viewUserDetails(userId) {
    const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
    const content = document.getElementById('userDetailsContent');
    
    content.innerHTML = '<div class="text-center"><i class="bi bi-hourglass-split"></i> Caricamento...</div>';
    
    fetch('admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'ajax=get_user_details&user_id=' + userId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            content.innerHTML = `
                <div class="row">
                    <div class="col-md-4 text-center">
                        <img src="https://mc-heads.net/avatar/${encodeURIComponent(data.user.minecraft_nick)}/128" 
                             alt="Avatar" class="img-fluid rounded mb-3" width="128" height="128">
                        <h5>${data.user.minecraft_nick}</h5>
                        <span class="badge ${data.user.is_admin ? 'bg-warning text-dark' : 'bg-secondary'}">
                            <i class="bi bi-${data.user.is_admin ? 'shield-check' : 'person'}"></i>
                            ${data.user.is_admin ? 'Amministratore' : 'Utente'}
                        </span>
                    </div>
                    <div class="col-md-8">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Server Posseduti</h6>
                                        <h4 class="text-primary">${data.user.owned_servers}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Voti Totali</h6>
                                        <h4 class="text-success">${data.user.total_votes}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Voti Oggi</h6>
                                        <h4 class="text-info">${data.user.today_votes}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Registrazione</h6>
                                        <small>${new Date(data.user.data_registrazione).toLocaleDateString('it-IT')}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        ${data.user.last_vote ? `
                        <div class="mt-3">
                            <h6>Ultimo Voto</h6>
                            <p class="text-muted">${new Date(data.user.last_vote).toLocaleString('it-IT')}</p>
                        </div>
                        ` : ''}
                        
                        ${data.servers && data.servers.length > 0 ? `
                        <div class="mt-3">
                            <h6>Server Posseduti</h6>
                            <div class="list-group">
                                ${data.servers.map(server => `
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>${server.nome}</strong>
                                            <br><small class="text-muted">${server.ip}:${server.porta}</small>
                                        </div>
                                        <span class="badge ${server.attivo ? 'bg-success' : 'bg-danger'}">
                                            ${server.attivo ? 'Attivo' : 'Inattivo'}
                                        </span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
        } else {
            content.innerHTML = '<div class="alert alert-danger">Errore nel caricamento dei dettagli utente</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        content.innerHTML = '<div class="alert alert-danger">Errore di connessione</div>';
    });
    
    modal.show();
}

function toggleUserRole(userId) {
    if (!confirm('Sei sicuro di voler cambiare il ruolo di questo utente?')) {
        return;
    }
    
    fetch('admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'ajax=toggle_user_role&user_id=' + userId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Ruolo utente aggiornato con successo!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Errore nell\'aggiornamento del ruolo', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Errore di connessione', 'error');
    });
}

function deleteUser(userId) {
    if (!confirm('Sei sicuro di voler eliminare questo utente? Questa azione non può essere annullata.')) {
        return;
    }
    
    fetch('admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'ajax=delete_user&user_id=' + userId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Utente eliminato con successo!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Errore nell\'eliminazione dell\'utente', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Errore di connessione', 'error');
    });
}

function initializeUserFilters() {
    const searchInput = document.getElementById('userSearch');
    const roleFilter = document.getElementById('userRoleFilter');
    const sortFilter = document.getElementById('userSortFilter');
    
    if (searchInput) {
        searchInput.addEventListener('input', applyUserFilters);
    }
    if (roleFilter) {
        roleFilter.addEventListener('change', applyUserFilters);
    }
    if (sortFilter) {
        sortFilter.addEventListener('change', applyUserFilters);
    }
}

function applyUserFilters() {
    const searchTerm = document.getElementById('userSearch')?.value.toLowerCase() || '';
    const roleFilter = document.getElementById('userRoleFilter')?.value || '';
    const sortFilter = document.getElementById('userSortFilter')?.value || 'registration_desc';
    
    const table = document.getElementById('usersTable');
    if (!table) return;
    
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    
    // Filter rows
    const filteredRows = rows.filter(row => {
        const username = row.querySelector('.user-info strong')?.textContent.toLowerCase() || '';
        const role = row.getAttribute('data-user-role') || '';
        
        const matchesSearch = !searchTerm || username.includes(searchTerm);
        const matchesRole = !roleFilter || role === roleFilter;
        
        return matchesSearch && matchesRole;
    });
    
    // Sort rows
    filteredRows.sort((a, b) => {
        switch (sortFilter) {
            case 'registration_asc':
                return new Date(a.cells[6].textContent) - new Date(b.cells[6].textContent);
            case 'registration_desc':
                return new Date(b.cells[6].textContent) - new Date(a.cells[6].textContent);
            case 'votes_asc':
                return parseInt(a.cells[4].querySelector('.badge').textContent) - parseInt(b.cells[4].querySelector('.badge').textContent);
            case 'votes_desc':
                return parseInt(b.cells[4].querySelector('.badge').textContent) - parseInt(a.cells[4].querySelector('.badge').textContent);
            case 'servers_asc':
                return parseInt(a.cells[3].querySelector('.badge').textContent) - parseInt(b.cells[3].querySelector('.badge').textContent);
            case 'servers_desc':
                return parseInt(b.cells[3].querySelector('.badge').textContent) - parseInt(a.cells[3].querySelector('.badge').textContent);
            default:
                return 0;
        }
    });
    
    // Hide all rows
    rows.forEach(row => row.style.display = 'none');
    
    // Show filtered rows
    filteredRows.forEach(row => row.style.display = '');
}

function clearUserFilters() {
    document.getElementById('userSearch').value = '';
    document.getElementById('userRoleFilter').value = '';
    document.getElementById('userSortFilter').value = 'registration_desc';
    applyUserFilters();
}

function initializeUserBulkActions() {
    const selectAllCheckbox = document.getElementById('selectAllUsers');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    const bulkActionsDiv = document.getElementById('userBulkActions');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateUserBulkButtons();
        });
    }
    
    userCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateUserBulkButtons);
    });
}

function updateUserBulkButtons() {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    const bulkActionsDiv = document.getElementById('userBulkActions');
    
    if (bulkActionsDiv) {
        bulkActionsDiv.style.display = checkedBoxes.length > 0 ? 'block' : 'none';
    }
}

function bulkDeleteUsers() {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    const userIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    if (userIds.length === 0) {
        showToast('Seleziona almeno un utente', 'warning');
        return;
    }
    
    if (!confirm(`Sei sicuro di voler eliminare ${userIds.length} utenti? Questa azione non può essere annullata.`)) {
        return;
    }
    
    fetch('admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'ajax=bulk_delete_users&user_ids=' + userIds.join(',')
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(`${userIds.length} utenti eliminati con successo!`, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Errore nell\'eliminazione degli utenti', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Errore di connessione', 'error');
    });
}

function bulkToggleAdminUsers() {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    const userIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    if (userIds.length === 0) {
        showToast('Seleziona almeno un utente', 'warning');
        return;
    }
    
    if (!confirm(`Sei sicuro di voler cambiare il ruolo di ${userIds.length} utenti?`)) {
        return;
    }
    
    fetch('admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'ajax=bulk_toggle_admin&user_ids=' + userIds.join(',')
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(`Ruolo di ${userIds.length} utenti aggiornato con successo!`, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Errore nell\'aggiornamento dei ruoli', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Errore di connessione', 'error');
    });
}
</script>

<?php include 'footer.php'; ?>