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

// Pagine top-level senza estensione: /forum, /annunci, /login, /register, /profile, /admin
if (preg_match('#^/(forum|annunci|login|register|profile|admin)/?$#', $request_path, $m)) {
    $map = [
        'forum' => 'forum.php',
        'annunci' => 'annunci.php',
        'login' => 'login.php',
        'register' => 'register.php',
        'profile' => 'profile.php',
        'admin' => 'admin.php',
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
               CASE WHEN ss.server_id IS NOT NULL THEN 1 ELSE 0 END as is_sponsored
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
                                <span>SPONSORIZZATO</span>
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
                                <h4><a href="server.php?id=<?php echo $sponsored['id']; ?>" style="text-decoration: none; color: inherit;"><?php echo htmlspecialchars($sponsored['nome']); ?></a></h4>
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
                    <a href="forum" class="sponsored-cta-link">clicca qui</a>
                </div>
            </div>
            <?php endif; ?>
            
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
                    ?>
                        <div class="homepage-server-card" data-name="<?php echo htmlspecialchars(strtolower($server['nome'])); ?>" data-server-id="<?php echo $server['id']; ?>" data-votes="<?php echo $server['voti_totali']; ?>">
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
                                    <a href="server.php?id=<?php echo $server['id']; ?>" class="server-name">
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
                                    <?php foreach ($tags as $tag): ?>
                                        <span class="server-tag"><?php echo $tag; ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="server-players">
                                <div class="player-count" data-playercounter-ip="<?php echo htmlspecialchars($server['ip']); ?>">
                                    ...
                                </div>
                                <div class="player-status">online</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">
                                    <?php echo htmlspecialchars($server['tipo_server'] ?? 'Java & Bedrock'); ?>
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
        <div class="col-lg-3">
            <div class="filters-sidebar">
                <div class="filters-header">
                    <h4>Filtri</h4>
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

<script>
// Funzione di ricerca server
function searchServers() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const servers = document.querySelectorAll('.homepage-server-card');
    
    servers.forEach(server => {
        const serverName = server.getAttribute('data-name');
        if (serverName && serverName.includes(searchTerm)) {
            server.style.display = 'flex';
        } else {
            server.style.display = 'none';
        }
    });
    
    // Riapplica l'ordinamento corrente
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
        });
        document.getElementById('searchInput').value = '';
        updateClearFiltersButton();
        // Riapplica l'ordinamento corrente
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
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const servers = document.querySelectorAll('.homepage-server-card');
    
    let visibleCount = 0;
    
    servers.forEach(server => {
        const serverName = server.getAttribute('data-name') || '';
        const serverTags = Array.from(server.querySelectorAll('.server-tag')).map(tag => tag.textContent.toLowerCase());
        
        const matchesSearch = !searchTerm || serverName.includes(searchTerm);
        const matchesFilter = activeFilters.length === 0 || activeFilters.some(filter => serverTags.includes(filter));
        
        if (matchesSearch && matchesFilter) {
            server.style.display = 'flex';
            visibleCount++;
        } else {
            server.style.display = 'none';
        }
    });
    
    // Update results count
    updateResultsCount(visibleCount);
    
    // Riapplica l'ordinamento corrente sui server visibili
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
    
    visibleServers.forEach((server, index) => {
        const rank = index + 1;
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