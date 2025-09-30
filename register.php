<?php
/**
 * Pagina di Registrazione
 * Registration Page
 */

require_once 'config.php';

// Se l'utente è già loggato, reindirizza alla homepage
if (isLoggedIn()) {
    redirect('/');
}

$error = '';
$success = '';

// Gestione del form di registrazione
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Sessione scaduta o token non valido. Ricarica la pagina e riprova.';
    }
    $minecraft_nick = sanitize($_POST['minecraft_nick'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $captcha_response = $_POST['g-recaptcha-response'] ?? '';
    
    // Validazione input
    if (empty($minecraft_nick) || empty($password) || empty($password_confirm)) {
        $error = 'Per favore, compila tutti i campi.';
    } elseif (strlen($minecraft_nick) < 3 || strlen($minecraft_nick) > 16) {
        $error = 'Il nickname Minecraft deve essere tra 3 e 16 caratteri.';
    } elseif (strlen($password) < 6) {
        $error = 'La password deve essere di almeno 6 caratteri.';
    } elseif ($password !== $password_confirm) {
        $error = 'Le password non coincidono.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $minecraft_nick)) {
        $error = 'Il nickname può contenere solo lettere, numeri e underscore.';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Inserisci un indirizzo email valido.';
    } else {
        // Verifica reCAPTCHA di Google
        $secret_key = RECAPTCHA_SECRET_KEY;
        
        if (!empty($captcha_response)) {
            $verify_url = "https://www.google.com/recaptcha/api/siteverify";
            $verify_data = [
                'secret' => $secret_key,
                'response' => $captcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            ];
            
            $verify_request = curl_init();
            curl_setopt($verify_request, CURLOPT_URL, $verify_url);
            curl_setopt($verify_request, CURLOPT_POST, true);
            curl_setopt($verify_request, CURLOPT_POSTFIELDS, http_build_query($verify_data));
            curl_setopt($verify_request, CURLOPT_RETURNTRANSFER, true);
            
            $verify_response = curl_exec($verify_request);
            curl_close($verify_request);
            
            $captcha_result = json_decode($verify_response, true);
            
            if (!$captcha_result['success']) {
                $error = 'Verifica reCAPTCHA fallita. Riprova.';
            }
        } else {
            $error = 'Completa la verifica reCAPTCHA.';
        }
        
        if (empty($error)) {
            try {
                // Controlla se il nickname esiste già
                $stmt = $pdo->prepare("SELECT id FROM sl_users WHERE minecraft_nick = ?");
                $stmt->execute([$minecraft_nick]);
                
                if ($stmt->fetch()) {
                    $error = 'Questo nickname Minecraft è già registrato.';
                } else {
                    // Crea l'utente
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $data_registrazione = date('Y-m-d H:i:s');
                    
                    // Assicura colonna email (se già esiste, ignora errore)
                    try { $pdo->exec("ALTER TABLE sl_users ADD COLUMN email VARCHAR(255) NULL"); } catch (Exception $e) {}
                    $stmt = $pdo->prepare("INSERT INTO sl_users (minecraft_nick, email, password_hash, data_registrazione) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$minecraft_nick, ($email ?: null), $password_hash, $data_registrazione]);
                    
                    $success = 'Registrazione completata con successo! Ora puoi effettuare il login.';
                    
                    // Reindirizza al login dopo 3 secondi
                    echo '<meta http-equiv="refresh" content="3;url=login.php">';
                }
            } catch (PDOException $e) {
                $error = 'Errore durante la registrazione. Riprova più tardi.';
            }
        }
    }
}



$page_title = "Registrazione";
include 'header.php';
?>


<script src="https://www.google.com/recaptcha/api.js" async defer></script>
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

<!-- Register Page Container -->
<div class="auth-page-container">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-12">
                <div class="auth-card">
                    <div class="auth-header">
                        <div class="auth-logo">
                            <i class="bi bi-boxes"></i>
                        </div>
                        <h1 class="auth-title">Unisciti a Blocksy</h1>
                        <p class="auth-subtitle">Crea il tuo account per iniziare</p>
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
                        
                        <form method="POST" action="register.php" id="registerForm" class="auth-form">
                            <?php echo csrfInput(); ?>

                            <!-- Riga 1: Nick a sinistra, Email a destra -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="minecraft_nick" class="form-label">
                                            <i class="bi bi-person"></i> Nickname Minecraft
                                        </label>
                                        <input type="text" class="form-input" id="minecraft_nick" name="minecraft_nick" 
                                               value="<?php echo htmlspecialchars($_POST['minecraft_nick'] ?? ''); ?>" required
                                               placeholder="Il tuo nickname Minecraft" maxlength="16">
                                        <div class="form-hint">3-16 caratteri, solo lettere, numeri e underscore</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="form-label">
                                            <i class="bi bi-envelope"></i> Email
                                        </label>
                                        <input type="email" class="form-input" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                               placeholder="La tua email (consigliato)">
                                        <div class="form-hint">Serve per il recupero password e comunicazioni importanti</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Riga 2: Password in basso a sinistra, Conferma in basso a destra -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password" class="form-label">
                                            <i class="bi bi-lock"></i> Password
                                        </label>
                                        <div class="password-input-group">
                                            <input type="password" class="form-input" id="password" name="password" required
                                                   placeholder="Crea una password sicura">
                                            <button type="button" class="password-toggle" id="togglePassword">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-hint">Minimo 6 caratteri</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password_confirm" class="form-label">
                                            <i class="bi bi-lock-fill"></i> Conferma Password
                                        </label>
                                        <div class="password-input-group">
                                            <input type="password" class="form-input" id="password_confirm" name="password_confirm" required
                                                   placeholder="Ripeti la password">
                                            <button type="button" class="password-toggle" id="togglePasswordConfirm">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="bi bi-shield-check"></i> Verifica di sicurezza
                                </label>
                                <div class="recaptcha-container" style="display: flex; justify-content: center; margin: 1rem 0;">
                                    <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                                </div>
                                <div class="form-hint" style="text-align: center;">
                                    Completa la verifica reCAPTCHA per continuare
                                </div>
                            </div>
                            
                            <button type="submit" class="auth-button">
                                <i class="bi bi-person-plus"></i>
                                <span>Crea Account</span>
                            </button>
                        </form>
                    </div>
                    
                    <div class="auth-footer">
                        <p class="auth-switch-text">Hai già un account?</p>
<a href="/login" class="auth-switch-link">
                            <i class="bi bi-box-arrow-in-right"></i> Accedi ora
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

document.getElementById('togglePasswordConfirm').addEventListener('click', function() {
    const passwordInput = document.getElementById('password_confirm');
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
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const minecraftNick = document.getElementById('minecraft_nick').value.trim();
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirm').value;
    const recaptchaResponse = grecaptcha.getResponse();
    
    if (minecraftNick.length < 3 || minecraftNick.length > 16) {
        e.preventDefault();
        showAuthToast('Il nickname deve essere tra 3 e 16 caratteri.', 'error');
        return false;
    }
    
    if (!/^[a-zA-Z0-9_]+$/.test(minecraftNick)) {
        e.preventDefault();
        showAuthToast('Il nickname può contenere solo lettere, numeri e underscore.', 'error');
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        showAuthToast('La password deve essere di almeno 6 caratteri.', 'error');
        return false;
    }
    
    if (password !== passwordConfirm) {
        e.preventDefault();
        showAuthToast('Le password non coincidono.', 'error');
        return false;
    }
    
    if (!recaptchaResponse) {
        e.preventDefault();
        showAuthToast('Completa la verifica reCAPTCHA.', 'error');
        return false;
    }
});

// Enhanced toast for auth pages
function showAuthToast(message, type = 'success') {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.auth-toast');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = 'auth-toast';
    
    const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
    const bgColor = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6';
    
    toast.innerHTML = `
        <div style="
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${bgColor};
            color: white;
            padding: 16px 24px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            z-index: 99999;
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            font-weight: 500;
            animation: authToastSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(20px);
            max-width: 300px;
        ">
            <span style="font-size: 18px;">${icon}</span>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Remove after 4 seconds
    setTimeout(() => {
        toast.style.animation = 'authToastSlideOut 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards';
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

// Add CSS animations for toast
const style = document.createElement('style');
style.textContent = `
    @keyframes authToastSlideIn {
        from {
            opacity: 0;
            transform: translateX(100%) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
    }
    
    @keyframes authToastSlideOut {
        from {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
        to {
            opacity: 0;
            transform: translateX(100%) scale(0.9);
        }
    }
`;
document.head.appendChild(style);
</script>

<?php include 'footer.php'; ?>