<?php
/**
 * Pagina Messaggi Utente
 */
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('/login?next=/messages');
}

$page_title = "I Miei Messaggi";

// Gestione invio risposta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_to']) && isset($_POST['reply_message'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Token di sicurezza non valido";
    } else {
        $reply_to = (int)$_POST['reply_to'];
        $reply_message = trim($_POST['reply_message']);
        
        if (empty($reply_message)) {
            $error = "Il messaggio non può essere vuoto";
        } else {
            // Recupera il messaggio originale
            $stmt = $pdo->prepare("SELECT * FROM sl_messages WHERE id = ? AND to_user_id = ?");
            $stmt->execute([$reply_to, $_SESSION['user_id']]);
            $original = $stmt->fetch();
            
            if ($original) {
                // Invia risposta (to_user_id diventa from_user_id originale o NULL se era admin)
                $to_user = $original['from_user_id'] ?? null;
                
                if ($to_user === null) {
                    // Risposta all'admin - trova un admin qualsiasi
                    $stmt = $pdo->query("SELECT id FROM sl_users WHERE is_admin = 1 LIMIT 1");
                    $admin = $stmt->fetch();
                    $to_user = $admin ? $admin['id'] : null;
                }
                
                if ($to_user) {
                    $subject = "Re: " . $original['subject'];
                    try {
                        $stmt = $pdo->prepare("INSERT INTO sl_messages (from_user_id, to_user_id, subject, message, parent_id) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$_SESSION['user_id'], $to_user, $subject, $reply_message, $reply_to]);
                        $_SESSION['success_message'] = "Risposta inviata con successo! Il messaggio è stato inviato a " . ($original['from_nick'] ?? 'Amministrazione');
                        redirect('/messages');
                    } catch (PDOException $e) {
                        $error = "Errore durante l'invio: " . $e->getMessage();
                    }
                } else {
                    $error = "Impossibile trovare il destinatario";
                }
            } else {
                $error = "Messaggio non trovato";
            }
        }
    }
}

// Segna come letto se richiesto
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $msg_id = (int)$_GET['read'];
    $stmt = $pdo->prepare("UPDATE sl_messages SET is_read = 1 WHERE id = ? AND to_user_id = ?");
    $stmt->execute([$msg_id, $_SESSION['user_id']]);
    redirect('/messages');
}

// Recupera messaggi ricevuti e inviati dall'utente
$stmt = $pdo->prepare("
    SELECT m.*, 
           u_from.minecraft_nick as from_nick,
           u_to.minecraft_nick as to_nick,
           (SELECT COUNT(*) FROM sl_messages WHERE parent_id = m.id) as reply_count,
           CASE WHEN m.from_user_id = ? THEN 'sent' ELSE 'received' END as message_type
    FROM sl_messages m
    LEFT JOIN sl_users u_from ON m.from_user_id = u_from.id
    LEFT JOIN sl_users u_to ON m.to_user_id = u_to.id
    WHERE m.to_user_id = ? OR m.from_user_id = ?
    ORDER BY m.created_at DESC
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$messages = $stmt->fetchAll();

$unread_count = 0;
foreach ($messages as $msg) {
    if (!$msg['is_read']) $unread_count++;
}

include 'header.php';
?>

<div class="container" style="margin-top: 2rem; margin-bottom: 3rem;">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="messages-page">
                <div class="page-header text-center mb-4">
                    <div class="messages-icon mb-3">
                        <i class="bi bi-envelope" style="font-size: 4rem; color: var(--accent-purple);"></i>
                    </div>
                    <h1 class="page-title">I Miei Messaggi</h1>
                    <p class="page-subtitle">
                        <?php if ($unread_count > 0): ?>
                            Hai <strong><?php echo $unread_count; ?></strong> messaggi non letti
                        <?php else: ?>
                            Nessun messaggio non letto
                        <?php endif; ?>
                    </p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (empty($messages)): ?>
                    <div class="no-messages text-center">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                        <h3>Nessun Messaggio</h3>
                        <p>Non hai ancora ricevuto messaggi dagli amministratori.</p>
                    </div>
                <?php else: ?>
                    <div class="messages-list">
                        <?php foreach ($messages as $msg): ?>
                            <div class="message-card <?php echo ($msg['message_type'] === 'received' && !$msg['is_read']) ? 'unread' : ''; ?> <?php echo $msg['message_type'] === 'sent' ? 'sent-message' : ''; ?>">
                                <div class="message-header">
                                    <div class="message-from">
                                        <?php if ($msg['message_type'] === 'sent'): ?>
                                            <span class="badge bg-info me-2">Inviato</span>
                                            <i class="bi bi-arrow-right-circle"></i>
                                            <strong>A: <?php echo htmlspecialchars($msg['to_nick'] ?? 'Amministrazione'); ?></strong>
                                        <?php else: ?>
                                            <i class="bi bi-person-circle"></i>
                                            <strong>Da: <?php echo htmlspecialchars($msg['from_nick'] ?? 'Amministrazione'); ?></strong>
                                        <?php endif; ?>
                                    </div>
                                    <div class="message-date">
                                        <?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="message-subject">
                                    <?php if ($msg['message_type'] === 'received' && !$msg['is_read']): ?>
                                        <span class="badge bg-warning text-dark">Nuovo</span>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($msg['subject']); ?>
                                </div>
                                <div class="message-body">
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                </div>
                                <div class="message-actions">
                                    <?php if ($msg['message_type'] === 'received' && !$msg['is_read']): ?>
                                        <a href="/messages?read=<?php echo $msg['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-check2"></i> Segna come letto
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($msg['message_type'] === 'received'): ?>
                                        <button class="btn btn-sm btn-outline-primary" onclick="toggleReply(<?php echo $msg['id']; ?>)">
                                            <i class="bi bi-reply"></i> Rispondi
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Form di risposta (nascosto di default, solo per messaggi ricevuti) -->
                                <?php if ($msg['message_type'] === 'received'): ?>
                                    <div id="reply-form-<?php echo $msg['id']; ?>" class="reply-form" style="display: none;">
                                        <form method="POST" action="/messages">
                                            <?php echo csrfInput(); ?>
                                            <input type="hidden" name="reply_to" value="<?php echo $msg['id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">La tua risposta:</label>
                                                <textarea name="reply_message" class="form-control" rows="4" required placeholder="Scrivi la tua risposta..."></textarea>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-send"></i> Invia Risposta
                                                </button>
                                                <button type="button" class="btn btn-secondary" onclick="toggleReply(<?php echo $msg['id']; ?>)">
                                                    Annulla
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.messages-page {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 2rem;
    border: 1px solid var(--border-color);
}

.page-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.page-subtitle {
    font-size: 1.1rem;
    color: var(--text-secondary);
}

.messages-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.message-card {
    background: var(--primary-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.message-card.unread {
    border-left: 4px solid var(--accent-purple);
    background: rgba(124, 58, 237, 0.05);
}

.message-card.sent-message {
    border-left: 4px solid #17a2b8;
    background: rgba(23, 162, 184, 0.05);
}

.message-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.message-from {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-primary);
    font-weight: 600;
}

.message-date {
    color: var(--text-muted);
    font-size: 0.9rem;
}

.message-subject {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 1rem;
}

.message-body {
    color: var(--text-secondary);
    line-height: 1.6;
    padding: 1rem;
    background: var(--card-bg);
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.message-actions {
    margin-top: 1rem;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.reply-form {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--card-bg);
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.reply-form textarea {
    background: var(--primary-bg);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
}

.reply-form textarea:focus {
    background: var(--primary-bg);
    border-color: var(--accent-purple);
    color: var(--text-primary);
}

.no-messages {
    padding: 3rem;
    background: var(--primary-bg);
    border-radius: 12px;
    border: 1px solid var(--border-color);
}
</style>

<script>
function toggleReply(messageId) {
    const form = document.getElementById('reply-form-' + messageId);
    if (form.style.display === 'none') {
        form.style.display = 'block';
        form.querySelector('textarea').focus();
    } else {
        form.style.display = 'none';
    }
}
</script>

<?php include 'footer.php'; ?>
