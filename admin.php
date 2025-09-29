<?php
require_once 'config.php';

// Verifica autenticazione admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('/login');
}

// Connessione mysqli per compatibilità
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Errore di connessione: " . $conn->connect_error);
}

$action = $_GET['action'] ?? 'dashboard';
$message = '';
$error = '';

// ==================== AJAX HANDLERS ====================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            // Dashboard Stats
            case 'stats':
                $stats = [
                    'total_users' => $pdo->query("SELECT COUNT(*) FROM sl_users")->fetchColumn(),
                    'admin_users' => $pdo->query("SELECT COUNT(*) FROM sl_users WHERE is_admin = 1")->fetchColumn(),
                    'total_servers' => $pdo->query("SELECT COUNT(*) FROM sl_servers")->fetchColumn(),
                    'active_servers' => $pdo->query("SELECT COUNT(*) FROM sl_servers WHERE is_active = 1")->fetchColumn(),
                    'pending_servers' => $pdo->query("SELECT COUNT(*) FROM sl_servers WHERE is_active = 2")->fetchColumn(),
                    'disabled_servers' => $pdo->query("SELECT COUNT(*) FROM sl_servers WHERE is_active = 0")->fetchColumn(),
                    'sponsored_servers' => $pdo->query("SELECT COUNT(*) FROM sl_sponsored_servers WHERE is_active = 1")->fetchColumn(),
                    'total_votes' => $pdo->query("SELECT COUNT(*) FROM sl_votes")->fetchColumn(),
                    'today_votes' => $pdo->query("SELECT COUNT(*) FROM sl_votes WHERE DATE(data_voto) = CURDATE()")->fetchColumn(),
                    'total_licenses' => $pdo->query("SELECT COUNT(*) FROM sl_server_licenses")->fetchColumn(),
                    'active_licenses' => $pdo->query("SELECT COUNT(*) FROM sl_server_licenses WHERE is_active = 1")->fetchColumn(),
                    'total_rewards' => $pdo->query("SELECT COUNT(*) FROM sl_reward_logs")->fetchColumn(),
                    'successful_rewards' => $pdo->query("SELECT COUNT(*) FROM sl_reward_logs WHERE reward_status = 'success'")->fetchColumn(),
                    'failed_rewards' => $pdo->query("SELECT COUNT(*) FROM sl_reward_logs WHERE reward_status = 'error'")->fetchColumn(),
                    'pending_vote_codes' => $pdo->query("SELECT COUNT(*) FROM sl_vote_codes WHERE status = 'pending'")->fetchColumn()
                ];
                echo json_encode(['success' => true, 'data' => $stats]);
                break;

            // Users Management
            case 'toggle_admin':
                $user_id = (int)$_POST['user_id'];
                $current_status = (int)$_POST['current_status'];
                $new_status = $current_status ? 0 : 1;
                
                // Verifica che non sia l'ultimo admin
                if ($current_status == 1) {
                    $admin_count = $pdo->query("SELECT COUNT(*) FROM sl_users WHERE is_admin = 1")->fetchColumn();
                    if ($admin_count <= 1) {
                        echo json_encode(['success' => false, 'message' => 'Non puoi rimuovere l\'ultimo amministratore']);
                        exit;
                    }
                }
                
                $stmt = $pdo->prepare("UPDATE sl_users SET is_admin = ? WHERE id = ?");
                $stmt->execute([$new_status, $user_id]);
                
                echo json_encode(['success' => true, 'message' => 'Stato admin aggiornato']);
                break;

            case 'delete_user':
                $user_id = (int)$_POST['user_id'];
                
                // Verifica che non sia l'ultimo admin
                $user = $pdo->prepare("SELECT is_admin FROM sl_users WHERE id = ?");
                $user->execute([$user_id]);
                $user_data = $user->fetch();
                
                if ($user_data['is_admin'] == 1) {
                    $admin_count = $pdo->query("SELECT COUNT(*) FROM sl_users WHERE is_admin = 1")->fetchColumn();
                    if ($admin_count <= 1) {
                        echo json_encode(['success' => false, 'message' => 'Non puoi eliminare l\'ultimo amministratore']);
                        exit;
                    }
                }
                
                // Elimina utente e dati correlati
                $pdo->beginTransaction();
                $pdo->prepare("DELETE FROM sl_votes WHERE user_id = ?")->execute([$user_id]);
                $pdo->prepare("DELETE FROM sl_users WHERE id = ?")->execute([$user_id]);
                $pdo->commit();
                
                echo json_encode(['success' => true, 'message' => 'Utente eliminato']);
                break;

            case 'edit_user':
                $user_id = (int)$_POST['user_id'];
                $minecraft_nick = trim($_POST['minecraft_nick']);
                $password = trim($_POST['password']);
                
                // Validazione
                if (empty($minecraft_nick)) {
                    echo json_encode(['success' => false, 'message' => 'Il nome Minecraft è obbligatorio']);
                    exit;
                }
                
                // Verifica che il nome Minecraft non sia già in uso da un altro utente
                $stmt = $pdo->prepare("SELECT id FROM sl_users WHERE minecraft_nick = ? AND id != ?");
                $stmt->execute([$minecraft_nick, $user_id]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Nome Minecraft già in uso']);
                    exit;
                }
                
                // Aggiorna nome Minecraft
                $stmt = $pdo->prepare("UPDATE sl_users SET minecraft_nick = ? WHERE id = ?");
                $stmt->execute([$minecraft_nick, $user_id]);
                
                // Aggiorna password se fornita
                if (!empty($password)) {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE sl_users SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$password_hash, $user_id]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Utente aggiornato con successo']);
                break;

            // Server Management
            case 'update_server_status':
                $server_id = (int)$_POST['server_id'];
                $status = (int)$_POST['status'];
                
                $stmt = $pdo->prepare("UPDATE sl_servers SET is_active = ? WHERE id = ?");
                $stmt->execute([$status, $server_id]);
                
                echo json_encode(['success' => true, 'message' => 'Stato server aggiornato']);
                break;

            case 'toggle_sponsorship':
                $server_id = (int)$_POST['server_id'];
                
                // Verifica se esiste già una sponsorizzazione
                $stmt = $pdo->prepare("SELECT id, is_active FROM sl_sponsored_servers WHERE server_id = ?");
                $stmt->execute([$server_id]);
                $sponsorship = $stmt->fetch();
                
                if ($sponsorship) {
                    $new_status = $sponsorship['is_active'] ? 0 : 1;
                    $stmt = $pdo->prepare("UPDATE sl_sponsored_servers SET is_active = ? WHERE server_id = ?");
                    $stmt->execute([$new_status, $server_id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO sl_sponsored_servers (server_id, is_active, created_at) VALUES (?, 1, NOW())");
                    $stmt->execute([$server_id]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Sponsorizzazione aggiornata']);
                break;

            case 'delete_server':
                $server_id = (int)$_POST['server_id'];
                
                $pdo->beginTransaction();
                $pdo->prepare("DELETE FROM sl_votes WHERE server_id = ?")->execute([$server_id]);
                $pdo->prepare("DELETE FROM sl_sponsored_servers WHERE server_id = ?")->execute([$server_id]);
                $pdo->prepare("DELETE FROM sl_reward_logs WHERE server_id = ?")->execute([$server_id]);
                $pdo->prepare("DELETE FROM sl_servers WHERE id = ?")->execute([$server_id]);
                $pdo->commit();
                
                echo json_encode(['success' => true, 'message' => 'Server eliminato']);
                break;

            // Vote Management
            case 'update_vote_code_status':
                $code_id = (int)$_POST['code_id'];
                $status = $_POST['status'];
                
                $stmt = $pdo->prepare("UPDATE sl_vote_codes SET status = ? WHERE id = ?");
                $stmt->execute([$status, $code_id]);
                
                echo json_encode(['success' => true, 'message' => 'Stato codice aggiornato']);
                break;

            case 'delete_vote':
                $vote_id = (int)$_POST['vote_id'];
                
                $pdo->beginTransaction();
                $pdo->prepare("DELETE FROM sl_vote_codes WHERE vote_id = ?")->execute([$vote_id]);
                $pdo->prepare("DELETE FROM sl_reward_logs WHERE vote_id = ?")->execute([$vote_id]);
                $pdo->prepare("DELETE FROM sl_votes WHERE id = ?")->execute([$vote_id]);
                $pdo->commit();
                
                echo json_encode(['success' => true, 'message' => 'Voto eliminato']);
                break;

            // Reward Management
            case 'retry_reward':
                $log_id = (int)$_POST['log_id'];
                
                $stmt = $pdo->prepare("UPDATE sl_reward_logs SET reward_status = 'pending', retry_count = retry_count + 1 WHERE id = ?");
                $stmt->execute([$log_id]);
                
                echo json_encode(['success' => true, 'message' => 'Reward in coda per retry']);
                break;

            case 'delete_reward_log':
                $log_id = (int)$_POST['log_id'];
                
                $stmt = $pdo->prepare("DELETE FROM sl_reward_logs WHERE id = ?");
                $stmt->execute([$log_id]);
                
                echo json_encode(['success' => true, 'message' => 'Log eliminato']);
                break;

            // License Management
            case 'toggle_license':
                $license_id = (int)$_POST['license_id'];
                $current_status = (int)$_POST['current_status'];
                $new_status = $current_status ? 0 : 1;
                
                $stmt = $pdo->prepare("UPDATE sl_server_licenses SET is_active = ? WHERE id = ?");
                $stmt->execute([$new_status, $license_id]);
                
                echo json_encode(['success' => true, 'message' => 'Stato licenza aggiornato']);
                break;

            case 'delete_license':
                $license_id = (int)$_POST['license_id'];
                
                // Eliminazione forzata - rimuovi prima l'associazione dal server se esiste
                $stmt = $pdo->prepare("UPDATE sl_servers SET license_key = NULL WHERE id IN (SELECT server_id FROM sl_server_licenses WHERE id = ?)");
                $stmt->execute([$license_id]);
                
                // Ora elimina la licenza
                $stmt = $pdo->prepare("DELETE FROM sl_server_licenses WHERE id = ?");
                $stmt->execute([$license_id]);
                
                echo json_encode(['success' => true, 'message' => 'Licenza eliminata']);
                break;

            case 'generate_license':
                $server_id = isset($_POST['server_id']) ? (int)$_POST['server_id'] : null;
                
                if (!$server_id) {
                    echo json_encode(['success' => false, 'message' => 'Server ID richiesto']);
                    exit;
                }
                
                // Verifica che il server esista
                $stmt = $pdo->prepare("SELECT id, nome FROM sl_servers WHERE id = ? AND is_active = 1");
                $stmt->execute([$server_id]);
                $server = $stmt->fetch();
                
                if (!$server) {
                    echo json_encode(['success' => false, 'message' => 'Server non trovato o non attivo']);
                    exit;
                }
                
                // Genera una licenza univoca di 24 caratteri
                $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                $max_attempts = 10;
                $license_key = '';
                
                for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
                    $license_key = '';
                    for ($i = 0; $i < 24; $i++) {
                        $license_key .= $characters[random_int(0, strlen($characters) - 1)];
                    }
                    
                    // Verifica che non esista già
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sl_server_licenses WHERE license_key = ?");
                    $stmt->execute([$license_key]);
                    
                    if ($stmt->fetchColumn() == 0) {
                        break; // Licenza univoca trovata
                    }
                    
                    if ($attempt === $max_attempts - 1) {
                        echo json_encode(['success' => false, 'message' => 'Impossibile generare una licenza univoca']);
                        exit;
                    }
                }
                
                // Inserisci o aggiorna la licenza
                $stmt = $pdo->prepare("
                    INSERT INTO sl_server_licenses (server_id, license_key, is_active) 
                    VALUES (?, ?, 1) 
                    ON DUPLICATE KEY UPDATE 
                        license_key = VALUES(license_key),
                        is_active = 1,
                        created_at = CURRENT_TIMESTAMP
                ");
                $stmt->execute([$server_id, $license_key]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Licenza generata con successo per ' . htmlspecialchars($server['nome']),
                    'license_key' => $license_key,
                    'server_name' => $server['nome']
                ]);
                break;

            case 'generate_license_with_input':
                $server_input = isset($_POST['server_input']) ? trim($_POST['server_input']) : '';
                
                if (!$server_input) {
                    echo json_encode(['success' => false, 'message' => 'Nome o ID server richiesto']);
                    exit;
                }
                
                // Cerca il server per ID numerico o nome esatto
                if (is_numeric($server_input)) {
                    // Ricerca per ID
                    $stmt = $pdo->prepare("SELECT id, nome FROM sl_servers WHERE id = ? AND is_active = 1");
                    $stmt->execute([(int)$server_input]);
                } else {
                    // Ricerca per nome esatto
                    $stmt = $pdo->prepare("SELECT id, nome FROM sl_servers WHERE nome = ? AND is_active = 1");
                    $stmt->execute([$server_input]);
                }
                
                $server = $stmt->fetch();
                
                if (!$server) {
                    echo json_encode(['success' => false, 'message' => 'Server non trovato. Verifica che il nome sia esatto o che l\'ID sia corretto e che il server sia attivo.']);
                    exit;
                }
                
                // Genera una licenza univoca di 24 caratteri
                $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                $max_attempts = 10;
                $license_key = '';
                
                for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
                    $license_key = '';
                    for ($i = 0; $i < 24; $i++) {
                        $license_key .= $characters[random_int(0, strlen($characters) - 1)];
                    }
                    
                    // Verifica che non esista già
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sl_server_licenses WHERE license_key = ?");
                    $stmt->execute([$license_key]);
                    
                    if ($stmt->fetchColumn() == 0) {
                        break; // Licenza univoca trovata
                    }
                    
                    if ($attempt === $max_attempts - 1) {
                        echo json_encode(['success' => false, 'message' => 'Impossibile generare una licenza univoca']);
                        exit;
                    }
                }
                
                // Inserisci o aggiorna la licenza
                $stmt = $pdo->prepare("
                    INSERT INTO sl_server_licenses (server_id, license_key, is_active) 
                    VALUES (?, ?, 1) 
                    ON DUPLICATE KEY UPDATE 
                        license_key = VALUES(license_key),
                        is_active = 1,
                        created_at = CURRENT_TIMESTAMP
                ");
                $stmt->execute([$server['id'], $license_key]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Licenza generata con successo per "' . htmlspecialchars($server['nome']) . '" (ID: ' . $server['id'] . ')',
                    'license_key' => $license_key,
                    'server_name' => $server['nome'],
                    'server_id' => $server['id']
                ]);
                break;

            case 'search_servers':
                $search = isset($_POST['search']) ? trim($_POST['search']) : '';
                
                if (strlen($search) < 1) {
                    echo json_encode(['success' => true, 'servers' => []]);
                    exit;
                }
                
                // Cerca server per nome o ID
                $stmt = $pdo->prepare("
                    SELECT id, nome 
                    FROM sl_servers 
                    WHERE is_active = 1 
                    AND (nome LIKE ? OR id LIKE ?) 
                    ORDER BY nome ASC 
                    LIMIT 10
                ");
                $searchTerm = '%' . $search . '%';
                $stmt->execute([$searchTerm, $searchTerm]);
                $servers = $stmt->fetchAll();
                
                echo json_encode(['success' => true, 'servers' => $servers]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Azione non riconosciuta']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
    }
    exit;
}

$page_title = 'Pannello Admin';
include 'header.php';
?>

<style>
.admin-container {
    background: var(--secondary-bg);
    min-height: 100vh;
    padding: 2rem 0 2rem 0;
    margin-bottom: -4rem;
}

.admin-sidebar {
    background: var(--card-bg);
    border-radius: 15px;
    padding: 1.5rem;
    height: fit-content;
    position: sticky;
    top: 2rem;
    border: 1px solid var(--border-color);
}

.admin-nav-item {
    display: block;
    color: var(--text-secondary);
    text-decoration: none;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.admin-nav-item:hover {
    background: var(--hover-bg);
    color: var(--text-primary);
    transform: translateX(5px);
}

.admin-nav-item.active {
    background: var(--gradient-primary);
    color: white;
    border-color: var(--accent-purple);
}

.admin-content {
    background: var(--card-bg);
    border-radius: 15px;
    padding: 2rem;
    border: 1px solid var(--border-color);
}

.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.data-table {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 15px;
    overflow: hidden;
}

.table-dark {
    --bs-table-bg: var(--card-bg);
    --bs-table-border-color: var(--border-color);
}

.btn-admin {
    border-radius: 10px;
    padding: 0.5rem 1rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-admin:hover {
    transform: translateY(-2px);
}

.alert-admin {
    border-radius: 15px;
    border: none;
    padding: 1rem 1.5rem;
}

.form-control, .form-select {
    background: var(--secondary-bg);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    border-radius: 10px;
}

.form-control:focus, .form-select:focus {
    background: var(--secondary-bg);
    border-color: var(--accent-purple);
    color: var(--text-primary);
    box-shadow: 0 0 0 0.2rem rgba(124, 58, 237, 0.25);
}

.pagination .page-link {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
}

.pagination .page-link:hover {
    background: var(--hover-bg);
    color: var(--text-primary);
}

.pagination .page-item.active .page-link {
    background: var(--accent-purple);
    border-color: var(--accent-purple);
}

/* Alert persistenti - non scompaiono mai */
.alert-persistent {
    animation: none !important;
    transition: none !important;
}

.alert-persistent .btn-close {
    display: none !important;
}

/* Assicura che gli alert warning rimangano sempre visibili */
.alert-warning.alert-persistent {
    opacity: 1 !important;
    visibility: visible !important;
    display: block !important;
}
</style>

<div class="admin-container">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="admin-sidebar">
                    <h5 class="mb-3"><i class="bi bi-gear"></i> Admin Panel</h5>
                    <a href="?action=dashboard" class="admin-nav-item <?= $action === 'dashboard' ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="?action=users" class="admin-nav-item <?= $action === 'users' ? 'active' : '' ?>">
                        <i class="bi bi-people"></i> Gestione Utenti
                    </a>
                    <a href="?action=servers" class="admin-nav-item <?= $action === 'servers' ? 'active' : '' ?>">
                        <i class="bi bi-server"></i> Gestione Server
                    </a>
                    <a href="?action=votes" class="admin-nav-item <?= $action === 'votes' ? 'active' : '' ?>">
                        <i class="bi bi-hand-thumbs-up"></i> Gestione Voti
                    </a>
                    <a href="?action=rewards" class="admin-nav-item <?= $action === 'rewards' ? 'active' : '' ?>">
                        <i class="bi bi-gift"></i> Gestione Reward
                    </a>
                    <a href="?action=licenses" class="admin-nav-item <?= $action === 'licenses' ? 'active' : '' ?>">
                        <i class="bi bi-key"></i> Gestione Licenze
                    </a>
                    <hr>
            <a href="/" class="admin-nav-item">
                <i class="bi bi-house"></i> Torna al Sito
            </a>
                </div>
            </div>

            <!-- Content -->
            <div class="col-md-9 col-lg-10">
                <div class="admin-content">
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-admin"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-admin"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php
                    switch ($action) {
                        case 'dashboard':
                            include_dashboard();
                            break;
                        case 'users':
                            include_users();
                            break;
                        case 'servers':
                            include_servers();
                            break;
                        case 'votes':
                            include_votes();
                            break;
                        case 'rewards':
                            include_rewards();
                            break;
                        case 'licenses':
                            include_licenses();
                            break;
                        default:
                            include_dashboard();
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Funzioni AJAX globali
function makeAjaxRequest(action, data, callback) {
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', action);
    
    for (const key in data) {
        formData.append(key, data[key]);
    }
    
    fetch('admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(callback)
    .catch(error => {
        console.error('Error:', error);
        showAlert('Errore di connessione', 'danger');
    });
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-admin alert-dismissible fade show mb-3`;
    alertDiv.style.cssText = `
        border-radius: 8px;
        border: 1px solid;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        font-weight: 500;
        position: relative;
        z-index: 1050;
    `;
    
    // Colori specifici per ogni tipo
    if (type === 'warning') {
        alertDiv.style.borderColor = '#ffc107';
        alertDiv.style.backgroundColor = '#fff3cd';
        alertDiv.style.color = '#856404';
    } else if (type === 'danger') {
        alertDiv.style.borderColor = '#dc3545';
        alertDiv.style.backgroundColor = '#f8d7da';
        alertDiv.style.color = '#721c24';
    } else if (type === 'success') {
        alertDiv.style.borderColor = '#28a745';
        alertDiv.style.backgroundColor = '#d4edda';
        alertDiv.style.color = '#155724';
    } else {
        alertDiv.style.borderColor = '#17a2b8';
        alertDiv.style.backgroundColor = '#d1ecf1';
        alertDiv.style.color = '#0c5460';
    }
    
    alertDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close ms-3" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    const container = document.querySelector('.admin-content');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Gli alert warning rimangono fissi, gli altri scompaiono dopo 5 secondi
    if (type !== 'warning') {
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Auto-refresh stats ogni 30 secondi
if (window.location.search.includes('action=dashboard') || !window.location.search.includes('action=')) {
    setInterval(() => {
        makeAjaxRequest('stats', {}, (response) => {
            if (response.success) {
                updateDashboardStats(response.data);
            }
        });
    }, 30000);
}

function updateDashboardStats(stats) {
    for (const [key, value] of Object.entries(stats)) {
        const element = document.getElementById(key);
        if (element) {
            element.textContent = value;
        }
    }
}
</script>

<?php
// ==================== SEZIONI INCLUSE ====================

function include_dashboard() {
    global $pdo;
    
    // Statistiche generali
    $stats = [
        'total_users' => $pdo->query("SELECT COUNT(*) FROM sl_users")->fetchColumn(),
        'admin_users' => $pdo->query("SELECT COUNT(*) FROM sl_users WHERE is_admin = 1")->fetchColumn(),
        'total_servers' => $pdo->query("SELECT COUNT(*) FROM sl_servers")->fetchColumn(),
        'active_servers' => $pdo->query("SELECT COUNT(*) FROM sl_servers WHERE is_active = 1")->fetchColumn(),
        'pending_servers' => $pdo->query("SELECT COUNT(*) FROM sl_servers WHERE is_active = 2")->fetchColumn(),
        'disabled_servers' => $pdo->query("SELECT COUNT(*) FROM sl_servers WHERE is_active = 0")->fetchColumn(),
        'sponsored_servers' => $pdo->query("SELECT COUNT(*) FROM sl_sponsored_servers WHERE is_active = 1")->fetchColumn(),
        'total_votes' => $pdo->query("SELECT COUNT(*) FROM sl_votes")->fetchColumn(),
        'today_votes' => $pdo->query("SELECT COUNT(*) FROM sl_votes WHERE DATE(data_voto) = CURDATE()")->fetchColumn(),
        'total_licenses' => $pdo->query("SELECT COUNT(*) FROM sl_server_licenses")->fetchColumn(),
        'active_licenses' => $pdo->query("SELECT COUNT(*) FROM sl_server_licenses WHERE is_active = 1")->fetchColumn(),
        'total_rewards' => $pdo->query("SELECT COUNT(*) FROM sl_reward_logs")->fetchColumn(),
        'successful_rewards' => $pdo->query("SELECT COUNT(*) FROM sl_reward_logs WHERE reward_status = 'success'")->fetchColumn(),
        'failed_rewards' => $pdo->query("SELECT COUNT(*) FROM sl_reward_logs WHERE reward_status = 'error'")->fetchColumn(),
        'pending_vote_codes' => $pdo->query("SELECT COUNT(*) FROM sl_vote_codes WHERE status = 'pending'")->fetchColumn()
    ];
    
    // Utenti recenti
    $recent_users = $pdo->query("SELECT minecraft_nick, data_registrazione FROM sl_users ORDER BY data_registrazione DESC LIMIT 5")->fetchAll();
    
    // Server più votati
    $top_servers = $pdo->query("
        SELECT s.nome, s.ip, COUNT(v.id) as voti 
        FROM sl_servers s 
        LEFT JOIN sl_votes v ON s.id = v.server_id 
        WHERE s.is_active = 1 
        GROUP BY s.id 
        ORDER BY voti DESC 
        LIMIT 5
    ")->fetchAll();
    
    // Attività recente
    $recent_activity = $pdo->query("
        SELECT 'vote' as type, u.minecraft_nick, s.nome as server_name, v.data_voto as date 
        FROM sl_votes v 
        JOIN sl_users u ON v.user_id = u.id 
        JOIN sl_servers s ON v.server_id = s.id 
        ORDER BY v.data_voto DESC 
        LIMIT 10
    ")->fetchAll();
    ?>
    
    <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
    <p class="text-secondary">Panoramica generale del sistema</p>
    
    <!-- Statistiche principali -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number" id="total_users"><?= $stats['total_users'] ?></div>
                <div class="text-secondary">Utenti Totali</div>
                <small class="text-success"><?= $stats['admin_users'] ?> admin</small>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number" id="total_servers"><?= $stats['total_servers'] ?></div>
                <div class="text-secondary">Server Totali</div>
                <small class="text-success"><?= $stats['active_servers'] ?> attivi</small>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number" id="total_votes"><?= $stats['total_votes'] ?></div>
                <div class="text-secondary">Voti Totali</div>
                <small class="text-info"><?= $stats['today_votes'] ?> oggi</small>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number" id="total_licenses"><?= $stats['total_licenses'] ?></div>
                <div class="text-secondary">Licenze</div>
                <small class="text-success"><?= $stats['active_licenses'] ?> attive</small>
            </div>
        </div>
    </div>
    
    <!-- Statistiche dettagliate -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <h6>Server per Stato</h6>
                <div class="text-success">Attivi: <?= $stats['active_servers'] ?></div>
                <div class="text-warning">In Attesa: <?= $stats['pending_servers'] ?></div>
                <div class="text-danger">Disabilitati: <?= $stats['disabled_servers'] ?></div>
                <div class="text-info">Sponsorizzati: <?= $stats['sponsored_servers'] ?></div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <h6>Reward Status</h6>
                <div class="text-success">Successo: <?= $stats['successful_rewards'] ?></div>
                <div class="text-danger">Errori: <?= $stats['failed_rewards'] ?></div>
                <div class="text-warning">Codici Pending: <?= $stats['pending_vote_codes'] ?></div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <h6>Sistema</h6>
                <div class="text-info">Uptime: Online</div>
                <div class="text-success">Database: OK</div>
                <div class="text-primary">Ultimo aggiornamento: <?= date('H:i:s') ?></div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Utenti recenti -->
        <div class="col-md-6 mb-4">
            <div class="data-table">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="mb-0"><i class="bi bi-people"></i> Utenti Recenti</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Minecraft Nick</th>
                                <th>Registrazione</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['minecraft_nick']) ?></td>
                                <td><?= date('d/m/Y', strtotime($user['data_registrazione'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Server più votati -->
        <div class="col-md-6 mb-4">
            <div class="data-table">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="mb-0"><i class="bi bi-trophy"></i> Top Server</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>IP</th>
                                <th>Voti</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_servers as $server): ?>
                            <tr>
                                <td><?= htmlspecialchars($server['nome']) ?></td>
                                <td><?= htmlspecialchars($server['ip']) ?></td>
                                <td><span class="badge bg-primary"><?= $server['voti'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php
}

function include_users() {
    global $pdo;
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    $role_filter = $_GET['role_filter'] ?? '';
    
    // Costruisci query con filtri
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "minecraft_nick LIKE ?";
        $params[] = "%$search%";
    }
    
    if ($role_filter !== '') {
        $where_conditions[] = "is_admin = ?";
        $params[] = (int)$role_filter;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Query utenti
    $users_query = "
        SELECT u.*, 
               COUNT(DISTINCT v.id) as total_votes,
               MAX(v.data_voto) as last_vote,
               COUNT(DISTINCT s.id) as owned_servers,
               GROUP_CONCAT(DISTINCT s.nome SEPARATOR ', ') as server_names
        FROM sl_users u 
        LEFT JOIN sl_votes v ON u.id = v.user_id 
        LEFT JOIN sl_servers s ON u.id = s.owner_id
        $where_clause
        GROUP BY u.id 
        ORDER BY u.data_registrazione DESC 
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($users_query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Conta totale per paginazione
    $count_query = "SELECT COUNT(*) FROM sl_users u $where_clause";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_users = $stmt->fetchColumn();
    $total_pages = ceil($total_users / $limit);
    ?>
    
    <h2><i class="bi bi-people"></i> Gestione Utenti</h2>
    <p class="text-secondary">Gestisci utenti, admin e statistiche</p>
    
    <!-- Filtri -->
    <div class="row mb-4">
        <div class="col-md-6">
            <form method="GET" class="d-flex">
                <input type="hidden" name="action" value="users">
                <input type="text" name="search" class="form-control me-2" placeholder="Cerca minecraft nick..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary btn-admin">Cerca</button>
            </form>
        </div>
        <div class="col-md-3">
            <form method="GET">
                <input type="hidden" name="action" value="users">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <select name="role_filter" class="form-select" onchange="this.form.submit()">
                    <option value="">Tutti i ruoli</option>
                    <option value="1" <?= $role_filter === '1' ? 'selected' : '' ?>>Solo Admin</option>
                    <option value="0" <?= $role_filter === '0' ? 'selected' : '' ?>>Solo Utenti</option>
                </select>
            </form>
        </div>
        <div class="col-md-3 text-end">
            <span class="text-secondary">Totale: <?= $total_users ?> utenti</span>
        </div>
    </div>
    
    <!-- Tabella utenti -->
    <div class="data-table">
        <div class="table-responsive">
            <table class="table table-dark table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Minecraft Nick</th>
                        <th>Ruolo</th>
                        <th>Registrazione</th>
                        <th>Ultimo Voto</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($user['minecraft_nick']) ?></strong>
                            <?php if ($user['is_admin']): ?>
                                <span class="badge bg-warning ms-1">Admin</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['is_admin']): ?>
                                <span class="badge bg-warning">Amministratore</span>
                            <?php endif; ?>
                            <?php if ($user['owned_servers'] > 0): ?>
                                <span class="badge bg-success">Owner</span>
                                <br><small class="text-info"><?= htmlspecialchars($user['server_names']) ?></small>
                            <?php endif; ?>
                            <?php if (!$user['is_admin'] && $user['owned_servers'] == 0): ?>
                                <span class="badge bg-secondary">Utente</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($user['data_registrazione'])) ?></td>
                        <td>
                            <?php if ($user['last_vote']): ?>
                                <?= date('d/m/Y H:i', strtotime($user['last_vote'])) ?>
                            <?php else: ?>
                                <span class="text-secondary">Mai</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary btn-admin me-1" 
                                    onclick="editUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['minecraft_nick']) ?>')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning btn-admin me-1" 
                                    onclick="toggleAdmin(<?= $user['id'] ?>, <?= $user['is_admin'] ?>)">
                                <i class="bi bi-shield"></i>
                                <?= $user['is_admin'] ? 'Rimuovi Admin' : 'Rendi Admin' ?>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-admin" 
                                    onclick="deleteUser(<?= $user['id'] ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Paginazione -->
    <?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?action=users&page=<?= $i ?>&search=<?= urlencode($search) ?>&role_filter=<?= urlencode($role_filter) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
    
    <script>
    function toggleAdmin(userId, currentStatus) {
        const action = currentStatus ? 'rimuovere i privilegi admin da' : 'rendere admin';
        confirmAction(`Sei sicuro di voler ${action} questo utente?`, () => {
            makeAjaxRequest('toggle_admin', {
                user_id: userId,
                current_status: currentStatus
            }, (response) => {
                if (response.success) {
                    showAlert(response.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(response.message, 'danger');
                }
            });
        });
    }
    
    function deleteUser(userId) {
        confirmAction('Sei sicuro di voler eliminare questo utente? Questa azione è irreversibile!', () => {
            makeAjaxRequest('delete_user', {
                user_id: userId
            }, (response) => {
                if (response.success) {
                    showAlert(response.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(response.message, 'danger');
                }
            });
        });
    }
    </script>
    
    <?php
}

function include_servers() {
    global $pdo;
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    $status_filter = $_GET['status_filter'] ?? '';
    
    // Costruisci query con filtri
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(s.nome LIKE ? OR s.ip LIKE ? OR s.descrizione LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($status_filter !== '') {
        $where_conditions[] = "s.is_active = ?";
        $params[] = (int)$status_filter;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Query server
    $servers_query = "
        SELECT s.*, 
               u.minecraft_nick as owner_name,
               COUNT(v.id) as total_votes,
               COUNT(CASE WHEN v.data_voto >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN 1 END) as monthly_votes,
               MAX(v.data_voto) as last_vote,
               sp.is_active as is_sponsored
        FROM sl_servers s 
        LEFT JOIN sl_users u ON s.owner_id = u.id
        LEFT JOIN sl_votes v ON s.id = v.server_id 
        LEFT JOIN sl_sponsored_servers sp ON s.id = sp.server_id
        $where_clause
        GROUP BY s.id 
        ORDER BY monthly_votes DESC, s.data_inserimento DESC 
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($servers_query);
    $stmt->execute($params);
    $servers = $stmt->fetchAll();
    
    // Conta totale per paginazione
    $count_query = "SELECT COUNT(*) FROM sl_servers s $where_clause";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_servers = $stmt->fetchColumn();
    $total_pages = ceil($total_servers / $limit);
    ?>
    
    <h2><i class="bi bi-server"></i> Gestione Server</h2>
    <p class="text-secondary">Gestisci server, stato e sponsorizzazioni</p>
    
    <!-- Filtri -->
    <div class="row mb-4">
        <div class="col-md-6">
            <form method="GET" class="d-flex">
                <input type="hidden" name="action" value="servers">
                <input type="text" name="search" class="form-control me-2" placeholder="Cerca nome, IP o descrizione..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary btn-admin">Cerca</button>
            </form>
        </div>
        <div class="col-md-3">
            <form method="GET">
                <input type="hidden" name="action" value="servers">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <select name="status_filter" class="form-select" onchange="this.form.submit()">
                    <option value="">Tutti gli stati</option>
                    <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Attivi</option>
                    <option value="2" <?= $status_filter === '2' ? 'selected' : '' ?>>In Attesa</option>
                    <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Disabilitati</option>
                </select>
            </form>
        </div>
        <div class="col-md-3 text-end">
            <span class="text-secondary">Totale: <?= $total_servers ?> server</span>
        </div>
    </div>
    
    <!-- Tabella server -->
    <div class="data-table">
        <div class="table-responsive">
            <table class="table table-dark table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>IP</th>
                        <th>Proprietario</th>
                        <th>Stato</th>
                        <th>Voti</th>
                        <th>Voti Mese</th>
                        <th>Aggiunto</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servers as $server): ?>
                    <tr>
                        <td><?= $server['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($server['nome']) ?></strong>
                            <?php if ($server['is_sponsored']): ?>
                                <span class="badge bg-warning ms-1">Sponsored</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code><?= htmlspecialchars($server['ip']) ?></code>
                        </td>
                        <td><?= htmlspecialchars($server['owner_name'] ?? 'N/A') ?></td>
                        <td>
                            <?php
                            switch ($server['is_active']) {
                                case 1:
                                    echo '<span class="badge bg-success">Attivo</span>';
                                    break;
                                case 2:
                                    echo '<span class="badge bg-warning">In Attesa</span>';
                                    break;
                                default:
                                    echo '<span class="badge bg-danger">Disabilitato</span>';
                            }
                            ?>
                        </td>
                        <td><span class="badge bg-primary"><?= $server['total_votes'] ?></span></td>
                        <td><span class="badge bg-info"><?= $server['monthly_votes'] ?></span></td>
                        <td><?= date('d/m/Y', strtotime($server['data_inserimento'])) ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-success btn-admin" 
                                        onclick="updateServerStatus(<?= $server['id'] ?>, 1)"
                                        title="Attiva">
                                    <i class="bi bi-check"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-warning btn-admin" 
                                        onclick="updateServerStatus(<?= $server['id'] ?>, 2)"
                                        title="In Attesa">
                                    <i class="bi bi-clock"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary btn-admin" 
                                        onclick="updateServerStatus(<?= $server['id'] ?>, 0)"
                                        title="Disabilita">
                                    <i class="bi bi-x"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-info btn-admin" 
                                        onclick="toggleSponsorship(<?= $server['id'] ?>)"
                                        title="Toggle Sponsorship">
                                    <i class="bi bi-star"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger btn-admin" 
                                        onclick="deleteServer(<?= $server['id'] ?>)"
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
    
    <!-- Paginazione -->
    <?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?action=servers&page=<?= $i ?>&search=<?= urlencode($search) ?>&status_filter=<?= urlencode($status_filter) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
    
    <script>
    function updateServerStatus(serverId, status) {
        const statusNames = {0: 'disabilitato', 1: 'attivo', 2: 'in attesa'};
        confirmAction(`Sei sicuro di voler impostare il server come ${statusNames[status]}?`, () => {
            makeAjaxRequest('update_server_status', {
                server_id: serverId,
                status: status
            }, (response) => {
                if (response.success) {
                    showAlert(response.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(response.message, 'danger');
                }
            });
        });
    }
    
    function toggleSponsorship(serverId) {
        confirmAction('Sei sicuro di voler modificare lo stato di sponsorizzazione?', () => {
            makeAjaxRequest('toggle_sponsorship', {
                server_id: serverId
            }, (response) => {
                if (response.success) {
                    showAlert(response.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(response.message, 'danger');
                }
            });
        });
    }
    
    function deleteServer(serverId) {
        confirmAction('Sei sicuro di voler eliminare questo server? Tutti i dati correlati verranno eliminati!', () => {
            makeAjaxRequest('delete_server', {
                server_id: serverId
            }, (response) => {
                if (response.success) {
                    showAlert(response.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(response.message, 'danger');
                }
            });
        });
    }
    </script>
    
    <?php
}

function include_votes() {
    global $pdo;
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    $status_filter = $_GET['status_filter'] ?? '';
    
    // Costruisci query con filtri
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(u.username LIKE ? OR s.nome LIKE ? OR s.ip LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($status_filter !== '') {
        $where_conditions[] = "vc.status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Query voti
    $votes_query = "
        SELECT v.*, 
               u.minecraft_nick as username,
               s.nome as server_name,
               s.ip as server_ip,
               vc.code as vote_code,
               vc.status as code_status
        FROM sl_votes v 
        LEFT JOIN sl_users u ON v.user_id = u.id
        LEFT JOIN sl_servers s ON v.server_id = s.id
        LEFT JOIN sl_vote_codes vc ON v.id = vc.vote_id
        $where_clause
        ORDER BY v.data_voto DESC 
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($votes_query);
    $stmt->execute($params);
    $votes = $stmt->fetchAll();
    
    // Conta totale per paginazione
    $count_query = "
        SELECT COUNT(*) FROM sl_votes v 
        LEFT JOIN sl_users u ON v.user_id = u.id
        LEFT JOIN sl_servers s ON v.server_id = s.id
        LEFT JOIN sl_vote_codes vc ON v.id = vc.vote_id
        $where_clause
    ";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_votes = $stmt->fetchColumn();
    $total_pages = ceil($total_votes / $limit);
    ?>
    
    <h2><i class="bi bi-hand-thumbs-up"></i> Gestione Voti</h2>
    <p class="text-secondary">Monitora voti, codici e reward</p>
    
    <!-- Filtri -->
    <div class="row mb-4">
        <div class="col-md-6">
            <form method="GET" class="d-flex">
                <input type="hidden" name="action" value="votes">
                <input type="text" name="search" class="form-control me-2" placeholder="Cerca utente, server o IP..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary btn-admin">Cerca</button>
            </form>
        </div>
        <div class="col-md-3">
            <form method="GET">
                <input type="hidden" name="action" value="votes">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <select name="status_filter" class="form-select" onchange="this.form.submit()">
                    <option value="">Tutti i codici</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="used" <?= $status_filter === 'used' ? 'selected' : '' ?>>Usati</option>
                    <option value="expired" <?= $status_filter === 'expired' ? 'selected' : '' ?>>Scaduti</option>
                </select>
            </form>
        </div>
        <div class="col-md-3 text-end">
            <span class="text-secondary">Totale: <?= $total_votes ?> voti</span>
        </div>
    </div>
    
    <!-- Tabella voti -->
    <div class="data-table">
        <div class="table-responsive">
            <table class="table table-dark table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utente</th>
                        <th>Server</th>
                        <th>Data Voto</th>
                        <th>Codice</th>
                        <th>Stato Codice</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($votes as $vote): ?>
                    <tr>
                        <td><?= $vote['id'] ?></td>
                        <td><?= htmlspecialchars($vote['username'] ?? 'N/A') ?></td>
                        <td>
                            <strong><?= htmlspecialchars($vote['server_name'] ?? 'N/A') ?></strong><br>
                            <small class="text-secondary"><?= htmlspecialchars($vote['server_ip'] ?? 'N/A') ?></small>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($vote['data_voto'])) ?></td>
                        <td>
                            <?php if ($vote['vote_code']): ?>
                                <code><?= htmlspecialchars($vote['vote_code']) ?></code>
                            <?php else: ?>
                                <span class="text-secondary">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($vote['code_status']): ?>
                                <?php
                                switch ($vote['code_status']) {
                                    case 'pending':
                                        echo '<span class="badge bg-warning">Pending</span>';
                                        break;
                                    case 'used':
                                        echo '<span class="badge bg-success">Usato</span>';
                                        break;
                                    case 'expired':
                                        echo '<span class="badge bg-danger">Scaduto</span>';
                                        break;
                                    default:
                                        echo '<span class="badge bg-secondary">' . htmlspecialchars($vote['code_status']) . '</span>';
                                }
                                ?>
                            <?php else: ?>
                                <span class="text-secondary">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($vote['vote_code']): ?>
                                <select class="form-select form-select-sm" onchange="updateVoteCodeStatus(<?= $vote['id'] ?>, this.value)">
                                    <option value="pending" <?= $vote['code_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="used" <?= $vote['code_status'] === 'used' ? 'selected' : '' ?>>Usato</option>
                                    <option value="expired" <?= $vote['code_status'] === 'expired' ? 'selected' : '' ?>>Scaduto</option>
                                </select>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-danger btn-admin mt-1" 
                                    onclick="deleteVote(<?= $vote['id'] ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Paginazione -->
    <?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?action=votes&page=<?= $i ?>&search=<?= urlencode($search) ?>&status_filter=<?= urlencode($status_filter) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
    
    <script>
    function updateVoteCodeStatus(voteId, status) {
        makeAjaxRequest('update_vote_code_status', {
            code_id: voteId,
            status: status
        }, (response) => {
            if (response.success) {
                showAlert(response.message, 'success');
            } else {
                showAlert(response.message, 'danger');
                location.reload();
            }
        });
    }
    
    function deleteVote(voteId) {
        confirmAction('Sei sicuro di voler eliminare questo voto? Tutti i dati correlati verranno eliminati!', () => {
            makeAjaxRequest('delete_vote', {
                vote_id: voteId
            }, (response) => {
                if (response.success) {
                    showAlert(response.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(response.message, 'danger');
                }
            });
        });
    }
    </script>
    
    <?php
}

function include_rewards() {
    global $pdo;
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    $status_filter = $_GET['status_filter'] ?? '';
    
    // Costruisci query con filtri
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(u.minecraft_nick LIKE ? OR s.nome LIKE ? OR rl.command LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($status_filter !== '') {
        $where_conditions[] = "rl.reward_status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Query reward logs
     $rewards_query = "
         SELECT rl.*, 
                u.minecraft_nick as username,
                s.nome as server_name,
                vc.code as vote_code
         FROM sl_reward_logs rl 
         LEFT JOIN sl_users u ON rl.user_id = u.id
         LEFT JOIN sl_servers s ON rl.server_id = s.id
         LEFT JOIN sl_vote_codes vc ON rl.vote_code_id = vc.id
         $where_clause
         ORDER BY rl.executed_at DESC 
         LIMIT $limit OFFSET $offset
     ";
     
     $stmt = $pdo->prepare($rewards_query);
     $stmt->execute($params);
     $rewards = $stmt->fetchAll();
     
     // Conta totale per paginazione
     $count_query = "
         SELECT COUNT(*) FROM sl_reward_logs rl 
         LEFT JOIN sl_users u ON rl.user_id = u.id
         LEFT JOIN sl_servers s ON rl.server_id = s.id
         $where_clause
     ";
     $stmt = $pdo->prepare($count_query);
     $stmt->execute($params);
     $total_rewards = $stmt->fetchColumn();
     $total_pages = ceil($total_rewards / $limit);
     ?>
     
     <h2><i class="bi bi-gift"></i> Gestione Reward</h2>
     <p class="text-secondary">Monitora reward, comandi e stati di esecuzione</p>
     
     <!-- Filtri -->
     <div class="row mb-4">
         <div class="col-md-6">
             <form method="GET" class="d-flex">
                 <input type="hidden" name="action" value="rewards">
                 <input type="text" name="search" class="form-control me-2" placeholder="Cerca utente, server o comando..." value="<?= htmlspecialchars($search) ?>">
                 <button type="submit" class="btn btn-primary btn-admin">Cerca</button>
             </form>
         </div>
         <div class="col-md-3">
             <form method="GET">
                 <input type="hidden" name="action" value="rewards">
                 <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                 <select name="status_filter" class="form-select" onchange="this.form.submit()">
                     <option value="">Tutti gli stati</option>
                     <option value="success" <?= $status_filter === 'success' ? 'selected' : '' ?>>Successo</option>
                     <option value="error" <?= $status_filter === 'error' ? 'selected' : '' ?>>Errore</option>
                     <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                 </select>
             </form>
         </div>
         <div class="col-md-3 text-end">
             <span class="text-secondary">Totale: <?= $total_rewards ?> reward</span>
         </div>
     </div>
     
     <!-- Tabella reward -->
     <div class="data-table">
         <div class="table-responsive">
             <table class="table table-dark table-hover">
                 <thead>
                     <tr>
                         <th>ID</th>
                         <th>Utente</th>
                         <th>Server</th>
                         <th>Comando</th>
                         <th>Stato</th>
                         <th>Retry</th>
                         <th>Data</th>
                         <th>Azioni</th>
                     </tr>
                 </thead>
                 <tbody>
                     <?php foreach ($rewards as $reward): ?>
                     <tr>
                         <td><?= $reward['id'] ?></td>
                         <td><?= htmlspecialchars($reward['username'] ?? 'N/A') ?></td>
                         <td><?= htmlspecialchars($reward['server_name'] ?? 'N/A') ?></td>
                         <td>
                             <code><?= htmlspecialchars($reward['command'] ?? 'N/A') ?></code>
                         </td>
                         <td>
                             <?php
                             switch ($reward['reward_status']) {
                                 case 'success':
                                     echo '<span class="badge bg-success">Successo</span>';
                                     break;
                                 case 'error':
                                     echo '<span class="badge bg-danger">Errore</span>';
                                     break;
                                 case 'pending':
                                     echo '<span class="badge bg-warning">Pending</span>';
                                     break;
                                 default:
                                     echo '<span class="badge bg-secondary">' . htmlspecialchars($reward['reward_status']) . '</span>';
                             }
                             ?>
                         </td>
                         <td>
                             <span class="badge bg-info"><?= $reward['retry_count'] ?? 0 ?></span>
                         </td>
                         <td><?= date('d/m/Y H:i', strtotime($reward['executed_at'])) ?></td>
                         <td>
                             <?php if ($reward['reward_status'] === 'error'): ?>
                                 <button class="btn btn-sm btn-outline-warning btn-admin" 
                                         onclick="retryReward(<?= $reward['id'] ?>)"
                                         title="Riprova">
                                     <i class="bi bi-arrow-clockwise"></i>
                                 </button>
                             <?php endif; ?>
                             <button class="btn btn-sm btn-outline-danger btn-admin" 
                                     onclick="deleteRewardLog(<?= $reward['id'] ?>)"
                                     title="Elimina">
                                 <i class="bi bi-trash"></i>
                             </button>
                         </td>
                     </tr>
                     <?php endforeach; ?>
                 </tbody>
             </table>
         </div>
     </div>
     
     <!-- Paginazione -->
     <?php if ($total_pages > 1): ?>
     <nav class="mt-4">
         <ul class="pagination justify-content-center">
             <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                 <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                     <a class="page-link" href="?action=rewards&page=<?= $i ?>&search=<?= urlencode($search) ?>&status_filter=<?= urlencode($status_filter) ?>">
                         <?= $i ?>
                     </a>
                 </li>
             <?php endfor; ?>
         </ul>
     </nav>
     <?php endif; ?>
     
     <script>
     function retryReward(logId) {
         confirmAction('Sei sicuro di voler riprovare questo reward?', () => {
             makeAjaxRequest('retry_reward', {
                 log_id: logId
             }, (response) => {
                 if (response.success) {
                     showAlert(response.message, 'success');
                     setTimeout(() => location.reload(), 1000);
                 } else {
                     showAlert(response.message, 'danger');
                 }
             });
         });
     }
     
     function deleteRewardLog(logId) {
         confirmAction('Sei sicuro di voler eliminare questo log?', () => {
             makeAjaxRequest('delete_reward_log', {
                 log_id: logId
             }, (response) => {
                 if (response.success) {
                     showAlert(response.message, 'success');
                     setTimeout(() => location.reload(), 1000);
                 } else {
                     showAlert(response.message, 'danger');
                 }
             });
         });
     }
     </script>
     
     <?php
 }
 
 function include_licenses() {
    global $pdo;
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    $status_filter = $_GET['status_filter'] ?? '';
    
    // Query per server attivi (per dropdown)
    $servers = [];
    $servers_without_license = [];
    $license_stats = [];
    
    try {
        // Ottieni tutti i server attivi per il dropdown
        $stmt = $pdo->prepare("SELECT id, nome FROM sl_servers WHERE is_active = 1 ORDER BY nome");
        $stmt->execute();
        $servers = $stmt->fetchAll();
        
        // Ottieni server attivi senza licenza attiva
        $stmt = $pdo->prepare("
            SELECT s.id, s.nome, s.data_inserimento 
            FROM sl_servers s 
            LEFT JOIN sl_server_licenses sl ON s.id = sl.server_id AND sl.is_active = 1
            WHERE s.is_active = 1 AND sl.server_id IS NULL
            ORDER BY s.nome
        ");
        $stmt->execute();
        $servers_without_license = $stmt->fetchAll();
        
        // Statistiche licenze - TUTTI i server (attivi e non attivi)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sl_servers");
        $stmt->execute();
        $total_servers = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM sl_servers s 
            INNER JOIN sl_server_licenses sl ON s.id = sl.server_id 
            WHERE sl.is_active = 1
        ");
        $stmt->execute();
        $servers_with_active_license = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM sl_servers s 
            INNER JOIN sl_server_licenses sl ON s.id = sl.server_id 
            WHERE sl.is_active = 0
        ");
        $stmt->execute();
        $servers_with_inactive_license = $stmt->fetchColumn();
        
        $servers_without_any_license = $total_servers - $servers_with_active_license - $servers_with_inactive_license;
        
        $license_stats = [
            'total_servers' => $total_servers,
            'with_active_license' => $servers_with_active_license,
            'with_inactive_license' => $servers_with_inactive_license,
            'without_license' => $servers_without_any_license
        ];
        
    } catch (PDOException $e) {
        // In caso di errore, array vuoti
    }
     
     // Costruisci query con filtri
     $where_conditions = [];
     $params = [];
     
     if ($search) {
         $where_conditions[] = "(sl.license_key LIKE ? OR s.nome LIKE ?)";
         $params[] = "%$search%";
         $params[] = "%$search%";
     }
     
     if ($status_filter !== '') {
         $where_conditions[] = "sl.is_active = ?";
         $params[] = (int)$status_filter;
     }
     
     $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
     
     // Query licenze
     $licenses_query = "
         SELECT sl.*, 
                s.nome as server_name,
                u.minecraft_nick as owner_name
         FROM sl_server_licenses sl 
         LEFT JOIN sl_servers s ON sl.server_id = s.id
         LEFT JOIN sl_users u ON s.owner_id = u.id
         $where_clause
         ORDER BY sl.created_at DESC 
         LIMIT $limit OFFSET $offset
     ";
     
     $stmt = $pdo->prepare($licenses_query);
     $stmt->execute($params);
     $licenses = $stmt->fetchAll();
     
     // Conta totale per paginazione
     $count_query = "
         SELECT COUNT(*) FROM sl_server_licenses sl 
         LEFT JOIN sl_servers s ON sl.server_id = s.id
         $where_clause
     ";
     $stmt = $pdo->prepare($count_query);
     $stmt->execute($params);
     $total_licenses = $stmt->fetchColumn();
     $total_pages = ceil($total_licenses / $limit);
     ?>
     
     <h2><i class="bi bi-key"></i> Gestione Licenze</h2>
    <p class="text-secondary">Gestisci licenze server e chiavi di accesso</p>
    
    <!-- Statistiche Licenze -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-dark border-secondary">
                <div class="card-body">
                    <h5 class="card-title text-success"><i class="bi bi-bar-chart"></i> Statistiche Licenze</h5>
                    <div class="row text-center">
                        <div class="col-md-2">
                            <div class="stat-item">
                                <h3 class="text-primary"><?= $license_stats['total_servers'] ?></h3>
                                <p class="text-secondary mb-0">Server Totali</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-item">
                                <h3 class="text-success"><?= $license_stats['with_active_license'] ?></h3>
                                <p class="text-secondary mb-0">Licenze Attive</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-item">
                                <h3 class="text-warning"><?= $license_stats['with_inactive_license'] ?></h3>
                                <p class="text-secondary mb-0">Licenze Disattivate</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-item">
                                <h3 class="text-danger"><?= $license_stats['without_license'] ?></h3>
                                <p class="text-secondary mb-0">Senza Licenza</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-item">
                                <h3 class="text-info"><?= $license_stats['total_servers'] > 0 ? round(($license_stats['with_active_license'] / $license_stats['total_servers']) * 100, 1) : 0 ?>%</h3>
                                <p class="text-secondary mb-0">Copertura Attiva</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-item">
                                <h3 class="text-secondary"><?= $license_stats['total_servers'] > 0 ? round((($license_stats['with_active_license'] + $license_stats['with_inactive_license']) / $license_stats['total_servers']) * 100, 1) : 0 ?>%</h3>
                                <p class="text-secondary mb-0">Copertura Totale</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($servers_without_license)): ?>
    <!-- Server Senza Licenza -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning alert-persistent" style="border-radius: 8px; border: 1px solid #ffc107; box-shadow: 0 2px 8px rgba(0,0,0,0.1); font-weight: 500; position: relative; z-index: 1050;">
                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Server Senza Licenza (<?= count($servers_without_license) ?>)</h5>
                <p>I seguenti server attivi non hanno una licenza generata:</p>
                <div class="row">
                    <?php foreach ($servers_without_license as $server): ?>
                        <div class="col-md-6 col-lg-4 mb-2">
                            <div class="d-flex justify-content-between align-items-center bg-dark p-2 rounded">
                                <div>
                                    <strong><?= htmlspecialchars($server['nome']) ?></strong><br>
                                    <small class="text-secondary">ID: <?= $server['id'] ?> - <?= date('d/m/Y', strtotime($server['data_inserimento'])) ?></small>
                                </div>
                                <button class="btn btn-sm btn-success" onclick="generateLicenseForServer(<?= $server['id'] ?>, '<?= htmlspecialchars($server['nome']) ?>')">
                                    <i class="bi bi-plus"></i> Genera
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Filtri e azioni -->
     <div class="row mb-4">
         <div class="col-md-5">
             <form method="GET" class="d-flex">
                 <input type="hidden" name="action" value="licenses">
                 <input type="text" name="search" class="form-control me-2" placeholder="Cerca licenza o server..." value="<?= htmlspecialchars($search) ?>">
                 <button type="submit" class="btn btn-primary btn-admin">Cerca</button>
             </form>
         </div>
         <div class="col-md-3">
             <form method="GET">
                 <input type="hidden" name="action" value="licenses">
                 <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                 <select name="status_filter" class="form-select" onchange="this.form.submit()">
                     <option value="">Tutti gli stati</option>
                     <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Attive</option>
                     <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Disattive</option>
                 </select>
             </form>
         </div>
         <div class="col-md-2">
             <button class="btn btn-success btn-admin w-100" onclick="generateLicense()">
                 <i class="bi bi-plus"></i> Genera Licenza
             </button>
         </div>
         <div class="col-md-2 text-end">
             <span class="text-secondary">Totale: <?= $total_licenses ?> licenze</span>
         </div>
     </div>
     
     <!-- Tabella licenze -->
     <div class="data-table">
         <div class="table-responsive">
             <table class="table table-dark table-hover">
                 <thead>
                     <tr>
                         <th>ID</th>
                         <th>Licenza</th>
                         <th>Server</th>
                         <th>Proprietario</th>
                         <th>Stato</th>
                         <th>Creazione</th>
                         <th>Azioni</th>
                     </tr>
                 </thead>
                 <tbody>
                     <?php foreach ($licenses as $license): ?>
                     <tr>
                         <td><?= $license['id'] ?></td>
                         <td>
                             <code><?= htmlspecialchars($license['license_key']) ?></code>
                         </td>
                         <td>
                             <?php if ($license['server_name']): ?>
                                 <?= htmlspecialchars($license['server_name']) ?>
                             <?php else: ?>
                                 <span class="text-secondary">Non assegnata</span>
                             <?php endif; ?>
                         </td>
                         <td><?= htmlspecialchars($license['owner_name'] ?? 'N/A') ?></td>
                         <td>
                             <?php if ($license['is_active']): ?>
                                 <span class="badge bg-success">Attiva</span>
                             <?php else: ?>
                                 <span class="badge bg-danger">Disattiva</span>
                             <?php endif; ?>
                         </td>
                         <td><?= date('d/m/Y H:i', strtotime($license['created_at'])) ?></td>
                         <td>
                             <button class="btn btn-sm btn-outline-warning btn-admin" 
                                     onclick="toggleLicense(<?= $license['id'] ?>, <?= $license['is_active'] ?>)"
                                     title="Toggle Stato">
                                 <i class="bi bi-toggle-<?= $license['is_active'] ? 'on' : 'off' ?>"></i>
                             </button>
                             <button class="btn btn-sm btn-outline-danger btn-admin" 
                                     onclick="deleteLicense(<?= $license['id'] ?>)"
                                     title="Elimina">
                                 <i class="bi bi-trash"></i>
                             </button>
                         </td>
                     </tr>
                     <?php endforeach; ?>
                 </tbody>
             </table>
         </div>
     </div>
     
     <!-- Paginazione -->
     <?php if ($total_pages > 1): ?>
     <nav class="mt-4">
         <ul class="pagination justify-content-center">
             <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                 <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                     <a class="page-link" href="?action=licenses&page=<?= $i ?>&search=<?= urlencode($search) ?>&status_filter=<?= urlencode($status_filter) ?>">
                         <?= $i ?>
                     </a>
                 </li>
             <?php endfor; ?>
         </ul>
     </nav>
     <?php endif; ?>
     
     <script>
     function generateLicense() {
        // Apri modal per selezione server
        const modal = new bootstrap.Modal(document.getElementById('generateLicenseModal'));
        modal.show();
    }
    
    function generateLicenseForServer(serverId, serverName) {
        confirmAction(`Sei sicuro di voler generare una licenza per "${serverName}"?`, () => {
            makeAjaxRequest('generate_license', {
                server_id: serverId
            }, (response) => {
                if (response.success) {
                    showAlert(response.message + '<br><strong>Licenza:</strong> <code>' + response.license_key + '</code>', 'success');
                    setTimeout(() => location.reload(), 3000);
                } else {
                    showAlert(response.message, 'danger');
                }
            });
        });
    }
    
    function submitGenerateLicense() {
        const selectedServerId = document.getElementById('selectedServerId').value;
        const serverInput = document.getElementById('serverInput').value.trim();
        
        if (!selectedServerId && !serverInput) {
            showAlert('Seleziona un server dalla lista o inserisci nome/ID', 'warning');
            return;
        }
        
        // Chiudi modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('generateLicenseModal'));
        modal.hide();
        
        if (selectedServerId) {
            // Usa l'ID selezionato dal dropdown
            const serverName = document.getElementById('serverInput').value;
            generateLicenseForServer(selectedServerId, serverName);
        } else {
            // Fallback al metodo manuale
            generateLicenseWithInput(serverInput);
        }
    }
    
    function generateLicenseWithInput(serverInput) {
        // Mostra messaggio di caricamento
        showAlert('Ricerca server in corso...', 'info');
        
        // Chiama il backend per generare la licenza con input manuale
        makeAjaxRequest('generate_license_with_input', {
            server_input: serverInput
        }, (response) => {
            if (response.success) {
                showAlert(response.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(response.message, 'danger');
            }
        });
    }
    
    function generateLicenseForServer(serverId, serverName) {
        showAlert('Generazione licenza in corso...', 'info');
        
        makeAjaxRequest('generate_license', {
            server_id: serverId
        }, (response) => {
            if (response.success) {
                showAlert(`Licenza generata con successo per ${serverName}: ${response.license_key}`, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(response.message, 'danger');
            }
        });
    }
    
    // Autocomplete per server
    let searchTimeout;
    
    function initServerAutocomplete() {
        const serverInput = document.getElementById('serverInput');
        const serverDropdown = document.getElementById('serverDropdown');
        const selectedServerId = document.getElementById('selectedServerId');
        
        if (!serverInput) return;
        
        serverInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            // Resetta selezione
            selectedServerId.value = '';
            
            // Cancella timeout precedente
            clearTimeout(searchTimeout);
            
            if (query.length < 1) {
                serverDropdown.style.display = 'none';
                return;
            }
            
            // Debounce per evitare troppe richieste
            searchTimeout = setTimeout(() => {
                searchServers(query);
            }, 300);
        });
        
        // Nascondi dropdown quando si clicca fuori
        document.addEventListener('click', function(e) {
            if (!serverInput.contains(e.target) && !serverDropdown.contains(e.target)) {
                serverDropdown.style.display = 'none';
            }
        });
    }
    
    function searchServers(query) {
        makeAjaxRequest('search_servers', {
            search: query
        }, (response) => {
            if (response.success) {
                displayServerResults(response.servers);
            }
        });
    }
    
    function displayServerResults(servers) {
        const serverDropdown = document.getElementById('serverDropdown');
        
        if (servers.length === 0) {
            serverDropdown.innerHTML = '<div class="dropdown-item text-secondary">Nessun server trovato</div>';
            serverDropdown.style.display = 'block';
            return;
        }
        
        let html = '';
        servers.forEach(server => {
            html += `
                <div class="dropdown-item server-option" data-id="${server.id}" data-name="${server.nome}" style="cursor: pointer;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><strong>${server.nome}</strong></span>
                        <small class="text-secondary">ID: ${server.id}</small>
                    </div>
                </div>
            `;
        });
        
        serverDropdown.innerHTML = html;
        serverDropdown.style.display = 'block';
        
        // Aggiungi event listener per la selezione
        serverDropdown.querySelectorAll('.server-option').forEach(option => {
            option.addEventListener('click', function() {
                const serverId = this.dataset.id;
                const serverName = this.dataset.name;
                
                document.getElementById('serverInput').value = serverName;
                document.getElementById('selectedServerId').value = serverId;
                serverDropdown.style.display = 'none';
            });
        });
    }
    
    // Inizializza autocomplete quando si apre il modal
    document.getElementById('generateLicenseModal').addEventListener('shown.bs.modal', function() {
        initServerAutocomplete();
        document.getElementById('serverInput').focus();
    });
    
    // Reset form quando si chiude il modal
    document.getElementById('generateLicenseModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('serverInput').value = '';
        document.getElementById('selectedServerId').value = '';
        document.getElementById('serverDropdown').style.display = 'none';
    });
     
     function toggleLicense(licenseId, currentStatus) {
         const action = currentStatus ? 'disattivare' : 'attivare';
         confirmAction(`Sei sicuro di voler ${action} questa licenza?`, () => {
             makeAjaxRequest('toggle_license', {
                 license_id: licenseId,
                 current_status: currentStatus
             }, (response) => {
                 if (response.success) {
                     showAlert(response.message, 'success');
                     setTimeout(() => location.reload(), 1000);
                 } else {
                     showAlert(response.message, 'danger');
                 }
             });
         });
     }
     
     function deleteLicense(licenseId) {
         confirmAction('Sei sicuro di voler eliminare questa licenza? Questa azione è irreversibile!', () => {
             makeAjaxRequest('delete_license', {
                 license_id: licenseId
             }, (response) => {
                 if (response.success) {
                     showAlert(response.message, 'success');
                     setTimeout(() => location.reload(), 1000);
                 } else {
                     showAlert(response.message, 'danger');
                 }
             });
         });
     }
     </script>
     
     <?php
 }
 ?>

<!-- Modal per modificare utente -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Modifica Utente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="editUserId" name="user_id">
                    <div class="mb-3">
                        <label for="editMinecraftNick" class="form-label">Nome Minecraft</label>
                        <input type="text" class="form-control" id="editMinecraftNick" name="minecraft_nick" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPassword" class="form-label">Nuova Password (lascia vuoto per non modificare)</label>
                        <input type="password" class="form-control" id="editPassword" name="password">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" onclick="saveUserChanges()">Salva Modifiche</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal per generare licenza -->
<div class="modal fade" id="generateLicenseModal" tabindex="-1" aria-labelledby="generateLicenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title" id="generateLicenseModalLabel">Genera Licenza Server</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="generateLicenseForm">
                    <div class="mb-3">
                        <label for="serverInput" class="form-label">Cerca Server</label>
                        <div class="position-relative">
                            <input type="text" class="form-control" id="serverInput" name="server_input" required 
                                   placeholder="Digita per cercare server..." autocomplete="off">
                            <input type="hidden" id="selectedServerId" name="selected_server_id">
                            <div id="serverDropdown" class="dropdown-menu w-100" style="max-height: 200px; overflow-y: auto; display: none;">
                                <!-- I risultati della ricerca appariranno qui -->
                            </div>
                        </div>
                        <div class="form-text mt-2">
                            <i class="bi bi-info-circle"></i> Inizia a digitare per vedere i server disponibili. 
                            Puoi cercare per nome o ID. Se il server ha già una licenza, verrà sostituita.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-success" onclick="submitGenerateLicense()">
                    <i class="bi bi-key"></i> Genera Licenza
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function editUser(userId, currentNick) {
    document.getElementById('editUserId').value = userId;
    document.getElementById('editMinecraftNick').value = currentNick;
    document.getElementById('editPassword').value = '';
    
    var modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}

function saveUserChanges() {
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    formData.append('action', 'edit_user');
    
    fetch('admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Errore durante il salvataggio', 'danger');
    });
}

// Protezione per alert persistenti
document.addEventListener('DOMContentLoaded', function() {
    // Previeni la rimozione automatica degli alert persistenti
    const persistentAlerts = document.querySelectorAll('.alert-persistent');
    
    persistentAlerts.forEach(alert => {
        // Rimuovi eventuali event listener di Bootstrap per auto-dismiss
        alert.removeAttribute('data-bs-dismiss');
        
        // Previeni la rimozione tramite JavaScript
        const originalRemove = alert.remove;
        alert.remove = function() {
            console.log('Tentativo di rimuovere alert persistente bloccato');
            return false;
        };
        
        // Previeni la modifica dello stile display
        const originalStyle = alert.style;
        Object.defineProperty(alert, 'style', {
            get: function() { return originalStyle; },
            set: function(value) {
                if (typeof value === 'object' && value.display === 'none') {
                    console.log('Tentativo di nascondere alert persistente bloccato');
                    return;
                }
                return originalStyle;
            }
        });
    });
    
    // Osserva mutazioni per prevenire rimozioni
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.removedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node.classList && node.classList.contains('alert-persistent')) {
                        console.log('Alert persistente rimosso, lo ripristino');
                        mutation.target.appendChild(node);
                    }
                });
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
</script>

 <?php include 'footer.php'; ?>