<?php
/**
 * Pagina Sponsorizzazione Server
 * Server Sponsorship Page
 */

require_once 'config.php';

$page_title = "Sponsorizza il tuo Server";
$page_description = "Sponsorizza il tuo server Minecraft e ottieni maggiore visibilità nella lista server";

$message = '';
$error = '';

// Gestione messaggi di sessione (per evitare alert di refresh)
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Gestione invio richiesta sponsorizzazione
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_sponsorship') {
    if (!isLoggedIn()) {
        $error = 'Devi essere loggato per richiedere una sponsorizzazione.';
    } elseif (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Sessione scaduta o token CSRF non valido.';
    } else {
        $server_id = (int)($_POST['server_id'] ?? 0);
        $duration = (int)($_POST['duration'] ?? 30);
        $notes = trim($_POST['notes'] ?? '');
        
        if ($server_id <= 0) {
            $error = 'Seleziona un server valido.';
        } else {
            try {
                // Verifica che il server appartenga all'utente
                $stmt = $pdo->prepare("SELECT id, nome FROM sl_servers WHERE id = ? AND owner_id = ? AND is_active = 1");
                $stmt->execute([$server_id, $_SESSION['user_id']]);
                $server = $stmt->fetch();
                
                if (!$server) {
                    $error = 'Server non trovato o non hai i permessi per sponsorizzarlo.';
                } else {
                    // Verifica se esiste già una richiesta pendente
                    $stmt = $pdo->prepare("SELECT id FROM sl_sponsorship_requests WHERE server_id = ? AND status = 'pending'");
                    $stmt->execute([$server_id]);
                    if ($stmt->fetch()) {
                        $error = 'Esiste già una richiesta di sponsorizzazione pendente per questo server.';
                    } else {
                        // Crea la richiesta
                        $stmt = $pdo->prepare("INSERT INTO sl_sponsorship_requests (server_id, user_id, duration_days, notes, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
                        $stmt->execute([$server_id, $_SESSION['user_id'], $duration, $notes]);
                        $_SESSION['success_message'] = 'Richiesta di sponsorizzazione inviata con successo! Un amministratore la esaminerà a breve.';
                        redirect('/sponsorizza-il-tuo-server');
                    }
                }
            } catch (PDOException $e) {
                $error = 'Errore durante l\'invio della richiesta. Riprova più tardi.';
            }
        }
    }
}

// Crea tabella richieste sponsorizzazione se non esiste
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS sl_sponsorship_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL,
        user_id INT NOT NULL,
        duration_days INT DEFAULT 30,
        notes TEXT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at TIMESTAMP NULL,
        processed_by INT NULL,
        admin_notes TEXT,
        FOREIGN KEY (server_id) REFERENCES sl_servers(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES sl_users(id) ON DELETE CASCADE,
        INDEX(status),
        INDEX(server_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    // Tabella già esistente
}

// Ottieni i server dell'utente
$user_servers = [];
if (isLoggedIn()) {
    try {
        $stmt = $pdo->prepare("SELECT id, nome, ip FROM sl_servers WHERE owner_id = ? AND is_active = 1 ORDER BY nome ASC");
        $stmt->execute([$_SESSION['user_id']]);
        $user_servers = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Errore nel caricamento server
    }
}

include 'header.php';
?>

<div class="container" style="margin-top: 2rem; margin-bottom: 3rem;">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="plugin-page">
                <div class="page-header text-center mb-5">
                    <div class="plugin-icon mb-3">
                        <i class="bi bi-star-fill" style="font-size: 4rem; color: #FFD700;"></i>
                    </div>
                    <h1 class="page-title">Sponsorizza il tuo Server</h1>
                    <p class="page-subtitle">Ottieni maggiore visibilità e raggiungi più giocatori</p>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Come Funziona -->
                <div class="info-section mb-5">
                    <h2 class="section-title">
                        <i class="bi bi-gear"></i> Come Funziona la Sponsorizzazione?
                    </h2>
                    <div class="info-card">
                        <p>
                            Quando sponsorizzi il tuo server su <?php echo SITE_NAME; ?>, ottieni una <strong>visibilità premium</strong> che ti permette di raggiungere molti più giocatori.
                        </p>
                        <h4 class="mt-3 mb-2" style="color: var(--text-primary); font-weight: 700;">Sistema di Rotazione</h4>
                        <p>
                            I server sponsorizzati vengono mostrati in <strong>due card speciali</strong> nella parte alta della homepage, prima di tutti gli altri server. Ad ogni refresh della pagina, i server sponsor vengono ruotati casualmente, garantendo che tutti i server sponsor ricevano visibilità equa nel tempo.
                        </p>
                        <h4 class="mt-3 mb-2" style="color: var(--text-primary); font-weight: 700;">Badge Distintivo</h4>
                        <p>
                            Il tuo server avrà un <strong>badge "SPONSOR"</strong> dorato che lo distingue dagli altri server nella lista, attirando immediatamente l'attenzione dei visitatori.
                        </p>
                        <h4 class="mt-3 mb-2" style="color: var(--text-primary); font-weight: 700;">Durata Flessibile</h4>
                        <p>
                            Puoi scegliere la durata della sponsorizzazione in base alle tue esigenze: 30, 60 o 90 giorni. Durante questo periodo, il tuo server rimarrà sempre in evidenza.
                        </p>
                    </div>
                </div>

                <!-- Vantaggi -->
                <div class="info-section mb-5">
                    <h2 class="section-title">
                        <i class="bi bi-star"></i> Vantaggi della Sponsorizzazione
                    </h2>
                    <div class="benefits-grid">
                        <div class="benefit-card">
                            <i class="bi bi-eye-fill"></i>
                            <h4>Maggiore Visibilità</h4>
                            <p>Due card in evidenza nella homepage con rotazione ad ogni refresh</p>
                        </div>
                        <div class="benefit-card">
                            <i class="bi bi-graph-up-arrow"></i>
                            <h4>Più Giocatori</h4>
                            <p>Attira nuovi giocatori e fai crescere la tua community</p>
                        </div>
                        <div class="benefit-card">
                            <i class="bi bi-star-fill"></i>
                            <h4>Badge Sponsor</h4>
                            <p>Badge dorato distintivo che evidenzia il tuo server</p>
                        </div>
                    </div>
                </div>

                <?php if (!isLoggedIn()): ?>
                    <div class="login-prompt text-center">
                        <i class="bi bi-lock-fill" style="font-size: 3rem; color: var(--accent-purple); margin-bottom: 1rem;"></i>
                        <h3>Accedi per Continuare</h3>
                        <p>Devi essere loggato per richiedere una sponsorizzazione</p>
                        <a href="/login" class="btn btn-hero">
                            <i class="bi bi-box-arrow-in-right"></i> Accedi
                        </a>
                    </div>
                <?php elseif (empty($user_servers)): ?>
                    <div class="no-servers-prompt text-center">
                        <i class="bi bi-server" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                        <h3>Nessun Server Disponibile</h3>
                        <p>Non hai ancora aggiunto un server. Aggiungi il tuo server per poterlo sponsorizzare.</p>
                        <a href="/profile" class="btn btn-hero">
                            <i class="bi bi-plus-circle"></i> Aggiungi Server
                        </a>
                    </div>
                <?php else: ?>
                    <div class="request-form-section">
                        <h3 class="section-title">Richiedi Sponsorizzazione</h3>
                        <form method="POST" action="/sponsorizza-il-tuo-server" class="sponsorship-form">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="request_sponsorship">
                            
                            <div class="form-group mb-3">
                                <label for="server_id" class="form-label">Seleziona Server</label>
                                <select name="server_id" id="server_id" class="form-control" required>
                                    <option value="">-- Seleziona un server --</option>
                                    <?php foreach ($user_servers as $srv): ?>
                                        <option value="<?php echo $srv['id']; ?>">
                                            <?php echo htmlspecialchars($srv['nome']); ?> (<?php echo htmlspecialchars($srv['ip']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label for="duration" class="form-label">Durata Sponsorizzazione</label>
                                <div class="input-group">
                                    <input type="number" name="duration" id="duration" class="form-control" min="1" max="30" value="7" required style="color: var(--text-primary); font-size: 1rem;">
                                    <span class="input-group-text" style="background: var(--primary-bg); border: 1px solid var(--border-color); color: var(--text-secondary);">giorni</span>
                                </div>
                                <small class="form-text" style="display: block; margin-top: 0.5rem; color: var(--text-secondary); opacity: 0.9;">Inserisci il numero di giorni (minimo 1, massimo 30)</small>
                                <div class="pricing-info mt-2" style="background: var(--primary-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem;">
                                    <h6 style="color: var(--text-primary); margin-bottom: 0.5rem;"><i class="bi bi-calculator"></i> Calcolo Prezzo</h6>
                                    <div class="price-display" style="font-size: 1.2rem; font-weight: 700; color: var(--accent-purple);">
                                        €<span id="totalPrice">5.00</span>
                                    </div>
                                    <small style="color: var(--text-secondary);">€5.00 per giorno</small>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="notes" class="form-label">Note Aggiuntive (opzionale)</label>
                                <textarea name="notes" id="notes" class="form-control" rows="4" placeholder="Aggiungi eventuali note o richieste speciali..." style="color: var(--text-primary); font-size: 1rem;"></textarea>
                            </div>

                            <div class="info-box mb-3">
                                <i class="bi bi-info-circle"></i>
                                <div>
                                    <strong>Nota:</strong> Dopo l'invio della richiesta, verrai reindirizzato a PayPal per completare il pagamento. La sponsorizzazione si attiverà automaticamente dopo il pagamento confermato.
                                </div>
                            </div>

                            <button type="submit" class="btn btn-hero w-100" style="font-weight: 600; font-size: 1.1rem; color: white;">
                                <i class="bi bi-paypal"></i> Procedi al Pagamento PayPal
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.plugin-page {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 3rem;
    border: 1px solid var(--border-color);
}

.page-title {
    font-size: 3rem;
    font-weight: 800;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.page-subtitle {
    font-size: 1.2rem;
    color: var(--text-secondary);
}

.plugin-icon {
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.section-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-title i {
    color: var(--accent-purple);
}

.info-card {
    background: var(--primary-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 2rem;
    line-height: 1.8;
}

.info-card p {
    margin-bottom: 1rem;
    color: var(--text-secondary);
}

.info-card p:last-child {
    margin-bottom: 0;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.benefit-card {
    background: var(--primary-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
}

.benefit-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.benefit-card i {
    font-size: 2.5rem;
    color: var(--accent-purple);
    margin-bottom: 1rem;
}

.benefit-card h4 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.benefit-card p {
    color: var(--text-secondary);
    margin: 0;
    font-size: 0.9rem;
}

.sponsorship-form .form-label {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.sponsorship-form .form-control {
    background: var(--primary-bg);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    border-radius: 8px;
    padding: 0.75rem;
    font-size: 1rem;
}

.sponsorship-form .form-control::placeholder {
    color: var(--text-muted);
    opacity: 0.7;
}

.sponsorship-form .form-control:focus {
    border-color: var(--accent-purple);
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    outline: none;
    background: var(--card-bg);
}

.info-box {
    background: rgba(124, 58, 237, 0.1);
    border: 1px solid var(--accent-purple);
    border-radius: 8px;
    padding: 1rem;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.info-box i {
    font-size: 1.5rem;
    color: var(--accent-purple);
    flex-shrink: 0;
}

.info-box div {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.login-prompt, .no-servers-prompt {
    padding: 3rem 2rem;
    background: var(--primary-bg);
    border-radius: 12px;
    border: 1px solid var(--border-color);
}

.login-prompt h3, .no-servers-prompt h3 {
    color: var(--text-primary);
    margin-bottom: 1rem;
}

.login-prompt p, .no-servers-prompt p {
    color: var(--text-secondary);
    margin-bottom: 1.5rem;
}

@media (max-width: 768px) {
    .plugin-page {
        padding: 1.5rem;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .benefits-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const durationInput = document.getElementById('duration');
    const totalPriceElement = document.getElementById('totalPrice');
    const pricePerDay = 5.00;
    
    function updatePrice() {
        const days = parseInt(durationInput.value) || 1;
        const total = (days * pricePerDay).toFixed(2);
        totalPriceElement.textContent = total;
    }
    
    if (durationInput && totalPriceElement) {
        durationInput.addEventListener('input', updatePrice);
        updatePrice(); // Calcolo iniziale
    }
});
</script>
</style>

<?php include 'footer.php'; ?>
