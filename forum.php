<?php
/**
 * Pagina Forum
 * Forum base: categorie, thread, risposte
 */

require_once 'config.php';

// Crea tabelle forum se non esistono
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS sl_forum_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        slug VARCHAR(160) NOT NULL UNIQUE,
        sort_order INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sl_forum_threads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        author_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        body TEXT NOT NULL,
        slug VARCHAR(220) NULL,
        replies_count INT DEFAULT 0,
        views INT DEFAULT 0,
        is_locked TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        INDEX(category_id),
        INDEX(author_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sl_forum_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        thread_id INT NOT NULL,
        author_id INT NOT NULL,
        body TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        INDEX(thread_id),
        INDEX(author_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Sottoscrizioni thread
    $pdo->exec("CREATE TABLE IF NOT EXISTS sl_forum_subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        thread_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_thread_user (thread_id, user_id),
        INDEX(thread_id), INDEX(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // Non bloccare la pagina
}

// Prova ad aggiungere colonna slug se tabella già esistente
try { $pdo->exec("ALTER TABLE sl_forum_threads ADD COLUMN slug VARCHAR(220) NULL"); } catch (Exception $e) {}
// Prova ad aggiungere colonna permesso in categorie
try { $pdo->exec("ALTER TABLE sl_forum_categories ADD COLUMN allow_user_threads TINYINT(1) NOT NULL DEFAULT 1"); } catch (Exception $e) {}

// Seed categorie di default
try {
    $cat_count = (int)$pdo->query("SELECT COUNT(*) FROM sl_forum_categories")->fetchColumn();
    if ($cat_count === 0) {
        $stmt = $pdo->prepare("INSERT INTO sl_forum_categories (name, slug, sort_order) VALUES (?, ?, ?), (?, ?, ?) ");
        $stmt->execute(['Generale', 'generale', 1, 'Supporto', 'supporto', 2]);
    }
} catch (Exception $e) {}

$page_title = "Forum";
$page_description = "Forum della community: discussioni generali e supporto";

$view = $_GET['view'] ?? '';
$category_id = isset($_GET['category']) ? max(0, (int)$_GET['category']) : 0;
$thread_id = isset($_GET['thread']) ? max(0, (int)$_GET['thread']) : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$error = '';
$message = '';

// Gestione POST: nuovo thread e risposte
// Helper per slug
function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return substr($text, 0, 200);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'new_thread') {
        if (!isLoggedIn()) {
            $error = 'Devi essere loggato per creare un thread.';
        } elseif (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'Sessione scaduta o token CSRF non valido.';
        } else {
            $cat_id = (int)($_POST['category_id'] ?? 0);
            $title = sanitize($_POST['title'] ?? '');
            $body = trim($_POST['body'] ?? '');
            if ($cat_id <= 0 || $title === '' || $body === '') {
                $error = 'Compila tutti i campi.';
            } else {
                try {
                    // Verifica categoria e permessi
                    $stmt = $pdo->prepare("SELECT id, allow_user_threads FROM sl_forum_categories WHERE id = ?");
                    $stmt->execute([$cat_id]);
                    $catRow = $stmt->fetch();
                    if (!$catRow) {
                        $error = 'Categoria non valida.';
                    } elseif (!isAdmin() && isset($catRow['allow_user_threads']) && (int)$catRow['allow_user_threads'] === 0) {
                        $error = 'Solo gli amministratori possono creare thread in questa categoria.';
                    } else {
                        $slug = slugify($title);
                        $stmt = $pdo->prepare("INSERT INTO sl_forum_threads (category_id, author_id, title, body, slug) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$cat_id, (int)$_SESSION['user_id'], $title, $body, $slug]);
                        $new_id = (int)$pdo->lastInsertId();
                        // Auto-subscribe autore
                        try {
                            $pdo->prepare("INSERT INTO sl_forum_subscriptions (thread_id, user_id) VALUES (?, ?)")->execute([$new_id, (int)$_SESSION['user_id']]);
                        } catch (Exception $e) {}
                        redirect('/forum?view=thread&thread=' . $new_id);
                    }
                } catch (Exception $e) {
                    $error = 'Errore creazione thread.';
                }
            }
        }
    } elseif ($action === 'reply') {
        if (!isLoggedIn()) {
            $error = 'Devi essere loggato per rispondere.';
        } elseif (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'Sessione scaduta o token CSRF non valido.';
        } else {
            $tid = (int)($_POST['thread_id'] ?? 0);
            $body = trim($_POST['body'] ?? '');
            if ($tid <= 0 || $body === '') {
                $error = 'Messaggio vuoto o thread non valido.';
            } else {
                try {
                    // Blocca risposte se thread chiuso
                    $stmt = $pdo->prepare("SELECT is_locked FROM sl_forum_threads WHERE id = ?");
                    $stmt->execute([$tid]);
                    $locked = (int)$stmt->fetchColumn();
                    if ($locked === 1) {
                        $error = 'Thread bloccato, non è possibile rispondere.';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO sl_forum_posts (thread_id, author_id, body) VALUES (?, ?, ?)");
                        $stmt->execute([$tid, (int)$_SESSION['user_id'], $body]);
                        // Aggiorna conteggio risposte
                        $pdo->prepare("UPDATE sl_forum_threads SET replies_count = replies_count + 1, updated_at = NOW() WHERE id = ?")->execute([$tid]);
                        redirect('/forum?view=thread&thread=' . $tid);
                    }
                } catch (Exception $e) {
                    $error = 'Errore nell\'invio della risposta.';
                }
            }
        }
    } elseif ($action === 'lock_thread') {
        // Moderazione: lock/unlock
        if (!isAdmin()) {
            $error = 'Permesso negato.';
        } elseif (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'Sessione scaduta o token CSRF non valido.';
        } else {
            $tid = (int)($_POST['thread_id'] ?? 0);
            $lock = (int)($_POST['lock'] ?? 1);
            try {
                $pdo->prepare("UPDATE sl_forum_threads SET is_locked = ?, updated_at = NOW() WHERE id = ?")->execute([$lock ? 1 : 0, $tid]);
                redirect('/forum?view=thread&thread=' . $tid);
            } catch (Exception $e) { $error = 'Errore cambio stato thread.'; }
        }
    } elseif ($action === 'delete_post') {
        // Moderazione: elimina post (autore o admin)
        if (!isLoggedIn()) {
            $error = 'Devi essere loggato.';
        } elseif (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'Sessione scaduta o token CSRF non valido.';
        } else {
            $pid = (int)($_POST['post_id'] ?? 0);
            try {
                $stmt = $pdo->prepare("SELECT p.thread_id, p.author_id FROM sl_forum_posts p WHERE p.id = ?");
                $stmt->execute([$pid]);
                $row = $stmt->fetch();
                if (!$row) {
                    $error = 'Post non trovato.';
                } else {
                    if ((int)$row['author_id'] !== (int)$_SESSION['user_id'] && !isAdmin()) {
                        $error = 'Permesso negato.';
                    } else {
                        $pdo->prepare("DELETE FROM sl_forum_posts WHERE id = ?")->execute([$pid]);
                        $pdo->prepare("UPDATE sl_forum_threads SET replies_count = GREATEST(replies_count - 1, 0), updated_at = NOW() WHERE id = ?")->execute([(int)$row['thread_id']]);
                        redirect('/forum?view=thread&thread=' . (int)$row['thread_id']);
                    }
                }
            } catch (Exception $e) { $error = 'Errore eliminazione post.'; }
        }
    }
}

// Helper semplice per rendering contenuto
function renderText($text) {
    return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
}
include 'header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <div class="row">
        <div class="col-12">
            <div class="forum-hero d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h1 class="hero-title">Forum</h1>
                    <p class="hero-subtitle">Discorsi della community e supporto.</p>
                </div>
                <?php if (isLoggedIn()): ?>
                <?php if ($view === 'category'): ?>
                <button class="btn btn-hero" type="button" data-bs-toggle="collapse" data-bs-target="#newThreadForm"><i class="bi bi-plus-circle"></i> Nuovo Thread</button>
                <?php elseif ($view === ''): ?>
                <button class="btn btn-hero" type="button" data-bs-toggle="collapse" data-bs-target="#newThreadGlobal"><i class="bi bi-plus-circle"></i> Nuovo Thread</button>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($view === 'thread' && $thread_id > 0): ?>
                <?php
                // Vista thread
                $thread = null;
                try {
                    $stmt = $pdo->prepare("SELECT t.*, \n                            u.minecraft_nick, u.is_admin,\n                            ml.minecraft_nick AS verified_nick,\n                            (SELECT GROUP_CONCAT(nome SEPARATOR ' / ') FROM sl_servers WHERE owner_id = u.id) AS owned_servers,\n                            c.name AS category_name, c.slug AS category_slug \n                        FROM sl_forum_threads t \n                        JOIN sl_users u ON u.id = t.author_id \n                        LEFT JOIN sl_minecraft_links ml ON ml.user_id = u.id\n                        JOIN sl_forum_categories c ON c.id = t.category_id \n                        WHERE t.id = ?");
                    $stmt->execute([$thread_id]);
                    $thread = $stmt->fetch();
                    if ($thread) {
                        $pdo->prepare("UPDATE sl_forum_threads SET views = views + 1 WHERE id = ?")->execute([$thread_id]);
                    }
                } catch (Exception $e) {}
                if (!$thread): ?>
                    <div class="text-center" style="padding:2rem; background: var(--card-bg); border:1px solid var(--border-color); border-radius:12px;">Thread non trovato.</div>
                <?php else: ?>
                    <article class="thread-card">
                        <header class="thread-header d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center author" style="gap: 0.6rem;">
                                <img src="<?= !empty($thread['verified_nick']) ? htmlspecialchars(getMinecraftAvatar($thread['verified_nick'], 32)) : '/logo.png' ?>" alt="Avatar" width="32" height="32" class="rounded-circle">
                                <div>
                                    <strong class="thread-title"><?= htmlspecialchars($thread['title']) ?></strong>
                                    <div class="thread-meta">
                                        <span class="meta-item"><i class="bi bi-folder2"></i> <?= htmlspecialchars($thread['category_name']) ?></span>
                                        <a class="meta-item" href="/utente/<?= (int)$thread['author_id'] ?>"><i class="bi bi-person"></i> <?= htmlspecialchars($thread['minecraft_nick']) ?></a>
                                        <?php if (!empty($thread['verified_nick'])): ?>
                                            <a class="meta-item" href="/utente/<?= (int)$thread['author_id'] ?>"><i class="bi bi-check2-circle"></i> <?= htmlspecialchars($thread['verified_nick']) ?></a>
                                        <?php endif; ?>
                                        <?php 
                                        if ((int)($thread['is_admin'] ?? 0) === 1): ?>
                                            <span class="admin-badge admin-role"><i class="bi bi-shield-check"></i> Amministratore</span>
                                        <?php elseif (!empty($thread['owned_servers'])): ?>
                                            <span class="admin-badge owner-role"><i class="bi bi-server"></i> Owner di <?= htmlspecialchars($thread['owned_servers']) ?></span>
                                        <?php else: ?>
                                            <span class="admin-badge user-role"><i class="bi bi-person"></i> Utente</span>
                                        <?php endif; ?>
                                        <span class="meta-item"><i class="bi bi-clock"></i>
                                        <?php 
                                            $dt = new DateTime($thread['created_at'], new DateTimeZone('UTC'));
                                            $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
                                            echo $dt->format('d/m/Y H:i');
                                        ?></span>
                                        <span class="meta-item"><i class="bi bi-eye"></i> <?= (int)$thread['views'] ?> visualizzazioni</span>
                                        <span class="meta-item"><i class="bi bi-chat"></i> <?= (int)$thread['replies_count'] ?> risposte</span>
                                    </div>
                                </div>
                            </div>
                            <a href="/forum/category/<?= (int)$thread['category_id'] ?>-<?= urlencode($thread['category_slug'] ?? 'categoria') ?>" class="btn btn-hero"><i class="bi bi-folder2-open"></i> Categoria</a>
                        </header>
                        <div class="thread-body mt-2">
                            <?= renderText($thread['body']) ?>
                        </div>
                        <div class="d-flex align-items-center" style="gap:0.5rem; margin-top:0.5rem;">
                            <a href="/forum?view=category&category=<?= (int)$thread['category_id'] ?>" class="btn btn-hero"><i class="bi bi-folder2-open"></i> Categoria</a>
                            <?php if ((int)$thread['is_locked'] === 1): ?>
                                <span class="badge bg-warning text-dark">Bloccato</span>
                            <?php endif; ?>
                            <?php if (isAdmin()): ?>
                                <form method="POST" action="/forum?view=thread&thread=<?= (int)$thread_id ?>" class="d-inline">
                                    <?= csrfInput(); ?>
                                    <input type="hidden" name="action" value="lock_thread">
                                    <input type="hidden" name="thread_id" value="<?= (int)$thread_id ?>">
                                    <input type="hidden" name="lock" value="<?= ((int)$thread['is_locked'] === 1) ? 0 : 1 ?>">
                                    <button class="btn btn-hero" type="submit">
                                        <?php if ((int)$thread['is_locked'] === 1): ?>
                                            <i class="bi bi-unlock"></i> Sblocca
                                        <?php else: ?>
                                            <i class="bi bi-lock"></i> Blocca
                                        <?php endif; ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>

                    <?php
                    // Risposte con paginazione
                    $posts = [];
                    $posts_limit = 20;
                    $posts_offset = ($page - 1) * $posts_limit;
                    $total_posts = 0;
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sl_forum_posts WHERE thread_id = ?");
                        $stmt->execute([$thread_id]);
                        $total_posts = (int)$stmt->fetchColumn();
                        $stmt = $pdo->prepare("SELECT p.*, u.minecraft_nick, u.is_admin, ml.minecraft_nick AS verified_nick,\n                                (SELECT GROUP_CONCAT(nome SEPARATOR ' / ') FROM sl_servers WHERE owner_id = u.id) AS owned_servers\n                            FROM sl_forum_posts p \n                            JOIN sl_users u ON u.id = p.author_id \n                            LEFT JOIN sl_minecraft_links ml ON ml.user_id = u.id\n                            WHERE p.thread_id = ? \n                            ORDER BY p.created_at ASC \n                            LIMIT $posts_limit OFFSET $posts_offset");
                        $stmt->execute([$thread_id]);
                        $posts = $stmt->fetchAll();
                    } catch (Exception $e) {}
                    ?>

                    <div class="posts-list mt-3">
                        <?php foreach ($posts as $p): ?>
                            <div class="post-card">
                                <div class="post-header">
                                    <div class="post-author d-flex align-items-center" style="gap:0.6rem;">
                                        <img src="<?= !empty($p['verified_nick']) ? htmlspecialchars(getMinecraftAvatar($p['verified_nick'], 28)) : '/logo.png' ?>" alt="Avatar" width="28" height="28" class="rounded-circle post-avatar">
                                        <a class="post-nick" href="/utente/<?= (int)$p['author_id'] ?>"><?= htmlspecialchars($p['minecraft_nick']) ?></a>
                                        <?php if (!empty($p['verified_nick'])): ?>
                                            <a class="admin-badge user-role" href="/utente/<?= (int)$p['author_id'] ?>"><i class="bi bi-check2-circle"></i> <?= htmlspecialchars($p['verified_nick']) ?></a>
                                        <?php endif; ?>
                                    </div>
                                    <span class="post-date">
                                        <?php 
                                        $dtp = new DateTime($p['created_at'], new DateTimeZone('UTC'));
                                        $dtp->setTimezone(new DateTimeZone(date_default_timezone_get()));
                                        echo $dtp->format('d/m/Y H:i');
                                        ?>
                                    </span>
                                    <?php if (isAdmin() || (isLoggedIn() && (int)$_SESSION['user_id'] === (int)$p['author_id'])): ?>
                                        <form method="POST" action="/forum?view=thread&thread=<?= (int)$thread_id ?>" class="d-inline">
                                            <?= csrfInput(); ?>
                                            <input type="hidden" name="action" value="delete_post">
                                            <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
                                            <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Eliminare questo post?');">
                                                <i class="bi bi-trash"></i> Elimina
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <div class="post-body mt-1"><?= renderText($p['body']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php $tp = max(1, (int)ceil($total_posts / $posts_limit)); if ($tp > 1): ?>
                        <nav class="mt-3">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $tp; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="/forum/<?= (int)$thread_id ?>-<?= urlencode($thread['slug'] ?? slugify($thread['title'])) ?>?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>

                    <?php if (isLoggedIn()): ?>
                        <div class="reply-form mt-4">
                            <form method="POST" action="/forum?view=thread&thread=<?= (int)$thread_id ?>">
                                <?= csrfInput(); ?>
                                <input type="hidden" name="action" value="reply">
                                <input type="hidden" name="thread_id" value="<?= (int)$thread_id ?>">
                                <div class="mb-2"><textarea name="body" class="form-control" rows="4" placeholder="Scrivi una risposta..."></textarea></div>
                                <button class="btn btn-hero" type="submit"><i class="bi bi-reply"></i> Rispondi</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

            <?php elseif ($view === 'category' && $category_id > 0): ?>
                <?php
                // Vista categoria
                $category = null;
                try {
                    $stmt = $pdo->prepare("SELECT * FROM sl_forum_categories WHERE id = ?");
                    $stmt->execute([$category_id]);
                    $category = $stmt->fetch();
                } catch (Exception $e) {}
                if (!$category): ?>
                    <div class="text-center" style="padding:2rem; background: var(--card-bg); border:1px solid var(--border-color); border-radius:12px;">Categoria non trovata.</div>
                <?php else: ?>
                    <div class="category-header d-flex align-items-center justify-content-between">
                        <h2 class="hero-title"><?= htmlspecialchars($category['name']) ?></h2>
                        <?php if (isLoggedIn() && (isAdmin() || (int)($category['allow_user_threads'] ?? 1) === 1)): ?>
                            <button class="btn btn-hero" data-bs-toggle="collapse" data-bs-target="#newThreadForm"><i class="bi bi-plus-circle"></i> Nuovo Thread</button>
                        <?php endif; ?>
                    </div>

                    <?php if (isLoggedIn() && (isAdmin() || (int)($category['allow_user_threads'] ?? 1) === 1)): ?>
                    <div class="collapse mt-3" id="newThreadForm">
                        <div class="card card-body" style="background: var(--card-bg); border:1px solid var(--border-color);">
                            <form method="POST" action="/forum?view=category&category=<?= (int)$category_id ?>">
                                <?= csrfInput(); ?>
                                <input type="hidden" name="action" value="new_thread">
                                <input type="hidden" name="category_id" value="<?= (int)$category_id ?>">
                                <div class="mb-2"><input type="text" name="title" class="form-control" placeholder="Titolo" required></div>
                                <div class="mb-2"><textarea name="body" class="form-control" rows="5" placeholder="Contenuto" required></textarea></div>
                                <button class="btn btn-hero" type="submit"><i class="bi bi-send"></i> Pubblica</button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php
                    // Threads della categoria con paginazione
                    $threads = [];
                    $total_threads = 0;
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sl_forum_threads WHERE category_id = ?");
                        $stmt->execute([$category_id]);
                        $total_threads = (int)$stmt->fetchColumn();
                        $stmt = $pdo->prepare("SELECT t.id, t.author_id, t.title, t.slug, t.created_at, t.updated_at, t.replies_count, t.views, u.minecraft_nick, ml.minecraft_nick AS verified_nick FROM sl_forum_threads t JOIN sl_users u ON u.id = t.author_id LEFT JOIN sl_minecraft_links ml ON ml.user_id = u.id WHERE t.category_id = ? ORDER BY t.updated_at DESC, t.created_at DESC LIMIT $limit OFFSET $offset");
                        $stmt->execute([$category_id]);
                        $threads = $stmt->fetchAll();
                    } catch (Exception $e) {}
                    ?>

                    <div class="threads-list mt-3">
                        <?php foreach ($threads as $t): ?>
                            <div class="thread-row d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center" style="gap:0.5rem;">
                                    <img src="<?= !empty($t['verified_nick']) ? htmlspecialchars(getMinecraftAvatar($t['verified_nick'], 24)) : '/logo.png' ?>" alt="Avatar" width="24" height="24" class="rounded-circle">
                                    <div>
                                        <a class="thread-link" href="/forum/<?= (int)$t['id'] ?>-<?= urlencode($t['slug'] ?? slugify($t['title'])) ?>"><?= htmlspecialchars($t['title']) ?></a>
                                        <div class="thread-small thread-meta">
                                            <a class="meta-item" href="/utente/<?= (int)$t['author_id'] ?>"><i class="bi bi-person"></i> <?= htmlspecialchars($t['minecraft_nick']) ?></a>
                                            <?php if (!empty($t['verified_nick'])): ?>
                                                <a class="meta-item" href="/utente/<?= (int)$t['author_id'] ?>"><i class="bi bi-check2-circle"></i> <?= htmlspecialchars($t['verified_nick']) ?></a>
                                            <?php endif; ?>
                                            <span class="meta-item"><i class="bi bi-clock"></i> <?php 
                                                $dtl = new DateTime($t['created_at'], new DateTimeZone('UTC'));
                                                $dtl->setTimezone(new DateTimeZone(date_default_timezone_get()));
                                                echo $dtl->format('d/m/Y H:i');
                                            ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center thread-stats" style="gap:0.6rem;">
                                    <span class="stat-chip"><i class="bi bi-eye"></i> <?= (int)$t['views'] ?></span>
                                    <span class="stat-chip"><i class="bi bi-chat"></i> <?= (int)$t['replies_count'] ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($threads)): ?>
                            <div class="text-center" style="padding:1rem; color: var(--text-secondary);">Nessun thread nella categoria.</div>
                        <?php endif; ?>
                    </div>

                    <?php $tp = max(1, (int)ceil($total_threads / $limit)); if ($tp > 1): ?>
                        <nav class="mt-3">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $tp; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="/forum/category/<?= (int)$category_id ?>-<?= urlencode($category['slug'] ?? 'categoria') ?>?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>

            <?php else: ?>
                <?php
                // Home forum: elenco categorie e ultimi thread
                $sections = [];
                $categories = [];
                $latest_by_cat = [];
                try { $sections = $pdo->query("SELECT * FROM sl_forum_sections ORDER BY sort_order ASC, name ASC")->fetchAll(); } catch (Exception $e) {}
                try {
                    $categories = $pdo->query("SELECT c.*, COALESCE(t.cnt,0) AS threads_count, COALESCE(m.posts_count,0) AS posts_count FROM sl_forum_categories c LEFT JOIN (SELECT category_id, COUNT(*) AS cnt FROM sl_forum_threads GROUP BY category_id) t ON t.category_id = c.id LEFT JOIN (SELECT category_id, SUM(replies_count) + COUNT(*) AS posts_count FROM sl_forum_threads GROUP BY category_id) m ON m.category_id = c.id ORDER BY c.sort_order ASC, c.name ASC")->fetchAll();
                } catch (Exception $e) {}
                try {
                    $rows = $pdo->query("SELECT tt.category_id, tt.id, tt.title, tt.slug, COALESCE(tt.updated_at, tt.created_at) AS latest_date, u.minecraft_nick, ml.minecraft_nick AS verified_nick FROM sl_forum_threads tt JOIN sl_users u ON u.id = tt.author_id LEFT JOIN sl_minecraft_links ml ON ml.user_id = u.id JOIN (SELECT category_id, MAX(COALESCE(updated_at, created_at)) AS mx FROM sl_forum_threads GROUP BY category_id) x ON x.category_id = tt.category_id AND COALESCE(tt.updated_at, tt.created_at) = x.mx")->fetchAll();
                    foreach ($rows as $r) { $latest_by_cat[(int)$r['category_id']] = $r; }
                } catch (Exception $e) { $latest_by_cat = []; }
                $latest = [];
                try {
                    $latest = $pdo->query("SELECT t.id, t.title, t.slug, t.created_at, t.replies_count, t.views, u.minecraft_nick, ml.minecraft_nick AS verified_nick, c.name AS category_name, c.slug AS category_slug, c.id AS category_id FROM sl_forum_threads t JOIN sl_users u ON u.id = t.author_id LEFT JOIN sl_minecraft_links ml ON ml.user_id = u.id LEFT JOIN sl_forum_categories c ON c.id = t.category_id ORDER BY COALESCE(t.updated_at, t.created_at) DESC LIMIT 12")->fetchAll();
                } catch (Exception $e) {}
                ?>

                <?php if (isLoggedIn()): ?>
                <div class="collapse" id="newThreadGlobal">
                    <div class="card card-body" style="background: var(--card-bg); border:1px solid var(--border-color);">
                        <form method="POST" action="/forum">
                            <?= csrfInput(); ?>
                            <input type="hidden" name="action" value="new_thread">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Seleziona categoria</option>
                                        <?php foreach ($categories as $c): ?>
                                            <?php if (!isAdmin() && isset($c['allow_user_threads']) && (int)$c['allow_user_threads'] === 0) continue; ?>
                                            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" name="title" class="form-control" placeholder="Titolo" required>
                                </div>
                                <div class="col-12">
                                    <textarea name="body" class="form-control" rows="5" placeholder="Contenuto" required></textarea>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero" type="submit"><i class="bi bi-send"></i> Pubblica</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="forum-card forum-card--categories">
                            <div class="card-header bg-transparent border-bottom"><h6 class="mb-0"><i class="bi bi-collection"></i> Sezioni</h6></div>
                            <div class="sections-wrap">
                                <?php foreach ($sections as $s): ?>
                                    <div class="section-block mb-3">
                                        <div class="section-header">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h6 class="mb-1" style="font-weight:700;">
                                                    <i class="bi bi-folder2"></i> <?= htmlspecialchars($s['name']) ?>
                                                </h6>
                                            </div>
                                            <?php if (!empty($s['description'])): ?>
                                                <div class="text-secondary" style="font-size:0.9rem;">
                                                    <?= htmlspecialchars($s['description']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($categories as $c): if ((int)($c['section_id'] ?? 0) !== (int)$s['id']) continue; ?>
                                                <li class="list-group-item bg-transparent text-white">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <a href="/forum/category/<?= (int)$c['id'] ?>-<?= urlencode($c['slug'] ?? 'categoria') ?>" class="forum-link" style="font-weight:600;">
                                                                <?= htmlspecialchars($c['name']) ?>
                                                            </a>
                                                            <div class="text-secondary" style="font-size:0.85rem;">
                                                                Discussioni <?= (int)$c['threads_count'] ?> • Messaggi <?= (int)$c['posts_count'] ?>
                                                            </div>
                                                            <?php if (isset($latest_by_cat[(int)$c['id']])): $lt = $latest_by_cat[(int)$c['id']]; ?>
                                                                <div class="text-secondary" style="font-size:0.85rem;">
                                                                    <span class="me-1"><?= htmlspecialchars($lt['minecraft_nick'] ?: ($lt['verified_nick'] ?? '')) ?></span>
                                                                    <a href="/forum/<?= (int)$lt['id'] ?>-<?= urlencode($lt['slug'] ?? 'thread') ?>" class="forum-link">
                                                                        <?= htmlspecialchars($lt['title']) ?>
                                                                    </a>
                                                                    <span>• <?= htmlspecialchars(date('d/m/Y', strtotime($lt['latest_date']))) ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <span class="badge bg-secondary rounded-pill"><?= (int)$c['threads_count'] ?></span>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                            <?php
                                            $hasCat = false; foreach ($categories as $c) { if ((int)($c['section_id'] ?? 0) === (int)$s['id']) { $hasCat = true; break; } }
                                            if (!$hasCat): ?>
                                                <li class="list-group-item bg-transparent text-secondary">Nessuna categoria in questa sezione.</li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                <?php endforeach; ?>
                                <?php
                                // Categorie senza sezione
                                $unassigned = array_filter($categories, function($c){ return empty($c['section_id']); });
                                if (!empty($unassigned)): ?>
                                    <div class="section-block mb-3">
                                        <div class="section-header">
                                            <h6 class="mb-1" style="font-weight:700;"><i class="bi bi-folder2"></i> Varie</h6>
                                            <div class="text-secondary" style="font-size:0.9rem;">Categorie non assegnate a nessuna sezione.</div>
                                        </div>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($unassigned as $c): ?>
                                                <li class="list-group-item bg-transparent text-white d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <a href="/forum/category/<?= (int)$c['id'] ?>-<?= urlencode($c['slug'] ?? 'categoria') ?>" class="forum-link" style="font-weight:600;">
                                                            <?= htmlspecialchars($c['name']) ?>
                                                        </a>
                                                        <div class="text-secondary" style="font-size:0.85rem;">
                                                            Discussioni <?= (int)$c['threads_count'] ?> • Messaggi <?= (int)$c['posts_count'] ?>
                                                        </div>
                                                        <?php if (isset($latest_by_cat[(int)$c['id']])): $lt = $latest_by_cat[(int)$c['id']]; ?>
                                                            <div class="text-secondary" style="font-size:0.85rem;">
                                                                <span class="me-1"><?= htmlspecialchars($lt['minecraft_nick'] ?: ($lt['verified_nick'] ?? '')) ?></span>
                                                                <a href="/forum/<?= (int)$lt['id'] ?>-<?= urlencode($lt['slug'] ?? 'thread') ?>" class="forum-link">
                                                                    <?= htmlspecialchars($lt['title']) ?>
                                                                </a>
                                                                <span>• <?= htmlspecialchars(date('d/m/Y', strtotime($lt['latest_date']))) ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="badge bg-secondary rounded-pill"><?= (int)$c['threads_count'] ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="forum-card forum-card--latest">
                            <div class="card-header bg-transparent border-bottom"><h6 class="mb-0"><i class="bi bi-chat-dots"></i> Ultimi Thread</h6></div>
                            <div class="list-group list-group-flush">
                                <?php foreach ($latest as $t): ?>
                                    <a class="list-group-item list-group-item-action bg-transparent text-white forum-latest-item" href="/forum/<?= (int)$t['id'] ?>-<?= urlencode($t['slug'] ?? slugify($t['title'])) ?>">
                                        <div class="d-flex w-100 align-items-center justify-content-between">
                                            <div class="d-flex align-items-center" style="gap:0.6rem;">
                                                <img src="<?= !empty($t['verified_nick']) ? htmlspecialchars(getMinecraftAvatar($t['verified_nick'], 24)) : '/logo.png' ?>" alt="Avatar" width="24" height="24" class="rounded-circle forum-avatar">
                                                <div>
                                                    <h6 class="mb-1 thread-link" style="margin:0;"><?= htmlspecialchars($t['title']) ?></h6>
                                                    <div class="thread-small thread-meta">
                                                        <span class="meta-item"><i class="bi bi-folder2"></i> <?= htmlspecialchars($t['category_name']) ?></span>
                                                        <span class="meta-item"><i class="bi bi-person"></i> <?= htmlspecialchars($t['minecraft_nick']) ?></span>
                                                        <?php if (!empty($t['verified_nick'])): ?>
                                                            <span class="meta-item"><i class="bi bi-check2-circle"></i> <?= htmlspecialchars($t['verified_nick']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center thread-stats" style="gap:0.6rem;">
                                                <span class="stat-chip"><i class="bi bi-eye"></i> <?= (int)$t['views'] ?></span>
                                                <span class="stat-chip"><i class="bi bi-chat"></i> <?= (int)$t['replies_count'] ?></span>
                                                <small class="text-secondary"><?php 
                                                    $dtlast = new DateTime($t['created_at'], new DateTimeZone('UTC'));
                                                    $dtlast->setTimezone(new DateTimeZone(date_default_timezone_get()));
                                                    echo $dtlast->format('d/m/Y H:i');
                                                ?></small>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                                <?php if (empty($latest)): ?>
                                    <div class="list-group-item bg-transparent text-white">Nessun thread recente.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.forum-hero { background: linear-gradient(135deg, rgba(102,126,234,0.18), rgba(118,75,162,0.18)), var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; padding: 1rem 1.25rem; }
.forum-hero .hero-title { margin:0; font-weight:800; font-size:1.5rem; background: var(--gradient-primary); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
.forum-hero .hero-subtitle { margin: 0.35rem 0 0 0; color: var(--text-secondary); }
.btn-hero { border-radius: 10px; border: 1px solid var(--border-color); background: var(--primary-bg); color: var(--text-secondary); }
.btn-hero:hover { background: var(--accent-purple); color:#fff; }

.forum-card { background: var(--card-bg); border:1px solid var(--border-color); border-radius:12px; box-shadow: 0 8px 22px rgba(0,0,0,0.25); }
.forum-link { color: var(--text-primary); text-decoration:none; }
.forum-link:hover { color: var(--accent-purple); }

.thread-card { background: var(--card-bg); border:1px solid var(--border-color); border-radius:12px; padding:1rem; box-shadow: 0 8px 22px rgba(0,0,0,0.25); }
.thread-header .thread-title { font-size:1.1rem; }
.thread-meta { display:flex; flex-wrap:wrap; align-items:center; gap: 0.25rem 0.6rem; color: var(--text-muted); font-size:0.9rem; }
.thread-meta .meta-item { display:inline-flex; align-items:center; gap:0.35rem; padding: 0.15rem 0.5rem; border-radius:999px; border:1px solid var(--border-color); background: rgba(255,255,255,0.04); }
.admin-badge { display:inline-flex; align-items:center; gap:0.35rem; padding: 0.15rem 0.5rem; border-radius:999px; border:1px solid var(--border-color); background: rgba(255,255,255,0.04); font-size:0.85rem; }

.post-card { background: var(--secondary-bg); border:1px solid var(--border-color); border-radius:10px; padding:0.75rem 1rem; margin-bottom:0.75rem; }
.post-header { display:flex; align-items:center; justify-content:space-between; gap:0.75rem; padding-bottom:0.35rem; border-bottom: 1px dashed rgba(255,255,255,0.08); }
.post-header .post-date { color: var(--text-muted); font-size:0.85rem; }
.post-avatar { box-shadow: 0 2px 8px rgba(0,0,0,0.35); border: 1px solid var(--border-color); }
 /* Forum sidebar and latest list cards */
 .forum-card { background: var(--card-bg); border:1px solid var(--border-color); border-radius:12px; overflow:hidden; }
 .forum-card .card-header { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color); }
 .forum-card .list-group-item { padding: 0.75rem 1rem; }
 .forum-link { color: var(--text-primary); text-decoration:none; }
 .forum-link:hover { color: var(--accent-purple); }

 /* Forum card variants */
 .forum-card--categories { background: linear-gradient(180deg, rgba(124,58,237,0.12), rgba(124,58,237,0.06)), var(--card-bg); border-color: rgba(124,58,237,0.35); }
 .forum-card--categories .card-header h6 { font-weight:700; background: var(--gradient-primary); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
 .forum-card--categories .list-group-item { transition: background 0.2s ease, transform 0.2s ease; }
 .forum-card--categories .list-group-item:hover { background: rgba(124,58,237,0.08); transform: translateX(2px); }
 .forum-card--categories .badge { background: rgba(124,58,237,0.2); border: 1px solid rgba(124,58,237,0.3); }
 .forum-card--categories .forum-link { font-weight:600; }
 .forum-card--categories .section-header { padding: 0.75rem 1rem; }
 .forum-card--categories .section-block { border-radius: 10px; }
 .forum-card--categories .section-block .list-group-item:hover { background: rgba(124,58,237,0.08); transform: translateX(2px); }

 .forum-card--latest { background: linear-gradient(180deg, rgba(56,189,248,0.12), rgba(56,189,248,0.06)), var(--card-bg); border-color: rgba(56,189,248,0.35); }
 .forum-card--latest .forum-latest-item { position: relative; padding-left: 1.1rem; }
 .forum-card--latest .forum-latest-item::before { content:''; position:absolute; left:0.5rem; top:0.6rem; bottom:0.6rem; width:3px; border-radius:2px; background: linear-gradient(180deg, rgba(56,189,248,0.7), rgba(124,58,237,0.7)); opacity:0.6; transition: opacity 0.2s ease, transform 0.2s ease; }
 .forum-card--latest .forum-latest-item:hover::before { opacity:1; transform: scaleX(1.05); }
 .forum-card--latest .forum-avatar { box-shadow: 0 0 0 2px rgba(56,189,248,0.4), 0 2px 8px rgba(0,0,0,0.25); }
 .forum-card--latest .thread-stats .stat-chip { background: transparent; border-color: rgba(56,189,248,0.3); }
 /* Global link normalization in forum contexts */
 .forum-card a, .thread-card a, .thread-row a, .post-card a, .thread-meta a, .post-header a {
   text-decoration: none;
   color: inherit;
 }
 .forum-card a:hover, .thread-card a:hover, .thread-row a:hover, .post-card a:hover, .thread-meta a:hover, .post-header a:hover {
   color: var(--accent-purple);
 }
 /* Thread list item styling */
 .thread-row { background: var(--card-bg); border:1px solid var(--border-color); border-radius:10px; padding:0.75rem 1rem; margin-bottom:0.6rem; }
 </style>

<?php include 'footer.php'; ?>