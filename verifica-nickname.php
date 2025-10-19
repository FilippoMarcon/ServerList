<?php
require_once 'config.php';
$page_title = "Verifica nickname";

$verify_error = '';
$verify_success = '';
$current_link = null;

if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT minecraft_nick FROM sl_minecraft_links WHERE user_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $current_link = $stmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isLoggedIn()) {
        redirect('/login?next=/verifica-nickname');
    }
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $verify_error = 'Sessione scaduta o token CSRF non valido. Ricarica la pagina e riprova.';
    } else {
        // Gestione scollegamento account, altrimenti verifica con codice
        if (isset($_POST['unlink_minecraft'])) {
            try {
                $stmtDel = $pdo->prepare("DELETE FROM sl_minecraft_links WHERE user_id = ?");
                $stmtDel->execute([$_SESSION['user_id']]);
                $verify_success = 'Account Minecraft scollegato. Puoi collegarne uno nuovo.';
                $current_link = null;
            } catch (Exception $e) {
                $verify_error = 'Errore durante lo scollegamento dell\'account. Riprova.';
            }
        } else {
            // Pulizia codici scaduti
            try {
                $now = date('Y-m-d H:i:s');
                $stmtDel = $pdo->prepare("DELETE FROM sl_verification_codes WHERE expires_at < ?");
                $stmtDel->execute([$now]);
            } catch (Exception $e) {}

            $code = sanitize($_POST['verification_code'] ?? '');
            if ($code === '') {
                $verify_error = 'Inserisci il codice di verifica.';
            } else {
                $stmtC = $pdo->prepare("SELECT id, player_nick, expires_at, consumed_at FROM sl_verification_codes WHERE code = ? LIMIT 1");
                $stmtC->execute([$code]);
                $row = $stmtC->fetch();
                if (!$row) {
                    $verify_error = 'Codice non valido.';
                } elseif (!empty($row['consumed_at'])) {
                    $verify_error = 'Questo codice è già stato utilizzato.';
                } elseif (strtotime($row['expires_at']) < time()) {
                    $verify_error = 'Il codice è scaduto. Generane uno nuovo collegandoti al server.';
                } else {
                    $mcNick = $row['player_nick'];
                    // Il nickname Minecraft non può già essere collegato a un altro account
                    $stmtChk = $pdo->prepare("SELECT user_id FROM sl_minecraft_links WHERE minecraft_nick = ? LIMIT 1");
                    $stmtChk->execute([$mcNick]);
                    $existing = $stmtChk->fetchColumn();
                    if ($existing && (int)$existing !== (int)$_SESSION['user_id']) {
                        $verify_error = 'Questo nickname Minecraft è già collegato ad un altro account.';
                    } else {
                        // Un solo collegamento per utente
                        $stmtUsr = $pdo->prepare("SELECT id FROM sl_minecraft_links WHERE user_id = ? LIMIT 1");
                        $stmtUsr->execute([$_SESSION['user_id']]);
                        $hasLink = $stmtUsr->fetchColumn();
                        if ($hasLink) {
                            $verify_error = 'Hai già collegato un account Minecraft.';
                        } else {
                            // Collega e consuma il codice
                            $pdo->beginTransaction();
                            try {
                                $stmtIns = $pdo->prepare("INSERT INTO sl_minecraft_links (user_id, minecraft_nick) VALUES (?, ?)");
                                $stmtIns->execute([$_SESSION['user_id'], $mcNick]);
                                $stmtUpd = $pdo->prepare("UPDATE sl_verification_codes SET consumed_at = NOW() WHERE id = ?");
                                $stmtUpd->execute([$row['id']]);
                                $pdo->commit();
                                $current_link = $mcNick;
                                $verify_success = 'Verifica completata! Account collegato come ' . htmlspecialchars($mcNick) . '.';
                            } catch (Exception $e) {
                                $pdo->rollBack();
                                $verify_error = 'Errore durante il collegamento. Riprova.';
                            }
                        }
                    }
                }
            }
        }
    }
}

include 'header.php';
?>

<style>
    .server-ip-display {
        background: var(--primary-bg);
        color: var(--text-primary);
        border: 1px solid var(--border-color);
        padding: 0.5rem 0.75rem;
        border-radius: 10px;
        font-weight: 700;
        letter-spacing: 0.2px;
    }
    #copyVerifyIP {
        border-color: var(--border-color);
        color: var(--text-secondary);
    }
    .verify-status {
        padding: 0.75rem 1rem;
        border-radius: 10px;
        border: 1px solid #f39c12;
        background: rgba(243, 156, 18, 0.1);
    }

    .verify-status i{
        color: #f39c12;
    }
    .verify-alert-success { color: #0f5132; background: #d1e7dd; border-color: #badbcc; }
    .verify-alert-error { color: #842029; background: #f8d7da; border-color: #f5c2c7; }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card" style="background: var(--primary-bg); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden;">
                <div class="card-header" style="background: var(--gradient-primary); color: white;">
                    <h2 class="m-0" style="font-weight: 700;">
                        <i class="bi bi-person-badge"></i> Verifica Nickname Minecraft
                    </h2>
                </div>
                <div class="card-body" style="color: var(--text-secondary);">
                    <?php if (!isLoggedIn()): ?>
                        <div class="verify-status mb-3">
                            <i class="bi bi-info-circle"></i> Devi effettuare il <a href="/login?next=/verifica-nickname">login</a> per collegare l'account.
                        </div>
                    <?php else: ?>
                        <div class="verify-status mb-3">
                            <?php if ($current_link): ?>
                                <i class="bi bi-patch-check" style="color: var(--accent-blue);"></i>
                                Attualmente verificato come <strong><?php echo htmlspecialchars($current_link); ?></strong>.
                            <?php else: ?>
                                <i class="bi bi-dash-circle"></i> Nessun account Minecraft collegato.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($verify_error)): ?>
                        <div class="verify-alert-error p-3 mb-3" style="border-radius:10px;">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $verify_error; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($verify_success)): ?>
                        <div class="verify-alert-success p-3 mb-3" style="border-radius:10px;">
                            <i class="bi bi-check-circle"></i> <?php echo $verify_success; ?>
                        </div>
                    <?php endif; ?>

                    <p style="font-size: 1rem;">Per verificare il tuo nickname, entra in Minecraft Java Edition e collegati all'indirizzo <strong>verifica.blocksy.it</strong>. Segui le istruzioni in gioco per completare la verifica.</p>

                    <div class="row g-3 my-3">
                        <div class="col-md-6">
                            <div class="p-3" style="background: var(--primary-bg); border: 1px solid var(--border-color); border-radius: 12px;">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-minecraft" style="color: var(--accent-purple);"></i>
                                    <strong>Requisiti</strong>
                                </div>
                                <p class="mb-0 mt-2" style="font-size: 0.95rem;">
                                    La verifica è disponibile solo per <strong>Minecraft Java Edition</strong> e richiede un <strong>account Premium</strong>.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3" style="background: var(--primary-bg); border: 1px solid var(--border-color); border-radius: 12px;">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-exclamation-triangle" style="color: var(--accent-blue);"></i>
                                    <strong>Utenti SP (non premium)</strong>
                                </div>
                                <p class="mb-0 mt-2" style="font-size: 0.95rem;">
                                    Se non possiedi un account premium, apri un ticket su <a href="https://discord.blocksy.it" target="_blank" rel="noopener">discord.blocksy.it</a> per assistenza.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-server" style="color: var(--accent-purple);"></i>
                            <strong>Indirizzo di Verifica</strong>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <code class="server-ip-display">verifica.blocksy.it</code>
                            <button class="btn btn-sm btn-outline-secondary" id="copyVerifyIP" style="border-color: var(--border-color);">
                                <i class="bi bi-clipboard"></i> Copia
                            </button>
                        </div>
                        <small class="text-secondary d-block mt-2">Collegati al server e segui le istruzioni per completare la verifica.</small>
                    </div>

                    <?php if (isLoggedIn()): ?>
                    <hr class="my-4" />
                    <div class="mt-2">
                        <?php if (!empty($current_link)): ?>
                            <div class="pending-server-info">
                                <p class="pending-message">
                                    <i class="bi bi-info-circle"></i>
                                    Il tuo profilo è già collegato come <strong><?= htmlspecialchars($current_link) ?></strong>.
                                </p>
                                <form method="POST" action="/verifica-nickname" class="mt-2">
                                    <?= csrfInput(); ?>
                                    <button type="submit" name="unlink_minecraft" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-x-circle"></i> Scollega account
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-key" style="color: var(--accent-blue);"></i>
                                <strong>Inserisci il codice qui</strong>
                            </div>
                            <form method="POST" action="/verifica-nickname" class="mt-2">
                                <?= csrfInput(); ?>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="verification_code" placeholder="XXXX-XXXX-XXXX" maxlength="32" required>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check2-circle"></i> Verifica
                                    </button>
                                </div>
                                <small class="text-secondary d-block mt-2">Il codice scade dopo 5 minuti dalla generazione.</small>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <a class="btn btn-primary" href="https://discord.blocksy.it" target="_blank" rel="noopener">
                            <i class="bi bi-discord"></i> Apri Discord
                        </a>
                        <a class="btn btn-outline-secondary ms-2" href="https://telegram.blocksy.it" target="_blank" rel="noopener" style="border-color: var(--border-color);">
                            <i class="bi bi-telegram"></i> Apri Telegram
                        </a>
                    </div>

                    <!-- Carosello dimostrativo -->
                    <div id="verifyCarousel" class="carousel slide mt-4" data-bs-ride="carousel">
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#verifyCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                            <button type="button" data-bs-target="#verifyCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                        </div>
                        <div class="carousel-inner" style="border-radius:12px; overflow:hidden;">
                            <div class="carousel-item active">
                                <img src="add_server.png" class="d-block w-100" alt="Dimostrazione: Aggiungere il server alla lista">
                                <div class="carousel-caption d-none d-md-block">
                                    <h5>Come aggiungere il server</h5>
                                    <p>Clicca su Aggiungi Server e compila i campi richiesti.</p>
                                </div>
                            </div>
                            <div class="carousel-item">
                                <img src="direct_access.png" class="d-block w-100" alt="Dimostrazione: Entrare nel server">
                                <div class="carousel-caption d-none d-md-block">
                                    <h5>Come entrare velocemente nel server</h5>
                                    <p>Collegati a <strong>verifica.blocksy.it</strong> e segui le istruzioni.</p>
                                </div>
                            </div>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#verifyCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Precedente</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#verifyCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Successivo</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function copyTextFallback(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        try { document.execCommand('copy'); } catch(e) {}
        document.body.removeChild(ta);
    }
    
    document.getElementById('copyVerifyIP')?.addEventListener('click', function() {
        const text = 'verifica.blocksy.it';
        const btn = document.getElementById('copyVerifyIP');
        const original = btn.innerHTML;
        
        function showCopied() {
            btn.innerHTML = '<i class="bi bi-check2"></i> Copiato!';
            setTimeout(() => { btn.innerHTML = original; }, 1500);
        }
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(showCopied).catch(function(){
                copyTextFallback(text);
                showCopied();
            });
        } else {
            copyTextFallback(text);
            showCopied();
        }
    });
</script>

<?php include 'footer.php'; ?>