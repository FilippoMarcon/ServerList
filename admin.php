<?php
/**
 * Pannello di Amministrazione
 * Admin Panel
 * Gestione dei server Minecraft
 */

require_once 'config.php';

// Controlla se l'utente è loggato e se è un amministratore
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Gestione delle azioni admin
$action = isset($_GET['action']) ? sanitize($_GET['action']) : 'list';
$message = '';
$error = '';

// Gestione delle azioni CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'add':
            // Aggiungi nuovo server
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
                    $action = 'list';
                } catch (PDOException $e) {
                    $error = 'Errore durante l\'aggiunta del server.';
                }
            }
            break;
            
        case 'edit':
            // Modifica server esistente
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
                    $action = 'list';
                } catch (PDOException $e) {
                    $error = 'Errore durante la modifica del server.';
                }
            }
            break;
            
        case 'delete':
            // Elimina server
            $server_id = (int)($_POST['server_id'] ?? 0);
            
            if ($server_id === 0) {
                $error = 'ID server non valido.';
            } else {
                try {
                    // Prima elimina i voti associati
                    $stmt = $pdo->prepare("DELETE FROM sl_votes WHERE server_id = ?");
                    $stmt->execute([$server_id]);
                    
                    // Poi elimina il server
                    $stmt = $pdo->prepare("DELETE FROM sl_servers WHERE id = ?");
                    $stmt->execute([$server_id]);
                    
                    $message = 'Server eliminato con successo!';
                    $action = 'list';
                } catch (PDOException $e) {
                    $error = 'Errore durante l\'eliminazione del server.';
                }
            }
            break;
            
        case 'sponsored_add':
            // Aggiungi server sponsorizzato
            $server_id = (int)($_POST['server_id'] ?? 0);
            $sponsor_priority = (int)($_POST['sponsor_priority'] ?? 1);
            $sponsor_expires_at = sanitize($_POST['sponsor_expires_at'] ?? '');
            
            if ($server_id === 0 || empty($sponsor_expires_at)) {
                $error = 'Server e data di scadenza sono obbligatori.';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE sl_servers SET is_sponsored = 1, sponsor_priority = ?, sponsor_expires_at = ? WHERE id = ?");
                    $stmt->execute([$sponsor_priority, $sponsor_expires_at, $server_id]);
                    
                    // Log dell'azione
                    $log_stmt = $pdo->prepare("INSERT INTO sl_sponsor_logs (server_id, action, admin_user_id, action_date) VALUES (?, 'add', ?, NOW())");
                    $log_stmt->execute([$server_id, $_SESSION['user_id']]);
                    
                    $message = 'Server sponsorizzato aggiunto con successo!';
                    $action = 'sponsored';
                } catch (PDOException $e) {
                    $error = 'Errore durante l\'aggiunta del server sponsorizzato.';
                }
            }
            break;
            
        case 'sponsored_edit':
            // Modifica server sponsorizzato
            $server_id = (int)($_POST['server_id'] ?? 0);
            $sponsor_priority = (int)($_POST['sponsor_priority'] ?? 1);
            $sponsor_expires_at = sanitize($_POST['sponsor_expires_at'] ?? '');
            
            if ($server_id === 0 || empty($sponsor_expires_at)) {
                $error = 'Server e data di scadenza sono obbligatori.';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE sl_servers SET sponsor_priority = ?, sponsor_expires_at = ? WHERE id = ?");
                    $stmt->execute([$sponsor_priority, $sponsor_expires_at, $server_id]);
                    
                    // Log dell'azione
                    $log_stmt = $pdo->prepare("INSERT INTO sl_sponsor_logs (server_id, action, admin_user_id, action_date) VALUES (?, 'edit', ?, NOW())");
                    $log_stmt->execute([$server_id, $_SESSION['user_id']]);
                    
                    $message = 'Server sponsorizzato modificato con successo!';
                    $action = 'sponsored';
                } catch (PDOException $e) {
                    $error = 'Errore durante la modifica del server sponsorizzato.';
                }
            }
            break;
            
        case 'sponsored_remove':
            // Rimuovi server sponsorizzato
            $server_id = (int)($_POST['server_id'] ?? 0);
            
            if ($server_id === 0) {
                $error = 'ID server non valido.';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE sl_servers SET is_sponsored = 0, sponsor_priority = 0, sponsor_expires_at = NULL WHERE id = ?");
                    $stmt->execute([$server_id]);
                    
                    // Log dell'azione
                    $log_stmt = $pdo->prepare("INSERT INTO sl_sponsor_logs (server_id, action, admin_user_id, action_date) VALUES (?, 'remove', ?, NOW())");
                    $log_stmt->execute([$server_id, $_SESSION['user_id']]);
                    
                    $message = 'Server sponsorizzato rimosso con successo!';
                    $action = 'sponsored';
                } catch (PDOException $e) {
                    $error = 'Errore durante la rimozione del server sponsorizzato.';
                }
            }
            break;
    }
}

// Recupera i dati in base all'azione
$servers = [];
$server_to_edit = null;

if ($action === 'list' || $action === 'delete') {
    // Recupera tutti i server con statistiche
    try {
        $stmt = $pdo->query("SELECT s.*, COALESCE(v.vote_count, 0) as vote_count, 
                                    COALESCE(v.today_votes, 0) as today_votes 
                             FROM sl_servers s 
                             LEFT JOIN (
                                 SELECT server_id, COUNT(*) as vote_count, 
                                        SUM(CASE WHEN DATE(data_voto) = CURDATE() THEN 1 ELSE 0 END) as today_votes 
                                 FROM sl_votes 
                                 GROUP BY server_id
                             ) v ON s.id = v.server_id 
                             ORDER BY s.data_inserimento DESC");
        $servers = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = 'Errore nel recupero dei server.';
    }
} elseif ($action === 'edit') {
    // Recupera il server da modificare
    $server_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($server_id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM sl_servers WHERE id = ?");
            $stmt->execute([$server_id]);
            $server_to_edit = $stmt->fetch();
            
            if (!$server_to_edit) {
                $error = 'Server non trovato.';
                $action = 'list';
            }
        } catch (PDOException $e) {
            $error = 'Errore nel recupero del server.';
            $action = 'list';
        }
    }
} elseif ($action === 'sponsored') {
    // Recupera server sponsorizzati e non sponsorizzati
    try {
        // Server sponsorizzati attivi
        $stmt = $pdo->query("SELECT s.*, COALESCE(v.vote_count, 0) as vote_count 
                             FROM sl_servers s 
                             LEFT JOIN (
                                 SELECT server_id, COUNT(*) as vote_count 
                                 FROM sl_votes 
                                 GROUP BY server_id
                             ) v ON s.id = v.server_id 
                             WHERE s.is_sponsored = 1 AND s.sponsor_expires_at > NOW()
                             ORDER BY s.sponsor_priority DESC, s.data_aggiornamento DESC");
        $sponsored_servers = $stmt->fetchAll();
        
        // Server non sponsorizzati per il select
        $stmt = $pdo->query("SELECT s.id, s.nome FROM sl_servers s 
                             WHERE s.is_sponsored = 0 OR s.sponsor_expires_at <= NOW()
                             ORDER BY s.nome ASC");
        $available_servers = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        $error = 'Errore nel recupero dei server sponsorizzati.';
        $sponsored_servers = [];
        $available_servers = [];
    }
}

// Recupera statistiche generali
try {
    $stats = [];
    
    // Totale server
    $stmt = $pdo->query("SELECT COUNT(*) FROM sl_servers");
    $stats['total_servers'] = $stmt->fetchColumn();
    
    // Totale utenti
    $stmt = $pdo->query("SELECT COUNT(*) FROM sl_users");
    $stats['total_users'] = $stmt->fetchColumn();
    
    // Totale voti
    $stmt = $pdo->query("SELECT COUNT(*) FROM sl_votes");
    $stats['total_votes'] = $stmt->fetchColumn();
    
    // Voti di oggi
    $stmt = $pdo->query("SELECT COUNT(*) FROM sl_votes WHERE DATE(data_voto) = CURDATE()");
    $stats['today_votes'] = $stmt->fetchColumn();
    
    // Utenti registrati oggi
    $stmt = $pdo->query("SELECT COUNT(*) FROM sl_users WHERE DATE(data_registrazione) = CURDATE()");
    $stats['today_users'] = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $stats = [
        'total_servers' => 0,
        'total_users' => 0,
        'total_votes' => 0,
        'today_votes' => 0,
        'today_users' => 0
    ];
}

$page_title = "Pannello Admin";
include 'header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="display-6 fw-bold text-primary mb-4">
            <i class="bi bi-gear-fill"></i> Pannello di Amministrazione
        </h1>
    </div>
</div>

<div class="container-lg" style="margin-top: 2rem;">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistiche -->
        <div class="row mb-4 g-3">
            <div class="col-md-2 col-6">
                <div class="profile-stats-card" style="padding: 1.5rem; margin-bottom: 0;">
                    <div class="text-center">
                        <div class="display-6 fw-bold" style="color: var(--accent-purple); margin-bottom: 0.5rem;"><?php echo $stats['total_servers']; ?></div>
                        <small style="color: var(--text-secondary);">Server Totali</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="profile-stats-card" style="padding: 1.5rem; margin-bottom: 0;">
                    <div class="text-center">
                        <div class="display-6 fw-bold" style="color: var(--accent-green); margin-bottom: 0.5rem;"><?php echo $stats['total_users']; ?></div>
                        <small style="color: var(--text-secondary);">Utenti Totali</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="profile-stats-card" style="padding: 1.5rem; margin-bottom: 0;">
                    <div class="text-center">
                        <div class="display-6 fw-bold" style="color: var(--accent-cyan); margin-bottom: 0.5rem;"><?php echo $stats['total_votes']; ?></div>
                        <small style="color: var(--text-secondary);">Voti Totali</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="profile-stats-card" style="padding: 1.5rem; margin-bottom: 0;">
                    <div class="text-center">
                        <div class="display-6 fw-bold" style="color: var(--accent-orange); margin-bottom: 0.5rem;"><?php echo $stats['today_votes']; ?></div>
                        <small style="color: var(--text-secondary);">Voti Oggi</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="profile-stats-card" style="padding: 1.5rem; margin-bottom: 0;">
                    <div class="text-center">
                        <div class="display-6 fw-bold" style="color: var(--text-primary); margin-bottom: 0.5rem;"><?php echo $stats['today_users']; ?></div>
                        <small style="color: var(--text-secondary);">Nuovi Oggi</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="profile-stats-card" style="padding: 1.5rem; margin-bottom: 0;">
                    <div class="text-center">
                        <div class="display-6" style="color: var(--accent-purple); margin-bottom: 0.5rem;"><i class="bi bi-person-circle"></i></div>
                        <small style="color: var(--text-secondary);"><?php echo htmlspecialchars($_SESSION['minecraft_nick']); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu Admin -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="profile-section" style="padding: 1.5rem;">
                    <h5 class="mb-3" style="color: var(--text-primary);"><i class="bi bi-gear-fill me-2"></i>Gestione Admin</h5>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a href="admin.php?action=list" class="btn btn-primary<?php echo $action === 'list' ? ' active' : ''; ?>">
                            <i class="bi bi-list"></i> Lista Server
                        </a>
                        <a href="admin.php?action=add" class="btn btn-success<?php echo $action === 'add' ? ' active' : ''; ?>">
                            <i class="bi bi-plus-circle"></i> Aggiungi Server
                        </a>
                        <a href="admin_rewards.php" class="btn btn-info">
                            <i class="bi bi-gift"></i> Gestione Ricompense
                        </a>
                        <a href="admin_reward_logs.php" class="btn btn-secondary">
                            <i class="bi bi-history"></i> Log Ricompense
                        </a>
                        <a href="admin.php?action=webhooks" class="btn btn-warning<?php echo $action === 'webhooks' ? ' active' : ''; ?>">
                            <i class="bi bi-webhook"></i> Webhook
                        </a>
                        <a href="admin.php?action=sponsored" class="btn btn-warning<?php echo $action === 'sponsored' ? ' active' : ''; ?>">
                            <i class="bi bi-star"></i> Server Sponsor
                        </a>
                        <a href="index.php" class="btn btn-info">
                            <i class="bi bi-house"></i> Vai al Sito
                        </a>
                        <a href="logout.php" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ...existing code... -->

<?php if ($action === 'list'): ?>
    <!-- Lista Server -->
    <div class="profile-section">
        <div class="d-flex align-items-center mb-4">
            <h5 class="mb-0" style="color: var(--text-primary);"><i class="bi bi-server me-2"></i>Gestione Server <span class="badge bg-secondary"><?php echo count($servers); ?></span></h5>
        </div>
        <?php if (count($servers) > 0): ?>
            <div class="servers-grid">
                <?php foreach ($servers as $server): ?>
                    <div class="server-card-profile" style="background: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="server-card-header">
                            <?php if ($server['logo_url']): ?>
                                <img src="<?php echo htmlspecialchars($server['logo_url']); ?>" alt="Logo" class="server-logo-small">
                            <?php else: ?>
                                <div class="server-logo-small default-logo" style="background: var(--dark-bg);"><i class="bi bi-cube"></i></div>
                            <?php endif; ?>
                            <div class="server-info-small">
                                <div class="server-name-small">
                                    <a href="server.php?id=<?php echo $server['id']; ?>" target="_blank" style="color: var(--text-primary);">
                                        <?php echo htmlspecialchars($server['nome']); ?>
                                    </a>
                                </div>
                                <div class="server-ip-small" style="color: var(--text-secondary);">
                                    <i class="bi bi-server"></i> <?php echo htmlspecialchars($server['ip']); ?>
                                </div>
                                <div class="server-description-small" style="color: var(--text-secondary);">
                                    <?php echo htmlspecialchars($server['descrizione']); ?>
                                </div>
                            </div>
                            <div class="server-stats-small">
                                <div class="stat-item-small">
                                    <span class="stat-number-small" style="color: var(--accent-cyan);"><i class="bi bi-bar-chart"></i> <?php echo number_format($server['vote_count']); ?></span>
                                    <span class="stat-label-small" style="color: var(--text-secondary);">Voti Totali</span>
                                </div>
                                <div class="stat-item-small">
                                    <span class="stat-number-small" style="color: var(--accent-orange);"><i class="bi bi-star"></i> <?php echo number_format($server['today_votes']); ?></span>
                                    <span class="stat-label-small" style="color: var(--text-secondary);">Oggi</span>
                                </div>
                                <div class="stat-item-small">
                                    <span class="stat-label-small" style="color: var(--text-secondary);"><i class="bi bi-calendar"></i> <?php echo date('d/m/Y', strtotime($server['data_inserimento'])); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="server-actions mt-2">
                            <a href="server.php?id=<?php echo $server['id']; ?>" class="btn-view-server" target="_blank" style="background: var(--accent-purple); color: #fff;">
                                <i class="bi bi-eye"></i> Vedi
                            </a>
                            <a href="admin.php?action=edit&id=<?php echo $server['id']; ?>" class="btn-edit-server" style="background: var(--accent-yellow); color: #000;">
                                <i class="bi bi-pencil"></i> Modifica
                            </a>
                            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $server['id']; ?>, '<?php echo addslashes($server['nome']); ?>')">
                                <i class="bi bi-trash"></i> Elimina
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="profile-stats-card" style="padding: 2rem; text-align: center;">
                <i class="bi bi-inbox" style="font-size: 2rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Nessun server registrato.</p>
                <a href="admin.php?action=add" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Aggiungi il primo server
                </a>
            </div>
        <?php endif; ?>
    </div>
<?php elseif ($action === 'add' || $action === 'edit'): ?>
    <!-- Form Aggiungi/Modifica Server -->
    <div class="profile-section">
        <div class="d-flex align-items-center mb-4">
            <h5 class="mb-0" style="color: var(--text-primary);">
                <i class="bi bi-<?php echo $action === 'edit' ? 'pencil' : 'plus-circle'; ?> me-2"></i>
                <?php echo $action === 'edit' ? 'Modifica Server' : 'Aggiungi Nuovo Server'; ?>
            </h5>
        </div>
        <form method="POST" enctype="multipart/form-data" id="serverForm">
            <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $server['id']; ?>">
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nome" class="form-label" style="color: var(--text-primary);">Nome Server *</label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($server['nome'] ?? ''); ?>" required style="background: var(--dark-bg); border: 1px solid var(--border-color); color: var(--text-primary);">
                    </div>
                    <div class="mb-3">
                        <label for="ip" class="form-label" style="color: var(--text-primary);">IP Server *</label>
                        <input type="text" class="form-control" id="ip" name="ip" value="<?php echo htmlspecialchars($server['ip'] ?? ''); ?>" required style="background: var(--dark-bg); border: 1px solid var(--border-color); color: var(--text-primary);">
                    </div>
                    <div class="mb-3">
                        <label for="porta" class="form-label" style="color: var(--text-primary);">Porta *</label>
                        <input type="number" class="form-control" id="porta" name="porta" value="<?php echo htmlspecialchars($server['porta'] ?? '25565'); ?>" required style="background: var(--dark-bg); border: 1px solid var(--border-color); color: var(--text-primary);">
                    </div>
                    <div class="mb-3">
                        <label for="versione" class="form-label" style="color: var(--text-primary);">Versione</label>
                        <input type="text" class="form-control" id="versione" name="versione" value="<?php echo htmlspecialchars($server['versione'] ?? ''); ?>" style="background: var(--dark-bg); border: 1px solid var(--border-color); color: var(--text-primary);">
                    </div>
                    <div class="mb-3">
                        <label for="tipo" class="form-label" style="color: var(--text-primary);">Tipo Server</label>
                        <select class="form-select" id="tipo" name="tipo" style="background: var(--dark-bg); border: 1px solid var(--border-color); color: var(--text-primary);">
                            <option value="vanilla" <?php echo (isset($server['tipo']) && $server['tipo'] === 'vanilla') ? 'selected' : ''; ?>>Vanilla</option>
                            <option value="modded" <?php echo (isset($server['tipo']) && $server['tipo'] === 'modded') ? 'selected' : ''; ?>>Modded</option>
                            <option value="minigames" <?php echo (isset($server['tipo']) && $server['tipo'] === 'minigames') ? 'selected' : ''; ?>>Minigames</option>
                            <option value="pvp" <?php echo (isset($server['tipo']) && $server['tipo'] === 'pvp') ? 'selected' : ''; ?>>PvP</option>
                            <option value="pve" <?php echo (isset($server['tipo']) && $server['tipo'] === 'pve') ? 'selected' : ''; ?>>PvE</option>
                            <option value="roleplay" <?php echo (isset($server['tipo']) && $server['tipo'] === 'roleplay') ? 'selected' : ''; ?>>Roleplay</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="logo_url" class="form-label" style="color: var(--text-primary);">URL Logo</label>
                        <input type="url" class="form-control" id="logo_url" name="logo_url" value="<?php echo htmlspecialchars($server['logo_url'] ?? ''); ?>" style="background: var(--dark-bg); border: 1px solid var(--border-color); color: var(--text-primary);">
                        <small style="color: var(--text-secondary);">Inserisci l'URL completo del logo (https://...)</small>
                    </div>
                    <div class="mb-3">
                        <label for="banner_url" class="form-label" style="color: var(--text-primary);">URL Banner</label>
                        <input type="url" class="form-control" id="banner_url" name="banner_url" value="<?php echo htmlspecialchars($server['banner_url'] ?? ''); ?>" style="background: var(--dark-bg); border: 1px solid var(--border-color); color: var(--text-primary);">
                        <small style="color: var(--text-secondary);">Inserisci l'URL completo del banner (https://...)</small>
                    </div>
                    <div class="mb-3">
                        <label for="descrizione" class="form-label" style="color: var(--text-primary);">Descrizione</label>
                        <textarea class="form-control" id="descrizione" name="descrizione" rows="4" style="background: var(--dark-bg); border: 1px solid var(--border-color); color: var(--text-primary);"><?php echo htmlspecialchars($server['descrizione'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="server_order" class="form-label" style="color: var(--text-primary);">Ordine</label>
                        <input type="number" class="form-control" id="server_order" name="server_order" value="<?php echo htmlspecialchars($server['server_order'] ?? '0'); ?>" style="background: var(--dark-bg); border: 1px solid var(--border-color); color: var(--text-primary);">
                        <small style="color: var(--text-secondary);">Numero per ordinare i server (più alto = prima)</small>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <button type="submit" name="save_server" class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i><?php echo $action === 'edit' ? 'Aggiorna Server' : 'Aggiungi Server'; ?>
                    </button>
                    <a href="admin.php?action=list" class="btn btn-secondary ms-2">
                        <i class="bi bi-arrow-left me-2"></i>Torna alla Lista
                    </a>
                </div>
            </div>
        </form>
    </div>
<?php elseif ($action === 'webhooks'): ?>
    <!-- Gestione Webhook -->
    <div class="card shadow">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="bi bi-webhook"></i> Configurazione Webhook Minecraft
            </h5>
        </div>
        <div class="card-body">
            <?php
            // Recupera configurazioni webhook esistenti
            $webhook_stmt = $pdo->prepare("SELECT * FROM sl_webhooks WHERE server_id = ?");
            $webhook_stmt->execute([$_GET['server_id'] ?? 0]);
            $webhook = $webhook_stmt->fetch();
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_webhook'])) {
                $server_id = intval($_POST['server_id']);
                $webhook_url = filter_var($_POST['webhook_url'], FILTER_SANITIZE_URL);
                $webhook_secret = $_POST['webhook_secret'];
                $command_template = $_POST['command_template'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if (filter_var($webhook_url, FILTER_VALIDATE_URL)) {
                    if ($webhook) {
                        // Aggiorna webhook esistente
                        $stmt = $pdo->prepare("UPDATE sl_webhooks SET webhook_url = ?, webhook_secret = ?, command_template = ?, is_active = ?, updated_at = NOW() WHERE server_id = ?");
                        $stmt->execute([$webhook_url, $webhook_secret, $command_template, $is_active, $server_id]);
                    } else {
                        // Crea nuovo webhook
                        $stmt = $pdo->prepare("INSERT INTO sl_webhooks (server_id, webhook_url, webhook_secret, command_template, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                        $stmt->execute([$server_id, $webhook_url, $webhook_secret, $command_template, $is_active]);
                    }
                    echo '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Configurazione webhook salvata con successo!</div>';
                    $webhook_stmt->execute([$server_id]);
                    $webhook = $webhook_stmt->fetch();
                } else {
                    echo '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> URL webhook non valido!</div>';
                }
            }
            
            // Recupera lista server per dropdown
            $servers_stmt = $pdo->query("SELECT id, nome FROM sl_servers WHERE is_active = 1 ORDER BY nome");
            $servers_list = $servers_stmt->fetchAll();
            ?>
            
            <form method="POST" action="admin.php?action=webhooks">
                <div class="mb-3">
                    <label for="server_id" class="form-label">
                        <i class="bi bi-server"></i> Seleziona Server
                    </label>
                    <select class="form-select" id="server_id" name="server_id" required onchange="this.form.submit()">
                        <option value="">Seleziona un server...</option>
                        <?php foreach ($servers_list as $server_item): ?>
                            <option value="<?php echo $server_item['id']; ?>" 
                                    <?php echo (($_GET['server_id'] ?? $webhook['server_id'] ?? '') == $server_item['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($server_item['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if (isset($_GET['server_id']) || $webhook): ?>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="webhook_url" class="form-label">
                                    <i class="bi bi-link"></i> URL Webhook
                                </label>
                                <input type="url" class="form-control" id="webhook_url" name="webhook_url" 
                                       value="<?php echo htmlspecialchars($webhook['webhook_url'] ?? ''); ?>"
                                       placeholder="https://tuo-server.com/webhook">
                                <div class="form-text">
                                    L'URL dove inviare le notifiche quando un utente vota
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="webhook_secret" class="form-label">
                                    <i class="bi bi-key"></i> Secret Key
                                </label>
                                <input type="text" class="form-control" id="webhook_secret" name="webhook_secret" 
                                       value="<?php echo htmlspecialchars($webhook['webhook_secret'] ?? bin2hex(random_bytes(16))); ?>"
                                       placeholder="Chiave segreta per firma HMAC">
                                <div class="form-text">
                                    Usato per verificare l'autenticità delle richieste
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="command_template" class="form-label">
                            <i class="bi bi-terminal"></i> Template Comando
                        </label>
                        <textarea class="form-control" id="command_template" name="command_template" rows="3"
                                  placeholder="give {player} diamond 1&#10;eco give {player} 100&#10;say {player} ha votato!"><?php echo htmlspecialchars($webhook['command_template'] ?? 'give {player} diamond 1'); ?></textarea>
                        <div class="form-text">
                            Comandi da eseguire nel server Minecraft. Usa <code>{player}</code> per il nickname del votante.<br>
                            Un comando per riga, verranno eseguiti in ordine.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   <?php echo ($webhook['is_active'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                <i class="bi bi-toggle-on"></i> Webhook Attivo
                            </label>
                        </div>
                    </div>
                    
                    <?php if ($webhook): ?>
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="bi bi-info-circle"></i> Informazioni Webhook
                            </h6>
                            <p class="mb-1"><strong>Endpoint:</strong> <code>POST <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . '/webhook.php'); ?></code></p>
                            <p class="mb-1"><strong>Headers richiesti:</strong></p>
                            <ul class="mb-0">
                                <li><code>X-Webhook-Signature: sha256=&lt;signature&gt;</code></li>
                                <li><code>Content-Type: application/json</code></li>
                            </ul>
                            <p class="mb-1"><strong>Payload JSON:</strong></p>
                            <pre class="mb-0"><code>{
  "server_id": <?php echo $webhook['server_id']; ?>,
  "player_name": "PlayerName",
  "timestamp": "2024-01-20T10:30:00Z",
  "vote_id": 123
}</code></pre>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" name="save_webhook" class="btn btn-warning">
                            <i class="bi bi-save"></i> Salva Configurazione
                        </button>
                        <a href="admin.php?action=list" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Torna alla lista
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
<?php elseif ($action === 'sponsored'): ?>
    <!-- Gestione Server Sponsorizzati -->
    <div class="card shadow">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="bi bi-star"></i> Gestione Server Sponsorizzati
            </h5>
        </div>
        <div class="card-body">
            <!-- Aggiungi nuovo server sponsorizzato -->
            <div class="profile-section mb-4">
                <h6 class="mb-3" style="color: var(--text-primary);">
                    <i class="bi bi-plus-circle"></i> Aggiungi Server Sponsorizzato
                </h6>
                <form method="POST" action="admin.php?action=sponsored_add">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="mb-3">
                                <label for="server_id" class="form-label">Seleziona Server</label>
                                <select class="form-select" id="server_id" name="server_id" required>
                                    <option value="">Seleziona un server...</option>
                                    <?php foreach ($available_servers as $server): ?>
                                        <option value="<?php echo $server['id']; ?>">
                                            <?php echo htmlspecialchars($server['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="sponsor_priority" class="form-label">Priorità</label>
                                <input type="number" class="form-control" id="sponsor_priority" name="sponsor_priority" 
                                       value="1" min="1" max="999" required>
                                <small class="form-text">Più alto = prima posizione</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="sponsor_expires_at" class="form-label">Scadenza</label>
                                <input type="datetime-local" class="form-control" id="sponsor_expires_at" name="sponsor_expires_at" required>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="mb-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Lista server sponsorizzati -->
            <div class="profile-section">
                <h6 class="mb-3" style="color: var(--text-primary);">
                    <i class="bi bi-list"></i> Server Sponsorizzati Attivi
                </h6>
                <?php if (empty($sponsored_servers)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Nessun server sponsorizzato attivo al momento.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Priorità</th>
                                    <th>Nome Server</th>
                                    <th>IP</th>
                                    <th>Voti</th>
                                    <th>Scadenza</th>
                                    <th>Stato</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sponsored_servers as $server): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $server['sponsor_priority']; ?></span>
                                        </td>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($server['logo_url'] ?? '/assets/default-logo.png'); ?>" 
                                                 alt="Logo" class="rounded" width="24" height="24" style="margin-right: 8px;">
                                            <?php echo htmlspecialchars($server['nome']); ?>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($server['ip']); ?></code></td>
                                        <td>
                                            <span class="badge bg-success"><?php echo $server['vote_count']; ?> voti</span>
                                        </td>
                                        <td>
                                            <?php 
                                            $expiry_date = new DateTime($server['sponsor_expires_at']);
                                            $now = new DateTime();
                                            $days_left = $now->diff($expiry_date)->days;
                                            ?>
                                            <span class="<?php echo $days_left <= 7 ? 'text-warning' : 'text-light'; ?>">
                                                <?php echo $expiry_date->format('d/m/Y H:i'); ?>
                                                <?php if ($days_left <= 7): ?>
                                                    <small class="d-block">(<?php echo $days_left; ?> giorni rimanenti)</small>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($server['sponsor_expires_at'] > date('Y-m-d H:i:s')): ?>
                                                <span class="badge bg-success">Attivo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Scaduto</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-warning" 
                                                        onclick="editSponsoredServer(<?php echo $server['id']; ?>, <?php echo $server['sponsor_priority']; ?>, '<?php echo $server['sponsor_expires_at']; ?>')"
                                                        title="Modifica">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" action="admin.php?action=sponsored_remove" style="display: inline;">
                                                    <input type="hidden" name="server_id" value="<?php echo $server['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            onclick="return confirm('Sei sicuro di voler rimuovere questo server dalla sponsorizzazione?')"
                                                            title="Rimuovi">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal per modificare server sponsorizzato -->
    <div class="modal fade" id="editSponsoredModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background: var(--card-bg); border: 1px solid var(--border-color);">
                <div class="modal-header" style="background: var(--warning-color); color: white;">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil"></i> Modifica Server Sponsorizzato
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="admin.php?action=sponsored_edit">
                    <div class="modal-body" style="color: var(--text-primary);">
                        <input type="hidden" name="server_id" id="edit_server_id">
                        <div class="mb-3">
                            <label for="edit_sponsor_priority" class="form-label">Priorità</label>
                            <input type="number" class="form-control" id="edit_sponsor_priority" name="sponsor_priority" 
                                   min="1" max="999" required>
                            <small class="form-text">Più alto = prima posizione</small>
                        </div>
                        <div class="mb-3">
                            <label for="edit_sponsor_expires_at" class="form-label">Nuova Scadenza</label>
                            <input type="datetime-local" class="form-control" id="edit_sponsor_expires_at" name="sponsor_expires_at" required>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid var(--border-color);">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Annulla
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-save"></i> Salva Modifiche
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    function editSponsoredServer(serverId, priority, expiresAt) {
        document.getElementById('edit_server_id').value = serverId;
        document.getElementById('edit_sponsor_priority').value = priority;
        document.getElementById('edit_sponsor_expires_at').value = expiresAt.replace(' ', 'T');
        
        const modal = new bootstrap.Modal(document.getElementById('editSponsoredModal'));
        modal.show();
    }
    
    // Imposta il valore minimo per datetime-local a oggi
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        const localDateTime = now.toISOString().slice(0, 16);
        document.getElementById('sponsor_expires_at').min = localDateTime;
        document.getElementById('edit_sponsor_expires_at').min = localDateTime;
    });
    </script>
    
<?php endif; ?>

<!-- Modal di Conferma Eliminazione -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background: var(--card-bg); border: 1px solid var(--border-color);">
            <div class="modal-header" style="background: var(--danger-color); color: white; border-bottom: 1px solid var(--border-color);">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle"></i> Conferma Eliminazione
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="color: var(--text-primary);">
                <p>Sei sicuro di voler eliminare il server <strong id="deleteServerName" style="color: var(--text-primary);"></strong>?</p>
                <p style="color: var(--text-secondary);">
                    <i class="bi bi-warning"></i> Questa azione eliminerà anche tutti i voti associati al server e non può essere annullata.
                </p>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--border-color);">
                <form method="POST" action="admin.php?action=delete" id="deleteForm">
                    <input type="hidden" name="server_id" id="deleteServerId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Annulla
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Elimina
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Funzione per confermare l'eliminazione
function confirmDelete(serverId, serverName) {
    document.getElementById('deleteServerId').value = serverId;
    document.getElementById('deleteServerName').textContent = serverName;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Validazione form
document.getElementById('serverForm')?.addEventListener('submit', function(e) {
    const nome = document.getElementById('nome').value.trim();
    const ip = document.getElementById('ip').value.trim();
    const versione = document.getElementById('versione').value.trim();
    
    if (nome.length < 3) {
        e.preventDefault();
        showToast('Il nome del server deve essere di almeno 3 caratteri.', 'error');
        return false;
    }
    
    if (ip.length < 5) {
        e.preventDefault();
        showToast('L\'IP del server deve essere valido.', 'error');
        return false;
    }
    
    if (versione.length < 3) {
        e.preventDefault();
        showToast('La versione deve essere specificata.', 'error');
        return false;
    }
});

// Aggiungi stili CSS per il tema scuro ai form
const style = document.createElement('style');
style.textContent = `
    .form-control:focus, .form-select:focus {
        background: var(--dark-bg) !important;
        border-color: var(--accent-purple) !important;
        color: var(--text-primary) !important;
        box-shadow: 0 0 0 0.25rem rgba(147, 51, 234, 0.25) !important;
    }
    .form-control::placeholder, .form-select::placeholder {
        color: var(--text-secondary) !important;
    }
    .form-text {
        color: var(--text-secondary) !important;
    }
    .invalid-feedback {
        color: var(--danger-color) !important;
    }
    .valid-feedback {
        color: var(--accent-green) !important;
    }
`;
document.head.appendChild(style);
</script>

<?php include 'footer.php'; ?>