<?php
/**
 * Pagina di Login
 * Login Page
 */

require_once 'config.php';

// Se l'utente è già loggato, reindirizza alla homepage
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

// Gestione del form di login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $minecraft_nick = sanitize($_POST['minecraft_nick'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha_response = $_POST['g-recaptcha-response'] ?? '';
    
    // Validazione input
    if (empty($minecraft_nick) || empty($password)) {
        $error = 'Per favore, compila tutti i campi.';
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
                // Cerca l'utente nel database
                $stmt = $pdo->prepare("SELECT id, minecraft_nick, password_hash, is_admin FROM sl_users WHERE minecraft_nick = ?");
                $stmt->execute([$minecraft_nick]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    // Login riuscito
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['minecraft_nick'] = $user['minecraft_nick'];
                    $_SESSION['is_admin'] = $user['is_admin'];
                    
                    // Rimuovi la domanda matematica dalla sessione
                    unset($_SESSION['math_question']);
                    
                    $success = 'Login effettuato con successo! Reindirizzamento...';
                    
                    // Reindirizza dopo 2 secondi
                    echo '<meta http-equiv="refresh" content="2;url=index.php">';
                } else {
                    $error = 'Nome utente o password non validi.';
                }
            } catch (PDOException $e) {
                $error = 'Errore durante il login. Riprova più tardi.';
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

$page_title = "Login";
include 'header.php';
?>

<!-- Login Page Container -->
<div class="auth-page-container">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5 col-xl-4">
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
                        
                        <form method="POST" action="login.php" id="loginForm" class="auth-form">
                            <div class="form-group">
                                <label for="minecraft_nick" class="form-label">
                                    <i class="bi bi-person"></i> Nickname Minecraft
                                </label>
                                <input type="text" class="form-input" id="minecraft_nick" name="minecraft_nick" 
                                       value="<?php echo htmlspecialchars($_POST['minecraft_nick'] ?? ''); ?>" required
                                       placeholder="Il tuo nickname Minecraft">
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
                                <i class="bi bi-box-arrow-in-right"></i>
                                <span>Accedi</span>
                            </button>
                        </form>
                    </div>
                    
                    <div class="auth-footer">
                        <p class="auth-switch-text">Non hai un account?</p>
                        <a href="register.php" class="auth-switch-link">
                            <i class="bi bi-person-plus"></i> Registrati ora
                        </a>
                        
                        <div class="auth-demo-info">
                            <i class="bi bi-info-circle"></i>
                            <span>Demo: admin / admin123</span>
                        </div>
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
    const minecraftNick = document.getElementById('minecraft_nick').value.trim();
    const password = document.getElementById('password').value;
    const mathAnswer = document.getElementById('math_answer').value;
    
    if (minecraftNick.length < 3) {
        e.preventDefault();
        showToast('Il nickname deve essere di almeno 3 caratteri.', 'error');
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        showToast('La password deve essere di almeno 6 caratteri.', 'error');
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