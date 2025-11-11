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

.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.pricing-card {
    background: var(--primary-bg);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.pricing-card:hover {
    transform: translateY(-4px);
    border-color: var(--accent-purple);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.pricing-card.selected {
    border-color: #FFD700;
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, transparent 100%);
    box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
}

.pricing-card .price {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0.5rem 0;
}

.pricing-card .duration {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.pricing-card .features {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.pricing-card .feature {
    color: var(--text-secondary);
    font-size: 0.85rem;
    margin: 0.5rem 0;
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
                <h3>Scegli il Piano</h3>
                <div class="pricing-grid">
                    <div class="pricing-card" data-plan="7" data-price="5.00">
                        <div class="duration">7 Giorni</div>
                        <div class="price">€5</div>
                        <div class="features">
                            <div class="feature">✓ Sponsorizzazione 7 giorni</div>
                            <div class="feature">✓ Badge dorato</div>
                        </div>
                    </div>
                    <div class="pricing-card" data-plan="30" data-price="15.00">
                        <div class="duration">30 Giorni</div>
                        <div class="price">€15</div>
                        <div class="features">
                            <div class="feature">✓ Sponsorizzazione 30 giorni</div>
                            <div class="feature">✓ Badge dorato</div>
                            <div class="feature">✓ Risparmio 25%</div>
                        </div>
                    </div>
                    <div class="pricing-card" data-plan="90" data-price="35.00">
                        <div class="duration">90 Giorni</div>
                        <div class="price">€35</div>
                        <div class="features">
                            <div class="feature">✓ Sponsorizzazione 90 giorni</div>
                            <div class="feature">✓ Badge dorato</div>
                            <div class="feature">✓ Risparmio 42%</div>
                        </div>
                    </div>
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
            </div>
        </form>

        <?php endif; ?>
    </div>
</div>

<script src="https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=EUR"></script>
<script>
// Gestione selezione piano
document.querySelectorAll('.pricing-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.pricing-card').forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        
        const days = this.dataset.plan;
        const price = this.dataset.price;
        
        document.getElementById('plan_days').value = days;
        document.getElementById('plan_price').value = price;
        
        // Reinizializza PayPal button
        initPayPalButton();
    });
});

function initPayPalButton() {
    const price = document.getElementById('plan_price').value;
    const serverId = document.getElementById('server_id').value;
    
    if (!price || !serverId) {
        return;
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
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert('Pagamento completato! La tua sponsorizzazione è ora attiva.');
                        window.location.href = '/profile';
                    } else {
                        alert('Errore nell\'attivazione della sponsorizzazione. Contatta il supporto.');
                    }
                });
            });
        },
        onError: function(err) {
            alert('Errore durante il pagamento. Riprova.');
            console.error(err);
        }
    }).render('#paypal-button-container');
}

// Reinizializza quando cambia il server
document.getElementById('server_id').addEventListener('change', initPayPalButton);
</script>

<?php include 'footer.php'; ?>
