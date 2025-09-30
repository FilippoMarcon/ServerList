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
                // Supporta sia toggle per server_id sia per id sponsor (più robusto)
                $server_id = isset($_POST['server_id']) ? (int)$_POST['server_id'] : 0;
                $sponsor_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

                if ($sponsor_id > 0) {
                    // Toggle tramite ID della sponsorizzazione
                    $stmt = $pdo->prepare("SELECT id, is_active FROM sl_sponsored_servers WHERE id = ?");
                    $stmt->execute([$sponsor_id]);
                    $sponsorship = $stmt->fetch();
                    if (!$sponsorship) { echo json_encode(['success' => false, 'message' => 'Sponsor non trovato']); break; }
                    $new_status = ((int)$sponsorship['is_active'] === 1) ? 0 : 1;
                    $stmt = $pdo->prepare("UPDATE sl_sponsored_servers SET is_active = ? WHERE id = ?");
                    $stmt->execute([$new_status, $sponsor_id]);
                    $msg = $new_status ? 'Sponsor attivato' : 'Sponsor disattivato';
                    echo json_encode(['success' => true, 'message' => $msg]);
                    break;
                }

                if ($server_id > 0) {
                    // Toggle tramite server_id (comportamento esistente)
                    $stmt = $pdo->prepare("SELECT id, is_active FROM sl_sponsored_servers WHERE server_id = ?");
                    $stmt->execute([$server_id]);
                    $sponsorship = $stmt->fetch();
                    if ($sponsorship) {
                        $new_status = ((int)$sponsorship['is_active'] === 1) ? 0 : 1;
                        $stmt = $pdo->prepare("UPDATE sl_sponsored_servers SET is_active = ? WHERE server_id = ?");
                        $stmt->execute([$new_status, $server_id]);
                        $msg = $new_status ? 'Sponsor attivato' : 'Sponsor disattivato';
                        echo json_encode(['success' => true, 'message' => $msg]);
                        break;
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO sl_sponsored_servers (server_id, is_active, created_at) VALUES (?, 1, NOW())");
                        $stmt->execute([$server_id]);
                        echo json_encode(['success' => true, 'message' => 'Sponsor creato e attivato']);
                        break;
                    }
                }

                echo json_encode(['success' => false, 'message' => 'Parametri mancanti (id o server_id)']);
                break;

            case 'add_sponsorship':
                $server_id = (int)($_POST['server_id'] ?? 0);
                $priority = (int)($_POST['priority'] ?? 1);
                $expires_at = $_POST['expires_at'] ?? null;

                if ($server_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Server non valido']);
                    break;
                }

                // Verifica server esistente e attivo
                $stmt = $pdo->prepare("SELECT id, nome, is_active FROM sl_servers WHERE id = ?");
                $stmt->execute([$server_id]);
                $server = $stmt->fetch();
                if (!$server) { echo json_encode(['success' => false, 'message' => 'Server non trovato']); break; }
                if ((int)$server['is_active'] !== 1) { echo json_encode(['success' => false, 'message' => 'Il server non è attivo']); break; }

                // Evita duplicati
                $stmt = $pdo->prepare("SELECT id FROM sl_sponsored_servers WHERE server_id = ?");
                $stmt->execute([$server_id]);
                if ($stmt->fetch()) { echo json_encode(['success' => false, 'message' => 'Server già sponsorizzato']); break; }

                if ($expires_at === '') { $expires_at = null; }
                $stmt = $pdo->prepare("INSERT INTO sl_sponsored_servers (server_id, priority, is_active, created_at, expires_at) VALUES (?, ?, 1, NOW(), ?)");
                $stmt->execute([$server_id, $priority, $expires_at]);
                echo json_encode(['success' => true, 'message' => 'Sponsor creato con successo']);
                break;

            case 'update_sponsorship':
                $sponsor_id = (int)($_POST['id'] ?? 0);
                $priority = isset($_POST['priority']) ? (int)$_POST['priority'] : null;
                $expires_at = $_POST['expires_at'] ?? null;
                if ($sponsor_id <= 0) { echo json_encode(['success' => false, 'message' => 'ID sponsor non valido']); break; }
                $fields = [];
                $params = [];
                if ($priority !== null) { $fields[] = 'priority = ?'; $params[] = $priority; }
                if ($expires_at !== null) { if ($expires_at === '') { $fields[] = 'expires_at = NULL'; } else { $fields[] = 'expires_at = ?'; $params[] = $expires_at; } }
                if (empty($fields)) { echo json_encode(['success' => false, 'message' => 'Nessuna modifica fornita']); break; }
                $params[] = $sponsor_id;
                $sql = 'UPDATE sl_sponsored_servers SET ' . implode(', ', $fields) . ' WHERE id = ?';
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                echo json_encode(['success' => true, 'message' => 'Sponsor aggiornato']);
                break;

            case 'delete_sponsorship':
                $sponsor_id = (int)($_POST['id'] ?? 0);
                if ($sponsor_id <= 0) { echo json_encode(['success' => false, 'message' => 'ID sponsor non valido']); break; }
                $stmt = $pdo->prepare("DELETE FROM sl_sponsored_servers WHERE id = ?");
                $stmt->execute([$sponsor_id]);
                echo json_encode(['success' => true, 'message' => 'Sponsor eliminato']);
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
                // Elimina prima i reward logs legati ai codici del voto
                $pdo->prepare("DELETE FROM sl_reward_logs WHERE vote_code_id IN (SELECT id FROM sl_vote_codes WHERE vote_id = ?)")->execute([$vote_id]);
                // Poi elimina i codici di voto associati
                $pdo->prepare("DELETE FROM sl_vote_codes WHERE vote_id = ?")->execute([$vote_id]);
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

            // Annunci Management
            case 'create_annuncio':
                $title = trim($_POST['title'] ?? '');
                $body = trim($_POST['body'] ?? '');
                $author_id = (int)($_POST['author_id'] ?? 0);
                $is_published = isset($_POST['is_published']) ? (int)$_POST['is_published'] : 1;
                $created_at_in = trim($_POST['created_at'] ?? '');
                if (!$title || !$body || $author_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Titolo, contenuto e autore sono obbligatori']);
                    break;
                }
                // Normalizza datetime-local (YYYY-MM-DDTHH:MM) -> Y-m-d H:i:s
                $created_at = $created_at_in ? str_replace('T', ' ', $created_at_in) . ':00' : date('Y-m-d H:i:s');
                $stmt = $pdo->prepare("INSERT INTO sl_annunci (title, body, author_id, is_published, created_at) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $body, $author_id, $is_published, $created_at]);
                echo json_encode(['success' => true, 'message' => 'Annuncio creato']);
                break;

            case 'update_annuncio':
                $id = (int)($_POST['id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $body = trim($_POST['body'] ?? '');
                $author_id = (int)($_POST['author_id'] ?? 0);
                $is_published = isset($_POST['is_published']) ? (int)$_POST['is_published'] : 1;
                $created_at_in = trim($_POST['created_at'] ?? '');
                if ($id <= 0 || !$title || !$body || $author_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'ID, titolo, contenuto e autore sono obbligatori']);
                    break;
                }
                $created_at = $created_at_in ? str_replace('T', ' ', $created_at_in) . ':00' : null;
                $sql = "UPDATE sl_annunci SET title = ?, body = ?, author_id = ?, is_published = ?, updated_at = NOW()";
                $params = [$title, $body, $author_id, $is_published];
                if ($created_at) { $sql .= ", created_at = ?"; $params[] = $created_at; }
                $sql .= " WHERE id = ?"; $params[] = $id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                echo json_encode(['success' => true, 'message' => 'Annuncio aggiornato']);
                break;

            case 'delete_annuncio':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'ID annuncio non valido']); break; }
                $pdo->beginTransaction();
                $pdo->prepare("DELETE FROM sl_annunci_likes WHERE annuncio_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM sl_annunci WHERE id = ?")->execute([$id]);
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Annuncio eliminato']);
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
/* Stile migliorato per le intestazioni delle card nell'Admin */
.card-header {
    background: var(--card-bg) !important;
    border-bottom: 1px solid var(--border-color) !important;
    padding: 1rem 1.25rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.75rem !important;
}

.card-header .card-title,
.card-header h5,
.card-header h6 {
    color: var(--text-primary) !important;
    margin: 0 !important;
    font-weight: 700 !important;
}

.card-header .badge,
.card-header .btn {
    margin-left: auto;
}
</style>
<?php
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
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    column-gap: 1rem;
    row-gap: 0.5rem;
    align-items: start;
}

.stat-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--gradient-primary);
    opacity: 0.6;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 34px rgba(0, 0, 0, 0.35);
    border-color: var(--accent-purple);
}

.stat-card h6 {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin: 0 0 0.75rem 0;
    font-weight: 800;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    grid-column: 1 / -1;
}

.stat-card h6 i {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--gradient-primary);
    color: #fff;
    -webkit-text-fill-color: #fff !important;
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.35);
    border: 1px solid rgba(255, 255, 255, 0.15);
}

.stat-number {
    font-size: 2rem;
    font-weight: 800;
    color: var(--text-primary);
}

/* Disporre le voci su una singola riga e prevenire a capo */
.stat-card > div {
    white-space: normal;
    font-weight: 600;
}

@media (max-width: 992px) {
    .stat-card {
        grid-template-columns: repeat(2, minmax(150px, 1fr));
    }
}

@media (max-width: 576px) {
    .stat-card {
        grid-template-columns: 1fr;
    }
}

/* Migliora la leggibilità di "Sponsorizzati" dentro le stat-card */
.stat-card .text-info {
    color: #7dd3fc !important; /* cyan chiaro ad alto contrasto */
    font-weight: 700;
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
/* ======= Admin Dashboard Upgrade ======= */
/* Hero banner */
.admin-hero {
    background: linear-gradient(135deg, rgba(102,126,234,0.25), rgba(118,75,162,0.25)) , var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 1.5rem 1.75rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.admin-hero .hero-title {
    margin: 0;
    font-weight: 800;
    font-size: 1.6rem;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.admin-hero .hero-subtitle {
    margin: 0.25rem 0 0 0;
    color: var(--text-secondary);
    font-size: 0.95rem;
}

.quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.btn-hero {
    border-radius: 10px !important;
    border: 1px solid var(--border-color) !important;
    background: var(--primary-bg) !important;
    color: var(--text-secondary) !important;
}

.btn-hero:hover {
    background: var(--accent-purple) !important;
    color: #fff !important;
    transform: translateY(-2px);
}

/* Metric cards grid */
.admin-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.metric-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.9rem;
    transition: all 0.25s ease;
}

.metric-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 22px rgba(0,0,0,0.25);
    border-color: var(--accent-purple);
}

.metric-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--gradient-primary);
    color: #fff;
    flex-shrink: 0;
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.35);
}

.metric-content {
    flex: 1;
}

.metric-label {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin: 0;
}

.metric-value {
    margin: 0.1rem 0 0 0;
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--text-primary);
}

.metric-meta {
    color: var(--text-muted);
    font-size: 0.85rem;
}

@media (max-width: 768px) {
    .admin-hero {
        flex-direction: column;
        align-items: flex-start;
    }
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
                    <a href="?action=annunci" class="admin-nav-item <?= $action === 'annunci' ? 'active' : '' ?>">
                        <i class="bi bi-megaphone"></i> Gestione Annunci
                    </a>
                    <a href="?action=sponsors" class="admin-nav-item <?= $action === 'sponsors' ? 'active' : '' ?>">
                        <i class="bi bi-star"></i> Gestione Sponsor
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
                        case 'sponsors':
                            include_sponsors();
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
                        case 'annunci':
                            include_annunci();
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
            element.setAttribute('data-count', value);
            animateCount(element, parseInt(value));
        }
    }
}

// Animazione contatori
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-count]').forEach(el => {
        const target = parseInt(el.getAttribute('data-count')) || parseInt(el.textContent) || 0;
        animateCount(el, target);
    });
});

function animateCount(el, target) {
    const start = parseInt(el.textContent) || 0;
    const duration = 800;
    const startTime = performance.now();
    function tick(now) {
        const progress = Math.min((now - startTime) / duration, 1);
        const value = Math.floor(start + (target - start) * progress);
        el.textContent = value;
        if (progress < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
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
    
    <div class="admin-hero">
        <div>
            <h2 class="hero-title"><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>
            <p class="hero-subtitle">Panoramica e controllo rapido del sistema</p>
        </div>
        <div class="quick-actions">
            <a href="?action=users" class="btn btn-hero"><i class="bi bi-people"></i> Utenti</a>
            <a href="?action=servers" class="btn btn-hero"><i class="bi bi-server"></i> Server</a>
            <a href="?action=licenses" class="btn btn-hero"><i class="bi bi-key"></i> Licenze</a>
            <a href="?action=rewards" class="btn btn-hero"><i class="bi bi-gift"></i> Reward</a>
        </div>
    </div>

    <!-- Statistiche principali - nuove metriche -->
    <div class="admin-stats-grid">
        <div class="metric-card">
            <div class="metric-icon"><i class="bi bi-people-fill"></i></div>
            <div class="metric-content">
                <p class="metric-label">Utenti Totali</p>
                <h3 class="metric-value" id="total_users" data-count="<?= (int)$stats['total_users'] ?>"><?= (int)$stats['total_users'] ?></h3>
                <span class="metric-meta">Admin: <?= (int)$stats['admin_users'] ?></span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon"><i class="bi bi-server"></i></div>
            <div class="metric-content">
                <p class="metric-label">Server Totali</p>
                <h3 class="metric-value" id="total_servers" data-count="<?= (int)$stats['total_servers'] ?>"><?= (int)$stats['total_servers'] ?></h3>
                <span class="metric-meta">Attivi: <?= (int)$stats['active_servers'] ?></span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon"><i class="bi bi-hand-thumbs-up"></i></div>
            <div class="metric-content">
                <p class="metric-label">Voti Totali</p>
                <h3 class="metric-value" id="total_votes" data-count="<?= (int)$stats['total_votes'] ?>"><?= (int)$stats['total_votes'] ?></h3>
                <span class="metric-meta">Oggi: <?= (int)$stats['today_votes'] ?></span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon"><i class="bi bi-key-fill"></i></div>
            <div class="metric-content">
                <p class="metric-label">Licenze</p>
                <h3 class="metric-value" id="total_licenses" data-count="<?= (int)$stats['total_licenses'] ?>"><?= (int)$stats['total_licenses'] ?></h3>
                <span class="metric-meta">Attive: <?= (int)$stats['active_licenses'] ?></span>
            </div>
        </div>
    </div>
    
    <!-- Statistiche dettagliate -->
    <div class="admin-stats-grid">
        <div class="stat-card">
            <h6><i class="bi bi-bar-chart"></i> Server per Stato</h6>
            <div class="text-success">Attivi: <?= $stats['active_servers'] ?></div>
            <div class="text-warning">In Attesa: <?= $stats['pending_servers'] ?></div>
            <div class="text-danger">Disabilitati: <?= $stats['disabled_servers'] ?></div>
            <div class="text-info">Sponsorizzati: <?= $stats['sponsored_servers'] ?></div>
        </div>
        <div class="stat-card">
            <h6><i class="bi bi-gift"></i> Reward Status</h6>
            <div class="text-success">Successo: <?= $stats['successful_rewards'] ?></div>
            <div class="text-danger">Errori: <?= $stats['failed_rewards'] ?></div>
            <div class="text-warning">Codici Pending: <?= $stats['pending_vote_codes'] ?></div>
        </div>
        <div class="stat-card">
            <h6><i class="bi bi-cpu"></i> Sistema</h6>
            <div class="text-info">Uptime: Online</div>
            <div class="text-success">Database: OK</div>
            <div class="text-primary">Ultimo aggiornamento: <?= date('H:i:s') ?></div>
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

    // Metriche per header utenti
    $admin_count = (int)$pdo->query("SELECT COUNT(*) FROM sl_users WHERE is_admin = 1")->fetchColumn();
    $users_with_servers = (int)$pdo->query("SELECT COUNT(DISTINCT owner_id) FROM sl_servers WHERE owner_id IS NOT NULL")->fetchColumn();
    $users_with_votes = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM sl_votes")->fetchColumn();
    ?>
    
    <!-- Header moderno utenti -->
    <div class="admin-hero mb-3">
        <div>
            <h2 class="hero-title"><i class="bi bi-people"></i> Gestione Utenti</h2>
            <p class="hero-subtitle">Gestisci ruoli, attività e overview utenti</p>
        </div>
    </div>

    <!-- Metriche utenti -->
    <div class="admin-stats-grid mb-4">
        <div class="metric-card">
            <div class="metric-icon"><i class="bi bi-people-fill"></i></div>
            <div class="metric-content">
                <p class="metric-label">Utenti Totali</p>
                <h3 class="metric-value"><?= (int)$total_users ?></h3>
                <span class="metric-meta">Admin: <?= (int)$admin_count ?></span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon"><i class="bi bi-hdd-network"></i></div>
            <div class="metric-content">
                <p class="metric-label">Utenti con Server</p>
                <h3 class="metric-value"><?= (int)$users_with_servers ?></h3>
                <span class="metric-meta">Owner attivi</span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon"><i class="bi bi-hand-thumbs-up"></i></div>
            <div class="metric-content">
                <p class="metric-label">Utenti con Voti</p>
                <h3 class="metric-value"><?= (int)$users_with_votes ?></h3>
                <span class="metric-meta">Partecipazione</span>
            </div>
        </div>
    </div>
    
    <!-- Filtri moderni -->
    <div class="card bg-dark border-secondary mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <form method="GET" class="d-flex">
                        <input type="hidden" name="action" value="users">
                        <input type="text" name="search" class="form-control me-2" placeholder="Cerca minecraft nick..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary btn-admin"><i class="bi bi-search"></i> Cerca</button>
                    </form>
                </div>
                <div class="col-md-3">
                    <form method="GET" class="d-flex">
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
                    <span class="text-secondary"><i class="bi bi-person-lines-fill"></i> Totale: <?= $total_users ?> utenti</span>
                </div>
            </div>
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

    // Metriche per header server
    $active_servers = (int)$pdo->query("SELECT COUNT(*) FROM sl_servers WHERE is_active = 1")->fetchColumn();
    $pending_servers = (int)$pdo->query("SELECT COUNT(*) FROM sl_servers WHERE is_active = 2")->fetchColumn();
    $disabled_servers = (int)$pdo->query("SELECT COUNT(*) FROM sl_servers WHERE is_active = 0")->fetchColumn();
    $sponsored_servers = (int)$pdo->query("SELECT COUNT(*) FROM sl_sponsored_servers WHERE is_active = 1")->fetchColumn();
    ?>
    
    <!-- Header moderno server -->
    <div class="admin-hero mb-3">
        <div>
            <h2 class="hero-title"><i class="bi bi-server"></i> Gestione Server</h2>
            <p class="hero-subtitle">Gestisci stato, sponsorizzazioni e performance</p>
        </div>
    </div>

    <!-- Metriche server -->
    <div class="admin-stats-grid mb-4">
        <div class="metric-card">
            <div class="metric-icon"><i class="bi bi-hdd-network"></i></div>
            <div class="metric-content">
                <p class="metric-label">Server Totali</p>
                <h3 class="metric-value"><?= (int)$total_servers ?></h3>
                <span class="metric-meta">Sponsored: <?= (int)$sponsored_servers ?></span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon"><i class="bi bi-check-circle"></i></div>
            <div class="metric-content">
                <p class="metric-label">Attivi</p>
                <h3 class="metric-value"><?= (int)$active_servers ?></h3>
                <span class="metric-meta">Online</span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="metric-content">
                <p class="metric-label">In Attesa</p>
                <h3 class="metric-value"><?= (int)$pending_servers ?></h3>
                <span class="metric-meta">Review</span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon"><i class="bi bi-slash-circle"></i></div>
            <div class="metric-content">
                <p class="metric-label">Disabilitati</p>
                <h3 class="metric-value"><?= (int)$disabled_servers ?></h3>
                <span class="metric-meta">Offline</span>
            </div>
        </div>
    </div>
    
    <!-- Filtri moderni -->
    <div class="card bg-dark border-secondary mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <form method="GET" class="d-flex">
                        <input type="hidden" name="action" value="servers">
                        <input type="text" name="search" class="form-control me-2" placeholder="Cerca nome, IP o descrizione..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary btn-admin"><i class="bi bi-search"></i> Cerca</button>
                    </form>
                </div>
                <div class="col-md-3">
                    <form method="GET" class="d-flex">
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
                    <span class="text-secondary"><i class="bi bi-hdd-network"></i> Totale: <?= $total_servers ?> server</span>
                </div>
            </div>
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

    function toggleSponsorById(sponsorId) {
        confirmAction('Sei sicuro di voler modificare lo stato di sponsorizzazione?', () => {
            makeAjaxRequest('toggle_sponsorship', {
                id: sponsorId
            }, (response) => {
                if (response.success) {
                    showAlert(response.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(response.message || 'Errore nel toggle sponsor', 'danger');
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

function include_sponsors() {
    global $pdo;

    $page = (int)($_GET['page'] ?? 1);
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    $status_filter = $_GET['status_filter'] ?? '';
    $expiry_filter = $_GET['expiry_filter'] ?? '';

    // Costruisci condizioni WHERE
    $where_conditions = [];
    $params = [];
    if ($search) {
        $where_conditions[] = "(s.nome LIKE ? OR s.ip LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($status_filter !== '') {
        $where_conditions[] = "ss.is_active = ?";
        $params[] = (int)$status_filter;
    }
    if ($expiry_filter === 'expired') {
        $where_conditions[] = "ss.expires_at IS NOT NULL AND ss.expires_at <= NOW()";
    } elseif ($expiry_filter === 'valid') {
        $where_conditions[] = "(ss.expires_at IS NULL OR ss.expires_at > NOW())";
    }
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Query sponsorships
    $query = "
        SELECT ss.*, s.nome, s.ip, s.is_active AS server_status
        FROM sl_sponsored_servers ss
        JOIN sl_servers s ON s.id = ss.server_id
        $where_clause
        ORDER BY ss.priority ASC, ss.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $sponsors = $stmt->fetchAll();

    // Conteggio totale per paginazione
    $count_query = "
        SELECT COUNT(*)
        FROM sl_sponsored_servers ss
        JOIN sl_servers s ON s.id = ss.server_id
        $where_clause
    ";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_sponsors = (int)$stmt->fetchColumn();
    $total_pages = ceil($total_sponsors / $limit);

    // Statistiche
    $stats = [
        'total' => (int)$pdo->query("SELECT COUNT(*) FROM sl_sponsored_servers")->fetchColumn(),
        'active' => (int)$pdo->query("SELECT COUNT(*) FROM sl_sponsored_servers WHERE is_active = 1")->fetchColumn(),
        'expired' => (int)$pdo->query("SELECT COUNT(*) FROM sl_sponsored_servers WHERE expires_at IS NOT NULL AND expires_at <= NOW() ")->fetchColumn(),
    ];
    ?>

    <div class="admin-hero mb-3">
        <div>
            <h2 class="hero-title"><i class="bi bi-star"></i> Gestione Sponsor</h2>
            <p class="hero-subtitle">Crea, aggiorna e monitora sponsorizzazioni dei server</p>
        </div>
        <div>
            <button class="btn btn-warning btn-admin" data-bs-toggle="modal" data-bs-target="#addSponsorModal">
                <i class="bi bi-plus-lg"></i> Aggiungi Sponsor
            </button>
        </div>
    </div>

    <div class="admin-stats-grid mb-4">
        <div class="metric-card">
            <div class="metric-icon"><i class="bi bi-collection"></i></div>
            <div class="metric-content">
                <p class="metric-label">Sponsor Totali</p>
                <h3 class="metric-value"><?= (int)$stats['total'] ?></h3>
                <span class="metric-meta">Filtrati: <?= (int)$total_sponsors ?></span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon"><i class="bi bi-check-circle"></i></div>
            <div class="metric-content">
                <p class="metric-label">Attivi</p>
                <h3 class="metric-value"><?= (int)$stats['active'] ?></h3>
                <span class="metric-meta">Online</span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon"><i class="bi bi-clock-history"></i></div>
            <div class="metric-content">
                <p class="metric-label">Scaduti</p>
                <h3 class="metric-value"><?= (int)$stats['expired'] ?></h3>
                <span class="metric-meta">Expired</span>
            </div>
        </div>
    </div>

    <div class="card bg-dark border-secondary mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <form method="GET" class="d-flex">
                        <input type="hidden" name="action" value="sponsors">
                        <input type="text" name="search" class="form-control me-2" placeholder="Cerca per nome o IP..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary btn-admin">Cerca</button>
                    </form>
                </div>
                <div class="col-md-3">
                    <form method="GET">
                        <input type="hidden" name="action" value="sponsors">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <select name="status_filter" class="form-select" onchange="this.form.submit()">
                            <option value="">Tutti gli stati</option>
                            <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Attivi</option>
                            <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Disattivati</option>
                        </select>
                    </form>
                </div>
                <div class="col-md-3">
                    <form method="GET">
                        <input type="hidden" name="action" value="sponsors">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <input type="hidden" name="status_filter" value="<?= htmlspecialchars($status_filter) ?>">
                        <select name="expiry_filter" class="form-select" onchange="this.form.submit()">
                            <option value="">Valide e scadute</option>
                            <option value="valid" <?= $expiry_filter === 'valid' ? 'selected' : '' ?>>Solo valide</option>
                            <option value="expired" <?= $expiry_filter === 'expired' ? 'selected' : '' ?>>Solo scadute</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="data-table">
        <div class="card-header bg-transparent border-bottom">
            <h6 class="mb-0"><i class="bi bi-star"></i> Sponsorizzazioni</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Server</th>
                        <th>Priorità</th>
                        <th>Stato Sponsor</th>
                        <th>Scadenza</th>
                        <th>Creato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sponsors as $sp): ?>
                    <tr>
                        <td><?= (int)$sp['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($sp['nome']) ?></strong>
                            <div><code><?= htmlspecialchars($sp['ip']) ?></code></div>
                            <small class="text-secondary">Server <?= (int)$sp['server_status'] === 1 ? 'attivo' : 'non attivo' ?></small>
                        </td>
                        <td style="max-width:120px;">
                            <input type="number" class="form-control form-control-sm" id="priority-<?= (int)$sp['id'] ?>" value="<?= (int)$sp['priority'] ?>" min="1">
                        </td>
                        <td>
                            <?php if ((int)$sp['is_active'] === 1): ?>
                                <span class="badge bg-success">Attivo</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Disattivato</span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:180px;">
                            <input type="date" class="form-control form-control-sm" id="expires-<?= (int)$sp['id'] ?>" value="<?= !empty($sp['expires_at']) ? date('Y-m-d', strtotime($sp['expires_at'])) : '' ?>">
                            <small class="text-secondary">Vuoto = senza scadenza</small>
                        </td>
                        <td><small><?= htmlspecialchars(date('Y-m-d H:i', strtotime($sp['created_at']))) ?></small></td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-primary btn-admin" onclick="updateSponsorship(<?= (int)$sp['id'] ?>)"><i class="bi bi-save"></i></button>
                                <button class="btn btn-sm btn-warning btn-admin" onclick="toggleSponsorById(<?= (int)$sp['id'] ?>)"><i class="bi bi-toggle2-on"></i></button>
                                <button class="btn btn-sm btn-outline-danger btn-admin" onclick="deleteSponsorship(<?= (int)$sp['id'] ?>)"><i class="bi bi-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?action=sponsors&page=<?= $i ?>&search=<?= urlencode($search) ?>&status_filter=<?= urlencode($status_filter) ?>&expiry_filter=<?= urlencode($expiry_filter) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>

    <!-- Modal Aggiungi Sponsor -->
    <div class="modal fade" id="addSponsorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title"><i class="bi bi-star"></i> Aggiungi Sponsor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 position-relative">
                        <label class="form-label">Server</label>
                        <input type="text" id="sponsorServerInput" class="form-control" placeholder="Cerca server attivo per nome o ID...">
                        <input type="hidden" id="selectedSponsorServerId" value="">
                        <div id="sponsorServerDropdown" class="dropdown-menu show" style="display:none; position:absolute; width:100%; max-height:220px; overflow:auto;">
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Priorità</label>
                            <input type="number" id="sponsorPriority" class="form-control" value="1" min="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Scadenza (opzionale)</label>
                            <input type="date" id="sponsorExpires" class="form-control">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button class="btn btn-warning btn-admin w-100" onclick="addSponsorship()"><i class="bi bi-plus-lg"></i> Crea Sponsor</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function initSponsorAutocomplete() {
        const input = document.getElementById('sponsorServerInput');
        const dropdown = document.getElementById('sponsorServerDropdown');
        let timeout;
        input.addEventListener('input', function() {
            const q = this.value.trim();
            document.getElementById('selectedSponsorServerId').value = '';
            if (timeout) clearTimeout(timeout);
            if (q.length < 1) { dropdown.style.display = 'none'; return; }
            timeout = setTimeout(() => {
                makeAjaxRequest('search_servers', { search: q }, (response) => {
                    if (response.success) {
                        if (!response.servers || response.servers.length === 0) {
                            dropdown.innerHTML = '<div class="dropdown-item text-secondary">Nessun server trovato</div>';
                            dropdown.style.display = 'block';
                        } else {
                            let html = '';
                            response.servers.forEach(s => {
                                html += `
                                    <div class="dropdown-item sponsor-option" data-id="${s.id}" data-name="${s.nome}" style="cursor:pointer;">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span><strong>${s.nome}</strong></span>
                                            <small class="text-secondary">ID: ${s.id}</small>
                                        </div>
                                    </div>
                                `;
                            });
                            dropdown.innerHTML = html;
                            dropdown.style.display = 'block';
                            dropdown.querySelectorAll('.sponsor-option').forEach(opt => {
                                opt.addEventListener('click', function() {
                                    document.getElementById('sponsorServerInput').value = this.dataset.name;
                                    document.getElementById('selectedSponsorServerId').value = this.dataset.id;
                                    dropdown.style.display = 'none';
                                });
                            });
                        }
                    }
                });
            }, 300);
        });
        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    }

    document.getElementById('addSponsorModal').addEventListener('shown.bs.modal', function() {
        initSponsorAutocomplete();
        document.getElementById('sponsorServerInput').focus();
    });
    document.getElementById('addSponsorModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('sponsorServerInput').value = '';
        document.getElementById('selectedSponsorServerId').value = '';
        document.getElementById('sponsorPriority').value = 1;
        document.getElementById('sponsorExpires').value = '';
        document.getElementById('sponsorServerDropdown').style.display = 'none';
    });

    function addSponsorship() {
        const serverId = document.getElementById('selectedSponsorServerId').value;
        const priority = parseInt(document.getElementById('sponsorPriority').value || '1', 10);
        const expires = document.getElementById('sponsorExpires').value;
        if (!serverId) { showAlert('Seleziona un server valido', 'warning'); return; }
        makeAjaxRequest('add_sponsorship', { server_id: serverId, priority: priority, expires_at: expires }, (response) => {
            if (response.success) {
                showAlert(response.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showAlert(response.message || 'Errore durante la creazione sponsor', 'danger');
            }
        });
    }

    function updateSponsorship(id) {
        const pr = parseInt(document.getElementById('priority-' + id).value || '1', 10);
        const ex = document.getElementById('expires-' + id).value;
        makeAjaxRequest('update_sponsorship', { id: id, priority: pr, expires_at: ex }, (response) => {
            if (response.success) { showAlert(response.message, 'success'); }
            else { showAlert(response.message || 'Errore aggiornamento sponsor', 'danger'); }
        });
    }

    function deleteSponsorship(id) {
        confirmAction('Eliminare questo sponsor?', () => {
            makeAjaxRequest('delete_sponsorship', { id: id }, (response) => {
                if (response.success) { showAlert(response.message, 'success'); setTimeout(() => location.reload(), 800); }
                else { showAlert(response.message || 'Errore eliminazione sponsor', 'danger'); }
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
                        <td>
                            <?php
                                $dt = new DateTime($vote['data_voto'], new DateTimeZone('UTC'));
                                $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
                                echo $dt->format('d/m/Y H:i');
                            ?>
                        </td>
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
        $where_conditions[] = "(u.minecraft_nick LIKE ? OR s.nome LIKE ? OR rl.commands_executed LIKE ?)";
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
                         <th>Tipo</th>
                         <th>Stato</th>
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
                             <?php
                                 $tipo = 'N/A';
                                 $executedRaw = $reward['commands_executed'] ?? '';
                                 if (!empty($executedRaw)) {
                                     $decoded = json_decode($executedRaw, true);
                                     if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                         if (!empty($decoded['auto_distributed'])) {
                                             $tipo = 'Auto Distribuito';
                                         }
                                     }
                                 }
                             ?>
                             <code><?= htmlspecialchars($tipo) ?></code>
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
                             <?php
                                 $dt = new DateTime($reward['executed_at'], new DateTimeZone('UTC'));
                                 $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
                                 echo $dt->format('d/m/Y H:i');
                             ?>
                         </td>
                         <td>
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
        
        // Ottieni server senza alcuna licenza (attivi e inattivi), per allineare lista e statistica "Senza Licenza"
        $stmt = $pdo->prepare("
            SELECT s.id, s.nome, s.data_inserimento, s.is_active
            FROM sl_servers s
            LEFT JOIN sl_server_licenses sl ON s.id = sl.server_id
            WHERE sl.server_id IS NULL
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
    
    <!-- Statistiche Licenze (Metric Cards) -->
    <div class="admin-stats-grid mb-4">
        <div class="metric-card">
            <div class="metric-icon"><i class="bi bi-collection"></i></div>
            <div class="metric-content">
                <p class="metric-label">Server Totali</p>
                <h3 class="metric-value text-light"><?= (int)$license_stats['total_servers'] ?></h3>
                <span class="metric-meta">Registrati</span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon"><i class="bi bi-key"></i></div>
            <div class="metric-content">
                <p class="metric-label">Licenze Attive</p>
                <h3 class="metric-value text-success"><?= (int)$license_stats['with_active_license'] ?></h3>
                <span class="metric-meta">Valide</span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon"><i class="bi bi-slash-circle"></i></div>
            <div class="metric-content">
                <p class="metric-label">Licenze Disattivate</p>
                <h3 class="metric-value text-warning"><?= (int)$license_stats['with_inactive_license'] ?></h3>
                <span class="metric-meta">Non attive</span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="metric-content">
                <p class="metric-label">Senza Licenza</p>
                <h3 class="metric-value text-danger"><?= (int)$license_stats['without_license'] ?></h3>
                <span class="metric-meta">Da generare</span>
            </div>
        </div>
    </div>
    
    <!-- Server Senza Licenza -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning alert-persistent" style="border-radius: 8px; border: 1px solid #ffc107; box-shadow: 0 2px 8px rgba(0,0,0,0.1); font-weight: 500; position: relative; z-index: 1050;">
                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Server Senza Licenza (<?= count($servers_without_license) ?>)</h5>
                <?php if (!empty($servers_without_license)): ?>
                    <p>I seguenti server non hanno alcuna licenza:</p>
                    <div class="row">
                        <?php foreach ($servers_without_license as $server): ?>
                            <div class="col-md-6 col-lg-4 mb-2">
                                <div class="d-flex justify-content-between align-items-center bg-dark p-2 rounded">
                                    <div>
                                        <strong><?= htmlspecialchars($server['nome']) ?></strong>
                                        <?php if ((int)$server['is_active'] === 0): ?>
                                            <span class="badge bg-secondary ms-2">Inattivo</span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-secondary">ID: <?= $server['id'] ?> - <?= date('d/m/Y', strtotime($server['data_inserimento'])) ?></small>
                                    </div>
                                    <?php if ((int)$server['is_active'] === 1): ?>
                                        <button class="btn btn-sm btn-success" onclick="generateLicenseForServer(<?= $server['id'] ?>, '<?= htmlspecialchars($server['nome']) ?>')">
                                            <i class="bi bi-plus"></i> Genera
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary" disabled title="Server inattivo">
                                            <i class="bi bi-slash-circle"></i> Inattivo
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="mb-2">Tutti i server attivi hanno già una licenza.</p>
                    <button class="btn btn-sm btn-success" onclick="generateLicense()">
                        <i class="bi bi-plus"></i> Genera Licenza
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
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

<?php
function include_annunci() {
    global $pdo;
    // Crea tabelle se non esistono (robustezza)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS sl_annunci (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            body TEXT NOT NULL,
            author_id INT NOT NULL,
            is_published TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            INDEX(author_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS sl_annunci_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            annuncio_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_like (annuncio_id, user_id),
            INDEX(annuncio_id),
            INDEX(user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {}

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;

    // Utenti per selezione autore
    $users = [];
    try {
        $users = $pdo->query("SELECT id, minecraft_nick FROM sl_users ORDER BY minecraft_nick ASC")->fetchAll();
    } catch (Exception $e) { $users = []; }

    // Annunci con conteggio like
    $where = '';
    $params = [];
    if ($search !== '') {
        $where = 'WHERE (a.title LIKE ? OR a.body LIKE ? OR u.minecraft_nick LIKE ?)';
        $params = ['%'.$search.'%', '%'.$search.'%', '%'.$search.'%'];
    }
    $sql = "
        SELECT a.*, u.minecraft_nick,
               COALESCE(l.cnt, 0) as likes
        FROM sl_annunci a
        JOIN sl_users u ON u.id = a.author_id
        LEFT JOIN (
            SELECT annuncio_id, COUNT(*) AS cnt
            FROM sl_annunci_likes
            GROUP BY annuncio_id
        ) l ON l.annuncio_id = a.id
        $where
        ORDER BY a.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $annunci = $stmt->fetchAll();

    $count_sql = "SELECT COUNT(*) FROM sl_annunci a JOIN sl_users u ON u.id = a.author_id $where";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $total_pages = max(1, (int)ceil($total / $limit));
    ?>

    <h2><i class="bi bi-megaphone"></i> Gestione Annunci</h2>
    <p class="text-secondary">Crea, modifica e pubblica annunci. Gli utenti possono mettere like.</p>

    <div class="row mb-3">
        <div class="col-md-6">
            <form method="GET" class="d-flex">
                <input type="hidden" name="action" value="annunci">
                <input type="text" name="search" class="form-control me-2" placeholder="Cerca per titolo, testo o autore" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary btn-admin"><i class="bi bi-search"></i> Cerca</button>
            </form>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-success btn-admin" onclick="openCreateAnnuncio()"><i class="bi bi-plus-circle"></i> Nuovo Annuncio</button>
        </div>
    </div>

    <div class="data-table">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titolo</th>
                        <th>Autore</th>
                        <th>Likes</th>
                        <th>Stato</th>
                        <th>Creato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($annunci as $a): 
                        $avatar = getMinecraftAvatar($a['minecraft_nick'], 24);
                        $createdVal = date('Y-m-d\TH:i', strtotime($a['created_at']));
                    ?>
                        <tr>
                            <td><?= (int)$a['id'] ?></td>
                            <td><?= htmlspecialchars($a['title']) ?></td>
                            <td>
                                <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" width="24" height="24" class="rounded-circle me-1">
                                <?= htmlspecialchars($a['minecraft_nick']) ?>
                            </td>
                            <td><i class="bi bi-heart-fill text-danger"></i> <?= (int)$a['likes'] ?></td>
                            <td>
                                <?php if ((int)$a['is_published'] === 1): ?>
                                    <span class="badge bg-success">Pubblicato</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Bozza</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($a['created_at'])) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary btn-admin"
                                        data-id="<?= (int)$a['id'] ?>"
                                        data-title="<?= htmlspecialchars($a['title']) ?>"
                                        data-body="<?= htmlspecialchars($a['body']) ?>"
                                        data-author="<?= (int)$a['author_id'] ?>"
                                        data-published="<?= (int)$a['is_published'] ?>"
                                        data-created="<?= htmlspecialchars($createdVal) ?>"
                                        onclick="editAnnuncioFromButton(this)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger btn-admin" onclick="deleteAnnuncio(<?= (int)$a['id'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($total_pages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?action=annunci&page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>

    <!-- Modal Annuncio -->
    <div class="modal fade" id="annuncioModal" tabindex="-1" aria-labelledby="annuncioModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title" id="annuncioModalLabel">Nuovo Annuncio</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="annuncioForm">
                        <input type="hidden" id="annuncioId">
                        <div class="mb-3">
                            <label class="form-label">Titolo</label>
                            <input type="text" class="form-control" id="annuncioTitolo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Autore</label>
                            <select class="form-select" id="annuncioAutore" required>
                                <option value="">Seleziona autore</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['minecraft_nick']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stato</label>
                            <select class="form-select" id="annuncioPubblicato">
                                <option value="1">Pubblicato</option>
                                <option value="0">Bozza</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Data di creazione</label>
                            <input type="datetime-local" class="form-control" id="annuncioCreatedAt">
                            <div class="form-text">Lascia vuoto per usare l'ora attuale.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contenuto</label>
                            <textarea class="form-control" id="annuncioBody" rows="6" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" class="btn btn-primary" onclick="submitAnnuncio()">Salva</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function openCreateAnnuncio() {
        document.getElementById('annuncioModalLabel').textContent = 'Nuovo Annuncio';
        document.getElementById('annuncioId').value = '';
        document.getElementById('annuncioTitolo').value = '';
        document.getElementById('annuncioAutore').value = '';
        document.getElementById('annuncioPubblicato').value = '1';
        document.getElementById('annuncioCreatedAt').value = '';
        document.getElementById('annuncioBody').value = '';
        new bootstrap.Modal(document.getElementById('annuncioModal')).show();
    }

    function editAnnuncioFromButton(btn) {
        document.getElementById('annuncioModalLabel').textContent = 'Modifica Annuncio';
        document.getElementById('annuncioId').value = btn.dataset.id;
        document.getElementById('annuncioTitolo').value = btn.dataset.title;
        document.getElementById('annuncioAutore').value = btn.dataset.author;
        document.getElementById('annuncioPubblicato').value = btn.dataset.published;
        document.getElementById('annuncioCreatedAt').value = btn.dataset.created || '';
        document.getElementById('annuncioBody').value = btn.dataset.body;
        new bootstrap.Modal(document.getElementById('annuncioModal')).show();
    }

    function submitAnnuncio() {
        const id = document.getElementById('annuncioId').value.trim();
        const title = document.getElementById('annuncioTitolo').value.trim();
        const author_id = document.getElementById('annuncioAutore').value;
        const is_published = document.getElementById('annuncioPubblicato').value;
        const created_at = document.getElementById('annuncioCreatedAt').value.trim();
        const body = document.getElementById('annuncioBody').value.trim();
        if (!title || !author_id || !body) {
            showAlert('Titolo, autore e contenuto sono obbligatori', 'warning');
            return;
        }
        const action = id ? 'update_annuncio' : 'create_annuncio';
        makeAjaxRequest(action, { id, title, author_id, is_published, created_at, body }, (response) => {
            if (response.success) {
                showAlert(response.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('annuncioModal')).hide();
                setTimeout(() => location.reload(), 800);
            } else {
                showAlert(response.message || 'Errore', 'danger');
            }
        });
    }

    function deleteAnnuncio(id) {
        confirmAction('Eliminare definitivamente questo annuncio?', () => {
            makeAjaxRequest('delete_annuncio', { id }, (response) => {
                if (response.success) {
                    showAlert(response.message, 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showAlert(response.message || 'Errore', 'danger');
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