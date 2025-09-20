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
    
    // Controlla se l'utente loggato può votare
    $can_vote = false;
    $user_has_voted_today = false;
    $time_until_next_vote = '';
    
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        
        // Controlla se l'utente ha già votato per questo server nelle ultime 24 ore
        $stmt = $pdo->prepare("SELECT data_voto FROM sl_votes 
                              WHERE server_id = ? AND user_id = ? 
                              AND data_voto >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                              ORDER BY data_voto DESC 
                              LIMIT 1");
        $stmt->execute([$server_id, $user_id]);
        $last_vote = $stmt->fetch();
        
        if ($last_vote) {
            $user_has_voted_today = true;
            // Calcola il tempo rimanente fino al prossimo voto
            $last_vote_time = strtotime($last_vote['data_voto']);
            $next_vote_time = $last_vote_time + (24 * 60 * 60); // 24 ore in secondi
            $current_time = time();
            $time_remaining = $next_vote_time - $current_time;
            
            if ($time_remaining > 0) {
                $hours = floor($time_remaining / 3600);
                $minutes = floor(($time_remaining % 3600) / 60);
                $time_until_next_vote = "{$hours}h {$minutes}m";
            }
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

<div class="row">
    <div class="col-lg-8">
        <!-- Banner del Server -->
        <?php if ($server['banner_url']): ?>
            <div class="server-banner mb-4">
                <img src="<?php echo htmlspecialchars($server['banner_url']); ?>" 
                     alt="Banner <?php echo htmlspecialchars($server['nome']); ?>" 
                     class="img-fluid rounded shadow">
            </div>
        <?php endif; ?>
        
        <!-- Informazioni Principali -->
        <div class="card shadow mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="display-5 fw-bold text-primary mb-2">
                            <?php echo htmlspecialchars($server['nome']); ?>
                        </h1>
                        <p class="text-muted mb-3">
                            <i class="bi bi-info-circle"></i> 
                            <?php echo htmlspecialchars($server['descrizione']); ?>
                        </p>
                        
                        <div class="row g-3">
                            <div class="col-6 col-md-4">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-server text-success me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">IP Server</small>
                                        <strong><?php echo htmlspecialchars($server['ip']); ?></strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-code-slash text-warning me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">Versione</small>
                                        <strong><?php echo htmlspecialchars($server['versione']); ?></strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-heart-fill text-danger me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">Voti Totali</small>
                                        <strong><?php echo number_format($server['vote_count']); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 text-center">
                        <?php if ($server['logo_url']): ?>
                            <img src="<?php echo htmlspecialchars($server['logo_url']); ?>" 
                                 alt="Logo <?php echo htmlspecialchars($server['nome']); ?>" 
                                 class="img-fluid rounded mb-3" style="max-height: 120px;">
                        <?php else: ?>
                            <div class="bg-light rounded p-4 mb-3">
                                <i class="bi bi-server display-4 text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Pulsante Copia IP -->
                        <button class="btn btn-outline-primary btn-sm w-100 mb-2" 
                                onclick="copyToClipboard('<?php echo htmlspecialchars($server['ip']); ?>')">
                            <i class="bi bi-clipboard"></i> Copia IP
                        </button>
                        
                        <!-- Pulsante Vota -->
                        <?php if (isLoggedIn()): ?>
                            <?php if ($can_vote): ?>
                                <button class="btn btn-success btn-lg w-100" onclick="voteServer(<?php echo $server_id; ?>)">
                                    <i class="bi bi-heart"></i> Vota Ora!
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-lg w-100" disabled>
                                    <i class="bi bi-check-circle"></i> Hai già votato
                                    <?php if ($time_until_next_vote): ?>
                                        <br><small>(<?php echo $time_until_next_vote; ?>)</small>
                                    <?php endif; ?>
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-mc-primary btn-lg w-100">
                                <i class="bi bi-box-arrow-in-right"></i> Accedi per Votare
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ultimi Votanti -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-people"></i> Ultimi Votanti
                </h5>
            </div>
            <div class="card-body">
                <?php if (count($voters) > 0): ?>
                    <div class="row g-3">
                        <?php foreach ($voters as $voter): ?>
                            <div class="col-6 col-md-3 col-lg-2 text-center">
                                <div class="voter-avatar mb-2">
                                    <img src="https://minotar.net/avatar/<?php echo urlencode($voter['minecraft_nick']); ?>/64.png" 
                                         alt="Avatar <?php echo htmlspecialchars($voter['minecraft_nick']); ?>" 
                                         class="rounded-circle border border-2 border-primary"
                                         onerror="this.src='https://via.placeholder.com/64x64/6c757d/ffffff?text=?'; this.onerror=null;">
                                </div>
                                <div class="voter-info">
                                    <small class="text-muted d-block">
                                        <?php echo htmlspecialchars($voter['minecraft_nick']); ?>
                                    </small>
                                    <small class="text-muted">
                                        <?php echo date('d/m H:i', strtotime($voter['data_voto'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-heart display-4"></i>
                        <p class="mt-3">Nessun voto ancora ricevuto.</p>
                        <p>Sii il primo a votare questo server!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Statistiche -->
        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up"></i> Statistiche
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="display-6 text-primary"><?php echo number_format($server['vote_count']); ?></div>
                            <small class="text-muted">Voti Totali</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="display-6 text-success"><?php echo count($voters); ?></div>
                            <small class="text-muted">Votanti</small>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="mb-3">
                    <small class="text-muted d-block">Data di Inserimento</small>
                    <strong><?php echo date('d/m/Y', strtotime($server['data_inserimento'])); ?></strong>
                </div>
                
                <?php if ($server['banner_url']): ?>
                    <div class="mb-3">
                        <small class="text-muted d-block">Banner</small>
                        <a href="<?php echo htmlspecialchars($server['banner_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-image"></i> Apri Banner
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Condividi -->
        <div class="card shadow">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-share"></i> Condividi
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="shareOnDiscord()">
                        <i class="bi bi-discord"></i> Condividi su Discord
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="copyShareLink()">
                        <i class="bi bi-link-45deg"></i> Copia Link
                    </button>
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
            showToast('Voto registrato con successo!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast(data.message || 'Errore durante la votazione.', 'error');
        }
    })
    .catch(error => {
        showToast('Errore di connessione. Riprova.', 'error');
    });
}

// Funzione per copiare l'IP del server
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showToast('IP copiato negli appunti!', 'success');
    }).catch(function() {
        // Fallback per browser più vecchi
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showToast('IP copiato negli appunti!', 'success');
    });
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

// Aggiungi event listener per gli avatar
document.addEventListener('DOMContentLoaded', function() {
    const avatarImages = document.querySelectorAll('.voter-avatar img');
    avatarImages.forEach(img => {
        img.addEventListener('error', function() {
            handleAvatarError(this);
        });
    });
});
</script>

<style>
.server-banner img {
    max-height: 300px;
    width: 100%;
    object-fit: cover;
}

.voter-avatar img {
    width: 64px;
    height: 64px;
    transition: transform 0.2s;
}

.voter-avatar img:hover {
    transform: scale(1.1);
}

.voter-info small {
    font-size: 0.8rem;
}

@media (max-width: 768px) {
    .server-banner img {
        max-height: 200px;
    }
    
    .voter-avatar img {
        width: 48px;
        height: 48px;
    }
}
</style>

<?php include 'footer.php'; ?>