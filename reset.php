<?php
/**
 * Reset Password - Imposta nuova password da token
 */
require_once 'config.php';

if (isLoggedIn()) {
    redirect('/');
}

$error = '';
$success = '';
$token = sanitize($_GET['token'] ?? ($_POST['token'] ?? ''));
$valid = false;
$user_id = null;

if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("SELECT user_id, expires_at FROM sl_password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        if ($row) {
            if (strtotime($row['expires_at']) >= time()) {
                $valid = true;
                $user_id = (int)$row['user_id'];
            } else {
                $error = 'Il link di reset è scaduto. Richiedi un nuovo link.';
            }
        } else {
            $error = 'Token non valido o già utilizzato.';
        }
    } catch (PDOException $e) {
        $error = 'Errore nel controllo del token.';
    }
} else {
    $error = 'Token mancante.';
}

if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Sessione scaduta o token CSRF non valido. Riprova.';
    } else {
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        if (strlen($password) < 6) {
            $error = 'La password deve essere di almeno 6 caratteri.';
        } elseif ($password !== $password_confirm) {
            $error = 'Le password non coincidono.';
        } else {
            try {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE sl_users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$password_hash, $user_id]);

                // Elimina il token usato
                $stmt = $pdo->prepare("DELETE FROM sl_password_resets WHERE token = ?");
                $stmt->execute([$token]);

                $success = 'Password aggiornata con successo. Ora puoi effettuare il login.';
            } catch (PDOException $e) {
                $error = 'Errore durante l\'aggiornamento della password.';
            }
        }
    }
}

$page_title = 'Imposta nuova password';
include 'header.php';
?>

<div class="auth-page-container">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-12">
                <div class="auth-card">
                    <div class="auth-header">
                        <div class="auth-logo">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <h1 class="auth-title">Imposta nuova password</h1>
                        <p class="auth-subtitle">Inserisci e conferma la tua nuova password</p>
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

                        <?php if ($valid): ?>
                        <form method="POST" action="reset.php" class="auth-form">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            <div class="form-group">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock"></i> Nuova Password
                                </label>
                                <div class="password-input-group">
                                    <input type="password" class="form-input" id="password" name="password" required placeholder="La tua nuova password">
                                    <button type="button" class="password-toggle" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="password_confirm" class="form-label">
                                    <i class="bi bi-lock-fill"></i> Conferma Password
                                </label>
                                <div class="password-input-group">
                                    <input type="password" class="form-input" id="password_confirm" name="password_confirm" required placeholder="Ripeti la password">
                                    <button type="button" class="password-toggle" id="togglePasswordConfirm">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="auth-button">
                                <i class="bi bi-check"></i>
                                <span>Imposta Password</span>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>

                    <div class="auth-footer">
                        <a href="/login" class="auth-switch-link">
                            <i class="bi bi-box-arrow-in-right"></i> Torna al Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePassword')?.addEventListener('click', function() {
    const input = document.getElementById('password');
    const icon = this.querySelector('i');
    if (input.type === 'password') { input.type = 'text'; icon.classList.replace('bi-eye','bi-eye-slash'); }
    else { input.type = 'password'; icon.classList.replace('bi-eye-slash','bi-eye'); }
});
document.getElementById('togglePasswordConfirm')?.addEventListener('click', function() {
    const input = document.getElementById('password_confirm');
    const icon = this.querySelector('i');
    if (input.type === 'password') { input.type = 'text'; icon.classList.replace('bi-eye','bi-eye-slash'); }
    else { input.type = 'password'; icon.classList.replace('bi-eye-slash','bi-eye'); }
});
</script>

<?php include 'footer.php'; ?>