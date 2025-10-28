<?php
require_once __DIR__ . '/config.php';

// Public User Profile Page
// Shows verified Minecraft nickname, basic stats, recent threads/posts

$page_title = 'Profilo Utente';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(404);
    include __DIR__ . '/header.php';
    echo '<div class="container py-5"><div class="alert alert-error"><i class="bi bi-exclamation-triangle"></i> Utente non trovato.</div></div>';
    include __DIR__ . '/footer.php';
    exit();
}

$user = null;
$verified_nick = null;
$owned_servers = [];
$stats = [
    'total_votes' => 0,
    'threads_count' => 0,
    'posts_count' => 0,
];

try {
    $stmt = $pdo->prepare('SELECT id, minecraft_nick, is_admin, data_registrazione FROM sl_users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    $user = null;
}

if (!$user) {
    http_response_code(404);
    include __DIR__ . '/header.php';
    echo '<div class="container py-5"><div class="alert alert-error"><i class="bi bi-exclamation-triangle"></i> Utente non trovato.</div></div>';
    include __DIR__ . '/footer.php';
    exit();
}

try {
    $stmt = $pdo->prepare('SELECT minecraft_nick FROM sl_minecraft_links WHERE user_id = ? LIMIT 1');
    $stmt->execute([$user['id']]);
    $verified_nick = $stmt->fetchColumn() ?: null;
} catch (Exception $e) {}

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM sl_votes WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    $stats['total_votes'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM sl_forum_threads WHERE author_id = ?');
    $stmt->execute([$user['id']]);
    $stats['threads_count'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM sl_forum_posts WHERE author_id = ?');
    $stmt->execute([$user['id']]);
    $stats['posts_count'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

try {
    $stmt = $pdo->prepare('SELECT s.id, s.nome FROM sl_servers s WHERE s.owner_id = ? ORDER BY s.nome ASC');
    $stmt->execute([$user['id']]);
    $owned_servers = $stmt->fetchAll();
} catch (Exception $e) {}

// Recent threads
$recent_threads = [];
try {
    $stmt = $pdo->prepare('SELECT t.id, t.title, t.slug, t.created_at, c.name AS category_name FROM sl_forum_threads t JOIN sl_forum_categories c ON t.category_id = c.id WHERE t.author_id = ? ORDER BY t.created_at DESC LIMIT 5');
    $stmt->execute([$user['id']]);
    $recent_threads = $stmt->fetchAll();
} catch (Exception $e) {}

// Recent posts
$recent_posts = [];
try {
    $stmt = $pdo->prepare('SELECT p.id, p.thread_id, p.created_at, SUBSTRING(p.body, 1, 160) AS excerpt, t.title AS thread_title, t.slug AS thread_slug FROM sl_forum_posts p JOIN sl_forum_threads t ON p.thread_id = t.id WHERE p.author_id = ? ORDER BY p.created_at DESC LIMIT 5');
    $stmt->execute([$user['id']]);
    $recent_posts = $stmt->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/header.php';
?>

<style>
/* Fix navbar to match index.php */
.navbar-toggler {
    color: white !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

.navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 1%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
}

.navbar-brand {
    padding: 0 !important;
}

.user-card { background: var(--primary-bg); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; }
.user-header { background: var(--gradient-primary); color: #fff; padding: 16px 20px; }
.user-body { padding: 20px; color: var(--text-secondary); }
.user-avatar { width: 80px; height: 80px; border-radius: 50%; }
.stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 16px; }
.stat-card { border: 1px solid var(--border-color); border-radius: 12px; padding: 12px; background: var(--primary-bg); }
.stat-label { font-size: 12px; color: var(--text-secondary); }
.stat-number { font-weight: 700; font-size: 18px; color: var(--text-primary); }
.section-title { font-weight: 700; font-size: 18px; color: var(--text-primary); }
.item-list { list-style: none; padding-left: 0; margin: 0; }
.item-list li { padding: 8px 0; border-bottom: 1px solid var(--border-color); }
.thread-link { color: var(--text-primary); text-decoration: none; }
.thread-link:hover { color: var(--accent-purple); }
.badge-verified { display:inline-flex; align-items:center; gap:6px; font-size: 13px; color: var(--text-primary); }
.badge-role { display:inline-flex; align-items:center; gap:6px; font-size: 13px; }
.owner-list { font-size: 13px; color: var(--text-secondary); }
</style>

<div class="container py-4">
    <div class="user-card">
        <div class="user-header">
            <h2 class="m-0"><i class="bi bi-person-badge"></i> Profilo pubblico</h2>
        </div>
        <div class="user-body">
            <div class="d-flex align-items-center" style="gap: 16px;">
                <img class="user-avatar" src="<?= !empty($verified_nick) ? htmlspecialchars(getMinecraftAvatar($verified_nick)) : '/logo.png' ?>" alt="Avatar">
                <div>
                    <h3 class="m-0" style="font-weight:700; color: var(--text-primary);"><?= htmlspecialchars($user['minecraft_nick']) ?></h3>
                    <div class="mt-1">
                        <span class="meta-item"><i class="bi bi-person-badge"></i> Username: <strong><?= htmlspecialchars($user['minecraft_nick']) ?></strong></span>
                        <span class="meta-item" style="margin-left:0.8rem;"><i class="bi bi-box-seam"></i> Minecraft: 
                            <?php if (!empty($verified_nick)): ?>
                                <a href="https://namemc.com/profile/<?= urlencode($verified_nick) ?>" target="_blank" rel="noopener">
                                    <strong><?= htmlspecialchars($verified_nick) ?></strong>
                                </a>
                                <span class="badge-verified" style="margin-left:0.5rem;"><i class="bi bi-check2-circle" style="color: var(--accent-blue);"></i> Verificato</span>
                            <?php else: ?>
                                <span class="text-secondary">Nessun account Minecraft collegato</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="mt-1">
                        <?php if (!empty($verified_nick)): ?>
                            <span class="badge-verified"><i class="bi bi-check2-circle" style="color: var(--accent-blue);"></i> Verificato come <strong><?= htmlspecialchars($verified_nick) ?></strong></span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-1">
                        <?php if (!empty($user['is_admin'])): ?>
                            <span class="badge-role"><i class="bi bi-shield-check"></i> Amministratore</span>
                        <?php elseif (!empty($owned_servers)): ?>
                            <span class="badge-role"><i class="bi bi-server"></i> Owner di 
                                <span class="owner-list">
                                    <?php foreach ($owned_servers as $i => $srv): ?>
                                        <?= ($i ? ' / ' : '') . htmlspecialchars($srv['nome']) ?>
                                    <?php endforeach; ?>
                                </span>
                            </span>
                        <?php else: ?>
                            <span class="badge-role"><i class="bi bi-person"></i> Utente</span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-1" style="font-size:13px; color: var(--text-secondary);">
                        <i class="bi bi-calendar"></i> Membro dal <?= htmlspecialchars(date('d/m/Y', strtotime($user['data_registrazione'] ?? date('Y-m-d H:i:s')))) ?>
                    </div>
                </div>
            </div>

            <div class="stat-grid">
                <div class="stat-card"><div class="stat-number"><?= number_format($stats['total_votes']) ?></div><div class="stat-label">Voti Totali</div></div>
                <div class="stat-card"><div class="stat-number"><?= number_format($stats['threads_count']) ?></div><div class="stat-label">Thread creati</div></div>
                <div class="stat-card"><div class="stat-number"><?= number_format($stats['posts_count']) ?></div><div class="stat-label">Post scritti</div></div>
            </div>

            <div class="mt-4">
                <div class="section-title"><i class="bi bi-chat-dots"></i> Ultimi thread</div>
                <ul class="item-list mt-2">
                    <?php if (!empty($recent_threads)): foreach ($recent_threads as $t): ?>
                        <li>
                            <a href="/forum/<?= (int)$t['id'] ?>-<?= urlencode(preg_replace('/[^a-z0-9]+/i','-', strtolower($t['slug'] ?: $t['title']))) ?>" class="thread-link"><?= htmlspecialchars($t['title']) ?></a>
                            <div style="font-size:12px; color: var(--text-secondary);">categoria: <?= htmlspecialchars($t['category_name']) ?> • <?= htmlspecialchars(date('d/m/Y H:i', strtotime($t['created_at']))) ?></div>
                        </li>
                    <?php endforeach; else: ?>
                        <li>Nessun thread recente.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="mt-4">
                <div class="section-title"><i class="bi bi-reply"></i> Ultimi post</div>
                <ul class="item-list mt-2">
                    <?php if (!empty($recent_posts)): foreach ($recent_posts as $p): ?>
                        <li>
                            <a href="/forum/<?= (int)$p['thread_id'] ?>-<?= urlencode(preg_replace('/[^a-z0-9]+/i','-', strtolower($p['thread_slug'] ?: $p['thread_title']))) ?>" class="thread-link"><?= htmlspecialchars($p['thread_title']) ?></a>
                            <div style="font-size:12px; color: var(--text-secondary);"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($p['created_at']))) ?></div>
                            <div style="font-size:13px; color: var(--text-secondary);"><?= htmlspecialchars($p['excerpt']) ?>...</div>
                        </li>
                    <?php endforeach; else: ?>
                        <li>Nessun post recente.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>