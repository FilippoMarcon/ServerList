<?php
require_once 'config.php';

// Controlla se l'utente è loggato
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$server_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Recupera le informazioni del server
$server = null;
try {
    $stmt = $pdo->prepare("
        SELECT s.*, u.minecraft_nick as owner_nick 
        FROM sl_servers s 
        JOIN sl_users u ON s.owner_id = u.id 
        WHERE s.id = ? AND s.owner_id = ?
    ");
    $stmt->execute([$server_id, $user_id]);
    $server = $stmt->fetch();
    
    if (!$server) {
        $_SESSION['error'] = "Server non trovato o non hai i permessi per modificarlo.";
        redirect('profile.php');
    }
} catch (PDOException $e) {
    error_log("Errore nel recupero server: " . $e->getMessage());
    $_SESSION['error'] = "Errore nel caricamento del server.";
    redirect('profile.php');
}

// Gestione del form di modifica
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $ip = trim($_POST['ip'] ?? '');
    $porta = intval($_POST['porta'] ?? 25565);
    $versione = trim($_POST['versione'] ?? '');
    $tipo_server = trim($_POST['tipo_server'] ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');
    $sito_web = trim($_POST['sito_web'] ?? '');
    $discord = trim($_POST['discord'] ?? '');
    $tebex = trim($_POST['tebex'] ?? '');
    
    // Validazione
    $errors = [];
    
    if (empty($nome)) {
        $errors[] = "Il nome del server è obbligatorio.";
    }
    
    if (empty($ip)) {
        $errors[] = "L'IP del server è obbligatorio.";
    }
    
    if (empty($descrizione)) {
        $errors[] = "La descrizione è obbligatoria.";
    }
    
    if (strlen($descrizione) < 50) {
        $errors[] = "La descrizione deve essere di almeno 50 caratteri.";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE sl_servers SET 
                    nome = ?, ip = ?, porta = ?, versione = ?, tipo_server = ?, 
                    descrizione = ?, sito_web = ?, discord = ?, tebex = ?, 
                    updated_at = NOW() 
                WHERE id = ? AND owner_id = ?
            ");
            
            $stmt->execute([
                $nome, $ip, $porta, $versione, $tipo_server, 
                $descrizione, $sito_web, $discord, $tebex,
                $server_id, $user_id
            ]);
            
            $_SESSION['toast_success'] = "Server modificato con successo!";
            redirect('profile.php');
            
        } catch (PDOException $e) {
            error_log("Errore nella modifica server: " . $e->getMessage());
            $_SESSION['toast_error'] = "Errore durante la modifica del server.";
        }
    }
}

$page_title = "Modifica Server - " . htmlspecialchars($server['nome']);
include 'header.php';
?>

<div class="edit-server-container">
    <div class="container" style="margin-top: 2rem; margin-bottom: 3rem;">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="edit-server-card">
                    <div class="card-header">
                        <h2><i class="bi bi-pencil-square"></i> Modifica Server</h2>
                        <p class="text-muted">Modifica le informazioni del tuo server</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                <?php foreach ($errors as $error): ?>
                                    showToast('<?php echo addslashes($error); ?>', 'error');
                                <?php endforeach; ?>
                            });
                        </script>
                    <?php endif; ?>
                    
                    <form method="POST" class="edit-server-form" id="editServerForm">
                        <script>
                            // Conferma prima di salvare e validazione
                            document.getElementById('editServerForm').addEventListener('submit', function(e) {
                                e.preventDefault();
                                
                                // Validazione lato client
                                const nome = document.getElementById('nome').value.trim();
                                const ip = document.getElementById('ip').value.trim();
                                const descrizione = document.getElementById('descrizione').value.trim();
                                
                                let valid = true;
                                
                                if (nome.length < 3 || nome.length > 50) {
                                    showToast('Il nome del server deve essere compreso tra 3 e 50 caratteri.', 'error');
                                    valid = false;
                                }
                                
                                if (!/^\d+\.\d+\.\d+\.\d+$/.test(ip)) {
                                    showToast('L\'indirizzo IP non è valido. Usa il formato xxx.xxx.xxx.xxx', 'error');
                                    valid = false;
                                }
                                
                                if (descrizione.length < 50 || descrizione.length > 500) {
                                    showToast('La descrizione deve essere compresa tra 50 e 500 caratteri.', 'error');
                                    valid = false;
                                }
                                
                                if (valid) {
                                    if (confirm('Sei sicuro di voler salvare le modifiche?')) {
                                        this.submit();
                                    }
                                }
                            });
                        </script>
                        <div class="form-group">
                            <label for="nome">Nome del Server *</label>
                            <input type="text" class="form-control" id="nome" name="nome" 
                                   value="<?php echo htmlspecialchars($server['nome']); ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="ip">IP del Server *</label>
                                    <input type="text" class="form-control" id="ip" name="ip" 
                                           value="<?php echo htmlspecialchars($server['ip']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="porta">Porta</label>
                                    <input type="number" class="form-control" id="porta" name="porta" 
                                           value="<?php echo htmlspecialchars($server['porta']); ?>" min="1" max="65535">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="versione">Versione</label>
                                    <input type="text" class="form-control" id="versione" name="versione" 
                                           value="<?php echo htmlspecialchars($server['versione']); ?>" 
                                           placeholder="es: 1.20.4">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tipo_server">Tipo Server</label>
                                    <select class="form-control" id="tipo_server" name="tipo_server">
                                        <option value="Java Edition" <?php echo $server['tipo_server'] === 'Java Edition' ? 'selected' : ''; ?>>Java Edition</option>
                                        <option value="Bedrock Edition" <?php echo $server['tipo_server'] === 'Bedrock Edition' ? 'selected' : ''; ?>>Bedrock Edition</option>
                                        <option value="Java & Bedrock" <?php echo $server['tipo_server'] === 'Java & Bedrock' ? 'selected' : ''; ?>>Java & Bedrock</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="descrizione">Descrizione *</label>
                            <textarea class="form-control" id="descrizione" name="descrizione" 
                                      rows="5" required minlength="50"
                                      placeholder="Descrivi il tuo server, le sue caratteristiche e cosa lo rende speciale..."><?php echo htmlspecialchars($server['descrizione']); ?></textarea>
                            <small class="form-text text-muted">Minimo 50 caratteri</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sito_web">Sito Web</label>
                                    <input type="url" class="form-control" id="sito_web" name="sito_web" 
                                           value="<?php echo htmlspecialchars($server['sito_web']); ?>"
                                           placeholder="https://mioserver.com">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="discord">Discord</label>
                                    <input type="text" class="form-control" id="discord" name="discord" 
                                           value="<?php echo htmlspecialchars($server['discord']); ?>"
                                           placeholder="https://discord.gg/...">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tebex">Tebex Store</label>
                                    <input type="url" class="form-control" id="tebex" name="tebex" 
                                           value="<?php echo htmlspecialchars($server['tebex']); ?>"
                                           placeholder="https://mioserver.tebex.io">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Salva Modifiche
                            </button>
                            <a href="profile.php" class="btn btn-secondary">
                                <i class="bi bi-x-lg"></i> Annulla
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.edit-server-container {
    min-height: 80vh;
    display: flex;
    align-items: center;
}

.edit-server-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.card-header {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.card-header h2 {
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.edit-server-form .form-group {
    margin-bottom: 1.5rem;
}

.edit-server-form label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
    font-weight: 500;
}

.edit-server-form .form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--primary-bg);
    color: var(--text-primary);
    transition: all 0.3s ease;
}

.edit-server-form .form-control:focus {
    outline: none;
    border-color: var(--accent-purple);
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
}

.edit-server-form textarea.form-control {
    resize: vertical;
    min-height: 120px;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color);
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: var(--gradient-primary);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: var(--primary-bg);
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
}

.btn-secondary:hover {
    background: var(--hover-bg);
    color: var(--text-primary);
    border-color: var(--accent-purple);
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #dc2626;
}

.alert ul {
    margin: 0;
    padding-left: 1.5rem;
}

@media (max-width: 768px) {
    .edit-server-card {
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php include 'footer.php'; ?>