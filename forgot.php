<?php
/**
 * Recupero Password - Richiesta token reset
 */
require_once 'config.php';

if (isLoggedIn()) {
    redirect('/');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Sessione scaduta o token CSRF non valido. Riprova.';
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        if ($identifier === '') {
            $error = 'Inserisci il tuo nickname Minecraft o la tua email.';
        } else {
            try {
                // Supporta sia nickname che email
                $is_email = strpos($identifier, '@') !== false;
                if ($is_email) {
                    try { $pdo->exec("ALTER TABLE sl_users ADD COLUMN email VARCHAR(255) NULL"); } catch (Exception $e) {}
                    $stmt = $pdo->prepare("SELECT id, minecraft_nick FROM sl_users WHERE email = ?");
                } else {
                    $stmt = $pdo->prepare("SELECT id, minecraft_nick FROM sl_users WHERE minecraft_nick = ?");
                }
                $stmt->execute([$identifier]);
                $user = $stmt->fetch();

                if (!$user) {
                    $error = 'Utente non trovato.';
                } else {
                    $token = bin2hex(random_bytes(32));
                    $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 ora
                    $stmt = $pdo->prepare("INSERT INTO sl_password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                    $stmt->execute([$user['id'], $token, $expires_at]);

                    $reset_link = SITE_URL . '/reset?token=' . urlencode($token);
                    $success = 'Se l\'utente esiste, è stato creato un link di reset.';

                    // In ambienti di sviluppo, mostra direttamente il link
                    if (defined('ENABLE_DEV_RESET_LINK_DISPLAY') && ENABLE_DEV_RESET_LINK_DISPLAY) {
                        $success .= ' Puoi usare questo link: <a href="' . htmlspecialchars($reset_link) . '">' . htmlspecialchars($reset_link) . '</a>';
                    }
                }
            } catch (PDOException $e) {
                $error = 'Errore durante la creazione del token di reset.';
            }
        }
    }
}

$page_title = 'Recupero Password';
include 'header.php';
?>

<div class="auth-page-container">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-12">
                <div class="auth-card">
                    <div class="auth-header">
                        <div class="auth-logo">
                            <i class="bi bi-key"></i>
                        </div>
                        <h1 class="auth-title">Recupero Password</h1>
                        <p class="auth-subtitle">Inserisci il tuo nickname Minecraft o la tua email per ricevere il link di reset</p>
                    </div>

                    <div class="auth-body">
                        <?php if (!empty($success)): ?>
                            <div class="auth-alert auth-alert-success">
                                <i class="bi bi-check-circle"></i>
                                <span><?php echo $success; ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($error)): ?>
                            <div class="auth-alert auth-alert-error">
                                <i class="bi bi-exclamation-triangle"></i>
                                <span><?php echo $error; ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="forgot.php" class="auth-form">
                            <?php echo csrfInput(); ?>
                            <div class="form-group">
                                <label for="identifier" class="form-label">
                                    <i class="bi bi-person"></i> Nickname o Email
                                </label>
                                <input type="text" class="form-input" id="identifier" name="identifier" placeholder="Nickname Minecraft o Email" required>
                            </div>
                            <button type="submit" class="auth-button">
                                <i class="bi bi-envelope"></i>
                                <span>Invia Link di Reset</span>
                            </button>
                        </form>
                    </div>

                    <div class="auth-footer">
                        <p class="auth-switch-text">Ti ricordi la password?</p>
                        <a href="/login" class="auth-switch-link">
                            <i class="bi bi-box-arrow-in-right"></i> Torna al Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>