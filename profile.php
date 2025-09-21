<?php
/**
 * Pagina Profilo Utente
 * User Profile Page
 */

require_once 'config.php';

// Controlla se l'utente è loggato
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Recupera i dati dell'utente dal database
try {
    $stmt = $pdo->prepare("SELECT * FROM sl_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        redirect('login.php');
    }
} catch (PDOException $e) {
    error_log("Errore nel recupero dati utente: " . $e->getMessage());
    $user = null;
}

// Recupera i server di proprietà dell'utente
$owned_servers = [];
try {
    $stmt = $pdo->prepare("
        SELECT s.*, COUNT(v.id) as vote_count 
        FROM sl_servers s 
        LEFT JOIN sl_votes v ON s.id = v.server_id 
        WHERE s.owner_id = ? AND s.is_active = 1 
        GROUP BY s.id 
        ORDER BY vote_count DESC
    ");
    $stmt->execute([$user_id]);
    $owned_servers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Errore nel recupero server utente: " . $e->getMessage());
}

// Recupera le statistiche dell'utente
$user_stats = [
    'total_votes' => 0,
    'servers_voted' => 0,
    'join_date' => $user['data_registrazione'] ?? date('Y-m-d H:i:s')
];

// Recupera le licenze dei server di proprietà dell'utente
$server_licenses = [];
try {
    $stmt = $pdo->prepare("
        SELECT sl.*, s.nome as server_name, s.ip as server_ip
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

$page_title = "Profilo - " . htmlspecialchars($user['minecraft_nick'] ?? 'Utente');
include 'header.php';
?>

<!-- Profile Page Container -->
<div class="profile-page-container">
    <div class="container" style="margin-top: 2rem;">
        <?php if ($user): ?>
            <div class="row">
                <!-- Main Profile Content -->
                <div class="col-lg-8">
                    <!-- Profile Header -->
                    <div class="profile-header-card">
                        <div class="profile-avatar-section">
                            <img src="<?php echo getMinecraftAvatar($user['minecraft_nick'] ?: 'MHF_Steve'); ?>" 
                                 alt="Avatar" class="profile-avatar">
                            <div class="profile-info">
                                <h1 class="profile-name"><?php echo htmlspecialchars($user['minecraft_nick'] ?: 'Utente'); ?></h1>
                                <p class="profile-join-date">
                                    <i class="bi bi-calendar"></i> 
                                    Membro dal <?php echo date('d/m/Y', strtotime($user_stats['join_date'])); ?>
                                </p>
                                <?php if ($user['is_admin']): ?>
                                    <span class="admin-badge">
                                        <i class="bi bi-shield-check"></i> Amministratore
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User's Servers -->
                    <?php if (!empty($owned_servers)): ?>
                        <div class="user-servers-section">
                            <h3 class="section-title">
                                <i class="bi bi-server"></i> I Miei Server
                            </h3>
                            
                            <div class="servers-grid">
                                <?php foreach ($owned_servers as $server): ?>
                                    <div class="server-card-profile">
                                        <div class="server-card-header">
                                            <?php if ($server['logo_url']): ?>
                                                <img src="<?php echo htmlspecialchars($server['logo_url']); ?>" 
                                                     alt="Logo" class="server-logo-small">
                                            <?php else: ?>
                                                <div class="server-logo-small default-logo">
                                                    <i class="bi bi-server"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="server-info-small">
                                                <h4 class="server-name-small">
                                                    <a href="server.php?id=<?php echo $server['id']; ?>">
                                                        <?php echo htmlspecialchars($server['nome']); ?>
                                                    </a>
                                                </h4>
                                                <p class="server-ip-small"><?php echo htmlspecialchars($server['ip']); ?></p>
                                            </div>
                                            
                                            <div class="server-stats-small">
                                                <div class="stat-item-small">
                                                    <span class="stat-number-small"><?php echo number_format($server['vote_count']); ?></span>
                                                    <span class="stat-label-small">Voti</span>
                                                </div>
                                                <?php
                                                // Controlla se l'utente ha votato questo server oggi
                                                $user_voted_this_server = false;
                                                if (isLoggedIn()) {
                                                    try {
                                                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM sl_votes WHERE user_id = ? AND server_id = ? AND DATE(data_voto) = CURDATE()");
                                                        $stmt->execute([$user_id, $server['id']]);
                                                        $user_voted_this_server = $stmt->fetch()['count'] > 0;
                                                    } catch (PDOException $e) {
                                                        $user_voted_this_server = false;
                                                    }
                                                }
                                                ?>
                                                <?php if ($user_voted_this_server): ?>
                                                    <div class="voted-indicator">
                                                        <i class="bi bi-check-circle-fill text-success"></i>
                                                        <span class="voted-text">Votato oggi</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="server-card-body">
                                            <p class="server-description-small">
                                                <?php echo htmlspecialchars(substr($server['descrizione'] ?: 'Nessuna descrizione disponibile.', 0, 100)); ?>
                                                <?php if (strlen($server['descrizione'] ?: '') > 100): ?>...<?php endif; ?>
                                            </p>
                                            
                                            <div class="server-actions">
                                                <a href="server.php?id=<?php echo $server['id']; ?>" class="btn-view-server">
                                                    <i class="bi bi-eye"></i> Visualizza
                                                </a>
                                                <?php if (isAdmin()): ?>
                                                    <a href="admin.php?edit=<?php echo $server['id']; ?>" class="btn-edit-server">
                                                        <i class="bi bi-pencil"></i> Modifica
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-servers-section">
                            <div class="no-servers-content">
                                <i class="bi bi-server no-servers-icon"></i>
                                <h3>Nessun Server</h3>
                                <p>Non possiedi ancora nessun server. Contatta un amministratore per aggiungere il tuo server alla lista!</p>
                                <?php if (isAdmin()): ?>
                                    <a href="admin.php" class="btn-add-server">
                                        <i class="bi bi-plus-circle"></i> Aggiungi Server
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Server Licenses Section -->
                    <?php if (!empty($server_licenses)): ?>
                        <div class="server-licenses-section">
                            <h3 class="section-title">
                                <i class="bi bi-key-fill"></i> Licenze dei Server
                            </h3>
                            
                            <div class="licenses-grid">
                                <?php foreach ($server_licenses as $license): ?>
                                    <div class="license-card">
                                        <div class="license-card-header">
                                            <h4 class="license-server-name">
                                                <?php echo htmlspecialchars($license['server_name']); ?>
                                            </h4>
                                            <span class="license-status <?php echo $license['is_active'] ? 'active' : 'inactive'; ?>">
                                                <i class="bi bi-<?php echo $license['is_active'] ? 'check-circle-fill' : 'x-circle-fill'; ?>"></i>
                                                <?php echo $license['is_active'] ? 'Attiva' : 'Inattiva'; ?>
                                            </span>
                                        </div>
                                        
                                        <div class="license-card-body">
                                            <div class="license-key-display">
                                                <span class="license-label">License Key:</span>
                                                <code class="license-key-value license-hidden">
                                                    <span class="license-dots">•••••••••••••••••••••••••••••••••</span>
                                                    <span class="license-text" style="display: none;"><?php echo htmlspecialchars($license['license_key']); ?></span>
                                                </code>
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
                                                <div class="license-meta-item">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                    <span>Utilizzi: <?php echo number_format($license['usage_count']); ?></span>
                                                </div>
                                            </div>
                                            
                                            <div class="license-actions">
                                                <button class="view-license-btn" data-license="<?php echo htmlspecialchars($license['license_key']); ?>">
                                                    <i class="bi bi-eye"></i> Visualizza
                                                </button>
                                                <button class="copy-license-btn" data-license="<?php echo htmlspecialchars($license['license_key']); ?>">
                                                    <i class="bi bi-copy"></i> Copia
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- JavaScript per gestione licenze -->
                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            // Gestione pulsanti licenze
            const viewLicenseButtons = document.querySelectorAll('.view-license-btn');
            const copyLicenseButtons = document.querySelectorAll('.copy-license-btn');
                            
                            // Funzione per visualizzare la licenza
                            viewLicenseButtons.forEach(button => {
                                button.addEventListener('click', function() {
                                    const licenseCard = this.closest('.license-card');
                                    const licenseKeyDisplay = licenseCard.querySelector('.license-key-value');
                                    const licenseDots = licenseKeyDisplay.querySelector('.license-dots');
                                    const licenseText = licenseKeyDisplay.querySelector('.license-text');
                                    const buttonIcon = this.querySelector('i');
                                    const buttonText = this.querySelector('span');
                                    
                                    if (licenseDots.style.display === 'none') {
                                        // Nascondi il testo e mostra i pallini
                                        licenseDots.style.display = 'inline';
                                        licenseText.style.display = 'none';
                                        buttonIcon.className = 'bi bi-eye';
                                        buttonText.textContent = ' Visualizza';
                                    } else {
                                        // Mostra il testo e nascondi i pallini
                                        licenseDots.style.display = 'none';
                                        licenseText.style.display = 'inline';
                                        buttonIcon.className = 'bi bi-eye-slash';
                                        buttonText.textContent = ' Nascondi';
                                    }
                                });
                            });
                            
                            // Funzione per copiare la licenza
                            copyLicenseButtons.forEach(button => {
                                button.addEventListener('click', function() {
                                    const licenseKey = this.getAttribute('data-license');
                                    
                                    navigator.clipboard.writeText(licenseKey).then(function() {
                                        // Usa la funzione showToast esistente se disponibile
                                        if (typeof showToast === 'function') {
                                            showToast('Licenza copiata negli appunti!', 'success');
                                        } else {
                                            alert('Licenza copiata negli appunti!');
                                        }
                                        
                                        // Cambia temporaneamente l'icona per feedback visivo
                                        const icon = button.querySelector('i');
                                        const originalClass = icon.className;
                                        icon.className = 'bi bi-check-lg';
                                        
                                        setTimeout(() => {
                                            icon.className = originalClass;
                                        }, 2000);
                                        
                                    }).catch(function() {
                                        // Fallback per browser più vecchi
                                        var textArea = document.createElement('textarea');
                                        textArea.value = licenseKey;
                                        document.body.appendChild(textArea);
                                        textArea.select();
                                        document.execCommand('copy');
                                        document.body.removeChild(textArea);
                                        
                                        if (typeof showToast === 'function') {
                                            showToast('Licenza copiata negli appunti!', 'success');
                                        } else {
                                            alert('Licenza copiata negli appunti!');
                                        }
                                    });
                                });
                            });
                        });
                        </script>
                    <?php else: ?>
                        <div class="no-licenses-section">
                            <div class="no-licenses-content">
                                <i class="bi bi-key no-licenses-icon"></i>
                                <h3>Nessuna Licenza</h3>
                                <p>Non hai ancora licenze attive per i tuoi server. Le licenze vengono generate automaticamente quando un server viene aggiunto.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- User Stats -->
                    <div class="profile-stats-card">
                        <h4><i class="bi bi-graph-up"></i> Statistiche</h4>
                        
                        <div class="stats-list">
                            <div class="stat-item-profile">
                                <div class="stat-icon">
                                    <i class="bi bi-hand-thumbs-up"></i>
                                </div>
                                <div class="stat-content">
                                    <span class="stat-number"><?php echo number_format($user_stats['total_votes']); ?></span>
                                    <span class="stat-label">Voti Dati</span>
                                </div>
                            </div>
                            
                            <div class="stat-item-profile">
                                <div class="stat-icon">
                                    <i class="bi bi-server"></i>
                                </div>
                                <div class="stat-content">
                                    <span class="stat-number"><?php echo number_format($user_stats['servers_voted']); ?></span>
                                    <span class="stat-label">Server Votati</span>
                                </div>
                            </div>
                            
                            <div class="stat-item-profile">
                                <div class="stat-icon">
                                    <i class="bi bi-collection"></i>
                                </div>
                                <div class="stat-content">
                                    <span class="stat-number"><?php echo count($owned_servers); ?></span>
                                    <span class="stat-label">Server Posseduti</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Account Info -->
                    <div class="profile-info-card">
                        <h4><i class="bi bi-person-circle"></i> Informazioni Account</h4>
                        
                        <div class="info-list">
                            <div class="info-item">
                                <span class="info-label">Nickname Minecraft:</span>
                                <span class="info-value">
                                    <?php echo htmlspecialchars($user['minecraft_nick'] ?: 'Non impostato'); ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Data Registrazione:</span>
                                <span class="info-value">
                                    <?php echo date('d/m/Y H:i', strtotime($user_stats['join_date'])); ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Stato Account:</span>
                                <span class="info-value status-active">
                                    <i class="bi bi-check-circle"></i> Attivo
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> 
                Impossibile caricare i dati del profilo. Si prega di riprovare più tardi.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>