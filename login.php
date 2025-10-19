<?php
/**
 * Pagina di Login
 * Login Page
 */

require_once 'config.php';

// Se l'utente è già loggato, reindirizza alla homepage
if (isLoggedIn()) {
    redirect('/');
}

$error = '';
$success = '';

// Gestione del form di login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitize($_POST['minecraft_nick'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha_response = $_POST['g-recaptcha-response'] ?? '';
    
    // Validazione input
    if (empty($identifier) || empty($password)) {
        $error = 'Per favore, compila tutti i campi.';
    } else {
        // Verifica CAPTCHA (usa reCAPTCHA di Google, opzionale)
        $secret_key = defined('RECAPTCHA_SECRET_KEY') ? RECAPTCHA_SECRET_KEY : '';
        if ($secret_key && !empty($captcha_response)) {
            $verify = @file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret_key}&response={$captcha_response}");
            if ($verify !== false) {
                $captcha_success = json_decode($verify);
                if (!$captcha_success || empty($captcha_success->success)) {
                    $error = 'Verifica CAPTCHA fallita. Riprova.';
                }
            }
        }
        
        if (empty($error)) {
            try {
                // Supporta login tramite email o nickname Minecraft (nickname non univoci)
                $is_email = strpos($identifier, '@') !== false;
                $user = null;
                if ($is_email) {
                    try { $pdo->exec("ALTER TABLE sl_users ADD COLUMN email VARCHAR(255) NULL"); } catch (Exception $e) {}
                    $stmt = $pdo->prepare("SELECT id, minecraft_nick, password_hash, is_admin FROM sl_users WHERE email = ?");
                    $stmt->execute([$identifier]);
                    $user = $stmt->fetch();
                } else {
                    // Login via nickname Minecraft solo se collegato all'account
                    $stmtL = $pdo->prepare("SELECT user_id FROM sl_minecraft_links WHERE minecraft_nick = ? LIMIT 1");
                    $stmtL->execute([$identifier]);
                    $link_user_id = $stmtL->fetchColumn();
                    if ($link_user_id) {
                        $stmt = $pdo->prepare("SELECT id, minecraft_nick, password_hash, is_admin FROM sl_users WHERE id = ? LIMIT 1");
                        $stmt->execute([$link_user_id]);
                        $u = $stmt->fetch();
                        if ($u && password_verify($password, $u['password_hash'])) {
                            $user = $u;
                        }
                    }
                }
                
                if ($user) {
                    // Login riuscito
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['minecraft_nick'] = $user['minecraft_nick'];
                    $_SESSION['is_admin'] = $user['is_admin'];

                    $success = 'Login effettuato con successo! Reindirizzamento...';
                    
                    // Reindirizza dopo 2 secondi
                    echo '<meta http-equiv="refresh" content="2;url=index.php">';
                } else {
                    $error = 'Nome utente/email o password non validi.';
                }
            } catch (PDOException $e) {
                $error = 'Errore durante il login. Riprova più tardi.';
            }
        }
    }
}



$page_title = "Login";
include 'header.php';
?>


<style>
/* Force center alignment - Override any conflicting styles */
.auth-header {
    text-align: center !important;
}

.auth-logo {
    margin: 0 auto 2rem auto !important;
    display: flex !important;
    margin-left: auto !important;
    margin-right: auto !important;
}

.auth-body {
    margin-bottom: 0 !important;
}

@media (min-width: 769px) {
    .auth-header {
        text-align: center !important;
    }
    
    .auth-body {
        margin-bottom: 0 !important;
    }
}
</style>

<!-- Login Page Container -->
<div class="auth-page-container">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-12">
                <div class="auth-card">
                    <div class="auth-header">
                        <div class="auth-logo">
                            <i class="bi bi-boxes"></i>
                        </div>
                        <h1 class="auth-title">Benvenuto su Blocksy</h1>
                        <p class="auth-subtitle">Accedi al tuo account per continuare</p>
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
                        
                        <form method="POST" action="/login" id="loginForm" class="auth-form">
                            <div class="form-group">
                                <label for="minecraft_nick" class="form-label">
                                    <i class="bi bi-person"></i> Email o Nick Minecraft (se collegato)
                                </label>
                                <input type="text" class="form-input" id="minecraft_nick" name="minecraft_nick" 
                                       value="<?php echo htmlspecialchars($_POST['minecraft_nick'] ?? ''); ?>" required
                                       placeholder="Il tuo nickname o email">
                            </div>
                            
                            <div class="form-group">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock"></i> Password
                                </label>
                                <div class="password-input-group">
                                    <input type="password" class="form-input" id="password" name="password" required
                                           placeholder="La tua password">
                                    <button type="button" class="password-toggle" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            

                            
                            <button type="submit" class="auth-button">
                                <i class="bi bi-box-arrow-in-right"></i>
                                <span>Accedi</span>
                            </button>
                        </form>
                    </div>
                    
                    <div class="auth-footer">
                        <p class="auth-switch-text">Non hai un account?</p>
                        <a href="/register" class="auth-switch-link">
                            <i class="bi bi-person-plus"></i> Registrati ora
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('bi-eye');
        toggleIcon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('bi-eye-slash');
        toggleIcon.classList.add('bi-eye');
    }
});

// Form validation
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const identifier = document.getElementById('minecraft_nick').value.trim();
    const password = document.getElementById('password').value;
    
    if (identifier.length === 0) {
        e.preventDefault();
        showAuthToast('Inserisci nickname o email.', 'error');
        return false;
    }
    // Se non è email, applica regola minima lunghezza nickname
    if (!identifier.includes('@') && identifier.length < 3) {
        e.preventDefault();
        showAuthToast('Il nickname deve essere di almeno 3 caratteri.', 'error');
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        showAuthToast('La password deve essere di almeno 6 caratteri.', 'error');
        return false;
    }
});
</script>

<?php include 'footer.php'; ?>