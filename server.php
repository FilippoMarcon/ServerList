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
    
    // Recupera gli utenti che hanno votato per questo server (ultimi 20 voti)
    $stmt = $pdo->prepare("SELECT u.minecraft_nick, v.data_voto 
                          FROM sl_votes v 
                          JOIN sl_users u ON v.user_id = u.id 
                          WHERE v.server_id = ? 
                          ORDER BY v.data_voto DESC 
                          LIMIT 20");
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
                            <div class="server-rank-badge">
                                <i class="bi bi-trophy-fill"></i> 1°
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
                        <div class="vote-info-below" style="margin-top: 1.5rem;">
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
                                    <p><?php echo htmlspecialchars($server['descrizione'] ?: 'Il Roleplay Realistico Italiano'); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="server-description">
                            <p class="description-text">
                                <?php echo nl2br(htmlspecialchars($server['descrizione'] ?: 'Hai mai sognato di giocare a minecraft simulando al 100% la vita reale? Nella città di NeoTecno questo è possibile!')); ?>
                            </p>
                            
                            <div class="description-highlight">
                                <p>Su <?php echo htmlspecialchars($server['nome']); ?> tutto il gameplay è gestito dagli utenti.</p>
                            </div>
                            
                            <p class="description-detail">
                                Come nella vita reale, ogni cittadino contribuisce all'esperienza degli altri: il governo stabilisce le leggi della nazione, 
                                gli insegnanti ti istruiranno a scuola, i dottori ti cureranno in ospedale, le forze armate ti proteggeranno dalle 
                                associazioni criminali, e tanti lavoratori di decine altre aziende ti aiuteranno nel tuo gameplay!
                            </p>
                            
                            <div class="gameplay-highlight">
                                <p><em>Qual è il bello?</em> <strong>Che puoi diventare uno di loro.</strong></p>
                            </div>
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
                        <span class="info-value">Java</span>
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
                        <span class="server-tag-modern">RolePlay</span>
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
                    <h4><i class="bi bi-people"></i> Ultimi Voti</h4>
                    
                    <div class="voters-grid">
                        <?php 
                        $recent_voters_display = array_slice($voters, 0, 16);
                        foreach ($recent_voters_display as $voter): 
                        ?>
                            <div class="voter-avatar-modern" 
                                 title="<?php echo htmlspecialchars($voter['minecraft_nick']); ?>"
                                 data-nickname="<?php echo htmlspecialchars($voter['minecraft_nick']); ?>">
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
function showVoterTooltip(element, nickname) {
    // Rimuovi tooltip esistenti
    hideVoterTooltip();
    
    const tooltip = document.createElement('div');
    tooltip.className = 'voter-tooltip';
    tooltip.textContent = nickname;
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
    tooltip.style.top = (rect.top - tooltip.offsetHeight - 8) + 'px';
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
            showVoterTooltip(this, nickname);
        });
        
        avatar.addEventListener('mouseleave', function() {
            hideVoterTooltip();
        });
    });
});
</script>



<?php include 'footer.php'; ?>