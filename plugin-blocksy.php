<?php
/**
 * Pagina Plugin Blocksy
 * Blocksy Plugin Information Page
 */

require_once 'config.php';

$page_title = "Plugin Blocksy";
$page_description = "Installa il plugin Blocksy per ricevere automaticamente i reward quando voti per i server";

include 'header.php';
?>

<div class="container" style="margin-top: 2rem; margin-bottom: 3rem;">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="plugin-page">
                <div class="page-header text-center mb-5">
                    <div class="plugin-icon mb-3">
                        <i class="bi bi-plugin" style="font-size: 4rem; color: var(--accent-purple);"></i>
                    </div>
                    <h1 class="page-title">Plugin Blocksy</h1>
                    <p class="page-subtitle">Sistema automatico di reward per i voti</p>
                </div>

                <!-- Cos'è Blocksy -->
                <div class="info-section mb-5">
                    <h2 class="section-title">
                        <i class="bi bi-info-circle"></i> Cos'è Blocksy?
                    </h2>
                    <div class="info-card">
                        <p>
                            <strong>Blocksy</strong> è un plugin avanzato per server Minecraft (Spigot/Paper) che automatizza completamente la gestione dei voti. 
                            Utilizza un sistema di <strong>polling API</strong> per recuperare i voti dal sito e si integra con <strong>NuVotifier</strong> per la massima compatibilità.
                        </p>
                        <p>
                            Quando un giocatore vota per il tuo server su <?php echo SITE_NAME; ?>, il plugin:
                        </p>
                        <ul>
                            <li>Rileva automaticamente il voto tramite API (nessuna configurazione porte necessaria)</li>
                            <li>Crea un evento Votifier standard che tutti i plugin compatibili possono ricevere</li>
                            <li>Esegue i comandi reward configurati dall'owner del server</li>
                            <li>Consegna i premi istantaneamente al giocatore (se online) o al prossimo login</li>
                        </ul>
                        <p class="mb-0">
                            <strong>Zero configurazione di rete richiesta</strong> - funziona ovunque, anche dietro NAT o proxy!
                        </p>
                    </div>
                </div>

                <!-- Come Funziona -->
                <div class="info-section mb-5">
                    <h2 class="section-title">
                        <i class="bi bi-gear"></i> Come Funziona il Sistema?
                    </h2>
                    
                    <div class="info-card mb-4">
                        <h4><i class="bi bi-diagram-3"></i> Architettura del Sistema</h4>
                        <p>
                            Blocksy utilizza un sistema di <strong>polling API</strong> moderno e affidabile che elimina la necessità di configurare porte o firewall. 
                            Il plugin si integra perfettamente con <strong>NuVotifier</strong>, rendendolo compatibile con tutti i plugin che supportano Votifier.
                        </p>
                        
                        <div class="alert-card alert-success mt-3">
                            <i class="bi bi-check-circle-fill"></i>
                            <div>
                                <strong>Vantaggi:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>✅ Nessuna configurazione porte o firewall</li>
                                    <li>✅ Funziona dietro NAT e proxy</li>
                                    <li>✅ Compatibile con tutti i plugin Votifier</li>
                                    <li>✅ Sistema affidabile al 100%</li>
                                    <li>✅ Facile da configurare</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="mb-3"><i class="bi bi-arrow-repeat"></i> Flusso del Voto</h4>
                    <div class="steps-grid">
                        <div class="step-card">
                            <div class="step-number">1</div>
                            <h3>Voti sul Sito</h3>
                            <p>Il voto viene salvato nel database con stato "pendente"</p>
                        </div>
                        <div class="step-card">
                            <div class="step-number">2</div>
                            <h3>Plugin Fa Polling</h3>
                            <p>Ogni 15 secondi il plugin controlla se ci sono nuovi voti tramite API</p>
                        </div>
                        <div class="step-card">
                            <div class="step-number">3</div>
                            <h3>Evento Votifier</h3>
                            <p>Il plugin crea un evento Votifier che viene ricevuto da tutti i plugin compatibili</p>
                        </div>
                        <div class="step-card">
                            <div class="step-number">4</div>
                            <h3>Reward Automatici</h3>
                            <p>I comandi configurati vengono eseguiti e ricevi i premi istantaneamente</p>
                        </div>
                    </div>
                    
                    <div class="info-card mt-4">
                        <h4><i class="bi bi-key"></i> Sistema API Key</h4>
                        <p>
                            Ogni server ha una <strong>API key univoca</strong> di 64 caratteri che il plugin usa per autenticarsi con il sito. 
                            Questa chiave sostituisce il vecchio sistema di licenze ed è molto più semplice da gestire.
                        </p>
                        <p class="mb-0">
                            <strong>Nota:</strong> Solo gli amministratori del sito possono generare le API keys. 
                            Se sei un owner di un server, contatta un admin per richiedere la tua API key.
                        </p>
                    </div>
                    
                    <div class="info-card mt-3">
                        <h4><i class="bi bi-plugin"></i> Integrazione con Votifier</h4>
                        <p>
                            Blocksy si integra nativamente con <strong>NuVotifier</strong>, il sistema standard per gestire i voti nei server Minecraft. 
                            Questo significa che:
                        </p>
                        <ul class="mb-0">
                            <li>Tutti i plugin che supportano Votifier funzioneranno automaticamente</li>
                            <li>Puoi usare plugin come VotingPlugin, SuperbVote, GAListener insieme a Blocksy</li>
                            <li>Gli eventi di voto vengono gestiti in modo standard e compatibile</li>
                            <li>Non serve configurare Votifier direttamente - Blocksy lo fa per te</li>
                        </ul>
                    </div>
                </div>

                <!-- Server con Più Modalità -->
                <div class="info-section mb-5">
                    <h2 class="section-title">
                        <i class="bi bi-hdd-network"></i> Server con Più Modalità
                    </h2>
                    <div class="alert-card alert-info">
                        <i class="bi bi-info-circle-fill"></i>
                        <div>
                            <strong>Importante:</strong> Se il plugin Blocksy è installato su più server o modalità (es. Survival, Skyblock, Creative), i comandi reward verranno eseguiti nel <strong>primo server dove entri</strong> o dove sei già presente dopo aver votato.
                        </div>
                    </div>
                    <div class="info-card mt-3">
                        <h4>Comandi Personalizzati per Modalità:</h4>
                        <p>
                            Ogni modalità può avere la propria configurazione di comandi reward. Il plugin eseguirà automaticamente i comandi configurati nella modalità specifica dove il giocatore si trova o entra per primo.
                        </p>
                        <div class="alert-card alert-info mt-3 mb-3" style="background: rgba(124, 58, 237, 0.1); border: 1px solid rgba(124, 58, 237, 0.3);">
                            <i class="bi bi-key-fill" style="color: #7c3aed;"></i>
                            <div>
                                <strong>Nota sull'API Key:</strong> L'API key del server deve essere la stessa in tutte le modalità dove installi il plugin Blocksy. Usa la stessa api-key nel config.yml di ogni modalità.
                            </div>
                        </div>
                        <h4 class="mt-3">Esempio:</h4>
                        <ul>
                            <li>Hai votato per "MioServer" che ha 3 modalità: Survival, Skyblock e Creative</li>
                            <li>Tutte e 3 le modalità hanno il plugin Blocksy installato con la <strong>stessa API key</strong> ma comandi diversi:
                                <ul style="margin-top: 0.5rem;">
                                    <li><strong>Survival:</strong> <code>give %player% diamond 5</code></li>
                                    <li><strong>Skyblock:</strong> <code>is level add %player% 100</code></li>
                                    <li><strong>Creative:</strong> <code>eco give %player% 1000</code></li>
                                </ul>
                            </li>
                            <li>Se sei già connesso a Survival, riceverai 5 diamanti</li>
                            <li>Se entri prima in Skyblock, riceverai 100 livelli isola</li>
                            <li>Se entri prima in Creative, riceverai 1000 monete</li>
                        </ul>
                        <p class="mt-2">
                            <strong>In questo modo</strong> puoi offrire reward personalizzati e bilanciati per ogni modalità del tuo network!
                        </p>
                    </div>
                </div>

                <!-- Installazione per Owner -->
                <div class="info-section mb-5">
                    <h2 class="section-title">
                        <i class="bi bi-download"></i> Installazione (Per Owner)
                    </h2>
                    <div class="installation-steps">
                        <div class="install-step">
                            <div class="install-step-header">
                                <span class="install-step-number">1</span>
                                <h3>Registra il tuo Server</h3>
                            </div>
                            <p>Registrati su <?php echo SITE_NAME; ?> e aggiungi il tuo server dalla dashboard. Attendi l'approvazione degli amministratori.</p>
                        </div>

                        <div class="install-step">
                            <div class="install-step-header">
                                <span class="install-step-number">2</span>
                                <h3>Richiedi l'API Key</h3>
                            </div>
                            <p>Una volta approvato il server, contatta un amministratore per richiedere l'API key del tuo server. Gli admin la genereranno tramite il pannello di controllo.</p>
                        </div>

                        <div class="install-step">
                            <div class="install-step-header">
                                <span class="install-step-number">3</span>
                                <h3>Scarica il Plugin</h3>
                            </div>
                            <p>Scarica il plugin Blocksy (.jar) direttamente da questa pagina o dal tuo profilo.</p>
                        </div>

                        <div class="install-step">
                            <div class="install-step-header">
                                <span class="install-step-number">4</span>
                                <h3>Installa sul Server</h3>
                            </div>
                            <p>Copia il file <code>Blocksy.jar</code> nella cartella <code>plugins</code> del tuo server Minecraft e riavvia il server per generare il file di configurazione.</p>
                        </div>

                        <div class="install-step">
                            <div class="install-step-header">
                                <span class="install-step-number">5</span>
                                <h3>Configura il Plugin</h3>
                            </div>
                            <p>Ricevi l'API key dall'amministratore e inseriscila nel file di configurazione insieme ai comandi reward:</p>
                            <div class="code-block">
                                <pre><code># plugins/Blocksy/config.yml
api-key: "TUA-API-KEY-QUI"
check-interval: 15
commands:
  - "give %player% diamond 5"
  - "eco give %player% 1000"
  - "broadcast %player% ha votato per il server!"</code></pre>
                            </div>
                        </div>

                        <div class="install-step">
                            <div class="install-step-header">
                                <span class="install-step-number">6</span>
                                <h3>Riavvia e Testa</h3>
                            </div>
                            <p>Riavvia il server Minecraft per applicare le modifiche. Il plugin è ora attivo e pronto a distribuire reward automaticamente!</p>
                        </div>
                    </div>
                </div>

                <!-- Vantaggi -->
                <div class="info-section mb-5">
                    <h2 class="section-title">
                        <i class="bi bi-star"></i> Vantaggi
                    </h2>
                    <div class="benefits-grid">
                        <div class="benefit-card">
                            <i class="bi bi-lightning-fill"></i>
                            <h4>Automatico</h4>
                            <p>Nessun codice da inserire manualmente</p>
                        </div>
                        <div class="benefit-card">
                            <i class="bi bi-shield-check"></i>
                            <h4>Sicuro</h4>
                            <p>Sistema verificato e protetto</p>
                        </div>
                        <div class="benefit-card">
                            <i class="bi bi-speedometer2"></i>
                            <h4>Istantaneo</h4>
                            <p>Reward ricevuti immediatamente</p>
                        </div>
                        <div class="benefit-card">
                            <i class="bi bi-gear-fill"></i>
                            <h4>Personalizzabile</h4>
                            <p>Configura i comandi come preferisci</p>
                        </div>
                    </div>
                </div>

                <!-- Comandi Plugin -->
                <div class="info-section mb-5">
                    <h2 class="section-title">
                        <i class="bi bi-terminal"></i> Comandi Plugin
                    </h2>
                    <div class="info-card">
                        <p>Il plugin Blocksy include i seguenti comandi per gli amministratori del server:</p>
                        <div class="code-block">
                            <pre><code># Comandi disponibili (solo per OP/Admin)
/blocksy help                  - Mostra l'elenco dei comandi disponibili
/blocksy debug                 - Mostra informazioni di debug (API key, endpoint, stato)
/blocksy reload                - Ricarica la configurazione del plugin
/blocksy interval &lt;secondi&gt;    - Imposta l'intervallo di controllo voti (10-3600 secondi)

# Alias disponibili
/bl                            - Alias breve per /blocksy
/blocksyadmin                  - Alias alternativo per /blocksy</code></pre>
                        </div>
                        <p class="mt-3"><strong>Nota:</strong> I comandi sono disponibili solo per gli operatori del server o utenti con i permessi <code>blocksy.admin</code>, <code>blocksy.debug</code>, <code>blocksy.reload</code> o <code>blocksy.interval</code>.</p>
                        <p class="mt-2"><strong>Sistema Automatico:</strong> Il plugin controlla automaticamente i voti pendenti e distribuisce le ricompense senza bisogno di comandi manuali. I comandi sono utili solo per configurazione e debug.</p>
                    </div>
                </div>

                <!-- FAQ -->
                <div class="info-section mb-5">
                    <h2 class="section-title">
                        <i class="bi bi-question-circle"></i> Domande Frequenti
                    </h2>
                    <div class="faq-list">
                        <div class="faq-item">
                            <h4>Il plugin è gratuito?</h4>
                            <p>Sì, il plugin Blocksy è gratuito per tutti i server registrati su <?php echo SITE_NAME; ?>.</p>
                        </div>
                        <div class="faq-item">
                            <h4>Funziona con Spigot/Paper/Purpur?</h4>
                            <p>Sì, il plugin è compatibile con tutte le versioni moderne di Spigot, Paper, Purpur e derivati.</p>
                        </div>
                        <div class="faq-item">
                            <h4>Quali versioni di Minecraft sono supportate?</h4>
                            <p>Il plugin supporta Minecraft dalla versione 1.16 in poi. Consigliamo di usare sempre l'ultima versione stabile del server.</p>
                        </div>
                        <div class="faq-item">
                            <h4>Posso personalizzare i reward?</h4>
                            <p>Assolutamente! Puoi configurare qualsiasi comando nel file di configurazione del plugin. Puoi dare item, soldi, permessi, eseguire script personalizzati e molto altro.</p>
                        </div>
                        <div class="faq-item">
                            <h4>Cosa succede se il giocatore è offline?</h4>
                            <p>Il reward viene salvato e consegnato automaticamente quando il giocatore si connette al server. Non si perde nulla!</p>
                        </div>
                        <div class="faq-item">
                            <h4>Devo installare il plugin su tutte le modalità del mio network?</h4>
                            <p>Sì, se vuoi che i reward vengano consegnati su tutte le modalità. Ricorda di usare la stessa API key ma puoi configurare comandi diversi per ogni modalità.</p>
                        </div>
                        <div class="faq-item">
                            <h4>Il plugin funziona con BungeeCord/Velocity?</h4>
                            <p>Sì! Il plugin funziona perfettamente con network BungeeCord e Velocity. Installa il plugin su ogni server del network dove vuoi consegnare reward.</p>
                        </div>
                        <div class="faq-item">
                            <h4>Come faccio a sapere se il plugin funziona?</h4>
                            <p>Dopo l'installazione, controlla la console del server per messaggi di conferma. Puoi anche usare il comando <code>/blocksy debug</code> per verificare lo stato dell'API key e la configurazione.</p>
                        </div>
                        <div class="faq-item">
                            <h4>Il plugin rallenta il server?</h4>
                            <p>No, Blocksy è ottimizzato per avere un impatto minimo sulle performance. Usa chiamate API asincrone e non blocca il thread principale del server.</p>
                        </div>
                        <div class="faq-item">
                            <h4>Posso usare placeholder nei comandi?</h4>
                            <p>Sì! Puoi usare <code>%player%</code> per il nome del giocatore. Se hai PlaceholderAPI installato, puoi usare anche tutti i suoi placeholder.</p>
                        </div>
                        <div class="faq-item">
                            <h4>Cosa succede se la mia API key viene disattivata?</h4>
                            <p>Il plugin smetterà di funzionare e i reward non verranno più consegnati. Contatta gli amministratori di <?php echo SITE_NAME; ?> per risolvere eventuali problemi con l'API key.</p>
                        </div>
                        <div class="faq-item">
                            <h4>Posso vedere quanti reward sono stati consegnati?</h4>
                            <p>Il plugin registra automaticamente tutte le consegne nei log del server. Puoi monitorare l'attività controllando i log della console o usando il comando <code>/blocksy debug</code> per vedere informazioni sul sistema.</p>
                        </div>
                        <div class="faq-item">
                            <h4>Il plugin supporta più server con lo stesso IP ma porte diverse?</h4>
                            <p>Sì, ogni server registrato su <?php echo SITE_NAME; ?> ha la sua API key univoca, indipendentemente dall'IP o dalla porta.</p>
                        </div>
                        <div class="faq-item">
                            <h4>Dove posso trovare supporto se ho problemi?</h4>
                            <p>Contatta gli amministratori di <?php echo SITE_NAME; ?> tramite il forum o i canali di supporto ufficiali. Fornisci sempre i log del server per una diagnosi più rapida.</p>
                        </div>
                    </div>
                </div>

                <!-- CTA -->
                <div class="cta-section text-center">
                    <h3>Sei un Owner?</h3>
                    <p>Installa Blocksy sul tuo server e offri un'esperienza migliore ai tuoi giocatori!</p>
                    <?php if (isLoggedIn()): ?>
                        <a href="/profile?action=servers" class="btn btn-hero btn-lg">
                            <i class="bi bi-gear"></i> Vai alla Dashboard
                        </a>
                    <?php else: ?>
                        <a href="/register" class="btn btn-hero btn-lg">
                            <i class="bi bi-person-plus"></i> Registrati Ora
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

.steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.step-card {
    background: var(--primary-bg);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
}

.step-card:hover {
    transform: translateY(-5px);
    border-color: var(--accent-purple);
    box-shadow: 0 8px 25px rgba(124, 58, 237, 0.3);
}

.step-number {
    width: 60px;
    height: 60px;
    background: var(--gradient-primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0 auto 1rem;
}

.step-card h3 {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.step-card p {
    color: var(--text-secondary);
    margin: 0;
}

.alert-card {
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.alert-info {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.alert-card i {
    font-size: 1.5rem;
    color: #3b82f6;
    flex-shrink: 0;
}

.alert-card div {
    color: var(--text-secondary);
}

.info-card ul {
    margin: 1rem 0 0 0;
    padding-left: 1.5rem;
}

.info-card li {
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
}

.installation-steps {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.install-step {
    background: var(--primary-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
}

.install-step-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.install-step-number {
    width: 40px;
    height: 40px;
    background: var(--accent-purple);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
}

.install-step h3 {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.install-step p {
    color: var(--text-secondary);
    margin: 0;
}

.code-block {
    background: #1a1a2e;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
    overflow-x: auto;
}

.code-block code {
    color: #a8dadc;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    line-height: 1.6;
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

.faq-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.faq-item {
    background: var(--primary-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
}

.faq-item h4 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.75rem;
}

.faq-item p {
    color: var(--text-secondary);
    margin: 0;
}

.cta-section {
    background: var(--gradient-primary);
    border-radius: 12px;
    padding: 3rem 2rem;
    margin-top: 3rem;
}

.cta-section h3 {
    font-size: 2rem;
    font-weight: 800;
    color: white;
    margin-bottom: 1rem;
}

.cta-section p {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .plugin-page {
        padding: 1.5rem;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .steps-grid,
    .benefits-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'footer.php'; ?>
