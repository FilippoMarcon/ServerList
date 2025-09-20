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
                                                        <span class="voted-text">Votato</span>
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