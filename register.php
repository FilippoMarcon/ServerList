<?php
/**
 * Pagina di Registrazione
 * Registration Page
 */

require_once 'config.php';

// Se l'utente è già loggato, reindirizza alla homepage
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

// Gestione del form di registrazione
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $minecraft_nick = sanitize($_POST['minecraft_nick'] ?? '');
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
    } else {
        // Verifica CAPTCHA (usa reCAPTCHA di Google)
        $secret_key = 'YOUR_RECAPTCHA_SECRET_KEY'; // Sostituisci con la tua chiave segreta
        
        if (!empty($captcha_response)) {
            $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret_key}&response={$captcha_response}");
            $captcha_success = json_decode($verify);
            
            if (!$captcha_success->success) {
                $error = 'Verifica CAPTCHA fallita. Riprova.';
            }
        } else {
            // Se non c'è reCAPTCHA, usa una semplice verifica matematica come fallback
            $math_answer = $_POST['math_answer'] ?? '';
            $math_question = $_SESSION['math_question'] ?? '';
            
            if (empty($math_answer) || $math_answer != $math_question) {
                $error = 'Risposta matematica errata.';
            }
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
                    
                    $stmt = $pdo->prepare("INSERT INTO sl_users (minecraft_nick, password_hash, data_registrazione) VALUES (?, ?, ?)");
                    $stmt->execute([$minecraft_nick, $password_hash, $data_registrazione]);
                    
                    $success = 'Registrazione completata con successo! Ora puoi effettuare il login.';
                    
                    // Rimuovi la domanda matematica dalla sessione
                    unset($_SESSION['math_question']);
                    
                    // Reindirizza al login dopo 3 secondi
                    echo '<meta http-equiv="refresh" content="3;url=login.php">';
                }
            } catch (PDOException $e) {
                $error = 'Errore durante la registrazione. Riprova più tardi.';
            }
        }
    }
}

// Genera una domanda matematica semplice per il CAPTCHA fallback
if (!isset($_SESSION['math_question'])) {
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $_SESSION['math_question'] = $num1 + $num2;
    $_SESSION['math_text'] = "Quanto fa {$num1} + {$num2}?";
}

$page_title = "Registrazione";
include 'header.php';
?>

<!-- Register Page Container -->
<div class="auth-page-container">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5 col-xl-4">
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
                            <div class="form-group">
                                <label for="minecraft_nick" class="form-label">
                                    <i class="bi bi-person"></i> Nickname Minecraft
                                </label>
                                <input type="text" class="form-input" id="minecraft_nick" name="minecraft_nick" 
                                       value="<?php echo htmlspecialchars($_POST['minecraft_nick'] ?? ''); ?>" required
                                       placeholder="Il tuo nickname Minecraft" maxlength="16">
                                <div class="form-hint">3-16 caratteri, solo lettere, numeri e underscore</div>
                            </div>
                            
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
                            
                            <div class="form-group">
                                <label for="math_answer" class="form-label">
                                    <i class="bi bi-shield-check"></i> Verifica di sicurezza
                                </label>
                                <div class="captcha-group">
                                    <span class="captcha-question">
                                        <?php echo $_SESSION['math_text']; ?>
                                    </span>
                                    <input type="number" class="form-input captcha-input" id="math_answer" name="math_answer" required
                                           placeholder="Risultato">
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
                        <a href="login.php" class="auth-switch-link">
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
    const mathAnswer = document.getElementById('math_answer').value;
    
    if (minecraftNick.length < 3 || minecraftNick.length > 16) {
        e.preventDefault();
        showToast('Il nickname deve essere tra 3 e 16 caratteri.', 'error');
        return false;
    }
    
    if (!/^[a-zA-Z0-9_]+$/.test(minecraftNick)) {
        e.preventDefault();
        showToast('Il nickname può contenere solo lettere, numeri e underscore.', 'error');
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        showToast('La password deve essere di almeno 6 caratteri.', 'error');
        return false;
    }
    
    if (password !== passwordConfirm) {
        e.preventDefault();
        showToast('Le password non coincidono.', 'error');
        return false;
    }
    
    if (!mathAnswer || isNaN(mathAnswer)) {
        e.preventDefault();
        showToast('Inserisci una risposta valida per la verifica.', 'error');
        return false;
    }
});
</script>

<?php include 'footer.php'; ?>