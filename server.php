<?php
/**
 * Pagina del Singolo Server
 * Single Server Page
 */

// Ottieni l'ID del server dall'URL con pulizia migliorata PRIMA di caricare config.php
$raw_id = $_GET['id'] ?? '';
// Rimuovi caratteri non numerici e spazi
$clean_id = preg_replace('/[^0-9]/', '', trim($raw_id));
$server_id = !empty($clean_id) ? (int)$clean_id : 0;

// Se l'ID è 0, redirect immediatamente PRIMA di caricare config.php
if ($server_id === 0) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

try {
    // Recupera le informazioni del server con conteggio voti
    $stmt = $pdo->prepare("SELECT s.*, 
                          (SELECT COUNT(*) FROM sl_votes WHERE server_id = s.id AND MONTH(data_voto) = MONTH(CURRENT_DATE()) AND YEAR(data_voto) = YEAR(CURRENT_DATE())) as voti
                          FROM sl_servers s 
                          WHERE s.id = ? AND s.is_active = 1");
    $stmt->execute([$server_id]);
    $server = $stmt->fetch();
    
    if (!$server) {
        header('Location: index.php');
        exit;
    }
    
    // Calcola il ranking del server usando la stessa query dell'index.php
    $stmt = $pdo->prepare("
            SELECT s.id, s.nome, COUNT(v.id) as voti_totali 
            FROM sl_servers s 
            LEFT JOIN sl_votes v ON s.id = v.server_id AND MONTH(v.data_voto) = MONTH(CURRENT_DATE()) AND YEAR(v.data_voto) = YEAR(CURRENT_DATE())
            WHERE s.is_active = 1 
            GROUP BY s.id 
            ORDER BY voti_totali DESC, s.nome ASC
        ");
    $stmt->execute(); // FIX: Aggiunto execute() mancante
    $all_servers = $stmt->fetchAll();
    
    // Trova la posizione del server corrente nella classifica
    $server_rank = 1;
    foreach ($all_servers as $index => $ranked_server) {
        if ($ranked_server['id'] == $server_id) {
            $server_rank = $index + 1;
            break;
        }
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
    header('Location: index.php');
    exit;
}

$page_title = htmlspecialchars($server['nome']);
include 'header.php';
?>

<!-- Pagina Server utilizzando le classi di header.php -->
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <!-- Header del Server -->
            <div class="server-card-modern mb-4">
                <div class="server-header-modern">
                    <?php if (!empty($server['banner_url'])): ?>
                        <img src="<?php echo htmlspecialchars($server['banner_url']); ?>" alt="Banner" class="server-banner">
                    <?php endif; ?>
                    
                    <div class="server-info-overlay">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <?php if (!empty($server['logo_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($server['logo_url']); ?>" alt="Logo" class="server-logo-large me-3">
                                <?php endif; ?>
                                <div>
                                    <h1 class="server-name-large mb-2"><?php echo htmlspecialchars($server['nome']); ?></h1>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="server-ip-modern" onclick="copyToClipboard('<?php echo htmlspecialchars($server['ip']); ?>')">
                                            <i class="bi bi-server"></i> <?php echo htmlspecialchars($server['ip']); ?>
                                        </span>
                                        <?php if ($server['posizione'] <= 3): ?>
                                            <span class="rank-badge rank-<?php echo $server['posizione']; ?>">
                                                <i class="bi bi-trophy-fill"></i> #<?php echo $server['posizione']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="rank-badge">
                                                #<?php echo $server['posizione']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button class="vote-btn-modern" onclick="voteServer(<?php echo $server['id']; ?>)">
                                    <i class="bi bi-heart-fill"></i>
                                    <span>Vota Server</span>
                                </button>
                                <div class="server-stats-modern mt-2">
                                    <span class="stat-item">
                                        <i class="bi bi-people-fill"></i>
                                        <?php echo $server['giocatori_online']; ?>/<?php echo $server['giocatori_max']; ?>
                                    </span>
                                    <span class="stat-item">
                                        <i class="bi bi-heart-fill"></i>
                                        <?php echo $server['voti']; ?> voti
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contenuto del Server -->
            <div class="row">
                <div class="col-lg-8">
                    <!-- Descrizione -->
                    <div class="content-card-modern mb-4">
                        <h3 class="section-title-modern">
                            <i class="bi bi-info-circle-fill"></i>
                            Descrizione
                        </h3>
                        <div class="server-description">
                            <?php echo nl2br(htmlspecialchars($server['descrizione'])); ?>
                        </div>
                    </div>
                    
                    <!-- Statistiche Dettagliate -->
                    <div class="content-card-modern mb-4">
                        <h3 class="section-title-modern">
                            <i class="bi bi-graph-up"></i>
                            Statistiche
                        </h3>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="bi bi-trophy"></i>
                                </div>
                                <div class="stat-info">
                                    <div class="stat-value">#<?php echo $server['posizione']; ?></div>
                                    <div class="stat-label">Posizione</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="bi bi-heart-fill"></i>
                                </div>
                                <div class="stat-info">
                                    <div class="stat-value"><?php echo $server['voti']; ?></div>
                                    <div class="stat-label">Voti Totali</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div class="stat-info">
                                    <div class="stat-value"><?php echo $server['giocatori_online']; ?>/<?php echo $server['giocatori_max']; ?></div>
                                    <div class="stat-label">Giocatori</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="bi bi-calendar"></i>
                                </div>
                                <div class="stat-info">
                                    <div class="stat-value"><?php echo date('d/m/Y', strtotime($server['data_aggiunta'])); ?></div>
                                    <div class="stat-label">Aggiunto</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Informazioni Server -->
                    <div class="sidebar-card-modern mb-4">
                        <h4 class="sidebar-title">
                            <i class="bi bi-info-circle"></i>
                            Informazioni Server
                        </h4>
                        <div class="server-info-list">
                            <div class="info-item">
                                <span class="info-label">IP Server:</span>
                                <span class="info-value server-ip-copy" onclick="copyToClipboard('<?php echo htmlspecialchars($server['ip']); ?>')">
                                    <?php echo htmlspecialchars($server['ip']); ?>
                                    <i class="bi bi-clipboard"></i>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Versione:</span>
                                <span class="info-value"><?php echo htmlspecialchars($server['versione']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Modalità:</span>
                                <span class="info-value"><?php echo htmlspecialchars($server['modalita']); ?></span>
                            </div>
                            <?php if (!empty($server['sito_web'])): ?>
                            <div class="info-item">
                                <span class="info-label">Sito Web:</span>
                                <span class="info-value">
                                    <a href="<?php echo htmlspecialchars($server['sito_web']); ?>" target="_blank" class="server-link">
                                        Visita <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Ultimi Votanti -->
                    <?php if (!empty($daily_voters)): ?>
                    <div class="sidebar-card-modern">
                        <h4 class="sidebar-title">
                            <i class="bi bi-people"></i>
                            Ultimi Votanti (Oggi)
                        </h4>
                        <div class="voters-grid">
                            <?php foreach ($daily_voters as $voter): ?>
                                <div class="voter-avatar-modern" title="<?php echo htmlspecialchars($voter['username']); ?>">
                                    <?php if (!empty($voter['avatar_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($voter['avatar_url']); ?>" alt="<?php echo htmlspecialchars($voter['username']); ?>">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <?php echo strtoupper(substr($voter['username'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
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
</script>

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
</script>

<?php include 'footer.php'; ?>