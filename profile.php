<?php
/**
 * Pagina Profilo Utente Riprogettata
 * User Profile Page Redesigned
 */

require_once 'config.php';

// Controlla se l'utente è loggato
if (!isLoggedIn()) {
    redirect('/login');
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Assicurati che esista la colonna staff_list per memorizzare lo staff (JSON)
try {
    $pdo->exec("ALTER TABLE sl_servers ADD COLUMN staff_list JSON NULL");
} catch (Exception $e) {
    // Ignora se già esiste o se l'ALTER non è permesso
}

// Verifica CSRF per tutte le richieste POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Sessione scaduta o token non valido. Ricarica la pagina e riprova.';
    }
}

// Gestione form di richiesta nuovo server
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_server'])) {
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
        // Salva la richiesta in una tabella temporanea o invia email agli admin
        // Per ora salviamo come server inattivo che richiede approvazione
        try {
            $stmt = $pdo->prepare("INSERT INTO sl_servers (nome, ip, versione, tipo_server, descrizione, banner_url, logo_url, modalita, owner_id, is_active, data_inserimento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 2, NOW())");
            $stmt->execute([$nome, $ip, $versione, $tipo_server, $descrizione, $banner_url, $logo_url, $modalita_json, $user_id]);
            $message = 'Richiesta inviata con successo! Il tuo server sarà revisionato dagli amministratori.';
        } catch (PDOException $e) {
            $error = 'Errore durante l\'invio della richiesta.';
        }
    }
}

// Gestione modifica server
$edit_server_id = isset($_GET['edit_server']) ? (int)$_GET['edit_server'] : 0;
$server_to_edit = null;

if ($edit_server_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM sl_servers WHERE id = ? AND owner_id = ?");
        $stmt->execute([$edit_server_id, $user_id]);
        $server_to_edit = $stmt->fetch();
        
        if (!$server_to_edit) {
            $error = 'Server non trovato o non hai i permessi per modificarlo.';
        } else {
            // Decodifica le modalità esistenti
            $server_to_edit['modalita_array'] = [];
            if (!empty($server_to_edit['modalita'])) {
                $decoded_modalita = json_decode($server_to_edit['modalita'], true);
                if (is_array($decoded_modalita)) {
                    $server_to_edit['modalita_array'] = $decoded_modalita;
                }
            }

            // Decodifica StaffList esistente
            $server_to_edit['staff_list_array'] = [];
            if (!empty($server_to_edit['staff_list'])) {
                $decoded_staff = json_decode($server_to_edit['staff_list'], true);
                if (is_array($decoded_staff)) {
                    $server_to_edit['staff_list_array'] = $decoded_staff;
                }
            }

            // Decodifica Social Links esistenti (JSON)
            $server_to_edit['social_links_array'] = [];
            if (!empty($server_to_edit['social_links'])) {
                $decoded_social = json_decode($server_to_edit['social_links'], true);
                if (is_array($decoded_social)) {
                    $server_to_edit['social_links_array'] = $decoded_social;
                }
            }
        }
    } catch (PDOException $e) {
        $error = 'Errore nel caricamento del server.';
    }
}

// Gestione form di modifica server
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_server'])) {
    // Assicurati che esistano le colonne social (migrazione sicura)
    try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN website_url VARCHAR(255) NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN shop_url VARCHAR(255) NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN discord_url VARCHAR(255) NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN telegram_url VARCHAR(255) NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN social_links TEXT NULL"); } catch (Exception $e) {}
    $server_id = (int)($_POST['server_id'] ?? 0);
    $nome = sanitize($_POST['nome'] ?? '');
    $ip = sanitize($_POST['ip'] ?? '');
    $versione = sanitize($_POST['versione'] ?? '');
    $tipo_server = sanitize($_POST['tipo_server'] ?? 'Java & Bedrock');
    $descrizione = sanitizeQuillContent($_POST['descrizione'] ?? '');
    $banner_url = sanitize($_POST['banner_url'] ?? '');
    $logo_url = sanitize($_POST['logo_url'] ?? '');
    // Campi social
    $website_url = sanitize($_POST['website_url'] ?? '');
    $shop_url = sanitize($_POST['shop_url'] ?? '');
    $discord_url = sanitize($_POST['discord_url'] ?? '');
    $telegram_url = sanitize($_POST['telegram_url'] ?? '');
    $modalita = isset($_POST['modalita']) ? $_POST['modalita'] : [];
    // StaffList in JSON (array di ranks con staffer)
    $staff_list_json = $_POST['staff_list_json'] ?? '';
    // Social Links dinamici in JSON (array di {title, url})
    $social_links_json = $_POST['social_links_json'] ?? '';
    
    // Converti le modalità in JSON
    $modalita_json = json_encode(array_values($modalita));
    
    if ($server_id === 0 || empty($nome) || empty($ip) || empty($versione)) {
        $error = 'Nome, IP e Versione sono campi obbligatori.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM sl_servers WHERE id = ? AND owner_id = ?");
            $stmt->execute([$server_id, $user_id]);
            
            if ($stmt->fetch()) {
                // Prova ad aggiornare anche staff_list; fallback se la colonna non esiste
                try {
                    $stmt = $pdo->prepare("UPDATE sl_servers SET nome = ?, ip = ?, versione = ?, tipo_server = ?, descrizione = ?, banner_url = ?, logo_url = ?, website_url = ?, shop_url = ?, discord_url = ?, telegram_url = ?, modalita = ?, staff_list = ?, social_links = ? WHERE id = ? AND owner_id = ?");
                    $stmt->execute([$nome, $ip, $versione, $tipo_server, $descrizione, $banner_url, $logo_url, $website_url, $shop_url, $discord_url, $telegram_url, $modalita_json, $staff_list_json, $social_links_json, $server_id, $user_id]);
                } catch (PDOException $e1) {
                    $stmt = $pdo->prepare("UPDATE sl_servers SET nome = ?, ip = ?, versione = ?, tipo_server = ?, descrizione = ?, banner_url = ?, logo_url = ?, website_url = ?, shop_url = ?, discord_url = ?, telegram_url = ?, modalita = ? WHERE id = ? AND owner_id = ?");
                    $stmt->execute([$nome, $ip, $versione, $tipo_server, $descrizione, $banner_url, $logo_url, $website_url, $shop_url, $discord_url, $telegram_url, $server_id, $user_id]);
                }
                $message = 'Server modificato con successo!';
                $server_to_edit = null;
                $edit_server_id = 0;
            } else {
                $error = 'Non hai i permessi per modificare questo server.';
            }
        } catch (PDOException $e) {
            $error = 'Errore durante la modifica del server.';
        }
    }
}

// Recupera i dati dell'utente dal database
try {
    $stmt = $pdo->prepare("SELECT * FROM sl_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
    redirect('/login');
    }
} catch (PDOException $e) {
    error_log("Errore nel recupero dati utente: " . $e->getMessage());
    $user = null;
}

// Recupera le statistiche dell'utente
$user_stats = [
    'total_votes' => 0,
    'servers_voted' => 0,
    'join_date' => $user['data_registrazione'] ?? date('Y-m-d H:i:s')
];

try {
    // Voti totali dati dall'utente
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM sl_votes WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_stats['total_votes'] = $stmt->fetch()['total'];
    
    // Server diversi votati
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT server_id) as total FROM sl_votes WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_stats['servers_voted'] = $stmt->fetch()['total'];
} catch (PDOException $e) {
    error_log("Errore nel recupero statistiche utente: " . $e->getMessage());
}

// Recupera il voto giornaliero dell'utente
$daily_vote = null;
try {
    $daily_vote = getUserDailyVoteInfo($user_id, $pdo);
} catch (PDOException $e) {
    error_log("Errore nel recupero voto giornaliero: " . $e->getMessage());
}

// Recupera tutti i voti dell'utente per la sezione "Elenco Voti"
$user_votes = [];
try {
    $stmt = $pdo->prepare("
        SELECT v.*, s.nome as server_name, s.ip as server_ip, s.logo_url as server_logo
        FROM sl_votes v
        JOIN sl_servers s ON v.server_id = s.id
        WHERE v.user_id = ?
        ORDER BY v.data_voto DESC
        LIMIT 100
    ");
    $stmt->execute([$user_id]);
    $user_votes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Errore nel recupero voti utente: " . $e->getMessage());
}

// Recupera i server di proprietà dell'utente (attivi e in attesa di approvazione)
$owned_servers = [];
try {
    $stmt = $pdo->prepare("
        SELECT s.*, COUNT(v.id) as vote_count 
        FROM sl_servers s 
        LEFT JOIN sl_votes v ON s.id = v.server_id AND MONTH(v.data_voto) = MONTH(CURRENT_DATE()) AND YEAR(v.data_voto) = YEAR(CURRENT_DATE())
        WHERE s.owner_id = ? AND s.is_active IN (0, 1, 2)
        GROUP BY s.id 
        ORDER BY s.is_active DESC, vote_count DESC
    ");
    $stmt->execute([$user_id]);
    $owned_servers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Errore nel recupero server utente: " . $e->getMessage());
}

// Recupera le licenze dei server di proprietà dell'utente
$server_licenses = [];
try {
    $stmt = $pdo->prepare("
        SELECT sl.*, s.nome as server_name, s.ip as server_ip, s.logo_url as server_logo
        FROM sl_server_licenses sl
        JOIN sl_servers s ON sl.server_id = s.id
        WHERE s.owner_id = ? AND sl.is_active = 1
        ORDER BY s.nome ASC
    ");
    $stmt->execute([$user_id]);
    $server_licenses = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Errore nel recupero licenze server: " . $e->getMessage());
}

// Nickname Minecraft collegato (verificato)
$verified_nick = null;
try {
    $stmt = $pdo->prepare("SELECT minecraft_nick FROM sl_minecraft_links WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $verified_nick = $stmt->fetchColumn();
} catch (Exception $e) {}

$page_title = "Profilo - " . htmlspecialchars($user['minecraft_nick'] ?? 'Utente');
$include_rich_editor = true; // Abilita Quill.js per l'editor rich text
include 'header.php';
?>


<div class="profile-container">
    <div class="container">
        <!-- Header del Profilo -->
        <div class="profile-header">
            <div class="profile-avatar">
                <img src="<?php echo !empty($verified_nick) ? getMinecraftAvatar($verified_nick) : '/logo.png'; ?>" alt="Avatar" class="avatar-img">
            </div>
            <div class="profile-info">
                <h1 class="profile-nickname"><?php echo htmlspecialchars($user['minecraft_nick'] ?: 'Utente'); ?></h1>
                <p class="profile-join-date">
                    <i class="bi bi-calendar"></i> 
                    Membro dal <?php echo date('d/m/Y', strtotime($user_stats['join_date'])); ?>
                </p>
                <?php if (!empty($verified_nick)): ?>
                <p class="profile-join-date">
                    <i class="bi bi-box-seam"></i>
                    Minecraft: <a href="https://namemc.com/profile/<?= urlencode($verified_nick) ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($verified_nick); ?></a>
                </p>
                <?php else: ?>
                <p class="profile-join-date">
                    <i class="bi bi-box-seam"></i>
                    Minecraft:
                    <a href="/verifica-nickname" class="btn btn-primary btn-sm" style="margin-left: 6px;">
                        <i class="bi bi-link-45deg"></i> Collega account Minecraft
                    </a>
                </p>
                <?php endif; ?>
                <?php 
                // Determina il ruolo dell'utente e il badge da mostrare
                if ($user['is_admin']): ?>
                    <span class="admin-badge admin-role">
                        <i class="bi bi-shield-check"></i> Amministratore
                    </span>
                <?php elseif (!empty($owned_servers)): 
                    // Crea la lista dei nomi dei server posseduti
                    $server_names = array_map(function($server) {
                        return htmlspecialchars($server['nome']);
                    }, $owned_servers);
                    $server_list = implode(' / ', $server_names);
                ?>
                    <span class="admin-badge owner-role">
                        <i class="bi bi-server"></i> Owner di <?php echo $server_list; ?>
                    </span>
                <?php else: ?>
                    <span class="admin-badge user-role">
                        <i class="bi bi-person"></i> Utente
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Messaggi di successo/errore -->
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Navigazione Principale -->
        <div class="profile-navigation">
            <button class="nav-btn active" data-section="profile">
                <i class="bi bi-person-circle"></i> Il tuo profilo
            </button>
            <button class="nav-btn" data-section="votes">
                <i class="bi bi-hand-thumbs-up"></i> Elenco Voti
            </button>
            <button class="nav-btn" data-section="new-server">
                <i class="bi bi-plus-circle"></i> Nuovo server
            </button>
            <button class="nav-btn" data-section="server-management">
                <i class="bi bi-server"></i> Gestione Server
            </button>
        </div>

        <!-- Contenuto delle Sezioni -->
        <div class="profile-content">
            
            <!-- Sezione: Il mio profilo -->
            <div class="content-section active" id="profile-section">
                <div class="section-header">
                    <h2><i class="bi bi-person-circle"></i> Il mio profilo</h2>
                </div>
                
                <div class="profile-stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-hand-thumbs-up"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-number"><?php echo number_format($user_stats['total_votes']); ?></span>
                            <span class="stat-label">Voti Totali</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-server"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-number"><?php echo number_format($user_stats['servers_voted']); ?></span>
                            <span class="stat-label">Server Votati</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-collection"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-number"><?php echo count($owned_servers); ?></span>
                            <span class="stat-label">Server Posseduti</span>
                        </div>
                    </div>
                </div>

                <!-- Voto Giornaliero -->
                <div class="daily-vote-section">
                    <h3><i class="bi bi-calendar-check"></i> Voto Giornaliero</h3>
                    <?php if ($daily_vote): ?>
                        <div class="daily-vote-card voted">
                            <div class="vote-info">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Hai già votato oggi per:</span>
                                <strong><?php echo htmlspecialchars($daily_vote['nome']); ?></strong>
                            </div>
                            <div class="vote-time">
                                <?php 
                                // Converte dal timestamp UTC del DB al fuso locale configurato
                                $vote_datetime = new DateTime($daily_vote['data_voto'], new DateTimeZone('UTC'));
                                $vote_datetime->setTimezone(new DateTimeZone(date_default_timezone_get()));
                                echo $vote_datetime->format('H:i'); 
                                ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="daily-vote-card not-voted">
                            <div class="vote-info">
                                <i class="bi bi-clock"></i>
                                <span>Non hai ancora votato oggi</span>
                            </div>
<a href="/" class="vote-btn">
                                <i class="bi bi-hand-thumbs-up"></i> Vota ora
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sezione: Elenco Voti -->
            <div class="content-section" id="votes-section">
                <div class="section-header">
                    <h2><i class="bi bi-hand-thumbs-up"></i> Elenco Voti</h2>
                    <p>Tutti i voti che hai dato ai server</p>
                </div>
                
                <?php if (!empty($user_votes)): ?>
                    <div class="votes-list">
                        <?php foreach ($user_votes as $vote): ?>
                            <div class="vote-item">
                                <div class="vote-server-info">
                                    <?php if ($vote['server_logo']): ?>
                                        <img src="<?php echo htmlspecialchars($vote['server_logo']); ?>" 
                                             alt="Logo" class="server-logo-small">
                                    <?php else: ?>
                                        <div class="server-logo-small default-logo">
                                            <i class="bi bi-server"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="server-details">
                                        <h4 class="server-name">
                                            <a href="<?php $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($vote['server_name'])); echo '/server/' . urlencode(trim($slug, '-')); ?>">
                                                <?php echo htmlspecialchars($vote['server_name']); ?>
                                            </a>
                                        </h4>
                                        <p class="server-ip"><?php echo htmlspecialchars($vote['server_ip']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="vote-date">
                                    <i class="bi bi-calendar"></i>
                                    <?php 
                                    // Converte dal timestamp UTC del DB al fuso locale configurato
                                    $vote_datetime = new DateTime($vote['data_voto'], new DateTimeZone('UTC'));
                                    $vote_datetime->setTimezone(new DateTimeZone(date_default_timezone_get()));
                                    echo $vote_datetime->format('d/m/Y H:i'); 
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-votes-section">
                        <i class="bi bi-hand-thumbs-up no-votes-icon"></i>
                        <h3>Nessun Voto</h3>
                        <p>Non hai ancora votato nessun server. Inizia a votare per supportare i tuoi server preferiti!</p>
<a href="/" class="btn-primary">
                            <i class="bi bi-hand-thumbs-up"></i> Vota ora
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sezione: Nuovo Server -->
            <div class="content-section" id="new-server-section">
                <div class="section-header">
                    <h2><i class="bi bi-plus-circle"></i> Richiedi Nuovo Server</h2>
                    <p>Compila il form per richiedere l'aggiunta del tuo server alla lista</p>
                </div>
                
                <form method="POST" class="server-request-form">
                    <?php echo csrfInput(); ?>
                    <input type="hidden" name="request_server" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome">Nome Server *</label>
                            <input type="text" id="nome" name="nome" required>
                        </div>
                        <div class="form-group">
                            <label for="ip">IP Server *</label>
                            <input type="text" id="ip" name="ip" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="versione">Versione *</label>
                            <input type="text" id="versione" name="versione" placeholder="es. 1.20.1" required>
                        </div>
                        <div class="form-group">
                            <label for="tipo_server">Tipo Server</label>
                            <select id="tipo_server" name="tipo_server">
                                <option value="Java & Bedrock">Java & Bedrock</option>
                                <option value="Java">Solo Java</option>
                                <option value="Bedrock">Solo Bedrock</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="logo_url">URL Logo (facoltativo)</label>
                            <input type="url" id="logo_url" name="logo_url" placeholder="https://...">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="bi bi-send"></i> Invia Richiesta
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sezione: Gestione Server -->
            <div class="content-section" id="server-management-section">
                <div class="section-header">
                    <h2><i class="bi bi-server"></i> Gestione Server</h2>
                    <p>Gestisci i tuoi server e visualizza le licenze</p>
                </div>

                <!-- Form Modifica Server -->
                <?php if ($server_to_edit): ?>
                <div class="edit-server-form">
                    <h3><i class="bi bi-pencil"></i> Modifica Server</h3>
                    <form method="POST">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="edit_server" value="1">
                        <input type="hidden" name="server_id" value="<?php echo $server_to_edit['id']; ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nome">Nome Server *</label>
                                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($server_to_edit['nome']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="ip">IP Server *</label>
                                <input type="text" id="ip" name="ip" value="<?php echo htmlspecialchars($server_to_edit['ip']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="versione">Versione *</label>
                                <input type="text" id="versione" name="versione" value="<?php echo htmlspecialchars($server_to_edit['versione']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="tipo_server">Tipo Server</label>
                                <select id="tipo_server" name="tipo_server">
                                    <option value="Java & Bedrock" <?php echo ($server_to_edit['tipo_server'] === 'Java & Bedrock') ? 'selected' : ''; ?>>Java & Bedrock</option>
                                    <option value="Java" <?php echo ($server_to_edit['tipo_server'] === 'Java') ? 'selected' : ''; ?>>Solo Java</option>
                                    <option value="Bedrock" <?php echo ($server_to_edit['tipo_server'] === 'Bedrock') ? 'selected' : ''; ?>>Solo Bedrock</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Modalità di Gioco</label>
                            <div class="modalita-selection">
                                <?php 
                                $modalita_disponibili = [
                                    'Adventure', 'Survival', 'Vanilla', 'Factions', 'Skyblock', 
                                    'RolePlay', 'MiniGames', 'BedWars', 'KitPvP', 'SkyPvP', 
                                    'Survival Games', 'Hunger Games', 'Pixelmon', 'Prison'
                                ];
                                
                                foreach ($modalita_disponibili as $modalita): 
                                    $is_selected = in_array($modalita, $server_to_edit['modalita_array']);
                                ?>
                                    <label class="modalita-checkbox">
                                        <input type="checkbox" name="modalita[]" value="<?php echo htmlspecialchars($modalita); ?>" 
                                               <?php echo $is_selected ? 'checked' : ''; ?>>
                                        <span class="modalita-tag"><?php echo htmlspecialchars($modalita); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="descrizione">Descrizione</label>
                            <textarea id="descrizione" name="descrizione" rows="4" style="background-color: #16213e; color: white;"><?php echo htmlspecialchars($server_to_edit['descrizione']); ?></textarea>
                        </div>

                        <!-- StaffList Editor -->
                        <div class="form-group">
                            <label>Staff del Server</label>
                            <div id="stafflist-editor" class="stafflist-editor" style="background:#16213e; padding:12px; border-radius:8px; border:1px solid #28324b; color:white;">
                                <!-- Ranks e membri verranno generati da JS -->
                                <div class="stafflist-actions" style="margin-bottom:10px; display:flex; gap:8px;">
                                    <button type="button" class="btn btn-sm btn-primary" id="add-rank-btn"><i class="bi bi-plus"></i> Aggiungi Rank</button>
                                </div>
                                <div id="stafflist-ranks"></div>
                            </div>
                            <input type="hidden" name="staff_list_json" id="staff_list_json">
                        </div>

                        <!-- Social Links Editor (dinamico) -->
                        <div class="form-group">
                            <label>Link Social (personalizzati)</label>
                            <div id="sociallinks-editor" style="background:#16213e; padding:12px; border-radius:8px; border:1px solid #28324b; color:white;">
                                <div class="sociallinks-actions" style="margin-bottom:10px; display:flex; gap:8px;">
                                    <button type="button" class="btn btn-sm btn-primary" id="add-social-btn"><i class="bi bi-plus"></i> Aggiungi Link</button>
                                </div>
                                <div id="sociallinks-list"></div>
                                <p style="margin-top:8px; font-size:12px; color:#b8c1d9;">Scegli un titolo (es. Instagram, Discord, YouTube, Sito, Shop) e incolla l'URL.</p>
                            </div>
                            <input type="hidden" name="social_links_json" id="social_links_json">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="logo_url">URL Logo</label>
                                <input type="url" id="logo_url" name="logo_url" value="<?php echo htmlspecialchars($server_to_edit['logo_url']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="banner_url">URL Banner</label>
                                <input type="url" id="banner_url" name="banner_url" value="<?php echo htmlspecialchars($server_to_edit['banner_url']); ?>">
                            </div>
                        </div>

                        <!-- Link Social legacy rimossi: usare editor dinamico sopra -->
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="bi bi-check"></i> Salva Modifiche
                            </button>
                            <a href="profile.php" class="btn-secondary">
                                <i class="bi bi-x"></i> Annulla
                            </a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Lista Server -->
                <?php if (!empty($owned_servers)): ?>
                    <div class="servers-section">
                        <h3><i class="bi bi-server"></i> I Miei Server</h3>
                        
                        <div class="profile-servers-grid">
                            <?php foreach ($owned_servers as $server): ?>
                                <div class="server-card <?php 
                                    if ($server['is_active'] == 2) echo 'pending-server';
                                    elseif ($server['is_active'] == 0) echo 'disabled-server';
                                ?>">
                                    <div class="server-card-header">
                                        <?php if ($server['logo_url']): ?>
                                            <img src="<?php echo htmlspecialchars($server['logo_url']); ?>" 
                                                 alt="Logo" class="server-logo">
                                        <?php else: ?>
                                            <div class="server-logo default-logo">
                                                <i class="bi bi-server"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="server-info">
                                            <h4 class="server-name">
                                                <?php if ($server['is_active'] == 1): ?>
                                                    <a href="<?php $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($server['nome'])); echo '/server/' . urlencode(trim($slug, '-')); ?>">
                                                        <?php echo htmlspecialchars($server['nome']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($server['nome']); ?>
                                                <?php endif; ?>
                                            </h4>
                                            <p class="server-ip"><?php echo htmlspecialchars($server['ip']); ?></p>
                                            
                                            <?php if ($server['is_active'] == 2): ?>
                                                <span class="pending-status">
                                                    <i class="bi bi-clock-history"></i>
                                                    In Approvazione
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($server['is_active'] == 1): ?>
                                            <div class="server-stats">
                                                <div class="stat-item">
                                                    <span class="stat-number"><?php echo number_format($server['vote_count']); ?></span>
                                                    <span class="stat-label">Voti</span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="server-card-body">
                                        <?php if ($server['is_active'] == 1): ?>
                            <div class="server-actions">
                                <a href="<?php $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($server['nome'])); echo '/server/' . urlencode(trim($slug, '-')); ?>" class="btn-view-server">
                                    <i class="bi bi-eye"></i> Visualizza
                                </a>
                                <a href="/profile?edit_server=<?php echo $server['id']; ?>" class="btn-edit-server">
                                    <i class="bi bi-pencil"></i> Modifica
                                </a>
                            </div>
                        <?php elseif ($server['is_active'] == 2): ?>
                            <div class="pending-server-info">
                                <p class="pending-message">
                                    <i class="bi bi-info-circle"></i>
                                    Il server è in attesa di approvazione da parte degli amministratori.
                                </p>
                            </div>
                        <?php elseif ($server['is_active'] == 0): ?>
                            <div class="disabled-server-info">
                                <p class="disabled-message">
                                    <i class="bi bi-x-circle"></i>
                                    Il server è stato disabilitato da un amministratore, se vuoi fare ricorso apri un ticket su <a href="https://discord.blocksy.it" target="_blank">discord.blocksy.it</a>.
                                </p>
                                <p class="disabled-date">
                                    Server disabilitato il <?php echo date('d/m/Y', strtotime($server['data_aggiornamento'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-servers-section">
                        <i class="bi bi-server no-servers-icon"></i>
                        <h3>Nessun Server</h3>
                        <p>Non possiedi ancora nessun server attivo. Usa la sezione "Nuovo server" per richiedere l'aggiunta del tuo server!</p>
                    </div>
                <?php endif; ?>

                <!-- Licenze Server -->
                <?php if (!empty($server_licenses)): ?>
                    <div class="licenses-section">
                        <h3><i class="bi bi-key-fill"></i> Licenze dei Server</h3>
                        
                        <div class="licenses-grid">
                            <?php foreach ($server_licenses as $license): ?>
                                <div class="license-card">
                                    <div class="license-card-header">
                                        <div class="license-server-info">
                                            <?php if ($license['server_logo']): ?>
                                                <img src="<?php echo htmlspecialchars($license['server_logo']); ?>" 
                                                     alt="Logo" class="server-logo-small">
                                            <?php else: ?>
                                                <div class="server-logo-small default-logo">
                                                    <i class="bi bi-server"></i>
                                                </div>
                                            <?php endif; ?>
                                            <h4 class="license-server-name">
                                                <?php echo htmlspecialchars($license['server_name']); ?>
                                            </h4>
                                        </div>
                                        <span class="license-status <?php echo $license['is_active'] ? 'active' : 'inactive'; ?>">
                                            <i class="bi bi-<?php echo $license['is_active'] ? 'check-circle-fill' : 'x-circle-fill'; ?>"></i>
                                            <?php echo $license['is_active'] ? 'Attiva' : 'Inattiva'; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="license-card-body">
                                        <div class="license-key-display">
                                            <span class="license-label">License Key:</span>
                                            <div class="license-key-container">
                                                <code class="license-key-value license-hidden">
                                                    <span class="license-dots">•••••••••••••••••••••</span>
                                                    <span class="license-text" style="display: none;"><?php echo htmlspecialchars($license['license_key']); ?></span>
                                                </code>
                                            </div>
                                        </div>
                                        
                                        <div class="license-meta">
                                            <div class="license-meta-item">
                                                <i class="bi bi-calendar"></i>
                                                <span>Creata: <?php echo date('d/m/Y', strtotime($license['created_at'])); ?></span>
                                            </div>
                                            <?php if ($license['last_used']): ?>
                                                <div class="license-meta-item">
                                                    <i class="bi bi-clock-history"></i>
                                                    <span>Ultimo uso: <?php echo date('d/m/Y H:i', strtotime($license['last_used'])); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="license-actions">
                                            <button class="view-license-btn" data-license="<?php echo htmlspecialchars($license['license_key']); ?>">
                                                <i class="bi bi-eye"></i> Visualizza
                                            </button>
                                            <button class="copy-license-btn" data-license="<?php echo htmlspecialchars($license['license_key']); ?>" title="Copia licenza">
                                                <i class="bi bi-clipboard"></i> Copia
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-licenses-section">
                        <i class="bi bi-key no-licenses-icon"></i>
                        <h3>Nessuna Licenza</h3>
                        <p>Non hai licenze attive per i tuoi server. Le licenze vengono generate automaticamente quando un server viene aggiunto; se il server risulta attivo ma non ha licenza, è stata disattivata da un amministratore.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestione navigazione tab
    const navButtons = document.querySelectorAll('.nav-btn');
    const contentSections = document.querySelectorAll('.content-section');
    
    navButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetSection = this.getAttribute('data-section');
            
            // Rimuovi classe active da tutti i bottoni e sezioni
            navButtons.forEach(btn => btn.classList.remove('active'));
            contentSections.forEach(section => section.classList.remove('active'));
            
            // Aggiungi classe active al bottone cliccato e alla sezione corrispondente
            this.classList.add('active');
            document.getElementById(targetSection + '-section').classList.add('active');
        });
    });
    
    // Gestione pulsanti licenze
    const viewLicenseButtons = document.querySelectorAll('.view-license-btn');
    
    // Funzione per visualizzare la licenza
    viewLicenseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const licenseCard = this.closest('.license-card');
            const licenseKeyDisplay = licenseCard.querySelector('.license-key-value');
            const licenseDots = licenseKeyDisplay.querySelector('.license-dots');
            const licenseText = licenseKeyDisplay.querySelector('.license-text');
            const buttonIcon = this.querySelector('i');
            
            if (licenseDots.style.display === 'none') {
                // Nascondi il testo e mostra i pallini
                licenseDots.style.display = 'inline';
                licenseText.style.display = 'none';
                buttonIcon.className = 'bi bi-eye';
                this.innerHTML = '<i class="bi bi-eye"></i> Visualizza';
            } else {
                // Mostra il testo e nascondi i pallini
                licenseDots.style.display = 'none';
                licenseText.style.display = 'inline';
                buttonIcon.className = 'bi bi-eye-slash';
                this.innerHTML = '<i class="bi bi-eye-slash"></i> Nascondi';
            }
        });
    });

    // Gestione pulsanti copia licenza
    const copyLicenseButtons = document.querySelectorAll('.copy-license-btn');
    
    copyLicenseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const licenseKey = this.getAttribute('data-license');
            
            // Copia la licenza negli appunti
            navigator.clipboard.writeText(licenseKey).then(() => {
                // Cambia temporaneamente il testo del bottone per confermare la copia
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="bi bi-check"></i> Copiato!';
                this.style.background = '#28a745';
                this.style.borderColor = '#28a745';
                this.style.color = 'white';
                
                // Ripristina il testo originale dopo 2 secondi
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.style.background = '';
                    this.style.borderColor = '';
                    this.style.color = '';
                }, 2000);
            }).catch(err => {
                console.error('Errore durante la copia: ', err);
                // Fallback per browser che non supportano clipboard API
                const textArea = document.createElement('textarea');
                textArea.value = licenseKey;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                // Mostra conferma anche per il fallback
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="bi bi-check"></i> Copiato!';
                this.style.background = '#28a745';
                this.style.borderColor = '#28a745';
                this.style.color = 'white';
                
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.style.background = '';
                    this.style.borderColor = '';
                    this.style.color = '';
                }, 2000);
            });
        });
    });

    
    // Gestione automatica della sezione attiva in base ai parametri URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('edit_server')) {
        // Se c'è il parametro edit_server, attiva la sezione Gestione Server
        navButtons.forEach(btn => btn.classList.remove('active'));
        contentSections.forEach(section => section.classList.remove('active'));
        
        const serverManagementBtn = document.querySelector('[data-section="server-management"]');
        const serverManagementSection = document.getElementById('server-management-section');
        
        if (serverManagementBtn && serverManagementSection) {
            serverManagementBtn.classList.add('active');
            serverManagementSection.classList.add('active');
        }
    }
});
</script>

<script>
// StaffList Editor JS
document.addEventListener('DOMContentLoaded', function() {
    const AVATAR_API = '<?php echo AVATAR_API; ?>';
    const ranksContainer = document.getElementById('stafflist-ranks');
    const addRankBtn = document.getElementById('add-rank-btn');
    const staffListInput = document.getElementById('staff_list_json');

    // Dati iniziali dal server (PHP → JS)
    const initialStaff = <?php echo json_encode($server_to_edit['staff_list_array'] ?? []); ?>;

    function renderRanks(data) {
        ranksContainer.innerHTML = '';
        data.forEach((group, idx) => {
            const groupEl = document.createElement('div');
            groupEl.className = 'staff-rank-group';
            groupEl.style.cssText = 'background: var(--card-bg); border:1px solid var(--border-color); padding:10px; border-radius:8px; margin-bottom:10px;';

            const headerEl = document.createElement('div');
            headerEl.style.cssText = 'display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px;';
            headerEl.innerHTML = `
                <div style="display:flex; align-items:center; gap:8px;">
                    <i class="bi bi-award"></i>
                    <input type="text" class="rank-title-input" placeholder="Nome Rank (es. Owner, Admin, Helper)" value="${group.rank ? escapeHtml(group.rank) : ''}" style="background: var(--card-bg); color:white; border:1px solid var(--border-color); padding:6px 8px; border-radius:6px; min-width:260px;">
                </div>
                <div style="display:flex; gap:6px;">
                    <button type="button" class="btn btn-sm btn-secondary add-member-btn"><i class="bi bi-person-plus"></i> Aggiungi Staffer</button>
                    <button type="button" class="btn btn-sm btn-danger remove-rank-btn"><i class="bi bi-trash"></i></button>
                </div>
            `;
            groupEl.appendChild(headerEl);

            const membersEl = document.createElement('div');
            membersEl.className = 'rank-members';
            membersEl.style.cssText = 'display:flex; flex-direction:column; gap:6px;';

            (group.members || []).forEach(member => {
                const row = document.createElement('div');
                row.style.cssText = 'display:flex; gap:8px; align-items:center;';
                const nickSafe = escapeHtml(member);
                row.innerHTML = `
                    <img class="member-avatar" src="${AVATAR_API}/${encodeURIComponent(nickSafe || 'MHF_Steve')}" alt="Avatar" width="24" height="24" style="border-radius:50%;">
                    <input type="text" class="member-name-input" placeholder="Nickname staffer" value="${nickSafe}" style="background: var(--card-bg); color:white; border:1px solid var(--border-color); padding:6px 8px; border-radius:6px; min-width:240px;">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-member-btn"><i class="bi bi-x"></i></button>
                `;
                membersEl.appendChild(row);
            });

            groupEl.appendChild(membersEl);
            ranksContainer.appendChild(groupEl);
        });
        syncHiddenInput();
    }

    function syncHiddenInput() {
        const groups = Array.from(ranksContainer.querySelectorAll('.staff-rank-group')).map(groupEl => {
            const rank = groupEl.querySelector('.rank-title-input').value.trim();
            const members = Array.from(groupEl.querySelectorAll('.member-name-input'))
                .map(inp => inp.value.trim())
                .filter(v => v.length > 0);
            return { rank, members };
        }).filter(g => g.rank.length > 0);
        staffListInput.value = JSON.stringify(groups);
    }

    function escapeHtml(str) { return str.replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[s])); }

    // Event delegation
    ranksContainer.addEventListener('input', function(e) {
        // Sync JSON
        syncHiddenInput();
        // Aggiorna avatar se cambia il nickname
        if (e.target && e.target.classList.contains('member-name-input')) {
            const row = e.target.closest('div');
            const img = row ? row.querySelector('.member-avatar') : null;
            if (img) {
                const nick = e.target.value.trim() || 'MHF_Steve';
                img.src = AVATAR_API + '/' + encodeURIComponent(nick);
            }
        }
    });
    ranksContainer.addEventListener('click', function(e) {
        const target = e.target.closest('button');
        if (!target) return;
        if (target.classList.contains('add-member-btn')) {
            const groupEl = target.closest('.staff-rank-group');
            const membersEl = groupEl.querySelector('.rank-members');
            const row = document.createElement('div');
            row.style.cssText = 'display:flex; gap:8px; align-items:center;';
            row.innerHTML = `
                <img class="member-avatar" src="${AVATAR_API}/MHF_Steve" alt="Avatar" width="24" height="24" style="border-radius:50%;">
                <input type="text" class="member-name-input" placeholder="Nickname staffer" style="background: var(--card-bg); color:white; border:1px solid var(--border-color); padding:6px 8px; border-radius:6px; min-width:240px;">
                <button type="button" class="btn btn-sm btn-outline-danger remove-member-btn"><i class="bi bi-x"></i></button>
            `;
            membersEl.appendChild(row);
            syncHiddenInput();
        } else if (target.classList.contains('remove-member-btn')) {
            const row = target.closest('div');
            row.remove();
            syncHiddenInput();
        } else if (target.classList.contains('remove-rank-btn')) {
            const groupEl = target.closest('.staff-rank-group');
            groupEl.remove();
            syncHiddenInput();
        }
    });

    addRankBtn.addEventListener('click', function() {
        const group = { rank: '', members: [] };
        initialStaff.push(group);
        renderRanks(initialStaff);
    });

    // Inizializza
    const initData = Array.isArray(initialStaff) && initialStaff.length ? initialStaff : [];
    renderRanks(initData);
});
</script>

<script>
// Social Links Editor JS
document.addEventListener('DOMContentLoaded', function() {
    const listContainer = document.getElementById('sociallinks-list');
    const addSocialBtn = document.getElementById('add-social-btn');
    const socialLinksInput = document.getElementById('social_links_json');
    const initialSocial = <?php echo json_encode($server_to_edit['social_links_array'] ?? []); ?>;

    function renderSocial(data) {
        listContainer.innerHTML = '';
        data.forEach((item, idx) => {
            const row = document.createElement('div');
            row.className = 'social-row';
            row.style.cssText = 'display:flex; gap:8px; align-items:center; margin-bottom:8px;';
            row.innerHTML = `
                <input type="text" class="social-title-input" placeholder="Titolo (es. Instagram)" value="${escapeHtml(item.title || '')}" style="background: var(--card-bg); color:white; border:1px solid var(--border-color); padding:6px 8px; border-radius:6px; min-width:200px;">
                <input type="url" class="social-url-input" placeholder="https://..." value="${escapeHtml(item.url || '')}" style="background: var(--card-bg); color:white; border:1px solid var(--border-color); padding:6px 8px; border-radius:6px; min-width:280px;">
                <button type="button" class="btn btn-sm btn-outline-danger remove-social-btn"><i class="bi bi-x"></i></button>
            `;
            listContainer.appendChild(row);
        });
        syncSocialInput();
    }

    function syncSocialInput() {
        const items = Array.from(listContainer.querySelectorAll('.social-row')).map(row => {
            const title = row.querySelector('.social-title-input').value.trim();
            const url = row.querySelector('.social-url-input').value.trim();
            return { title, url };
        }).filter(i => i.url.length > 0);
        socialLinksInput.value = JSON.stringify(items);
    }

    function escapeHtml(str) { return (str || '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[s])); }

    listContainer.addEventListener('input', function() { syncSocialInput(); });
    listContainer.addEventListener('click', function(e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        if (btn.classList.contains('remove-social-btn')) {
            const row = btn.closest('.social-row');
            row.remove();
            syncSocialInput();
        }
    });

    addSocialBtn.addEventListener('click', function() {
        initialSocial.push({ title: '', url: '' });
        renderSocial(initialSocial);
    });

    const initData = Array.isArray(initialSocial) && initialSocial.length ? initialSocial : [];
    renderSocial(initData);
});
</script>

<?php include 'footer.php'; ?>