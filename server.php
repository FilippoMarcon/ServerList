<?php
/**
 * Pagina del Singolo Server
 * Single Server Page
 */

require_once 'config.php';

// Ottieni l'ID del server dall'URL
$server_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($server_id === 0) {
    redirect('index.php');
}

try {
    // Recupera le informazioni del server con conteggio voti
    $stmt = $pdo->prepare("SELECT s.*, 
                          (SELECT COUNT(*) FROM sl_votes WHERE server_id = s.id) as vote_count 
                          FROM sl_servers s 
                          WHERE s.id = ? AND s.is_active = 1");
    $stmt->execute([$server_id]);
    $server = $stmt->fetch();
    
    if (!$server) {
        redirect('index.php');
    }
    
    // Calcola il ranking del server usando la stessa query dell'index.php
    $stmt = $pdo->query("
        SELECT s.id, COUNT(v.id) as voti_totali 
        FROM sl_servers s 
        LEFT JOIN sl_votes v ON s.id = v.server_id 
        WHERE s.is_active = 1 
        GROUP BY s.id 
        ORDER BY voti_totali DESC, s.nome ASC
    ");
    $all_servers = $stmt->fetchAll();
    
    // Trova la posizione del server corrente nella classifica
    $server_rank = 1;
    foreach ($all_servers as $index => $ranked_server) {
        if ($ranked_server['id'] == $server_id) {
            $server_rank = $index + 1;
            break;
        }
    }
    
    // Debug: verifica il calcolo del ranking
    // Ottieni la classifica completa per debug
    if (isset($_GET['debug'])) {
        $debug_stmt = $pdo->query("
            SELECT s.nome, s.id, COUNT(v.id) as voti_totali 
            FROM sl_servers s 
            LEFT JOIN sl_votes v ON s.id = v.server_id 
            WHERE s.is_active = 1 
            GROUP BY s.id 
            ORDER BY voti_totali DESC, s.nome ASC
        ");
        $debug_servers = $debug_stmt->fetchAll();
        
        echo "<!-- DEBUG CLASSIFICA:\n";
        foreach ($debug_servers as $i => $debug_server) {
            $pos = $i + 1;
            $current = ($debug_server['id'] == $server_id) ? " <-- QUESTO SERVER" : "";
            echo "Pos {$pos}: {$debug_server['nome']} ({$debug_server['voti_totali']} voti){$current}\n";
        }
        echo "-->";
    }
    
    // Determina la classe CSS per il ranking
    $rank_class = '';
    if ($server_rank == 1) $rank_class = 'gold';
    elseif ($server_rank == 2) $rank_class = 'silver';
    elseif ($server_rank == 3) $rank_class = 'bronze';
    
    // Recupera TUTTI gli utenti che hanno votato per questo server OGGI (voti giornalieri)
    $stmt = $pdo->prepare("SELECT u.minecraft_nick, v.data_voto 
                          FROM sl_votes v 
                          JOIN sl_users u ON v.user_id = u.id 
                          WHERE v.server_id = ? AND DATE(v.data_voto) = CURDATE()
                          ORDER BY v.data_voto DESC");
    $stmt->execute([$server_id]);
    $voters = $stmt->fetchAll();
    
    // Controlla se l'utente loggato può votare (NUOVO SISTEMA)
    $can_vote = false;
    $user_has_voted_today = false;
    $voted_server_name = '';
    $time_until_next_vote = '';
    
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        
        // Controlla se l'utente ha già votato oggi (qualsiasi server)
        $today_vote_info = getUserDailyVoteInfo($user_id, $pdo);
        
        if ($today_vote_info) {
            $user_has_voted_today = true;
            $voted_server_name = $today_vote_info['nome'];
            
            // Calcola il tempo fino a mezzanotte
            $now = new DateTime();
            $midnight = new DateTime('tomorrow midnight');
            $time_until_midnight = $midnight->diff($now);
            
            $hours = $time_until_midnight->h;
            $minutes = $time_until_midnight->i;
            $time_until_next_vote = "{$hours}h {$minutes}m";
        } else {
            $can_vote = true;
        }
    }
    
} catch (PDOException $e) {
    redirect('index.php');
}

$page_title = htmlspecialchars($server['nome']);
include 'header.php';
?>

<style>
/* Server rank badge styling */
.server-rank-badge {
    background: var(--gradient-secondary);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.server-rank-badge.gold {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #1a1a2e;
}

.server-rank-badge.silver {
    background: linear-gradient(135deg, #c0c0c0 0%, #e5e5e5 100%);
    color: #1a1a2e;
}

.server-rank-badge.bronze {
    background: linear-gradient(135deg, #cd7f32 0%, #daa520 100%);
    color: white;
}

/* Quill editor content styling for server description */
.server-description .description-text {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 2rem;
    margin: 1.5rem 0;
    line-height: 1.6;
    font-size: 1.1rem;
    color: var(--text-primary);
    white-space: normal;
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
    hyphens: auto;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';
}

/* Quill content styling */
.server-description .description-text h1,
.server-description .description-text h2,
.server-description .description-text h3 {
    color: var(--text-primary);
    font-weight: 700;
    margin: 1.5rem 0 1rem 0;
    line-height: 1.3;
}

.server-description .description-text h1 {
    font-size: 2rem;
    border-bottom: 2px solid var(--accent-purple);
    padding-bottom: 0.5rem;
}

.server-description .description-text h2 {
    font-size: 1.6rem;
    color: var(--accent-purple);
}

.server-description .description-text h3 {
    font-size: 1.3rem;
    color: var(--accent-green);
}

.server-description .description-text p {
    margin: 0.5rem 0;
    line-height: 1.6;
}

.server-description .description-text br {
    display: block;
    margin: 0;
    content: "";
    line-height: 0.8;
}

.server-description .description-text div {
    margin: 0;
}

/* Gestione specifica per il contenuto Quill */
.server-description .description-text .ql-editor {
    padding: 0;
    border: none;
    background: transparent;
}

.server-description .description-text .ql-editor p {
    margin: 0.3rem 0;
    line-height: 1.6;
}

.server-description .description-text .ql-editor p:first-child {
    margin-top: 0;
}

.server-description .description-text .ql-editor p:last-child {
    margin-bottom: 0;
}

.server-description .description-text strong {
    color: var(--accent-purple);
    font-weight: 700;
}

.server-description .description-text em {
    color: var(--accent-green);
    font-style: italic;
}

.server-description .description-text u {
    text-decoration: underline;
    text-decoration-color: var(--accent-purple);
}

.server-description .description-text ol,
.server-description .description-text ul {
    margin: 1rem 0;
    padding-left: 2rem;
}

.server-description .description-text li {
    margin: 0.5rem 0;
    line-height: 1.6;
}

.server-description .description-text ol li {
    list-style-type: decimal;
}

.server-description .description-text ul li {
    list-style-type: disc;
}

.server-description .description-text a {
    color: var(--accent-purple);
    text-decoration: none;
    border-bottom: 1px solid transparent;
    transition: all 0.3s ease;
}

.server-description .description-text a:hover {
    border-bottom-color: var(--accent-purple);
    color: var(--accent-green);
}

.server-description .description-text img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 1rem 0;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.server-description .description-text blockquote {
    border-left: 4px solid var(--accent-purple);
    margin: 1.5rem 0;
    padding: 1rem 1.5rem;
    background: var(--secondary-bg);
    border-radius: 0 8px 8px 0;
    font-style: italic;
}

/* Text alignment classes */
.server-description .description-text .ql-align-center {
    text-align: center;
}

.server-description .description-text .ql-align-right {
    text-align: right;
}

.server-description .description-text .ql-align-justify {
    text-align: justify;
}

/* Color styling for Quill editor colors */
.server-description .description-text .ql-color-red {
    color: #e74c3c;
}

.server-description .description-text .ql-color-green {
    color: var(--accent-green);
}

.server-description .description-text .ql-color-blue {
    color: #3498db;
}

.server-description .description-text .ql-color-purple {
    color: var(--accent-purple);
}

.server-description .description-text .ql-bg-yellow {
    background-color: #f1c40f;
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
}

.server-description .description-text .ql-bg-green {
    background-color: rgba(46, 204, 113, 0.2);
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
}

.server-description .description-text .ql-bg-blue {
    background-color: rgba(52, 152, 219, 0.2);
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
}

/* Migliore gestione delle interruzioni di riga */
.server-description .description-text br {
            display: block;
            margin: 0.8rem 0;
            height: 0.8rem;
        }

/* Stili per elementi HTML di Quill */
.server-description .description-text strong,
.server-description .description-text b {
    font-weight: bold;
    color: #ffffff;
}

.server-description .description-text em,
.server-description .description-text i {
    font-style: italic;
}

.server-description .description-text u {
    text-decoration: underline;
}

.server-description .description-text h1,
.server-description .description-text h2,
.server-description .description-text h3,
.server-description .description-text h4,
.server-description .description-text h5,
.server-description .description-text h6 {
    color: #ffffff;
    margin: 0.5rem 0;
    font-weight: bold;
}

.server-description .description-text h1 { font-size: 1.8rem; }
.server-description .description-text h2 { font-size: 1.6rem; }
.server-description .description-text h3 { font-size: 1.4rem; }
.server-description .description-text h4 { font-size: 1.2rem; }
.server-description .description-text h5 { font-size: 1.1rem; }
.server-description .description-text h6 { font-size: 1rem; }

.server-description .description-text ul,
.server-description .description-text ol {
    margin: 0.5rem 0;
    padding-left: 1.5rem;
}

.server-description .description-text li {
    margin: 0.2rem 0;
}

.server-description .description-text blockquote {
    border-left: 3px solid #007bff;
    padding-left: 1rem;
    margin: 0.5rem 0;
    font-style: italic;
    background-color: rgba(0, 123, 255, 0.1);
}

.server-description .description-text a {
    color: #007bff;
    text-decoration: none;
}

.server-description .description-text a:hover {
            text-decoration: underline;
        }
        
        .server-description .description-text p {
            margin: 0.5rem 0;
            line-height: 1.6;
        }
        
        .server-description .description-text p:first-child {
            margin-top: 0;
        }
        
        .server-description .description-text p:last-child {
            margin-bottom: 0;
        }
</style>

<!-- Server Page Container -->
<div class="server-page-container">
    <!-- Server Header with Banner -->
    <div class="server-header">
        <?php if ($server['banner_url']): ?>
            <div class="server-banner-bg" style="background-image: url('<?php echo htmlspecialchars($server['banner_url']); ?>');">
                <div class="server-banner-overlay"></div>
            </div>
        <?php else: ?>
            <div class="server-banner-bg default-banner">
                <div class="server-banner-overlay"></div>
            </div>
        <?php endif; ?>
        
        <div class="container" style="display: flex !important; align-items: center !important; justify-content: center !important; height: 100% !important; min-height: 400px !important;">
            <div class="server-header-content" style="display: flex !important; align-items: center !important; justify-content: space-between !important; width: 100% !important; max-width: 1200px !important;">
                <div class="server-main-info">
                    <div class="server-logo-section">
                        <?php if ($server['logo_url']): ?>
                            <img src="<?php echo htmlspecialchars($server['logo_url']); ?>" 
                                 alt="Logo" class="server-logo-large">
                        <?php else: ?>
                            <div class="server-logo-large default-logo">
                                <i class="bi bi-server"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="server-title-section">
                        <h1 class="server-title"><?php echo htmlspecialchars($server['nome']); ?></h1>
                        <div class="server-details-row">
                            <div class="server-rank-badge <?php echo $rank_class; ?>">
                                <?php if ($server_rank <= 3): ?>
                                    <i class="bi bi-trophy-fill"></i>
                                <?php endif; ?>
                                <?php echo $server_rank; ?>°
                            </div>
                            <div class="server-ip-display" title="Clicca per copiare">
                                <?php echo htmlspecialchars($server['ip']); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="server-vote-section">
                    <div class="vote-button-container">
                        <?php if ($can_vote): ?>
                            <button class="vote-button" onclick="voteServer(<?php echo $server_id; ?>)">
                                <i class="bi bi-hand-thumbs-up"></i> Vota il server
                            </button>
                        <?php else: ?>
                            <button class="vote-button vote-disabled" disabled>
                                <i class="bi bi-check-circle"></i> 
                                <?php if ($user_has_voted_today): ?>
                                    Hai già votato oggi
                                <?php else: ?>
                                    Accedi per votare
                                <?php endif; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="vote-count">
                        <?php echo number_format($server['vote_count']); ?> voti
                    </div>
                    <?php if ($can_vote): ?>
                        <div class="vote-action">
                            <button class="vote-increment" onclick="voteServer(<?php echo $server_id; ?>)">
                                +1
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($user_has_voted_today): ?>
                        <div class="vote-info-below">
                            <small>Hai votato: <strong><?php echo htmlspecialchars($voted_server_name); ?></strong></small>
                            <br>
                            <small>Prossimo voto tra: <strong><?php echo $time_until_next_vote; ?></strong></small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Server Content -->
    <div class="container server-content">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Navigation Tabs -->
                <div class="server-nav-tabs">
                    <button class="tab-btn active" data-tab="description">DESCRIZIONE</button>
                    <button class="tab-btn" data-tab="staff">STAFF</button>
                    <button class="tab-btn" data-tab="stats">STATISTICHE</button>
                </div>
                
                <!-- Tab Content -->
                <div class="tab-content-container">
                    <!-- Description Tab -->
                    <div class="tab-content active" id="description">
                        <?php if ($server['banner_url']): ?>
                            <div class="content-banner">
                                <img src="<?php echo htmlspecialchars($server['banner_url']); ?>" 
                                     alt="Banner" class="content-banner-img">
                                <div class="banner-text-overlay">
                                    <h2><?php echo htmlspecialchars($server['nome']); ?></h2>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="server-description">
                            <?php if (!empty($server['descrizione'])): ?>
                                <div class="description-text">
                                    <?php 
                                    // Il contenuto è già sanitizzato con HTML sicuro preservato
                                    echo $server['descrizione'];
                                    ?>
                                </div>
                            <?php else: ?>
                                <p class="description-text">
                                    Questo server non ha ancora una descrizione personalizzata.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Staff Tab -->
                    <div class="tab-content" id="staff">
                        <div class="staff-section">
                            <h3>Team del Server</h3>
                            <p>Informazioni sullo staff non disponibili al momento.</p>
                        </div>
                    </div>
                    
                    <!-- Stats Tab -->
                    <div class="tab-content" id="stats">
                        <div class="stats-section">
                            <h3>Statistiche Server</h3>
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo number_format($server['vote_count']); ?></div>
                                    <div class="stat-label">Voti Totali</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo count($voters); ?></div>
                                    <div class="stat-label">Votanti Recenti</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Server Info Card -->
                <div class="server-info-card">
                    <h4><i class="bi bi-info-circle"></i> Info Server</h4>
                    
                    <div class="info-item">
                        <span class="info-label">Edizione:</span>
                        <span class="info-value"><?php echo htmlspecialchars($server['tipo_server'] ?? 'Java & Bedrock'); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Versione:</span>
                        <span class="info-value"><?php echo htmlspecialchars($server['versione'] ?: '1.16.5 - 1.20.2'); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Utenti Online:</span>
                        <span class="info-value">
                            <i class="bi bi-person-fill"></i> 
                            <span data-playercounter-ip="<?php echo htmlspecialchars($server['ip']); ?>">...</span>
                        </span>
                    </div>
                    
                    <div class="server-tags-section">
                        <?php 
                        $modalita_array = [];
                        if (!empty($server['modalita'])) {
                            $modalita_array = json_decode($server['modalita'], true);
                            if (!is_array($modalita_array)) {
                                $modalita_array = [];
                            }
                        }
                        
                        if (!empty($modalita_array)): 
                            foreach ($modalita_array as $modalita): ?>
                                <span class="server-tag-modern"><?php echo htmlspecialchars($modalita); ?></span>
                            <?php endforeach;
                        else: ?>
                            <span class="server-tag-modern">Generale</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Social Links -->
                <div class="server-social-card">
                    <h4><i class="bi bi-share"></i> Social</h4>
                    
                    <div class="social-links-modern">
                        <a href="#" class="social-link-modern website">
                            <i class="bi bi-globe"></i> Website
                            <span class="social-url">https://forum.<?php echo strtolower($server['nome']); ?>...</span>
                        </a>
                        
                        <a href="#" class="social-link-modern shop">
                            <i class="bi bi-shop"></i> Shop
                            <span class="social-url">https://store.<?php echo strtolower($server['nome']); ?>.c...</span>
                        </a>
                        
                        <a href="#" class="social-link-modern discord">
                            <i class="bi bi-discord"></i> Discord
                            <span class="social-url">https://discord.gg/<?php echo strtolower($server['nome']); ?>...</span>
                        </a>
                        
                        <a href="#" class="social-link-modern telegram">
                            <i class="bi bi-telegram"></i> Telegram
                            <span class="social-url">https://telegram.me/<?php echo strtolower($server['nome']); ?>...</span>
                        </a>
                    </div>
                </div>
                
                <!-- Recent Voters -->
                <div class="recent-voters-card">
                    <h4><i class="bi bi-people"></i> Ultimi Voti (<?php echo count($voters); ?>)</h4>
                    
                    <div class="voters-grid <?php echo count($voters) > 40 ? 'scrollable' : ''; ?>">
                        <?php 
                        // Mostra TUTTI i voti giornalieri
                        foreach ($voters as $voter): 
                        ?>
                            <div class="voter-avatar-modern" 
                                 title="<?php echo htmlspecialchars($voter['minecraft_nick']); ?>"
                                 data-nickname="<?php echo htmlspecialchars($voter['minecraft_nick']); ?>"
                                 data-vote-time="<?php echo $voter['data_voto']; ?>">
                                <img src="https://mc-heads.net/avatar/<?php echo urlencode($voter['minecraft_nick']); ?>" 
                                     alt="<?php echo htmlspecialchars($voter['minecraft_nick']); ?>"
                                     onerror="this.src='https://via.placeholder.com/32x32/6c757d/ffffff?text=?';">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Funzione per votare il server
function voteServer(serverId) {
    if (!confirm('Sei sicuro di voler votare questo server? Puoi votare solo una volta ogni 24 ore.')) {
        return;
    }
    
    fetch('vote.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'server_id=' + serverId + '&action=vote'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showCustomToast('Voto registrato con successo!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showCustomToast(data.message || 'Errore durante la votazione.', 'error');
        }
    })
    .catch(error => {
        showCustomToast('Errore di connessione. Riprova.', 'error');
    });
}

// Funzione per copiare l'IP del server
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showCustomToast('IP copiato negli appunti!', 'success');
    }).catch(function() {
        // Fallback per browser più vecchi
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showCustomToast('IP copiato negli appunti!', 'success');
    });
}

// Toast personalizzato con grafica migliore
function showCustomToast(message, type = 'success') {
    // Rimuovi toast esistenti
    const existingToasts = document.querySelectorAll('.custom-toast');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = 'custom-toast';
    
    const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
    const bgColor = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6';
    
    toast.innerHTML = `
        <div style="
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${bgColor};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            font-weight: 500;
            animation: slideInToast 0.3s ease-out;
        ">
            <span style="font-size: 16px;">${icon}</span>
            ${message}
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Rimuovi dopo 3 secondi
    setTimeout(() => {
        toast.style.animation = 'slideOutToast 0.3s ease-in forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Funzione per condividere su Discord
function shareOnDiscord() {
    const serverName = '<?php echo addslashes($server['nome']); ?>';
    const serverIP = '<?php echo addslashes($server['ip']); ?>';
    const serverVersion = '<?php echo addslashes($server['versione']); ?>';
    const currentUrl = window.location.href;
    
    const message = `🎮 **${serverName}**\n` +
                   `📍 IP: \`${serverIP}\`\n` +
                   `🔧 Versione: ${serverVersion}\n` +
                   `🔗 Vota qui: ${currentUrl}`;
    
    // Copia il messaggio negli appunti
    navigator.clipboard.writeText(message).then(function() {
        showToast('Messaggio copiato! Incollalo su Discord.', 'success');
    });
}

// Funzione per copiare il link di condivisione
function copyShareLink() {
    const currentUrl = window.location.href;
    copyToClipboard(currentUrl);
}

// Gestione degli avatar con fallback
function handleAvatarError(img) {
    img.src = 'https://via.placeholder.com/64x64/6c757d/ffffff?text=?';
    img.onerror = null;
}

// Funzioni per i tooltip dei votanti
function showVoterTooltip(element, nickname, voteTime) {
    // Rimuovi tooltip esistenti
    hideVoterTooltip();
    
    // Calcola il tempo trascorso
    const now = new Date();
    const voteDate = new Date(voteTime);
    const diffMs = now - voteDate;
    const diffMins = Math.floor(diffMs / (1000 * 60));
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    
    let timeAgo;
    if (diffMins < 1) {
        timeAgo = "ora";
    } else if (diffMins < 60) {
        timeAgo = diffMins + (diffMins === 1 ? " minuto fa" : " minuti fa");
    } else if (diffHours < 24) {
        timeAgo = diffHours + (diffHours === 1 ? " ora fa" : " ore fa");
    } else {
        timeAgo = diffDays + (diffDays === 1 ? " giorno fa" : " giorni fa");
    }
    
    const tooltip = document.createElement('div');
    tooltip.className = 'voter-tooltip';
    tooltip.textContent = nickname + ' - ' + timeAgo;
    tooltip.style.cssText = `
        position: absolute;
        background: rgba(0, 0, 0, 0.9);
        color: white;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
        z-index: 1000;
        pointer-events: none;
        transform: translateX(-50%);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = (rect.left + rect.width / 2) + 'px';
    tooltip.style.top = (rect.top + window.scrollY - tooltip.offsetHeight - 8) + 'px';
}

function hideVoterTooltip() {
    const existingTooltip = document.querySelector('.voter-tooltip');
    if (existingTooltip) {
        existingTooltip.remove();
    }
}

// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and contents
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
        });
    });
    
    // Server IP click to copy
    const serverIP = document.querySelector('.server-ip-display');
    if (serverIP) {
        serverIP.addEventListener('click', function() {
            copyToClipboard(this.textContent);
        });
    }
    
    // Avatar error handling e tooltip
    const voterAvatars = document.querySelectorAll('.voter-avatar-modern');
    voterAvatars.forEach(avatar => {
        const img = avatar.querySelector('img');
        const nickname = avatar.getAttribute('data-nickname');
        
        // Error handling per immagini
        if (img) {
            img.addEventListener('error', function() {
                this.src = 'https://via.placeholder.com/32x32/6c757d/ffffff?text=?';
            });
        }
        
        // Tooltip events
        avatar.addEventListener('mouseenter', function() {
            const voteTime = this.getAttribute('data-vote-time');
            showVoterTooltip(this, nickname, voteTime);
        });
        
        avatar.addEventListener('mouseleave', function() {
            hideVoterTooltip();
        });
    });
});
</script>



<?php include 'footer.php'; ?>