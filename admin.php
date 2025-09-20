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

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistiche -->
<div class="row mb-4">
    <div class="col-md-2 col-6">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <div class="display-6"><?php echo $stats['total_servers']; ?></div>
                <small>Server Totali</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <div class="display-6"><?php echo $stats['total_users']; ?></div>
                <small>Utenti Totali</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <div class="display-6"><?php echo $stats['total_votes']; ?></div>
                <small>Voti Totali</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <div class="display-6"><?php echo $stats['today_votes']; ?></div>
                <small>Voti Oggi</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <div class="display-6"><?php echo $stats['today_users']; ?></div>
                <small>Nuovi Oggi</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card bg-dark text-white">
            <div class="card-body text-center">
                <div class="display-6">
                    <i class="bi bi-person-circle"></i>
                </div>
                <small><?php echo htmlspecialchars($_SESSION['minecraft_nick']); ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Menu Admin -->
<div class="row mb-4">
    <div class="col-12">
        <div class="btn-group" role="group">
            <a href="admin.php?action=list" class="btn btn-outline-primary <?php echo $action === 'list' ? 'active' : ''; ?>">
                <i class="bi bi-list"></i> Lista Server
            </a>
            <a href="admin.php?action=add" class="btn btn-outline-success <?php echo $action === 'add' ? 'active' : ''; ?>">
                <i class="bi bi-plus-circle"></i> Aggiungi Server
            </a>
            <a href="admin.php?action=webhooks" class="btn btn-outline-warning <?php echo $action === 'webhooks' ? 'active' : ''; ?>">
                <i class="bi bi-webhook"></i> Webhook
            </a>
            <a href="index.php" class="btn btn-outline-info">
                <i class="bi bi-house"></i> Vai al Sito
            </a>
            <a href="logout.php" class="btn btn-outline-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
</div>

<?php if ($action === 'list'): ?>
    <!-- Lista Server -->
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="bi bi-server"></i> Gestione Server (<?php echo count($servers); ?>)
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (count($servers) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>IP</th>
                                <th>Versione</th>
                                <th>Voti Totali</th>
                                <th>Voti Oggi</th>
                                <th>Data Inserimento</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($servers as $server): ?>
                                <tr>
                                    <td><?php echo $server['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($server['nome']); ?></strong>
                                        <?php if ($server['logo_url']): ?>
                                            <img src="<?php echo htmlspecialchars($server['logo_url']); ?>" 
                                                 alt="Logo" class="rounded" style="max-height: 24px; margin-left: 8px;">
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($server['ip']); ?></code></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($server['versione']); ?></span></td>
                                    <td><span class="badge bg-primary"><?php echo number_format($server['vote_count']); ?></span></td>
                                    <td><span class="badge bg-warning text-dark"><?php echo number_format($server['today_votes']); ?></span></td>
                                    <td><?php echo date('d/m/Y', strtotime($server['data_inserimento'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="server.php?id=<?php echo $server['id']; ?>" class="btn btn-outline-info" target="_blank">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="admin.php?action=edit&id=<?php echo $server['id']; ?>" class="btn btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $server['id']; ?>, '<?php echo addslashes($server['nome']); ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-4 text-muted"></i>
                    <p class="text-muted mt-3">Nessun server registrato.</p>
                    <a href="admin.php?action=add" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Aggiungi il primo server
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($action === 'add' || $action === 'edit'): ?>
    <!-- Form Aggiungi/Modifica Server -->
    <div class="card shadow">
        <div class="card-header bg-<?php echo $action === 'add' ? 'success' : 'primary'; ?> text-white">
            <h5 class="mb-0">
                <i class="bi bi-<?php echo $action === 'add' ? 'plus-circle' : 'pencil'; ?>"></i> 
                <?php echo $action === 'add' ? 'Aggiungi Nuovo Server' : 'Modifica Server'; ?>
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" action="admin.php?action=<?php echo $action; ?>" id="serverForm">
                <?php if ($action === 'edit' && $server_to_edit): ?>
                    <input type="hidden" name="server_id" value="<?php echo $server_to_edit['id']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nome" class="form-label">
                                <i class="bi bi-tag"></i> Nome Server *
                            </label>
                            <input type="text" class="form-control" id="nome" name="nome" required
                                   value="<?php echo htmlspecialchars($server_to_edit['nome'] ?? ''); ?>"
                                   placeholder="Es: Server Minecraft Italia">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="ip" class="form-label">
                                <i class="bi bi-server"></i> IP Server *
                            </label>
                            <input type="text" class="form-control" id="ip" name="ip" required
                                   value="<?php echo htmlspecialchars($server_to_edit['ip'] ?? ''); ?>"
                                   placeholder="Es: mc.server.com:25565">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="versione" class="form-label">
                                <i class="bi bi-code-slash"></i> Versione *
                            </label>
                            <input type="text" class="form-control" id="versione" name="versione" required
                                   value="<?php echo htmlspecialchars($server_to_edit['versione'] ?? ''); ?>"
                                   placeholder="Es: 1.20.4">
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipo_server" class="form-label">
                                <i class="bi bi-hdd-stack"></i> Tipo Server *
                            </label>
                            <select class="form-control" id="tipo_server" name="tipo_server" required>
                                <option value="Java" <?php echo (isset($server_to_edit) && $server_to_edit['tipo_server'] == 'Java') ? 'selected' : ''; ?>>Java</option>
                                <option value="Bedrock" <?php echo (isset($server_to_edit) && $server_to_edit['tipo_server'] == 'Bedrock') ? 'selected' : ''; ?>>Bedrock</option>
                                <option value="Java & Bedrock" <?php echo (!isset($server_to_edit) || $server_to_edit['tipo_server'] == 'Java & Bedrock') ? 'selected' : ''; ?>>Java & Bedrock</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="logo_url" class="form-label">
                                <i class="bi bi-image"></i> URL Logo
                            </label>
                            <input type="url" class="form-control" id="logo_url" name="logo_url"
                                   value="<?php echo htmlspecialchars($server_to_edit['logo_url'] ?? ''); ?>"
                                   placeholder="https://example.com/logo.png">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="banner_url" class="form-label">
                        <i class="bi bi-card-image"></i> URL Banner
                    </label>
                    <input type="url" class="form-control" id="banner_url" name="banner_url"
                           value="<?php echo htmlspecialchars($server_to_edit['banner_url'] ?? ''); ?>"
                           placeholder="https://example.com/banner.gif">
                    <div class="form-text">Banner consigliato: 468x60px o 728x90px</div>
                </div>
                
                <div class="mb-3">
                    <label for="descrizione" class="form-label">
                        <i class="bi bi-file-text"></i> Descrizione
                    </label>
                    <textarea class="form-control" id="descrizione" name="descrizione" rows="4"
                              placeholder="Descrivi il server, le sue caratteristiche, modalità di gioco, ecc."><?php echo htmlspecialchars($server_to_edit['descrizione'] ?? ''); ?></textarea>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-<?php echo $action === 'add' ? 'success' : 'primary'; ?>">
                        <i class="bi bi-<?php echo $action === 'add' ? 'plus-circle' : 'check-circle'; ?>"></i> 
                        <?php echo $action === 'add' ? 'Aggiungi Server' : 'Salva Modifiche'; ?>
                    </button>
                    <a href="admin.php?action=list" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Annulla
                    </a>
                </div>
            </form>
        </div>
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
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
<?php endif; ?>

<!-- Modal di Conferma Eliminazione -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle"></i> Conferma Eliminazione
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Sei sicuro di voler eliminare il server <strong id="deleteServerName"></strong>?</p>
                <p class="text-danger">
                    <i class="bi bi-warning"></i> Questa azione eliminerà anche tutti i voti associati al server e non può essere annullata.
                </p>
            </div>
            <div class="modal-footer">
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
</script>

<?php include 'footer.php'; ?>