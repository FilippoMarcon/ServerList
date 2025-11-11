<?php
/**
 * Pagina Pagamento Sponsorizzazione con PayPal
 */

require_once 'config.php';

if (!isLoggedIn()) {
    redirect('/login');
}

$user_id = $_SESSION['user_id'];

// Recupera i server dell'utente
$stmt = $pdo->prepare("SELECT id, nome, ip FROM sl_servers WHERE owner_id = ? AND is_active = 1");
$stmt->execute([$user_id]);
$user_servers = $stmt->fetchAll();

$page_title = "Sponsorizza il tuo Server";
include 'header.php';
?>

<style>
.sponsor-payment-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
}

.sponsor-payment-card {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 2rem;
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.sponsor-payment-header {
    text-align: center;
    margin-bottom: 2rem;
}

.sponsor-payment-header h1 {
    color: var(--text-primary);
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.sponsor-payment-header p {
    color: var(--text-secondary);
    font-size: 1.1rem;
}

.input-group {
    display: flex;
    align-items: stretch;
}

.input-group input {
    flex: 1;
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
}

.input-group-text {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
    border-left: none;
}

.form-section {
    margin-bottom: 2rem;
}

.form-section h3 {
    color: var(--text-primary);
    font-size: 1.2rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    color: var(--text-primary);
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.form-group select,
.form-group input {
    width: 100%;
    padding: 0.75rem;
    background: var(--primary-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 1rem;
}

.benefits-list {
    background: var(--primary-bg);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.benefits-list h3 {
    color: var(--text-primary);
    margin-bottom: 1rem;
}

.benefit-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0;
    color: var(--text-secondary);
}

.benefit-item i {
    color: #10b981;
    font-size: 1.2rem;
}

.paypal-button-container {
    text-align: center;
    margin-top: 2rem;
    min-height: 100px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

#paypal-button-container {
    max-width: 400px;
    width: 100%;
}

.payment-info {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 2rem;
}

.payment-info p {
    color: var(--text-primary);
    margin: 0.5rem 0;
    font-size: 0.9rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    display: inline-block;
    transition: transform 0.2s, box-shadow 0.2s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    color: white;
}
</style>

<div class="sponsor-payment-container">
    <div class="sponsor-payment-card">
        <div class="sponsor-payment-header">
            <h1><i class="bi bi-star-fill" style="color: #FFD700;"></i> Sponsorizza il tuo Server</h1>
            <p>Aumenta la visibilità del tuo server con una sponsorizzazione</p>
        </div>

        <?php if (empty($user_servers)): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> Non hai server attivi da sponsorizzare.
                <a href="/profile?section=new-server">Aggiungi un server</a>
            </div>
        <?php else: ?>

        <div class="benefits-list">
            <h3><i class="bi bi-check-circle-fill" style="color: #10b981;"></i> Vantaggi della Sponsorizzazione</h3>
            <div class="benefit-item">
                <i class="bi bi-star-fill"></i>
                <span>Posizione in evidenza nella homepage</span>
            </div>
            <div class="benefit-item">
                <i class="bi bi-eye-fill"></i>
                <span>Maggiore visibilità e più player</span>
            </div>
            <div class="benefit-item">
                <i class="bi bi-trophy-fill"></i>
                <span>Badge "SPONSOR" dorato sul tuo server</span>
            </div>
            <div class="benefit-item">
                <i class="bi bi-graph-up-arrow"></i>
                <span>Priorità nei risultati di ricerca</span>
            </div>
        </div>

        <form id="sponsorForm" method="POST" action="paypal_process.php">
            <?php echo csrfInput(); ?>
            
            <div class="form-section">
                <h3>Seleziona il Server</h3>
                <div class="form-group">
                    <label for="server_id">Server da Sponsorizzare</label>
                    <select name="server_id" id="server_id" required>
                        <option value="">-- Seleziona un server --</option>
                        <?php foreach ($user_servers as $server): ?>
                            <option value="<?= $server['id'] ?>"><?= htmlspecialchars($server['nome']) ?> (<?= htmlspecialchars($server['ip']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-section">
                <h3>Scegli la Durata</h3>
                <div class="form-group">
                    <label for="duration">Numero di Giorni</label>
                    <div class="input-group">
                        <input type="number" name="duration" id="duration" class="form-control" min="1" max="30" value="7" required style="color: var(--text-primary); font-size: 1rem;">
                        <span class="input-group-text" style="background: var(--primary-bg); border: 1px solid var(--border-color); color: var(--text-secondary);">giorni</span>
                    </div>
                    <small class="form-text" style="display: block; margin-top: 0.5rem; color: var(--text-secondary); opacity: 0.9;">Inserisci il numero di giorni (minimo 1, massimo 30)</small>
                </div>
                
                <div class="pricing-info mt-3" style="background: var(--primary-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; text-align: center;">
                    <h6 style="color: var(--text-primary); margin-bottom: 0.5rem;"><i class="bi bi-calculator"></i> Calcolo Prezzo</h6>
                    <div class="price-display" style="font-size: 2.5rem; font-weight: 700; color: #FFD700; margin: 1rem 0;">
                        €<span id="totalPrice">35.00</span>
                    </div>
                    <small style="color: var(--text-secondary); font-size: 0.95rem;">€5.00 per giorno × <span id="daysDisplay">7</span> giorni</small>
                </div>
                
                <input type="hidden" name="plan_days" id="plan_days" required>
                <input type="hidden" name="plan_price" id="plan_price" required>
            </div>

            <div class="payment-info">
                <p><strong><i class="bi bi-info-circle"></i> Informazioni Pagamento</strong></p>
                <p>• Pagamento sicuro tramite PayPal</p>
                <p>• La sponsorizzazione si attiva automaticamente dopo il pagamento</p>
                <p>• Riceverai una conferma via email</p>
            </div>

            <div class="paypal-button-container">
                <div id="paypal-button-container"></div>
                <p id="paypal-instructions" style="text-align: center; color: var(--text-secondary); margin-top: 1rem;">
                    Seleziona un server e un piano per procedere con il pagamento
                </p>
            </div>
        </form>

        <?php endif; ?>
    </div>
</div>

<script src="https://www.paypal.com/sdk/js?client-id=Abuq7FRL7WQPaqKz2fOGd0S1169QgHzbZuv7hA_s5VdD2PtxPOLUsf1gN7KMCSI2w7iThh_p8E9XPDmY&currency=EUR"></script>
<script>
const pricePerDay = 5.00;

// Calcola prezzo in base ai giorni
function updatePrice() {
    const durationInput = document.getElementById('duration');
    const totalPriceElement = document.getElementById('totalPrice');
    const daysDisplayElement = document.getElementById('daysDisplay');
    const planDaysInput = document.getElementById('plan_days');
    const planPriceInput = document.getElementById('plan_price');
    
    const days = parseInt(durationInput.value) || 1;
    const total = (days * pricePerDay).toFixed(2);
    
    totalPriceElement.textContent = total;
    daysDisplayElement.textContent = days;
    planDaysInput.value = days;
    planPriceInput.value = total;
    
    // Reinizializza PayPal button
    initPayPalButton();
}

function initPayPalButton() {
    const price = document.getElementById('plan_price').value;
    const serverId = document.getElementById('server_id').value;
    const instructions = document.getElementById('paypal-instructions');
    
    if (!price || !serverId) {
        document.getElementById('paypal-button-container').innerHTML = '';
        if (instructions) {
            instructions.style.display = 'block';
            if (!serverId) {
                instructions.textContent = 'Seleziona un server per continuare';
            } else if (!price) {
                instructions.textContent = 'Inserisci i giorni per continuare';
            }
        }
        return;
    }
    
    // Nascondi istruzioni
    if (instructions) {
        instructions.style.display = 'none';
    }
    
    // Pulisci container
    document.getElementById('paypal-button-container').innerHTML = '';
    
    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: price,
                        currency_code: 'EUR'
                    },
                    description: 'Sponsorizzazione Server Minecraft - ' + document.getElementById('plan_days').value + ' giorni'
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                // Invia dati al server per attivare la sponsorizzazione
                const formData = new FormData(document.getElementById('sponsorForm'));
                formData.append('paypal_order_id', data.orderID);
                formData.append('paypal_payer_id', details.payer.payer_id);
                
                fetch('paypal_ipn.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.error || 'Errore del server');
                        });
                    }
                    return response.json();
                })
                .then(result => {
                    if (result.success) {
                        // Mostra messaggio di successo
                        document.querySelector('.sponsor-payment-card').innerHTML = `
                            <div style="text-align: center; padding: 3rem;">
                                <div style="font-size: 4rem; color: #10b981; margin-bottom: 1rem;">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <h2 style="color: var(--text-primary); margin-bottom: 1rem;">Pagamento Completato!</h2>
                                <p style="color: var(--text-secondary); font-size: 1.1rem; margin-bottom: 0.5rem;">
                                    La sponsorizzazione per <strong>${result.server_name}</strong> è ora attiva
                                </p>
                                <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                                    Scadenza: ${new Date(result.expires_at).toLocaleString('it-IT')}
                                </p>
                                <a href="/profile" class="btn btn-primary" style="padding: 0.75rem 2rem; font-size: 1.1rem;">
                                    <i class="bi bi-arrow-left"></i> Vai al Profilo
                                </a>
                            </div>
                        `;
                    } else {
                        console.error('Errore:', result);
                        // Mostra errore nella pagina
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'alert alert-danger';
                        errorDiv.style.marginTop = '1rem';
                        errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Errore: ' + (result.error || 'Errore sconosciuto');
                        document.querySelector('.paypal-button-container').prepend(errorDiv);
                    }
                })
                .catch(error => {
                    console.error('Errore completo:', error);
                    // Mostra errore nella pagina
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'alert alert-danger';
                    errorDiv.style.marginTop = '1rem';
                    errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Errore nell\'attivazione: ' + error.message;
                    document.querySelector('.paypal-button-container').prepend(errorDiv);
                });
            });
        },
        onError: function(err) {
            alert('Errore durante il pagamento. Riprova.');
            console.error(err);
        }
    }).render('#paypal-button-container');
}

// Event listeners
document.getElementById('duration').addEventListener('input', updatePrice);
document.getElementById('server_id').addEventListener('change', initPayPalButton);

// Calcolo iniziale
updatePrice();
</script>

<?php include 'footer.php'; ?>
