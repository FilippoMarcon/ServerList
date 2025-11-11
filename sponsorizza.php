<?php
/**
 * Pagina Sponsorizzazione Server
 * Server Sponsorship Page
 */

require_once 'config.php';

$page_title = "Sponsorizza il tuo Server";
$page_description = "Sponsorizza il tuo server Minecraft e ottieni maggiore visibilità nella lista server";



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

                <!-- Prezzi e Informazioni -->
                <div class="pricing-section">
                    <h2 class="section-title">
                        <i class="bi bi-tag-fill"></i> Prezzi
                    </h2>
                    <div class="pricing-card-large">
                        <div class="price-header">
                            <div class="price-amount">€5.00</div>
                            <div class="price-period">per giorno</div>
                        </div>
                        <div class="price-details">
                            <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                                Scegli tu la durata della sponsorizzazione, da 1 a 30 giorni
                            </p>
                            <div class="price-examples">
                                <div class="example-item">
                                    <span class="example-days">7 giorni</span>
                                    <span class="example-price">€35.00</span>
                                </div>
                                <div class="example-item">
                                    <span class="example-days">14 giorni</span>
                                    <span class="example-price">€70.00</span>
                                </div>
                                <div class="example-item">
                                    <span class="example-days">30 giorni</span>
                                    <span class="example-price">€150.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Call to Action -->
                <div class="cta-section text-center">
                    <?php if (!isLoggedIn()): ?>
                        <div class="login-prompt">
                            <i class="bi bi-lock-fill" style="font-size: 3rem; color: var(--accent-purple); margin-bottom: 1rem;"></i>
                            <h3>Accedi per Continuare</h3>
                            <p>Devi essere loggato per sponsorizzare un server</p>
                            <a href="/login" class="btn btn-hero">
                                <i class="bi bi-box-arrow-in-right"></i> Accedi
                            </a>
                        </div>
                    <?php else: ?>
                        <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Pronto a Sponsorizzare?</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                            Clicca sul pulsante qui sotto per scegliere il tuo server e la durata
                        </p>
                        <a href="/sponsor-payment" class="btn btn-lg" style="background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); color: #000; font-weight: 700; font-size: 1.2rem; padding: 1.25rem 2.5rem; border-radius: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.75rem; box-shadow: 0 8px 25px rgba(255, 215, 0, 0.5); transition: all 0.3s ease; border: none;">
                            <i class="bi bi-rocket-takeoff-fill"></i> Inizia Ora
                        </a>
                    <?php endif; ?>
                </div>
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

.pricing-section {
    margin: 3rem 0;
}

.pricing-card-large {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, transparent 100%);
    border: 2px solid rgba(255, 215, 0, 0.3);
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
}

.price-header {
    margin-bottom: 1.5rem;
}

.price-amount {
    font-size: 3.5rem;
    font-weight: 800;
    color: #FFD700;
    line-height: 1;
}

.price-period {
    font-size: 1.2rem;
    color: var(--text-secondary);
    margin-top: 0.5rem;
}

.price-examples {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1.5rem;
}

.example-item {
    background: var(--primary-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.example-days {
    color: var(--text-primary);
    font-weight: 600;
}

.example-price {
    color: #FFD700;
    font-size: 1.3rem;
    font-weight: 700;
}

.cta-section {
    margin: 3rem 0;
    padding: 3rem 2rem;
    background: var(--primary-bg);
    border-radius: 16px;
    border: 1px solid var(--border-color);
}

.login-prompt {
    padding: 2rem 0;
}

.login-prompt h3 {
    color: var(--text-primary);
    margin-bottom: 1rem;
}

.login-prompt p {
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

</style>

<?php include 'footer.php'; ?>
