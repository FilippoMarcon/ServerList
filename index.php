<?php
/**
 * Homepage - Lista Server Minecraft
 * Minecraft Server List Homepage
 */

require_once 'config.php';

// Titolo pagina
$page_title = "Lista Server Minecraft";

// Query per ottenere i server con conteggio voti
try {
    $stmt = $pdo->query("
        SELECT s.*, COUNT(v.id) as voti_totali 
        FROM sl_servers s 
        LEFT JOIN sl_votes v ON s.id = v.server_id 
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

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Scopri i Migliori Server Minecraft</h1>
            <p class="hero-subtitle">
                Esplora centinaia di server unici, vota i tuoi preferiti e unisciti alla community più grande d'Italia
            </p>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Server List Header -->
            <div class="server-list-header">
                <div class="d-flex align-items-center">
                    <h3>Voti</h3>
                    <span class="ms-2">↑</span>
                    <h3 class="ms-4">Server</h3>
                    <span class="ms-2">↑</span>
                </div>
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Cerca" id="searchInput">
                    <button class="filters-btn">
                        <i class="bi bi-funnel"></i> Filtri
                    </button>
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
                        
                        // Generate some sample tags based on server name/description
                        $tags = [];
                        if (stripos($server['nome'], 'roleplay') !== false || stripos($server['descrizione'], 'roleplay') !== false) {
                            $tags[] = 'RolePlay';
                        }
                        if (stripos($server['nome'], 'survival') !== false || stripos($server['descrizione'], 'survival') !== false) {
                            $tags[] = 'Survival';
                        }
                        if (stripos($server['nome'], 'pvp') !== false || stripos($server['descrizione'], 'pvp') !== false) {
                            $tags[] = 'PvP';
                        }
                        if (stripos($server['nome'], 'mini') !== false || stripos($server['descrizione'], 'mini') !== false) {
                            $tags[] = 'MiniGames';
                        }
                        if (empty($tags)) {
                            $tags[] = 'Adventure';
                            $tags[] = 'Vanilla';
                        }
                    ?>
                        <div class="server-card" data-name="<?php echo htmlspecialchars(strtolower($server['nome'])); ?>">
                            <div class="server-rank <?php echo $rank_class; ?>">
                                <?php if ($rank <= 3): ?>
                                    <i class="bi bi-trophy-fill"></i>
                                <?php endif; ?>
                                +<?php echo $server['voti_totali']; ?>
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
                                <a href="ServerList/server/<?php echo $server['id']; ?>" class="server-name">
                                    <?php echo htmlspecialchars($server['nome']); ?> 
                                    <span style="font-size: 0.9rem; color: var(--text-muted);">
                                        <?php echo htmlspecialchars($server['versione'] ?: '1.20.2'); ?>
                                    </span>
                                </a>
                                <div class="server-ip"><?php echo htmlspecialchars($server['ip']); ?></div>
                                <div class="server-tags">
                                    <?php foreach ($tags as $tag): ?>
                                        <span class="server-tag"><?php echo $tag; ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="server-players">
                                <div class="player-count">
                                    <?php 
                                    // Generate random player count for demo
                                    $players = rand(50, 1500);
                                    echo number_format($players);
                                    ?>
                                </div>
                                <div class="player-status">online</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">
                                    Java & Bedrock
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
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Filtri</h4>
                    <button class="clear-filters">Rimuovi filtri</button>
                </div>
                
                <div class="filter-group">
                    <h5>Modalità</h5>
                    <div class="filter-tags">
                        <span class="filter-tag active">Adventure</span>
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
    const servers = document.querySelectorAll('.server-card');
    
    servers.forEach(server => {
        const serverName = server.getAttribute('data-name');
        if (serverName && serverName.includes(searchTerm)) {
            server.style.display = 'flex';
        } else {
            server.style.display = 'none';
        }
    });
}

// Ricerca in tempo reale
document.getElementById('searchInput').addEventListener('input', searchServers);

// Gestione filtri avanzata
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza animazioni
    initAnimations();
    
    // Filter tags
    const filterTags = document.querySelectorAll('.filter-tag');
    filterTags.forEach(tag => {
        tag.addEventListener('click', function() {
            this.classList.toggle('active');
            applyFilters();
            updateFilterCount();
        });
    });
    
    // Clear filters
    document.querySelector('.clear-filters').addEventListener('click', function() {
        filterTags.forEach(tag => tag.classList.remove('active'));
        document.querySelectorAll('.server-card').forEach(server => {
            server.style.display = 'flex';
        });
        document.getElementById('searchInput').value = '';
        updateFilterCount();
    });
    
    // Filters button toggle
    document.querySelector('.filters-btn').addEventListener('click', function() {
        const sidebar = document.querySelector('.col-lg-3');
        sidebar.classList.toggle('d-none');
        sidebar.classList.toggle('d-lg-block');
    });
    
    // Server IP click to copy
    document.querySelectorAll('.server-ip').forEach(ip => {
        ip.addEventListener('click', function() {
            copyServerIP(this.textContent);
        });
    });
});

function applyFilters() {
    const activeFilters = Array.from(document.querySelectorAll('.filter-tag.active')).map(tag => tag.textContent.toLowerCase());
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const servers = document.querySelectorAll('.server-card');
    
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
}

function updateFilterCount() {
    const activeCount = document.querySelectorAll('.filter-tag.active').length;
    const filtersBtn = document.querySelector('.filters-btn');
    
    if (activeCount > 0) {
        filtersBtn.innerHTML = `<i class="bi bi-funnel-fill"></i> Filtri (${activeCount})`;
    } else {
        filtersBtn.innerHTML = `<i class="bi bi-funnel"></i> Filtri`;
    }
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
    const cards = document.querySelectorAll('.server-card');
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
</script>

<?php include 'footer.php'; ?>