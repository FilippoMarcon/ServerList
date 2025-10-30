<?php
/**
 * Homepage - Lista Server Minecraft
 * Minecraft Server List Homepage
 */

require_once 'config.php';

// Routing fallback: se l'hosting reindirizza tutto a index.php,
// instradiamo le richieste verso le pagine senza estensione
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// /server/<slug>
if (preg_match('#^/server/([A-Za-z0-9_-]+)/?$#', $request_path, $m)) {
    $_GET['slug'] = $m[1];
    include __DIR__ . '/server.php';
    exit();
}

// Forum SEO subpaths: /forum/<id>-<slug> e /forum/category/<id>-<slug>
if (preg_match('#^/forum/([0-9]+)-[A-Za-z0-9_-]+/?$#', $request_path, $m)) {
    $_GET['view'] = 'thread';
    $_GET['thread'] = (int)$m[1];
    include __DIR__ . '/forum.php';
    exit();
}
if (preg_match('#^/forum/category/([0-9]+)-[A-Za-z0-9_-]+/?$#', $request_path, $m)) {
    $_GET['view'] = 'category';
    $_GET['category'] = (int)$m[1];
    include __DIR__ . '/forum.php';
    exit();
}
// ADD: Public profile SEO route /utente/<id> or /utente/<id>-<slug>
if (preg_match('#^/utente/([0-9]+)(?:-[A-Za-z0-9_-]+)?/?$#', $request_path, $m)) {
    $_GET['id'] = (int)$m[1];
    include __DIR__ . '/user.php';
    exit();
}

// Pagine top-level senza estensione: /forum, /annunci, /login, /register, /profile, /admin
if (preg_match('#^/(forum|annunci|login|register|profile|admin|forgot|reset|verifica-nickname|logout|sponsorizza-il-tuo-server|plugin-blocksy|eventi-server)/?$#', $request_path, $m)) {
    $map = [
        'forum' => 'forum.php',
        'annunci' => 'annunci.php',
        'login' => 'login.php',
        'register' => 'register.php',
        'profile' => 'profile.php',
        'admin' => 'admin.php',
        'forgot' => 'forgot.php',
        'reset' => 'reset.php',
        'verifica-nickname' => 'verifica-nickname.php',
        'logout' => 'logout.php',
        'sponsorizza-il-tuo-server' => 'sponsorizza.php',
        'plugin-blocksy' => 'plugin-blocksy.php',
        'eventi-server' => 'eventi-server.php',
    ];
    $target = $map[$m[1]] ?? null;
    if ($target) {
        include __DIR__ . '/' . $target;
        exit();
    }
}

// Titolo pagina
$page_title = "Lista Server";

// Gestione filtro da URL
$active_filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : '';

// Paginazione homepage: definisci valori sicuri
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Crea la tabella sponsored_servers se non esiste
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sl_sponsored_servers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            server_id INT NOT NULL,
            priority INT DEFAULT 1,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            FOREIGN KEY (server_id) REFERENCES sl_servers(id) ON DELETE CASCADE,
            UNIQUE KEY unique_server (server_id)
        )
    ");
} catch (PDOException $e) {
    // Ignora errori di creazione tabella se già esiste
}

// Crea la tabella eventi server se non esiste
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sl_server_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            server_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            event_date DATE NOT NULL,
            event_time TIME NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (server_id) REFERENCES sl_servers(id) ON DELETE CASCADE,
            INDEX(event_date),
            INDEX(server_id)
        )
    ");
} catch (PDOException $e) {
    // Ignora errori di creazione tabella se già esiste
}

// Query per ottenere i server sponsorizzati
$sponsored_servers = [];
try {
    // Prima verifica se la tabella sponsored_servers esiste
    $stmt = $pdo->query("SHOW TABLES LIKE 'sl_sponsored_servers'");
    if ($stmt->rowCount() > 0) {
        // Mostra 2 sponsor alla volta, a rotazione ad ogni caricamento
        // Mantiene la priorità come primo criterio, e ruota tra gli sponsor dello stesso livello
        $stmt = $pdo->query("
            SELECT s.*, COUNT(v.id) as voti_totali, ss.priority, ss.expires_at
            FROM sl_servers s 
            INNER JOIN sl_sponsored_servers ss ON s.id = ss.server_id
            LEFT JOIN sl_votes v ON s.id = v.server_id AND MONTH(v.data_voto) = MONTH(CURRENT_DATE()) AND YEAR(v.data_voto) = YEAR(CURRENT_DATE())
            WHERE s.is_active = 1 AND ss.is_active = 1 
            AND (ss.expires_at IS NULL OR ss.expires_at > NOW())
            GROUP BY s.id 
            ORDER BY ss.priority ASC, RAND()
            LIMIT 2
        ");
        $sponsored_servers = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Errore nel caricamento server sponsorizzati
    $sponsored_servers = [];
}

// Query per ottenere i server con conteggio voti e informazioni sponsorizzazione
try {
    $stmt = $pdo->query("
        SELECT s.*, COUNT(v.id) as voti_totali, 
               CASE WHEN ss.server_id IS NOT NULL 
                    AND ss.is_active = 1 
                    AND (ss.expires_at IS NULL OR ss.expires_at > NOW()) 
                    THEN 1 ELSE 0 END as is_sponsored
        FROM sl_servers s 
        LEFT JOIN sl_votes v ON s.id = v.server_id AND MONTH(v.data_voto) = MONTH(CURRENT_DATE()) AND YEAR(v.data_voto) = YEAR(CURRENT_DATE())
        LEFT JOIN sl_sponsored_servers ss ON s.id = ss.server_id
        WHERE s.is_active = 1 
        GROUP BY s.id 
        ORDER BY voti_totali DESC, s.nome ASC
    ");
    $servers = $stmt->fetchAll();
} catch (PDOException $e) {
    $servers = [];
    $error = "Errore nel caricamento dei server: " . $e->getMessage();
}

// Applica paginazione lato PHP se non ci sono errori
if (!isset($error)) {
    $total_servers = isset($servers) ? count($servers) : 0;
    $total_pages = max(1, (int)ceil($total_servers / $per_page));
    $servers = array_slice($servers, $offset, $per_page);
}

include 'header.php';
?>

<style>
.sort-controls {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.btn-sort {
    background: var(--gradient-primary);
    color: white;
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 0.5rem 1rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.btn-sort:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
}

.btn-sort.active {
    background: var(--gradient-secondary);
    border-color: transparent;
}

.btn-sort .sort-arrow {
    margin-left: 6px;
    font-weight: 700;
}

.events-section-standalone {
    margin-bottom: 2rem;
    padding: 1rem;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.events-section-standalone h5 {
    color: var(--text-primary);
    font-weight: 700;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.events-section-standalone h5 i {
    color: var(--accent-purple);
}

.events-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.event-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: var(--primary-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    transition: all 0.3s ease;
}

.event-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.event-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 50px;
    padding: 0.5rem;
    background: var(--gradient-primary);
    border-radius: 8px;
    color: white;
    font-weight: 700;
}

.date-label {
    font-size: 0.8rem;
    line-height: 1;
}

.event-time {
    font-size: 0.7rem;
    opacity: 0.9;
    margin-top: 2px;
}

.event-info {
    flex: 1;
}

.event-title {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9rem;
    line-height: 1.2;
}

.event-server {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-top: 2px;
}

.event-server-with-logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 4px;
}

.event-server-logo {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    object-fit: cover;
    border: 1px solid var(--border-color);
}

.event-server-logo-fallback {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    background: var(--accent-purple);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.7rem;
    border: 1px solid var(--border-color);
}

.event-server-name {
    font-size: 0.8rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.refresh-events-btn {
    background: none;
    border: none;
    color: var(--accent-purple);
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    margin-left: auto;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    border-radius: 6px;
}

.refresh-events-btn:hover {
    transform: rotate(180deg);
}

.refresh-events-btn.spinning {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.events-section-standalone h5 {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
}

/* Modal Styles */
.event-modal-content {
    padding: 1rem;
}

.event-modal-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--border-color);
}

.event-modal-logo {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    object-fit: cover;
    border: 2px solid var(--border-color);
}

.event-modal-logo-fallback {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    border: 2px solid var(--border-color);
}

.event-modal-info h3 {
    color: var(--text-primary);
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.event-modal-type {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: rgba(124, 58, 237, 0.1);
    color: var(--accent-purple);
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    border: 1px solid rgba(124, 58, 237, 0.3);
}

.event-modal-section {
    margin-bottom: 1.5rem;
}

.event-modal-section h5 {
    color: var(--text-primary);
    font-weight: 700;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.event-modal-section h5 i {
    color: var(--accent-purple);
}

.event-modal-description {
    color: var(--text-secondary);
    line-height: 1.6;
    padding: 1rem;
    background: var(--primary-bg);
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.event-modal-ip {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    background: var(--primary-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: var(--text-primary);
}

.event-modal-ip button {
    margin-left: auto;
    padding: 0.5rem 1rem;
    background: var(--gradient-primary);
    border: none;
    color: white;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.event-modal-ip button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.event-modal-socials {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.event-modal-social-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: var(--primary-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    color: var(--text-primary);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.event-modal-social-link:hover {
    background: var(--gradient-primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.event-modal-social-link i {
    font-size: 1.2rem;
}

/* Tema chiaro */
[data-theme="light"] .modal-content {
    background: #ffffff !important;
    border-color: #e2e8f0 !important;
}

[data-theme="light"] .modal-header,
[data-theme="light"] .modal-footer {
    border-color: #e2e8f0 !important;
}

[data-theme="light"] .event-modal-description,
[data-theme="light"] .event-modal-ip {
    background: #f8fafc !important;
    border-color: #e2e8f0 !important;
}

[data-theme="light"] .event-modal-social-link {
    background: #f8fafc !important;
    border-color: #e2e8f0 !important;
    color: #0f172a !important;
}

[data-theme="light"] .event-modal-social-link:hover {
    background: var(--gradient-primary) !important;
    color: white !important;
}

[data-theme="light"] .btn-close {
    filter: invert(1);
}

.btn-close-white {
    filter: brightness(0) invert(1);
}

[data-theme="light"] .btn-close-white {
    filter: none;
}

/* Pulsanti Toggle Mobile */
.mobile-sidebar-toggle {
    display: none;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

/* Pulsante chiusura mobile */
.mobile-close-btn {
    display: none;
    position: fixed;
    top: 1rem;
    right: 1rem;
    width: 40px;
    height: 40px;
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 50%;
    color: var(--text-primary);
    font-size: 1.2rem;
    cursor: pointer;
    z-index: 10000;
    transition: all 0.3s ease;
    align-items: center;
    justify-content: center;
}

.mobile-close-btn:hover {
    background: var(--accent-purple);
    color: white;
    border-color: var(--accent-purple);
    transform: rotate(90deg);
}

/* Desktop: mostra sempre eventi e filtri */
@media (min-width: 992px) {
    .col-lg-3 #eventsSection,
    .col-lg-3 #filtersSection {
        display: block !important;
    }
}

.btn-toggle-sidebar {
    flex: 1;
    padding: 0.75rem 1rem;
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    color: var(--text-primary);
    font-weight: 700;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-toggle-sidebar:hover {
    background: var(--primary-bg);
    border-color: var(--accent-purple);
}

.btn-toggle-sidebar.active {
    background: var(--gradient-primary);
    border-color: transparent;
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-toggle-sidebar i {
    font-size: 1.1rem;
}

/* Media Queries Mobile */
@media (max-width: 991px) {
    /* Mostra i pulsanti toggle */
    .mobile-sidebar-toggle {
        display: flex;
        margin-bottom: 1.5rem;
    }
    
    /* Rimuovi lo stato active dai pulsanti */
    .btn-toggle-sidebar.active {
        background: var(--card-bg);
        border-color: var(--border-color);
        color: var(--text-primary);
        box-shadow: none;
    }
    
    /* Sidebar come modal overlay */
    .col-lg-3 {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        display: none;
        padding: 1rem;
        overflow-y: auto;
    }
    
    .col-lg-3.modal-open {
        display: block;
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    /* Mostra pulsante chiusura su mobile */
    .col-lg-3.modal-open .mobile-close-btn {
        display: flex;
    }
    
    /* Contenuto del modal centrato */
    .col-lg-3 > div {
        max-width: 500px;
        margin: 2rem auto;
    }
    
    /* Nascondi le sezioni di default */
    .col-lg-3 #eventsSection,
    .col-lg-3 #filtersSection {
        display: none;
    }
    
    .col-lg-3 #eventsSection.active,
    .col-lg-3 #filtersSection.active {
        display: block;
    }
    
    /* Allinea al centro il CTA sponsor */
    .sponsored-cta {
        text-align: center;
    }
    
    /* Fix dimensioni pulsanti sort */
    .sort-controls {
        flex-wrap: wrap;
    }
    
    .btn-sort {
        min-width: 120px;
        min-height: 44px;
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        gap: 0.5rem;
    }
    
    .btn-sort i {
        font-size: 1rem;
        margin-right: 0;
    }
    
    /* Migliora spacing eventi */
    .events-section-standalone {
        margin-bottom: 1rem;
    }
}

@media (max-width: 576px) {
    /* Pulsanti sort più piccoli su schermi molto piccoli */
    .btn-sort {
        min-width: 100px;
        min-height: 42px;
        padding: 0.5rem 0.75rem;
        font-size: 0.85rem;
        gap: 0.4rem;
    }
    
    .sort-controls {
        gap: 0.4rem;
    }
    
    /* Toggle buttons più compatti */
    .btn-toggle-sidebar {
        padding: 0.6rem 0.75rem;
        font-size: 0.85rem;
    }
}
</style>


<div class="container" style="margin-top: 2rem;">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-9" style="position: relative;">
            
            <!-- Sponsored Servers Section -->
            <?php if (!empty($sponsored_servers)): ?>
            <div class="sponsored-servers-section">
                <div class="sponsored-servers-grid">
                    <?php foreach ($sponsored_servers as $sponsored): ?>
                        <div class="sponsored-server-card" data-server-id="<?php echo $sponsored['id']; ?>">
                            <div class="sponsored-overlay">
                                <i class="bi bi-star-fill"></i>
                                <span>SPONSOR</span>
                            </div>
                            <?php if (!empty($sponsored['logo_url'])): ?>
                                <img src="<?php echo htmlspecialchars($sponsored['logo_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($sponsored['nome']); ?>" 
                                     class="server-logo"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="server-logo logo-fallback" style="display: none;">
                                    <i class="bi bi-server"></i>
                                </div>
                            <?php else: ?>
                                <div class="server-logo logo-fallback">
                                    <i class="bi bi-server"></i>
                                </div>
                            <?php endif; ?>
                            <div class="server-info">
                                <h4><a href="<?php $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($sponsored['nome'])); echo '/server/' . urlencode(trim($slug, '-')); ?>" style="text-decoration: none; color: inherit;"><?php echo htmlspecialchars($sponsored['nome']); ?></a></h4>
                                <p class="server-ip"><?php echo htmlspecialchars($sponsored['ip']); ?></p>
                                <div class="server-stats">
                                    <span class="votes-count">
                                        <i class="bi bi-heart-fill"></i> <?php echo $sponsored['voti_totali']; ?> voti
                                    </span>
                                    <span class="server-version">
                                        <i class="bi bi-gear"></i> <?php echo htmlspecialchars($sponsored['versione']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="sponsored-cta">
                    <span class="sponsored-cta-text">Se vuoi sponsorizzare il tuo server </span>
                    <a href="/sponsorizza-il-tuo-server" class="sponsored-cta-link">clicca qui</a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Pulsanti Toggle Mobile per Eventi e Filtri -->
            <div class="mobile-sidebar-toggle">
                <button class="btn-toggle-sidebar active" data-target="events">
                    <i class="bi bi-calendar-event"></i> EVENTI
                </button>
                <button class="btn-toggle-sidebar" data-target="filters">
                    <i class="bi bi-funnel"></i> FILTRI
                </button>
            </div>
            
            <!-- Server List Header -->
            <div class="server-list-header">
                <div class="d-flex align-items-center">
                    <div class="sort-controls" role="group" aria-label="Ordinamento">
                        <button type="button" class="sort-option btn-sort active" data-sort="votes">
                            <i class="bi bi-trophy"></i> Voti <span class="sort-arrow" id="arrow-votes">↓</span>
                        </button>
                        <button type="button" class="sort-option btn-sort" data-sort="name">
                            <i class="bi bi-server"></i> Server <span class="sort-arrow" id="arrow-name"></span>
                        </button>
                        <button type="button" class="sort-option btn-sort" data-sort="players">
                            <i class="bi bi-people"></i> Player <span class="sort-arrow" id="arrow-players"></span>
                        </button>
                    </div>
                </div>
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Cerca" id="searchInput">
                </div>
            </div>

            <!-- Server List -->
            <div class="server-list">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                    </div>
                <?php elseif (empty($servers)): ?>
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i> Nessun server disponibile al momento.
                    </div>
                <?php else: ?>
                    <?php 
                    $rank = 1;
                    foreach ($servers as $server): 
                        // Get recent voters for this server
                        try {
                            $stmt = $pdo->prepare("
                                SELECT u.minecraft_nick, u.id as user_id 
                                FROM sl_votes v 
                                JOIN sl_users u ON v.user_id = u.id 
                                WHERE v.server_id = ? 
                                ORDER BY v.data_voto DESC 
                                LIMIT 8
                            ");
                            $stmt->execute([$server['id']]);
                            $recent_voters = $stmt->fetchAll();
                        } catch (PDOException $e) {
                            $recent_voters = [];
                        }
                        
                        // Determine rank class
                        $rank_class = '';
                        if ($rank == 1) $rank_class = 'gold';
                        elseif ($rank == 2) $rank_class = 'silver';
                        elseif ($rank == 3) $rank_class = 'bronze';
                        
                        // Ottieni i tag/modalità dal database
                        $tags = [];
                        if (!empty($server['modalita'])) {
                            $modalita_array = json_decode($server['modalita'], true);
                            if (is_array($modalita_array)) {
                                $tags = $modalita_array;
                            }
                        }
                        if (empty($tags)) {
                            $tags[] = 'Generale';
                        }
                        
                        // Mostra max 3 tag, il resto come "+X"
                        $visible_tags = array_slice($tags, 0, 3);
                        $remaining_count = count($tags) - 3;
                        
                        // Crea una stringa con TUTTE le modalità per il filtro
                        $all_tags_string = strtolower(implode(',', $tags));
                    ?>
                        <div class="homepage-server-card" 
                             data-name="<?php echo htmlspecialchars(strtolower($server['nome'])); ?>" 
                             data-server-id="<?php echo $server['id']; ?>" 
                             data-votes="<?php echo $server['voti_totali']; ?>"
                             data-original-rank="<?php echo $rank; ?>"
                             data-all-tags="<?php echo htmlspecialchars($all_tags_string); ?>">
                            <div class="server-rank-container">
                                <div class="server-rank <?php echo ($rank > 3) ? 'outlined' : $rank_class; ?>">
                                    <?php if ($rank <= 3): ?>
                                        <i class="bi bi-trophy-fill" style="margin-right: 6px;"></i><?php echo $rank; ?>°
                                    <?php endif; ?>
                                </div>
                                <div class="rank-votes" style="font-size: 0.9rem; color: var(--text-muted); margin-top: 5px; text-align: center;">
                                    +<?php echo $server['voti_totali']; ?>
                                </div>
                            </div>
                            
                            <?php if ($server['logo_url']): ?>
                                <img src="<?php echo htmlspecialchars($server['logo_url']); ?>" 
                                     alt="Logo" class="server-logo">
                            <?php else: ?>
                                <div class="server-logo d-flex align-items-center justify-content-center" 
                                     style="background-color: var(--accent-green);">
                                    <i class="bi bi-server text-white"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="server-info">
                                <div class="server-basic-info">
                                    <a href="<?php $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($server['nome'])); echo '/server/' . urlencode(trim($slug, '-')); ?>" class="server-name">
                                        <?php echo htmlspecialchars($server['nome']); ?> 
                                        <?php if ($server['is_sponsored']): ?>
                                            <span class="sponsored-indicator">
                                                <i class="bi bi-star-fill"></i> SPONSOR
                                            </span>
                                        <?php endif; ?>
                                        <span style="font-size: 0.9rem; color: var(--text-muted);">
                                            <?php echo htmlspecialchars($server['versione'] ?: '1.20.2'); ?>
                                        </span>
                                    </a>
                                    <div class="server-ip"><?php echo htmlspecialchars($server['ip']); ?></div>
                                </div>
                                <div class="server-tags">
                                    <?php foreach ($visible_tags as $tag): ?>
                                        <span class="server-tag"><?php echo htmlspecialchars($tag); ?></span>
                                    <?php endforeach; ?>
                                    <?php if ($remaining_count > 0): ?>
                                        <span class="server-tag server-tag-more" title="<?php echo htmlspecialchars(implode(', ', array_slice($tags, 3))); ?>">+<?php echo $remaining_count; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="server-players">
                                <div class="player-count" data-playercounter-ip="<?php echo htmlspecialchars($server['ip']); ?>">
                                    ...
                                </div>
                                <div class="player-status">online</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">
                                    <?php echo $server['tipo_server'] ?? 'Java & Bedrock'; ?>
                                </div>
                            </div>
                        </div>
                    <?php 
                    $rank++;
                    endforeach; 
                    ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-3" id="mobileSidebar">
            <!-- Pulsante chiusura mobile -->
            <button class="mobile-close-btn" id="closeMobileSidebar">
                <i class="bi bi-x-lg"></i>
            </button>
            
            <!-- Eventi Prossimi (Separato dai filtri) -->
            <?php
            // Ottieni eventi prossimi (oggi e prossimi 7 giorni) con logo server
            $upcoming_events = [];
            try {
                $stmt = $pdo->query("
                    SELECT e.*, s.nome as server_name, s.id as server_id, s.logo_url
                    FROM sl_server_events e 
                    JOIN sl_servers s ON e.server_id = s.id 
                    WHERE e.is_active = 1 
                    AND s.is_active = 1 
                    AND e.event_date >= CURDATE() 
                    AND e.event_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                    ORDER BY e.event_date ASC, e.event_time ASC 
                    LIMIT 5
                ");
                $upcoming_events = $stmt->fetchAll();
            } catch (PDOException $e) {
                $upcoming_events = [];
            }
            ?>
            
            <div class="events-section-standalone" id="eventsSection" <?php if (empty($upcoming_events)): ?>style="display: none;"<?php endif; ?>>
            <?php if (!empty($upcoming_events)): ?>
                <h5>
                    <i class="bi bi-calendar-event"></i> Eventi Prossimi
                    <button id="refreshEventsBtn" class="refresh-events-btn" title="Aggiorna eventi">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </h5>
                <div class="events-list" id="eventsList">
                    <?php foreach ($upcoming_events as $event): ?>
                        <?php
                        $event_date = new DateTime($event['event_date']);
                        $today = new DateTime();
                        $tomorrow = new DateTime('+1 day');
                        
                        if ($event_date->format('Y-m-d') === $today->format('Y-m-d')) {
                            $date_label = 'Oggi';
                        } elseif ($event_date->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
                            $date_label = 'Domani';
                        } else {
                            $date_label = $event_date->format('d/m');
                        }
                        ?>
                        <div class="event-card" style="cursor: pointer;" onclick="openEventModal(<?php echo $event['server_id']; ?>)">
                            <div class="event-date">
                                <span class="date-label"><?php echo $date_label; ?></span>
                                <?php if (!empty($event['event_time'])): ?>
                                    <span class="event-time"><?php echo date('H:i', strtotime($event['event_time'])); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="event-info">
                                <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                                <div class="event-server-with-logo">
                                    <?php if (!empty($event['logo_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($event['logo_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($event['server_name']); ?>" 
                                             class="event-server-logo"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                        <div class="event-server-logo-fallback" style="display: none;">
                                            <i class="bi bi-server"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="event-server-logo-fallback">
                                            <i class="bi bi-server"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="event-server-name"><?php echo htmlspecialchars($event['server_name']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>
            </div>
            
            <div class="filters-sidebar" id="filtersSection">
                <div class="filters-header">
                    <h4><i class="bi bi-funnel"></i> Filtri</h4>
                    <button class="clear-filters">Rimuovi filtri</button>
                </div>
                
                <div class="filter-group">
                    <h5>Modalità</h5>
                    <div class="filter-tags">
                        <span class="filter-tag">Adventure</span>
                        <span class="filter-tag">Survival</span>
                        <span class="filter-tag">Vanilla</span>
                        <span class="filter-tag">Factions</span>
                        <span class="filter-tag">Skyblock</span>
                        <span class="filter-tag">RolePlay</span>
                        <span class="filter-tag">MiniGames</span>
                        <span class="filter-tag">BedWars</span>
                        <span class="filter-tag">KitPvP</span>
                        <span class="filter-tag">SkyPvP</span>
                        <span class="filter-tag">Survival Games</span>
                        <span class="filter-tag">Hunger Games</span>
                        <span class="filter-tag">Pixelmon</span>
                        <span class="filter-tag">Prison</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Dettagli Evento -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: var(--card-bg); border: 1px solid var(--border-color);">
            <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                <h5 class="modal-title" id="eventModalLabel" style="color: var(--text-primary);">
                    <i class="bi bi-info-circle"></i> Dettagli Server
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="eventModalBody">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Caricamento...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Funzione per aprire il modal con i dettagli del server
async function openEventModal(serverId) {
    const modal = new bootstrap.Modal(document.getElementById('eventModal'));
    const modalBody = document.getElementById('eventModalBody');
    
    // Mostra loading
    modalBody.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Caricamento...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    try {
        const response = await fetch(`/api_get_server_info.php?server_id=${serverId}`);
        const data = await response.json();
        
        if (data.error) {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> ${data.error}
                </div>
            `;
            return;
        }
        
        const server = data.server;
        
        // Costruisci HTML del modal
        let socialLinksHtml = '';
        if (server.social_links && server.social_links.length > 0) {
            socialLinksHtml = server.social_links.map(link => {
                let icon = 'bi-link-45deg';
                const title = link.title.toLowerCase();
                if (title.includes('discord')) icon = 'bi-discord';
                else if (title.includes('telegram')) icon = 'bi-telegram';
                else if (title.includes('instagram')) icon = 'bi-instagram';
                else if (title.includes('youtube')) icon = 'bi-youtube';
                else if (title.includes('twitter') || title.includes('x')) icon = 'bi-twitter-x';
                else if (title.includes('facebook')) icon = 'bi-facebook';
                else if (title.includes('tiktok')) icon = 'bi-tiktok';
                else if (title.includes('sito') || title.includes('web')) icon = 'bi-globe';
                else if (title.includes('shop')) icon = 'bi-cart';
                
                return `
                    <a href="${link.url}" target="_blank" class="event-modal-social-link">
                        <i class="bi ${icon}"></i>
                        ${link.title}
                    </a>
                `;
            }).join('');
        }
        
        modalBody.innerHTML = `
            <div class="event-modal-content">
                <div class="event-modal-header">
                    ${server.logo_url ? 
                        `<img src="${server.logo_url}" alt="${server.nome}" class="event-modal-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                         <div class="event-modal-logo-fallback" style="display: none;">
                            <i class="bi bi-server"></i>
                         </div>` :
                        `<div class="event-modal-logo-fallback">
                            <i class="bi bi-server"></i>
                         </div>`
                    }
                    <div class="event-modal-info">
                        <h3>${server.nome}</h3>
                        <span class="event-modal-type">${server.tipo_server || 'Server'}</span>
                    </div>
                </div>
                
                ${server.descrizione ? `
                    <div class="event-modal-section">
                        <h5><i class="bi bi-file-text"></i> Descrizione</h5>
                        <div class="event-modal-description">${server.descrizione.replace(/\n/g, '<br>')}</div>
                    </div>
                ` : ''}
                
                <div class="event-modal-section">
                    <h5><i class="bi bi-hdd-network"></i> IP Server</h5>
                    <div class="event-modal-ip">
                        <span id="serverIpText">${server.ip}</span>
                        <button onclick="copyServerIp('${server.ip}')">
                            <i class="bi bi-clipboard"></i> Copia
                        </button>
                    </div>
                </div>
                
                ${socialLinksHtml ? `
                    <div class="event-modal-section">
                        <h5><i class="bi bi-share"></i> Social & Link</h5>
                        <div class="event-modal-socials">
                            ${socialLinksHtml}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
        
    } catch (error) {
        modalBody.innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> Errore nel caricamento dei dati
            </div>
        `;
    }
}

// Funzione per copiare l'IP
function copyServerIp(ip) {
    navigator.clipboard.writeText(ip).then(() => {
        // Mostra feedback visivo
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i> Copiato!';
        setTimeout(() => {
            btn.innerHTML = originalHtml;
        }, 2000);
    });
}

// Funzione per ricaricare gli eventi
async function refreshEvents() {
    const btn = document.getElementById('refreshEventsBtn');
    const eventsList = document.getElementById('eventsList');
    
    // Aggiungi animazione di rotazione
    btn.classList.add('spinning');
    btn.disabled = true;
    
    try {
        const response = await fetch('/api_get_events.php');
        const data = await response.json();
        
        if (data.error) {
            console.error('Errore nel caricamento degli eventi:', data.error);
            return;
        }
        
        // Ricostruisci la lista eventi
        if (data.events && data.events.length > 0) {
            eventsList.innerHTML = data.events.map(event => {
                const eventDate = new Date(event.event_date);
                const today = new Date();
                const tomorrow = new Date(today);
                tomorrow.setDate(tomorrow.getDate() + 1);
                
                let dateLabel;
                if (eventDate.toDateString() === today.toDateString()) {
                    dateLabel = 'Oggi';
                } else if (eventDate.toDateString() === tomorrow.toDateString()) {
                    dateLabel = 'Domani';
                } else {
                    dateLabel = eventDate.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit' });
                }
                
                const eventTime = event.event_time ? 
                    `<span class="event-time">${event.event_time.substring(0, 5)}</span>` : '';
                
                const logoHtml = event.logo_url ?
                    `<img src="${event.logo_url}" alt="${event.server_name}" class="event-server-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                     <div class="event-server-logo-fallback" style="display: none;">
                        <i class="bi bi-server"></i>
                     </div>` :
                    `<div class="event-server-logo-fallback">
                        <i class="bi bi-server"></i>
                     </div>`;
                
                return `
                    <div class="event-card" style="cursor: pointer;" onclick="openEventModal(${event.server_id})">
                        <div class="event-date">
                            <span class="date-label">${dateLabel}</span>
                            ${eventTime}
                        </div>
                        <div class="event-info">
                            <div class="event-title">${event.title}</div>
                            <div class="event-server-with-logo">
                                ${logoHtml}
                                <span class="event-server-name">${event.server_name}</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            eventsList.innerHTML = '<p class="text-center text-muted">Nessun evento nei prossimi 7 giorni</p>';
        }
        
    } catch (error) {
        console.error('Errore nel refresh degli eventi:', error);
    } finally {
        // Rimuovi animazione
        setTimeout(() => {
            btn.classList.remove('spinning');
            btn.disabled = false;
        }, 500);
    }
}

// Event listener per il pulsante refresh e toggle mobile
document.addEventListener('DOMContentLoaded', function() {
    const refreshBtn = document.getElementById('refreshEventsBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshEvents);
    }
    
    // Gestione toggle mobile tra eventi e filtri (come modal)
    const toggleButtons = document.querySelectorAll('.btn-toggle-sidebar');
    const eventsSection = document.getElementById('eventsSection');
    const filtersSection = document.getElementById('filtersSection');
    const mobileSidebar = document.getElementById('mobileSidebar');
    const closeMobileSidebar = document.getElementById('closeMobileSidebar');
    
    // Funzione per chiudere il modal
    function closeSidebarModal() {
        if (mobileSidebar) {
            mobileSidebar.classList.remove('modal-open');
            document.body.style.overflow = '';
        }
    }
    
    // Gestione click sui pulsanti toggle
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-target');
            
            // Nascondi tutte le sezioni
            if (eventsSection) eventsSection.classList.remove('active');
            if (filtersSection) filtersSection.classList.remove('active');
            
            // Mostra la sezione selezionata
            if (target === 'events' && eventsSection) {
                eventsSection.classList.add('active');
            } else if (target === 'filters' && filtersSection) {
                filtersSection.classList.add('active');
            }
            
            // Apri il modal su mobile
            if (window.innerWidth <= 991 && mobileSidebar) {
                mobileSidebar.classList.add('modal-open');
                document.body.style.overflow = 'hidden';
            }
        });
    });
    
    // Chiusura con pulsante X
    if (closeMobileSidebar) {
        closeMobileSidebar.addEventListener('click', closeSidebarModal);
    }
    
    // Chiusura cliccando sul backdrop
    if (mobileSidebar) {
        mobileSidebar.addEventListener('click', function(e) {
            if (e.target === this) {
                closeSidebarModal();
            }
        });
    }
    
    // Chiusura con tasto ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileSidebar && mobileSidebar.classList.contains('modal-open')) {
            closeSidebarModal();
        }
    });
});

// Funzione di ricerca server
function searchServers() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;
    
    const searchTerm = searchInput.value.toLowerCase().trim();
    const servers = document.querySelectorAll('.homepage-server-card');
    
    let matchCount = 0;
    
    servers.forEach(server => {
        const serverName = (server.getAttribute('data-name') || '').toLowerCase().trim();
        
        // Se la searchbar è vuota, mostra tutti i server
        if (searchTerm === '') {
            server.style.display = 'flex';
            server.style.visibility = 'visible';
            server.style.opacity = '1';
            matchCount++;
        } 
        // Altrimenti mostra SOLO i server che contengono il termine di ricerca nel nome
        else if (serverName.includes(searchTerm)) {
            server.style.display = 'flex';
            server.style.visibility = 'visible';
            server.style.opacity = '1';
            matchCount++;
        } else {
            // Nascondi completamente i server che non corrispondono
            server.style.display = 'none';
            server.style.visibility = 'hidden';
            server.style.opacity = '0';
        }
    });
    
    // Riapplica l'ordinamento corrente (che chiamerà updateRankings)
    const currentSort = sessionStorage.getItem('serverSort') || 'votes';
    const currentDirection = sessionStorage.getItem('sortDirection') || 'desc';
    applySorting(currentSort, currentDirection);
}

// Ricerca in tempo reale
document.getElementById('searchInput').addEventListener('input', function() {
    searchServers();
    updateClearFiltersButton();
});

// Gestione filtri avanzata
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza animazioni
    initAnimations();
    
    // Inizializza ordinamento salvato
    loadSavedSort();
    
    // Controlla player-count e aggiorna status offline
    function checkPlayerStatus() {
        document.querySelectorAll('.server-players').forEach(playerSection => {
            const playerCount = playerSection.querySelector('.player-count');
            const playerStatus = playerSection.querySelector('.player-status');
            
            if (playerCount && playerStatus) {
                const countText = playerCount.textContent.trim();
                if (countText === '...' || countText === 'Offline' || countText === '0/0') {
                    playerStatus.textContent = 'offline';
                    playerStatus.style.color = '#ef4444';
                } else {
                    playerStatus.textContent = 'online';
                    playerStatus.style.color = 'var(--accent-green)';
                }
            }
        });
    }
    
    // Controlla subito
    checkPlayerStatus();
    
    // Controlla ogni 2 secondi per aggiornamenti dinamici
    setInterval(checkPlayerStatus, 2000);
    
    // Applica filtro da URL se presente
    <?php if (!empty($active_filter)): ?>
    applyFilterFromURL('<?php echo $active_filter; ?>');
    <?php endif; ?>
    
    // Filter tags
    const filterTags = document.querySelectorAll('.filter-tag');
    filterTags.forEach(tag => {
        tag.addEventListener('click', function() {
            this.classList.toggle('active');
            applyFilters();
            updateClearFiltersButton();
        });
    });
    
    // Clear filters
    document.querySelector('.clear-filters').addEventListener('click', function() {
        filterTags.forEach(tag => tag.classList.remove('active'));
        document.querySelectorAll('.homepage-server-card').forEach(server => {
            server.style.display = 'flex';
            server.style.visibility = 'visible';
            server.style.opacity = '1';
        });
        document.getElementById('searchInput').value = '';
        updateClearFiltersButton();
        // Riapplica l'ordinamento corrente (che chiamerà updateRankings)
        const currentSort = sessionStorage.getItem('serverSort') || 'votes';
        const currentDirection = sessionStorage.getItem('sortDirection') || 'desc';
        applySorting(currentSort, currentDirection);
    });
    
    // Sort dropdown items
    document.querySelectorAll('.sort-option').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const sortType = this.getAttribute('data-sort');
            
            // Controlla se è lo stesso tipo di ordinamento per invertire
            const currentSort = sessionStorage.getItem('serverSort');
            const currentDirection = sessionStorage.getItem('sortDirection') || 'desc';
            
            let newDirection = 'desc';
            if (currentSort === sortType && currentDirection === 'desc') {
                newDirection = 'asc';
            }
            
            applySorting(sortType, newDirection);
            sessionStorage.setItem('serverSort', sortType);
            sessionStorage.setItem('sortDirection', newDirection);
            updateSortArrows(sortType, newDirection);
        });
    });
    
    // Server IP click to copy
    document.querySelectorAll('.server-ip').forEach(ip => {
        ip.addEventListener('click', function() {
            copyServerIP(this.textContent);
        });
    });
    
    // Inizializza stato bottone clear filters
    updateClearFiltersButton();
    
    // Debug: verifica che gli stili siano caricati
    console.log('Clear filters button found:', document.querySelector('.clear-filters'));
    console.log('Filter tags found:', document.querySelectorAll('.filter-tag').length);
    
    // Debug: verifica server cards
    const serverCards = document.querySelectorAll('.homepage-server-card');
    console.log('Server cards found:', serverCards.length);
    serverCards.forEach((card, index) => {
        const serverId = card.getAttribute('data-server-id');
        const votes = card.getAttribute('data-votes');
        const name = card.getAttribute('data-name');
        console.log(`Server ${index + 1}: ID=${serverId}, Votes=${votes}, Name=${name}`);
    });
});

function applyFilters() {
    const activeFilters = Array.from(document.querySelectorAll('.filter-tag.active')).map(tag => tag.textContent.toLowerCase());
    const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
    const servers = document.querySelectorAll('.homepage-server-card');
    
    let visibleCount = 0;
    
    servers.forEach(server => {
        const serverName = (server.getAttribute('data-name') || '').toLowerCase().trim();
        
        // Usa data-all-tags per includere anche le modalità nascoste
        const allTagsString = server.getAttribute('data-all-tags') || '';
        const serverTags = allTagsString.split(',').map(tag => tag.trim().toLowerCase()).filter(tag => tag);
        
        const matchesSearch = !searchTerm || serverName.includes(searchTerm);
        const matchesFilter = activeFilters.length === 0 || activeFilters.some(filter => serverTags.includes(filter));
        
        if (matchesSearch && matchesFilter) {
            server.style.display = 'flex';
            server.style.visibility = 'visible';
            server.style.opacity = '1';
            visibleCount++;
        } else {
            server.style.display = 'none';
            server.style.visibility = 'hidden';
            server.style.opacity = '0';
        }
    });
    
    // Update results count
    updateResultsCount(visibleCount);
    
    // Riapplica l'ordinamento corrente sui server visibili (che chiamerà updateRankings)
    const currentSort = sessionStorage.getItem('serverSort') || 'votes';
    const currentDirection = sessionStorage.getItem('sortDirection') || 'desc';
    applySorting(currentSort, currentDirection);
}

function updateClearFiltersButton() {
    const activeCount = document.querySelectorAll('.filter-tag.active').length;
    const clearBtn = document.querySelector('.clear-filters');
    
    if (!clearBtn) {
        console.warn('Clear filters button not found');
        return;
    }
    
    if (activeCount > 0) {
        clearBtn.classList.add('active');
        // Backup inline style per assicurarsi che funzioni
        clearBtn.style.backgroundColor = '#ffebee';
        clearBtn.style.borderColor = '#ffcdd2';
        clearBtn.style.color = '#c62828';
        console.log('Clear filters button activated - filters active:', activeCount);
    } else {
        clearBtn.classList.remove('active');
        // Rimuovi stili inline
        clearBtn.style.backgroundColor = '';
        clearBtn.style.borderColor = '';
        clearBtn.style.color = '';
        console.log('Clear filters button deactivated');
    }
}

function applySorting(sortType, direction = 'desc') {
    const serverList = document.querySelector('.server-list');
    const servers = Array.from(serverList.querySelectorAll('.homepage-server-card'));
    
    // Ordina solo i server visibili
    const visibleServers = servers.filter(server => server.style.display !== 'none');
    const hiddenServers = servers.filter(server => server.style.display === 'none');
    
    visibleServers.sort((a, b) => {
        let result = 0;
        switch (sortType) {
            case 'votes':
                result = getVotes(b) - getVotes(a);
                break;
            case 'name':
                result = getServerName(a).localeCompare(getServerName(b));
                break;
            case 'players':
                result = getPlayerCount(b) - getPlayerCount(a);
                break;
            default:
                return 0;
        }
        
        // Inverti il risultato se la direzione è ascendente
        return direction === 'asc' ? -result : result;
    });
    
    // Rimuovi tutti i server dal DOM
    servers.forEach(server => server.remove());
    
    // Aggiungi prima i server visibili ordinati, poi quelli nascosti
    visibleServers.forEach(server => serverList.appendChild(server));
    hiddenServers.forEach(server => serverList.appendChild(server));
    
    // Ricalcola i ranking dopo il riordinamento
    updateRankings();
}

function updateRankings() {
    const visibleServers = Array.from(document.querySelectorAll('.homepage-server-card')).filter(server => server.style.display !== 'none');
    
    // Aggiorna ogni server con il suo rank ORIGINALE (posizione nella classifica globale)
    visibleServers.forEach(server => {
        const rank = parseInt(server.getAttribute('data-original-rank')) || 1;
        const rankContainer = server.querySelector('.server-rank-container');
        const rankElement = server.querySelector('.server-rank');
        const rankNumberTop = server.querySelector('.rank-number-top');
        const votesValue = parseInt(server.getAttribute('data-votes')) || 0;
        let bottomVotes = rankContainer ? rankContainer.querySelector('.rank-votes') : null;
        
        if (!rankContainer || !rankElement) return;
        
        // Non mostrare mai il numero in alto (rimuovi se esiste)
        if (rankNumberTop) {
            rankNumberTop.remove();
        }
        
        // Aggiorna le classi CSS per i colori
        rankElement.className = 'server-rank' + (rank > 3 ? ' outlined' : '');
        if (rank === 1) {
            rankElement.classList.add('gold');
        } else if (rank === 2) {
            rankElement.classList.add('silver');
        } else if (rank === 3) {
            rankElement.classList.add('bronze');
        }
        
        // Aggiorna contenuto e box voti in base al rank
        if (rank <= 3) {
            // Mostra trofeo + posizione
            rankElement.innerHTML = '';
            const icon = document.createElement('i');
            icon.className = 'bi bi-trophy-fill';
            icon.style.marginRight = '6px';
            rankElement.appendChild(icon);
            rankElement.appendChild(document.createTextNode(rank + '°'));

            // Crea/aggiorna box voti sotto
            if (!bottomVotes) {
                bottomVotes = document.createElement('div');
                bottomVotes.className = 'rank-votes';
                bottomVotes.style.cssText = 'font-size: 0.9rem; color: var(--text-muted); margin-top: 5px; text-align: center;';
                rankContainer.appendChild(bottomVotes);
            }
            bottomVotes.textContent = '+' + votesValue;
        } else {
            // Per rank > 3: il badge mostra la posizione, sotto il box mostra +voti
            const trophyIcon = rankElement.querySelector('.bi-trophy-fill');
            if (trophyIcon) trophyIcon.remove();
            rankElement.textContent = rank + '°';
            if (!bottomVotes) {
                bottomVotes = document.createElement('div');
                bottomVotes.className = 'rank-votes';
                bottomVotes.style.cssText = 'font-size: 0.9rem; color: var(--text-muted); margin-top: 5px; text-align: center;';
                rankContainer.appendChild(bottomVotes);
            }
            bottomVotes.textContent = '+' + votesValue;
        }
    });
}

function getVotes(serverCard) {
    // Usa il data attribute che è più affidabile
    const votes = serverCard.getAttribute('data-votes');
    if (votes) return parseInt(votes);
    
    // Fallback al testo dell'elemento
    const rankElement = serverCard.querySelector('.server-rank');
    if (!rankElement) return 0;
    const voteText = rankElement.textContent.replace('+', '').replace(/[^\d]/g, '').trim();
    return parseInt(voteText) || 0;
}

function getServerName(serverCard) {
    return serverCard.getAttribute('data-name') || '';
}

function getPlayerCount(serverCard) {
    const playerElement = serverCard.querySelector('.player-count');
    if (!playerElement) return 0;
    const playerText = playerElement.textContent.trim();
    if (playerText === '...' || playerText === 'Offline') return 0;
    return parseInt(playerText) || 0;
}

function loadSavedSort() {
    // Default sempre a VOTI DESC all'aggiornamento pagina
    const savedSort = 'votes';
    const savedDirection = 'desc';
    sessionStorage.setItem('serverSort', savedSort);
    sessionStorage.setItem('sortDirection', savedDirection);
    applySorting(savedSort, savedDirection);
    updateSortArrows(savedSort, savedDirection);
}

function updateSortArrows(activeSort, direction) {
    // Reset tutte le frecce
    document.querySelectorAll('.sort-arrow').forEach(arrow => {
        arrow.textContent = '';
    });
    // Aggiorna la freccia dell'opzione attiva
    const activeArrow = document.getElementById(`arrow-${activeSort}`);
    if (activeArrow) {
        activeArrow.textContent = direction === 'desc' ? '↓' : '↑';
        activeArrow.style.color = 'var(--primary-color)';
    }

    // Aggiorna lo stato attivo dei pulsanti
    document.querySelectorAll('.sort-option').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-sort') === activeSort);
    });
}

function updateResultsCount(count) {
    let resultsInfo = document.querySelector('.results-info');
    if (!resultsInfo) {
        resultsInfo = document.createElement('div');
        resultsInfo.className = 'results-info';
        resultsInfo.style.cssText = 'color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem;';
        document.querySelector('.server-list').parentNode.insertBefore(resultsInfo, document.querySelector('.server-list'));
    }
    
    resultsInfo.textContent = `Mostrando ${count} server${count !== 1 ? 's' : ''}`;
}

function initAnimations() {
    // Staggered animation for server cards
    const cards = document.querySelectorAll('.homepage-server-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Animate filter tags
    const filterTags = document.querySelectorAll('.filter-tag');
    filterTags.forEach((tag, index) => {
        tag.style.opacity = '0';
        tag.style.transform = 'scale(0.8)';
        
        setTimeout(() => {
            tag.style.transition = 'all 0.4s ease';
            tag.style.opacity = '1';
            tag.style.transform = 'scale(1)';
        }, 500 + index * 50);
    });
}

// Copy server IP function
function copyServerIP(ip) {
    navigator.clipboard.writeText(ip).then(function() {
        // Show toast notification
        showToast('IP copiato negli appunti!', 'success');
    }).catch(function() {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = ip;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showToast('IP copiato negli appunti!', 'success');
    });
}

// Toast notification function
function showToast(message, type = 'info') {
    const toastContainer = document.querySelector('.toast-container') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'primary'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1050';
    document.body.appendChild(container);
    return container;
}

// Applica filtro da URL
function applyFilterFromURL(filterName) {
    const filterTags = document.querySelectorAll('.filter-tag');
    let filterFound = false;
    
    filterTags.forEach(tag => {
        const tagText = tag.textContent.toLowerCase();
        if (tagText === filterName.toLowerCase() || 
            (filterName === 'minigames' && tagText === 'minigames') ||
            (filterName === 'roleplay' && tagText === 'roleplay') ||
            (filterName === 'skyblock' && tagText === 'skyblock')) {
            tag.classList.add('active');
            filterFound = true;
        }
    });
    
    if (filterFound) {
        applyFilters();
        updateClearFiltersButton();
        showToast(`Filtro "${filterName}" applicato!`, 'success');
    }
}
</script>

<?php include 'footer.php'; ?>