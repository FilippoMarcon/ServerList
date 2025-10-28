<?php
/**
 * Pagina del Singolo Server
 * Single Server Page
 */

require_once 'config.php';

// Assicurati che esista la colonna staff_list per memorizzare lo staff (JSON)
try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN staff_list JSON NULL"); } catch (Exception $e) {}
// Assicurati che esistano le colonne social per memorizzare i link
try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN website_url VARCHAR(255) NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN shop_url VARCHAR(255) NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN discord_url VARCHAR(255) NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN telegram_url VARCHAR(255) NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN social_links TEXT NULL"); } catch (Exception $e) {}

// Supporto URL con slug: /server/<slug>, con fallback su id
$server_slug = isset($_GET['slug']) ? trim($_GET['slug']) : null;
$server_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($server_slug) {
    try {
        // Converti lo slug: sostituisci trattini con spazi per cercare il nome reale
        $server_name_from_slug = str_replace('-', ' ', $server_slug);
        
        // Risolvi direttamente per colonna nome (case-insensitive)
        $stmt = $pdo->prepare("SELECT id FROM sl_servers WHERE is_active = 1 AND LOWER(nome) = LOWER(?) LIMIT 1");
        $stmt->execute([$server_name_from_slug]);
        $row = $stmt->fetch();
        if ($row && isset($row['id'])) {
            $server_id = (int)$row['id'];
        } else {
            // Fallback: prova anche con lo slug originale (senza conversione)
            $stmt = $pdo->prepare("SELECT id FROM sl_servers WHERE is_active = 1 AND LOWER(nome) = LOWER(?) LIMIT 1");
            $stmt->execute([$server_slug]);
            $row = $stmt->fetch();
            if ($row && isset($row['id'])) {
                $server_id = (int)$row['id'];
            } else {
                // Fallback finale: prova a cercare lo slug all'interno dell'IP del server
                $stmt = $pdo->prepare("SELECT id FROM sl_servers WHERE is_active = 1 AND LOWER(ip) LIKE CONCAT('%', LOWER(?), '%') LIMIT 1");
                $stmt->execute([$server_slug]);
                $row2 = $stmt->fetch();
                if ($row2 && isset($row2['id'])) {
                    $server_id = (int)$row2['id'];
                }
            }
        }
        // Debug opzionale della risoluzione dello slug
        if (isset($_GET['slug_debug'])) {
            echo "<!-- SLUG DEBUG: input='{$server_slug}' converted='{$server_name_from_slug}' resolved_id='{$server_id}' -->";
        }
    } catch (PDOException $e) {
redirect('/');
    }
}

if ($server_id === 0) {
redirect('/');
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
redirect('/');
    }

    // Redirect 301 dai vecchi URL (server.php?id=...) ai nuovi URL (/server/<nome>)
    if (isset($_GET['id']) && !isset($_GET['slug'])) {
        $norm = function ($str) {
            $s = mb_strtolower($str, 'UTF-8');
            $s = preg_replace('/[^a-z0-9]+/i', '-', $s);
            return trim($s, '-');
        };
        $target_slug = $norm($server['nome']);
        $expected_path = '/server/' . $target_slug;
        $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if ($current_path !== $expected_path) {
            header('Location: ' . $expected_path, true, 301);
            exit();
        }
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
    // Usa minecraft_links per ottenere l'avatar corretto
    $stmt = $pdo->prepare("SELECT COALESCE(ml.minecraft_nick, u.minecraft_nick) as minecraft_nick, v.data_voto 
                          FROM sl_votes v 
                          JOIN sl_users u ON v.user_id = u.id 
                          LEFT JOIN sl_minecraft_links ml ON ml.user_id = u.id
                          WHERE v.server_id = ? AND DATE(v.data_voto) = CURDATE()
                          ORDER BY v.data_voto DESC");
    $stmt->execute([$server_id]);
    $voters = $stmt->fetchAll();

    // Decodifica StaffList del server
    $server_staff = [];
    if (!empty($server['staff_list'])) {
        $decoded = json_decode($server['staff_list'], true);
        if (is_array($decoded)) { $server_staff = $decoded; }
    }

    // Aggregazioni voti per grafici
    // Ultimi 30 giorni (per giorno)
    $last30_map = [];
    $stmt = $pdo->prepare("SELECT DATE(data_voto) AS day, COUNT(*) AS votes
                           FROM sl_votes
                           WHERE server_id = ? AND data_voto >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                           GROUP BY DATE(data_voto)");
    $stmt->execute([$server_id]);
    foreach ($stmt->fetchAll() as $row) {
        $last30_map[$row['day']] = (int)$row['votes'];
    }
    $last30Labels = [];
    $last30Data = [];
    for ($i = 29; $i >= 0; $i--) {
        $d = new DateTime();
        $d->modify("-{$i} day");
        $key = $d->format('Y-m-d');
        $last30Labels[] = $key;
        $last30Data[] = isset($last30_map[$key]) ? (int)$last30_map[$key] : 0;
    }

    // Ultimi 12 mesi (per mese)
    $last12_map = [];
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(data_voto, '%Y-%m') AS ym, COUNT(*) AS votes
                           FROM sl_votes
                           WHERE server_id = ? AND data_voto >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                           GROUP BY DATE_FORMAT(data_voto, '%Y-%m')");
    $stmt->execute([$server_id]);
    foreach ($stmt->fetchAll() as $row) {
        $last12_map[$row['ym']] = (int)$row['votes'];
    }
    $last12Labels = [];
    $last12Data = [];
    $start = new DateTime('first day of this month');
    $start->modify('-11 months');
    for ($m = 0; $m < 12; $m++) {
        $curr = clone $start;
        $curr->modify("+{$m} months");
        $ym = $curr->format('Y-m');
        $last12Labels[] = $ym;
        $last12Data[] = isset($last12_map[$ym]) ? (int)$last12_map[$ym] : 0;
    }

    // Controlla se l'utente loggato può votare (NUOVO SISTEMA)
    $can_vote = false;
    $user_has_voted_today = false;
    $voted_server_name = '';
    $time_until_next_vote = '';
    $needs_link = false;
    $verified_nick = null;
    
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

        // Richiede account Minecraft collegato per poter votare
        try {
            $stmt = $pdo->prepare("SELECT minecraft_nick FROM sl_minecraft_links WHERE user_id = ? LIMIT 1");
            $stmt->execute([$user_id]);
            $verified_nick = $stmt->fetchColumn();
            if (!$verified_nick) {
                $can_vote = false;
                $needs_link = true;
            }
        } catch (PDOException $e) {
            // Fail-closed: se c'è errore, non permettere il voto
            $can_vote = false;
            $needs_link = true;
        }
    }
    
} catch (PDOException $e) {
redirect('/');
}

// Meta SEO dinamici per la pagina server
$page_title = htmlspecialchars($server['nome']);
// Descrizione breve dai contenuti HTML (strip tags) limitata a ~160 caratteri
$raw_desc = isset($server['descrizione']) ? strip_tags($server['descrizione']) : '';
$raw_desc = preg_replace('/\s+/', ' ', $raw_desc);
$page_description = trim($raw_desc) ? mb_substr(trim($raw_desc), 0, 160) : ('Dettagli e IP di ' . $server['nome'] . ' su ' . SITE_NAME);
// Immagine Open Graph: preferisci banner, poi logo, fallback al logo del sito
$base_url = (defined('SITE_URL') ? SITE_URL : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']));
if (!empty($server['banner_url'])) {
    $og_image = $server['banner_url'];
} elseif (!empty($server['logo_url'])) {
    $og_image = $server['logo_url'];
} else {
    $og_image = $base_url . '/logo.png';
}

// Favicon specifico per la pagina server (se disponibile), altrimenti default
$page_favicon = !empty($server['logo_url']) ? $server['logo_url'] : 'logo.png';

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
            /* Stili base per la descrizione */
            background: var(--card-bg) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 12px !important;
            padding: 2rem !important;
            margin: 1.5rem 0 !important;
            outline: none !important;
            
            /* Stili di base che possono essere sovrascritti dagli stili inline */
            color: #e0e6ed;
            text-align: left;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
            line-height: 1.6;
            word-break: break-word;
            hyphens: auto;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', sans-serif;
            font-size: 1.1rem;
            font-weight: normal;
            text-decoration: none;
            text-transform: none;
            letter-spacing: normal;
        }
        
        /* Gli stili inline hanno precedenza naturale sui CSS esterni */



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
    font-weight: 700;
}

.server-description .description-text em {
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
            padding: 0;
            height: 0.8rem;
            background-color: transparent;
            border: none;
            color: transparent;
        }

/* Stili isolati per elementi HTML di Quill */
        .server-description .description-text strong,
        .server-description .description-text b {
            font-weight: bold;
            background-color: transparent;
            text-decoration: none;
            border: none;
        }
        
        .server-description .description-text em,
        .server-description .description-text i {
            font-style: italic;
            background-color: transparent;
            text-decoration: none;
            border: none;
        }
        
        .server-description .description-text u {
            text-decoration: underline;
            background-color: transparent;
            border: none;
        }
        
        .server-description .description-text h1,
        .server-description .description-text h2,
        .server-description .description-text h3,
        .server-description .description-text h4,
        .server-description .description-text h5,
        .server-description .description-text h6 {
            margin: 0.5rem 0;
            padding: 0;
            font-weight: bold;
            background-color: transparent;
            text-decoration: none;
            border: none;
            text-transform: none;
            letter-spacing: normal;
        }
        
        .server-description .description-text h1 { font-size: 1.8rem !important; }
        .server-description .description-text h2 { font-size: 1.6rem !important; }
        .server-description .description-text h3 { font-size: 1.4rem !important; }
        .server-description .description-text h4 { font-size: 1.2rem !important; }
        .server-description .description-text h5 { font-size: 1.1rem !important; }
        .server-description .description-text h6 { font-size: 1rem !important; }
        
        .server-description .description-text ul,
        .server-description .description-text ol {
            margin: 0.5rem 0;
            padding: 0 0 0 1.5rem;
            color: #e0e6ed;
            background-color: transparent;
            border: none;
            list-style-position: outside;
        }
        
        .server-description .description-text li {
            margin: 0.2rem 0;
            padding: 0;
            color: #e0e6ed;
            background-color: transparent;
            border: none;
        }
        
        .server-description .description-text blockquote {
            border-left: 3px solid #007bff;
            padding: 0 0 0 1rem;
            margin: 0.5rem 0;
            font-style: italic;
            background-color: rgba(0, 123, 255, 0.1);
            color: #e0e6ed;
            border-top: none;
            border-right: none;
            border-bottom: none;
        }
        
        .server-description .description-text a {
            color: #007bff;
            text-decoration: none;
            background-color: transparent;
            border: none;
            font-weight: normal;
        }
        
        .server-description .description-text a:hover {
            text-decoration: underline;
            color: #0056b3;
        }
        
        .server-description .description-text p {
            margin: 0.5rem 0;
            padding: 0;
            line-height: 1.6;
            color: #e0e6ed;
            background-color: transparent;
            border: none;
            font-size: inherit;
            font-weight: normal;
            text-decoration: none;
            text-transform: none;
            letter-spacing: normal;
        }
        
        .server-description .description-text p:first-child {
            margin-top: 0;
        }
        
        .server-description .description-text p:last-child {
            margin-bottom: 0;
        }
        
        /* Supporto per span e div con stili inline di Quill */
        .server-description .description-text span {
            background-color: transparent;
            border: none;
            margin: 0;
            padding: 0;
        }
        
        .server-description .description-text div {
            background-color: transparent;
            border: none;
            margin: 0;
            padding: 0;
        }
        
        /* Permette agli stili inline di Quill di funzionare */
        .server-description .description-text * {
            /* Gli stili inline hanno automaticamente precedenza */
        }

/* Server Header Layout */
.server-header-container {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    min-height: 400px;
}

.server-header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    max-width: 1200px;
}

.server-main-info {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

/* Mobile Responsive Styles */
@media (max-width: 768px) {
    .server-header {
        min-height: auto;
        padding: 2rem 0;
    }
    
    .server-header-container {
        min-height: auto;
        padding: 0 1rem;
        display: block;
    }
    
    .server-header-content {
        display: block;
        width: 100%;
    }
    
    /* Sezione superiore: Logo + Info */
    .server-main-info {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        width: 100%;
    }
    
    .server-logo-section {
        flex-shrink: 0;
    }
    
    .server-logo-large {
        width: 80px;
        height: 80px;
    }
    
    .server-title-section {
        flex: 1;
        min-width: 0;
    }
    
    .server-title {
        font-size: 1.4rem;
        margin-bottom: 0.75rem;
        line-height: 1.3;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    
    .server-details-row {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    
    .server-rank-badge {
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
        white-space: nowrap;
    }
    
    .server-ip-display {
        font-size: 0.9rem;
        padding: 0.4rem 0.8rem;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 6px;
        word-break: break-all;
    }
    
    /* Sezione voto: sotto tutto */
    .server-vote-section {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
        width: 100%;
    }
    
    .vote-button-container {
        width: 100%;
    }
    
    .vote-button {
        width: 100%;
        font-size: 1rem;
        padding: 0.875rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .vote-link-tip {
        text-align: center;
        font-size: 0.85rem;
    }
    
    .vote-count {
        font-size: 1.1rem;
        text-align: center;
    }
    
    .vote-action {
        display: none;
    }
    
    /* Spaziatura tra le card su mobile */
    .tab-content-container {
        margin-bottom: 1.5rem;
    }
    
    .server-info-card {
        margin-bottom: 1.5rem;
    }
    
    .server-info-card:last-child {
        margin-bottom: 0;
    }
}

@media (max-width: 576px) {
    .server-header {
        padding: 1.5rem 0;
    }
    
    .server-header-container {
        padding: 0 0.75rem;
    }
    
    .server-logo-large {
        width: 70px;
        height: 70px;
    }
    
    .server-title {
        font-size: 1.2rem;
    }
    
    .server-rank-badge {
        font-size: 0.8rem;
        padding: 0.35rem 0.7rem;
    }
    
    .server-ip-display {
        font-size: 0.85rem;
        padding: 0.35rem 0.7rem;
    }
    
    .vote-button {
        font-size: 0.95rem;
        padding: 0.75rem 1rem;
    }
    
    .vote-count {
        font-size: 1rem;
    }
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
        
        <div class="container server-header-container">
            <div class="server-header-content">
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
                                <?php elseif (isLoggedIn() && $needs_link): ?>
                                    Collega l'account Minecraft per votare
                                <?php else: ?>
                                    Accedi per votare
                                <?php endif; ?>
                            </button>
                            <?php if (isLoggedIn() && $needs_link && !$user_has_voted_today): ?>
                                <div class="vote-link-tip" style="margin-top:8px;">
                                    <small>
                                        <i class="bi bi-link-45deg"></i>
                                        <a href="/verifica-nickname" class="text-decoration-underline">Collega il tuo account Minecraft</a> per poter votare.
                                    </small>
                                </div>
                            <?php endif; ?>
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
                        <!-- content-banner rimosso (desktop e mobile) -->
                        
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
                            <?php if (!empty($server_staff)): ?>
                                <div class="server-staff-list" style="display:flex; flex-direction:column; gap:12px;">
                                    <?php foreach ($server_staff as $group): 
                                        $rank_title = isset($group['rank']) ? trim($group['rank']) : '';
                                        $members = (isset($group['members']) && is_array($group['members'])) ? $group['members'] : [];
                                        if ($rank_title === '' && empty($members)) continue;
                                    ?>
                                        <div class="staff-rank-card" style="background: var(--card-bg); border:none; border-radius:10px; padding:10px 12px;">
                                            <?php if ($rank_title !== ''): ?>
                                                <div class="staff-rank-header" style="display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:8px; text-align:center;">
                                                    <i class="bi bi-award"></i>
                                                    <strong style="font-size:1.15rem;"><?= htmlspecialchars($rank_title) ?></strong>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($members)): ?>
                                                <div class="staff-rank-members" style="display:flex; flex-wrap:wrap; gap:6px; justify-content:center;">
                                                    <?php foreach ($members as $m): $nick = trim((string)$m); if ($nick==='') continue; ?>
                                                        <div class="staff-member-card" style="background: var(--card-bg); border:1px solid var(--border-color); border-radius:10px; padding:12px; width:140px; display:flex; flex-direction:column; align-items:center; gap:8px; text-align:center;">
                                                            <img src="https://mc-heads.net/head/<?= urlencode($nick) ?>" alt="Head" width="80" height="80" style="border-radius:8px;">
                                                            <div class="staff-member-name" style="font-size:1.05rem; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                                <?= htmlspecialchars($nick) ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text--secondary" style="font-size:0.9rem;">Nessuno staffer indicato.</div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p>Informazioni sullo staff non disponibili al momento.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Stats Tab -->
                    <div class="tab-content" id="stats">
                        <div class="stats-section">
                            <h3>Ultimi 30 giorni</h3>
                            <div class="chart-container">
                                <canvas id="votes30daysChart" height="140"></canvas>
                            </div>
                        </div>
                        <div class="stats-section" style="margin-top: 1.5rem;">
                            <h3>Ultimi 12 mesi</h3>
                            <div class="chart-container">
                                <canvas id="votes12monthsChart" height="140"></canvas>
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
                
                <?php 
                // Preleva link social dinamici (JSON) e legacy dal DB
                $website = trim((string)($server['website_url'] ?? ''));
                $shop = trim((string)($server['shop_url'] ?? ''));
                $discord = trim((string)($server['discord_url'] ?? ''));
                $telegram = trim((string)($server['telegram_url'] ?? ''));
                $social_json = [];
                if (!empty($server['social_links'])) {
                    $decoded = json_decode($server['social_links'], true);
                    if (is_array($decoded)) {
                        // Normalizza elementi: richiede {title, url}
                        foreach ($decoded as $item) {
                            $title = trim((string)($item['title'] ?? ''));
                            $url = trim((string)($item['url'] ?? ''));
                            if ($url !== '') {
                                $social_json[] = ['title' => $title ?: 'Link', 'url' => $url];
                            }
                        }
                    }
                }
                $has_social = (!empty($social_json) || $website !== '' || $shop !== '' || $discord !== '' || $telegram !== '');
                ?>
                <?php if ($has_social): ?>
                <!-- Social Links -->
                <div class="server-social-card">
                    <h4><i class="bi bi-share"></i> Social</h4>
                    <div class="social-links-modern">
                        <?php
                        // Helper per determinare l'icona in base al titolo/URL
                        $iconFor = function($title, $url) {
                            $t = strtolower(trim($title));
                            $u = strtolower($url);
                            $host = parse_url($url, PHP_URL_HOST) ?: '';
                            $host = strtolower($host);
                            if (strpos($u,'instagram')!==false || strpos($host,'instagram')!==false || $t==='instagram') return 'bi-instagram';
                            if (strpos($u,'discord')!==false || strpos($host,'discord')!==false || $t==='discord') return 'bi-discord';
                            if (strpos($u,'telegram')!==false || strpos($host,'telegram')!==false || $t==='telegram') return 'bi-telegram';
                            if (strpos($u,'youtube')!==false || strpos($host,'youtube')!==false || $t==='youtube') return 'bi-youtube';
                            if (strpos($u,'twitch')!==false || strpos($host,'twitch')!==false || $t==='twitch') return 'bi-twitch';
                            if (strpos($u,'facebook')!==false || strpos($host,'facebook')!==false || $t==='facebook') return 'bi-facebook';
                            if (strpos($u,'tiktok')!==false || strpos($host,'tiktok')!==false || $t==='tiktok') return 'bi-tiktok';
                            if (strpos($u,'x.com')!==false || strpos($host,'x.com')!==false || $t==='twitter' || $t==='x') return 'bi-twitter-x';
                            if (strpos($u,'store')!==false || strpos($u,'shop')!==false || strpos($host,'store')!==false || strpos($host,'shop')!==false || $t==='shop') return 'bi-shop';
                            if ($t==='website' || $t==='sito' || $t==='site' || $t==='web') return 'bi-globe';
                            return 'bi-link-45deg';
                        };

                        if (!empty($social_json)) {
                            foreach ($social_json as $item) {
                                $title = $item['title'];
                                $url = $item['url'];
                                $icon = $iconFor($title, $url);
                                ?>
                                <a href="<?php echo htmlspecialchars($url); ?>" class="social-link-modern" target="_blank" rel="noopener">
                                    <i class="bi <?php echo $icon; ?>"></i> <?php echo htmlspecialchars($title); ?>
                                    <span class="social-url"><?php echo htmlspecialchars(preg_replace('#^https?://#i', '', (string)$url)); ?></span>
                                </a>
                                <?php
                            }
                        } else {
                            if ($website !== '') { ?>
                                <a href="<?php echo htmlspecialchars($website); ?>" class="social-link-modern website" target="_blank" rel="noopener">
                                    <i class="bi bi-globe"></i> Website
                                    <span class="social-url"><?php echo htmlspecialchars(preg_replace('#^https?://#i', '', $website)); ?></span>
                                </a>
                            <?php } ?>
                            <?php if ($shop !== '') { ?>
                                <a href="<?php echo htmlspecialchars($shop); ?>" class="social-link-modern shop" target="_blank" rel="noopener">
                                    <i class="bi bi-shop"></i> Shop
                                    <span class="social-url"><?php echo htmlspecialchars(preg_replace('#^https?://#i', '', $shop)); ?></span>
                                </a>
                            <?php } ?>
                            <?php if ($discord !== '') { ?>
                                <a href="<?php echo htmlspecialchars($discord); ?>" class="social-link-modern discord" target="_blank" rel="noopener">
                                    <i class="bi bi-discord"></i> Discord
                                    <span class="social-url"><?php echo htmlspecialchars(preg_replace('#^https?://#i', '', $discord)); ?></span>
                                </a>
                            <?php } ?>
                            <?php if ($telegram !== '') { ?>
                                <a href="<?php echo htmlspecialchars($telegram); ?>" class="social-link-modern telegram" target="_blank" rel="noopener">
                                    <i class="bi bi-telegram"></i> Telegram
                                    <span class="social-url"><?php echo htmlspecialchars(preg_replace('#^https?://#i', '', $telegram)); ?></span>
                                </a>
                            <?php } ?>
                        <?php } ?>
                        </div>

                </div>
                <?php endif; ?>
                
                <!-- Recent Voters -->
                <div class="recent-voters-card">
                    <h4><i class="bi bi-people"></i> Ultimi Voti (<?php echo count($voters); ?>)</h4>
                    
                    <div class="voters-grid <?php echo count($voters) > 40 ? 'scrollable' : ''; ?>">
                        <?php 
                        // Mostra TUTTI i voti giornalieri
                        foreach ($voters as $voter): 
                            // Converte il timestamp del DB (UTC) in fuso orario locale ISO 8601
                            $vote_dt = new DateTime($voter['data_voto'], new DateTimeZone('UTC'));
                            $vote_dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
                            $vote_iso_local = $vote_dt->format('c'); // es. 2025-09-29T15:44:23+02:00
                        ?>
                            <div class="voter-avatar-modern" 
                                 title="<?php echo htmlspecialchars($voter['minecraft_nick']); ?>"
                                 data-nickname="<?php echo htmlspecialchars($voter['minecraft_nick']); ?>"
                                 data-vote-time="<?php echo $vote_iso_local; ?>">
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
    
    fetch('/vote.php', {
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

<!-- Grafici voti (Chart.js) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Dati dal server
    const last30LabelsRaw = <?php echo json_encode($last30Labels); ?>;
    const last30Data = <?php echo json_encode($last30Data); ?>;
    const last12LabelsRaw = <?php echo json_encode($last12Labels); ?>;
    const last12Data = <?php echo json_encode($last12Data); ?>;

    // Formattazioni
    function formatDayLabel(isoDate) {
        const d = new Date(isoDate + 'T00:00:00');
        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        return dd + '/' + mm;
    }
    function formatMonthLabel(ym) {
        const d = new Date(ym + '-01T00:00:00');
        return d.toLocaleString('it-IT', { month: 'long', year: 'numeric' });
    }

    // Colori
    const colorDaily = 'rgba(99, 102, 241, 0.7)'; // indigo
    const colorMonthly = 'rgba(34, 197, 94, 0.7)'; // green

    // Grafico: Ultimi 30 giorni
    const ctx30 = document.getElementById('votes30daysChart');
    if (ctx30) {
        const chart30 = new Chart(ctx30, {
            type: 'bar',
            data: {
                labels: last30LabelsRaw.map(formatDayLabel),
                datasets: [{
                    label: 'Voti',
                    data: last30Data,
                    backgroundColor: colorDaily,
                    borderRadius: 6,
                    maxBarThickness: 24,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: (items) => items[0].label,
                            label: (item) => 'Voti: ' + item.formattedValue
                        }
                    },
                    legend: { display: false }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { display: false, beginAtZero: true }
                }
            }
        });
    }

    // Grafico: Ultimi 12 mesi
    const ctx12 = document.getElementById('votes12monthsChart');
    if (ctx12) {
        const chart12 = new Chart(ctx12, {
            type: 'bar',
            data: {
                labels: last12LabelsRaw.map(formatMonthLabel),
                datasets: [{
                    label: 'Voti',
                    data: last12Data,
                    backgroundColor: colorMonthly,
                    borderRadius: 6,
                    maxBarThickness: 36,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: (items) => items[0].label,
                            label: (item) => 'Voti: ' + item.formattedValue
                        }
                    },
                    legend: { display: false }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { display: false, beginAtZero: true }
                }
            }
        });
    }
</script>

<style>
    .stats-section h3 { margin-bottom: 0.75rem; }
    .chart-container { position: relative; width: 100%; height: 220px; }
</style>



<?php include 'footer.php'; ?>