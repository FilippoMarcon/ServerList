<?php
/**
 * Pagina Annunci
 * Announcements Page con like e avatar autore
 */

require_once 'config.php';

// Crea tabelle annunci e likes se non esistono
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS sl_annunci (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        body TEXT NOT NULL,
        author_id INT NOT NULL,
        is_published TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        INDEX(author_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sl_annunci_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        annuncio_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_like (annuncio_id, user_id),
        INDEX(annuncio_id),
        INDEX(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // Non bloccare la pagina per errori di creazione tabella
}

// Gestione AJAX like toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['action'] === 'toggle_like') {
    header('Content-Type: application/json');
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Devi essere loggato per mettere like.']);
        exit;
    }
    $annuncio_id = (int)($_POST['annuncio_id'] ?? 0);
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    if ($annuncio_id <= 0 || $user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Parametri non validi.']);
        exit;
    }
    try {
        // Verifica stato like
        $stmt = $pdo->prepare("SELECT id FROM sl_annunci_likes WHERE annuncio_id = ? AND user_id = ?");
        $stmt->execute([$annuncio_id, $user_id]);
        $like = $stmt->fetch();
        if ($like) {
            // Rimuovi like
            $pdo->prepare("DELETE FROM sl_annunci_likes WHERE id = ?")->execute([$like['id']]);
            $liked = false;
        } else {
            // Aggiungi like
            $pdo->prepare("INSERT INTO sl_annunci_likes (annuncio_id, user_id) VALUES (?, ?)")->execute([$annuncio_id, $user_id]);
            $liked = true;
        }
        // Conteggio aggiornato
        $count = (int)$pdo->query("SELECT COUNT(*) FROM sl_annunci_likes WHERE annuncio_id = " . (int)$annuncio_id)->fetchColumn();
        echo json_encode(['success' => true, 'liked' => $liked, 'count' => $count]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Errore like: ' . $e->getMessage()]);
    }
    exit;
}

$page_title = "Annunci";
include 'header.php';

// Recupera annunci pubblicati con autore e conteggio like
$annunci = [];
try {
    $query = "
        SELECT a.id, a.title, a.body, a.author_id, a.created_at, a.updated_at, u.minecraft_nick,
               COALESCE(l.cnt, 0) AS likes
        FROM sl_annunci a
        JOIN sl_users u ON u.id = a.author_id
        LEFT JOIN (
            SELECT annuncio_id, COUNT(*) AS cnt
            FROM sl_annunci_likes
            GROUP BY annuncio_id
        ) l ON l.annuncio_id = a.id
        WHERE a.is_published = 1
        ORDER BY a.created_at DESC
        LIMIT 50
    ";
    $stmt = $pdo->query($query);
    $annunci = $stmt->fetchAll();
} catch (Exception $e) {
    $annunci = [];
}

// Utility: verifica se l'utente ha messo like
function userLiked($announcementId, $pdo) {
    if (!isLoggedIn()) return false;
    $stmt = $pdo->prepare("SELECT 1 FROM sl_annunci_likes WHERE annuncio_id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([(int)$announcementId, (int)$_SESSION['user_id']]);
    return (bool)$stmt->fetchColumn();
}
// Rendering stile Discord/Markdown per il corpo degli annunci
function renderDiscordMarkup($text) {
    $text = str_replace(["\r\n", "\r"], "\n", (string)$text);
    $lines = explode("\n", $text);
    $html = '';
    $inCode = false;
    $codeBuffer = '';

    foreach ($lines as $line) {
        $trim = ltrim($line);

        // Code fence ```
        if (preg_match('/^```/', $trim)) {
            if ($inCode) {
                $html .= '<pre class="discord-code"><code>' . htmlspecialchars($codeBuffer, ENT_QUOTES, 'UTF-8') . '</code></pre>';
                $inCode = false;
                $codeBuffer = '';
            } else {
                $inCode = true;
                $codeBuffer = '';
            }
            continue;
        }

        if ($inCode) {
            $codeBuffer .= $line . "\n";
            continue;
        }

        // Headings
        if (preg_match('/^###\s*(.+)$/', $trim, $m)) {
            $content = applyInlineDiscord(htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8'));
            $html .= '<div class="discord-h3"><strong>' . $content . '</strong></div>';
            continue;
        }
        if (preg_match('/^##\s*(.+)$/', $trim, $m)) {
            $content = applyInlineDiscord(htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8'));
            $html .= '<div class="discord-h2">' . $content . '</div>';
            continue;
        }
        if (preg_match('/^#\s*(.+)$/', $trim, $m)) {
            $content = applyInlineDiscord(htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8'));
            $html .= '<div class="discord-h1">' . $content . '</div>';
            continue;
        }

        // Blockquote
        if (preg_match('/^>\s?(.*)$/', $trim, $m)) {
            $content = applyInlineDiscord(htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8'));
            $html .= '<blockquote class="discord-quote"><p>' . $content . '</p></blockquote>';
            continue;
        }

        // Empty line -> spacer
        if (trim($line) === '') {
            $html .= '<div class="discord-spacer"></div>';
            continue;
        }

        // Normal paragraph
        $content = applyInlineDiscord(htmlspecialchars($line, ENT_QUOTES, 'UTF-8'));
        $html .= '<p class="discord-p">' . $content . '</p>';
    }

    if ($inCode) {
        $html .= '<pre class="discord-code"><code>' . htmlspecialchars($codeBuffer, ENT_QUOTES, 'UTF-8') . '</code></pre>';
    }

    return $html;
}

function applyInlineDiscord($text) {
    // Links [label](url) — solo http/https, apre in nuova scheda
    $text = preg_replace_callback('/\[(.+?)\]\((https?:\/\/[^\s)]+)\)/', function($m) {
        $label = $m[1]; // già escapato a monte
        $url = $m[2];
        if (!preg_match('/^https?:\/\//i', $url)) {
            return $m[0];
        }
        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        // Permetti formattazioni inline nel label
        $label = preg_replace('/`([^`]+)`/', '<code class="discord-inline-code">$1</code>', $label);
        $label = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $label);
        $label = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $label);
        return '<a href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer nofollow">' . $label . '</a>';
    }, $text);

    // Inline code
    $text = preg_replace('/`([^`]+)`/', '<code class="discord-inline-code">$1</code>', $text);
    // Bold **...**
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    // Italic *...*
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
    return $text;
}
?>

<div class="container" style="margin-top: 2rem;">
    <div class="row">
        <div class="col-12">
            <div class="annunci-hero d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h1 class="hero-title">Annunci</h1>
                    <p class="hero-subtitle">Ultimi aggiornamenti, comunicazioni e anteprime dal team.</p>
                </div>
                <?php if (isAdmin()): ?>
                    <a href="/admin?action=annunci" class="btn btn-hero">
                        <i class="bi bi-megaphone"></i> Gestisci Annunci
                    </a>
                <?php endif; ?>
            </div>
            <?php if (empty($annunci)): ?>
                <div class="text-center" style="padding: 3rem; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px;">
                    <i class="bi bi-megaphone" style="font-size: 3rem; color: var(--accent-orange);"></i>
                    <p class="mt-2" style="color: var(--text-secondary);">Nessun annuncio pubblicato al momento.</p>
                </div>
            <?php else: ?>
                <div class="annunci-list">
                    <?php foreach ($annunci as $ann): 
                        $liked = userLiked($ann['id'], $pdo);
                        $authorNick = $ann['minecraft_nick'];
                        $avatar = getMinecraftAvatar($authorNick, 32);
                    ?>
                        <article class="annuncio-card" data-id="<?= (int)$ann['id'] ?>">
                            <header class="annuncio-header d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center author" style="gap: 0.6rem;">
                                    <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" width="32" height="32" class="rounded-circle">
                                    <div>
                                        <strong class="annuncio-title"><?= htmlspecialchars($ann['title']) ?></strong>
                                        <div class="annuncio-meta">di <?= htmlspecialchars($authorNick) ?> • <?= date('d/m/Y H:i', strtotime($ann['created_at'])) ?></div>
                                    </div>
                                </div>
                                <button class="btn btn-like <?= $liked ? 'liked' : '' ?>" data-id="<?= (int)$ann['id'] ?>">
                                    <i class="bi <?= $liked ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                                    <span class="like-count"><?= (int)$ann['likes'] ?></span>
                                </button>
                            </header>
                            <div class="annuncio-body mt-2">
                                <?= renderDiscordMarkup($ann['body']) ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-like').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            fetch('annunci.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ ajax: 1, action: 'toggle_like', annuncio_id: id })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Errore');
                    return;
                }
                const icon = this.querySelector('i');
                const countEl = this.querySelector('.like-count');
                if (data.liked) {
                    this.classList.add('liked');
                    icon.className = 'bi bi-heart-fill';
                } else {
                    this.classList.remove('liked');
                    icon.className = 'bi bi-heart';
                }
                countEl.textContent = data.count;
            })
            .catch(() => alert('Errore rete'));
        });
    });
});
</script>

<style>
/* Layout annunci migliorato */
.annunci-hero {
    background: linear-gradient(135deg, rgba(102,126,234,0.18), rgba(118,75,162,0.18)), var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 1rem 1.25rem;
}
.annunci-hero .hero-title {
    margin: 0;
    font-weight: 800;
    font-size: 1.5rem;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.annunci-hero .hero-subtitle { margin: 0.35rem 0 0 0; color: var(--text-secondary); }
.btn-hero { border-radius: 10px; border: 1px solid var(--border-color); background: var(--primary-bg); color: var(--text-secondary); }
.btn-hero:hover { background: var(--accent-purple); color: #fff; }

.annunci-list { display: grid; grid-template-columns: 1fr; gap: 1rem; }
.annuncio-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem; box-shadow: 0 8px 22px rgba(0,0,0,0.25); }
.annuncio-header .annuncio-title { color: var(--text-primary); font-weight: 700; }
.annuncio-header .annuncio-meta { font-size: 0.85rem; color: var(--text-muted); }

.btn-like { border: 1px solid var(--border-color); border-radius: 999px; padding: 0.35rem 0.75rem; display: inline-flex; align-items: center; gap: 0.4rem; background: transparent; color: var(--text-primary); }
.btn-like.liked { background: transparent; color: var(--text-primary); border-color: var(--border-color); }
.btn-like .bi-heart { color: var(--text-secondary); }
.btn-like .bi-heart-fill { color: #ff6b6b; }

/* Stili "Discord" per il contenuto */
.annuncio-body { color: var(--text-secondary); line-height: 1.6; }
.discord-h1 { font-size: 1.6rem; font-weight: 800; color: var(--text-primary); margin: 0.25rem 0; }
.discord-h2 { font-size: 1.3rem; font-weight: 700; color: var(--text-primary); margin: 0.25rem 0; }
.discord-h3 { font-size: 1.05rem; font-weight: 700; color: var(--text-primary); margin: 0.25rem 0; }
.discord-p { margin: 0.35rem 0; }
.discord-inline-code { background: var(--secondary-bg); border: 1px solid var(--border-color); border-radius: 6px; padding: 0 0.25rem; font-family: Consolas, Monaco, 'Courier New', monospace; }
.discord-code { background: var(--secondary-bg); border: 1px solid var(--border-color); border-radius: 10px; padding: 0.6rem 0.75rem; overflow-x: auto; }
.discord-code code { font-family: Consolas, Monaco, 'Courier New', monospace; color: var(--text-primary); }
.discord-quote { border-left: 3px solid var(--border-color); padding: 0.25rem 0.75rem; margin: 0.5rem 0; background: rgba(255,255,255,0.02); }
.discord-quote p { margin: 0.25rem 0; }
.discord-spacer { height: 0.5rem; }
</style>

<?php include 'footer.php'; ?>