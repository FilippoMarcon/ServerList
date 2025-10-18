<?php
require_once 'config.php';
include 'header.php';
?>

<style>
    .server-ip-display {
        background: var(--primary-bg);
        color: var(--text-primary);
        border: 1px solid var(--border-color);
        padding: 0.5rem 0.75rem;
        border-radius: 10px;
        font-weight: 700;
        letter-spacing: 0.2px;
    }
    #copyVerifyIP {
        border-color: var(--border-color);
        color: var(--text-secondary);
    }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card" style="background: var(--primary-bg); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden;">
                <div class="card-header" style="background: var(--gradient-primary); color: white;">
                    <h2 class="m-0" style="font-weight: 700;">
                        <i class="bi bi-person-badge"></i> Verifica Nickname Minecraft
                    </h2>
                </div>
                <div class="card-body" style="color: var(--text-secondary);">
                    <p style="font-size: 1rem;">Per verificare il tuo nickname, entra in Minecraft Java Edition e collegati all'indirizzo <strong>verifica.blocksy.it</strong>. Segui le istruzioni in gioco per completare la verifica.</p>

                    <div class="row g-3 my-3">
                        <div class="col-md-6">
                            <div class="p-3" style="background: var(--primary-bg); border: 1px solid var(--border-color); border-radius: 12px;">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-minecraft" style="color: var(--accent-purple);"></i>
                                    <strong>Requisiti</strong>
                                </div>
                                <p class="mb-0 mt-2" style="font-size: 0.95rem;">
                                    La verifica è disponibile solo per <strong>Minecraft Java Edition</strong> e richiede un <strong>account Premium</strong>.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3" style="background: var(--primary-bg); border: 1px solid var(--border-color); border-radius: 12px;">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-exclamation-triangle" style="color: var(--accent-blue);"></i>
                                    <strong>Utenti SP (non premium)</strong>
                                </div>
                                <p class="mb-0 mt-2" style="font-size: 0.95rem;">
                                    Se non possiedi un account premium, apri un ticket su <a href="https://discord.blocksy.it" target="_blank" rel="noopener">discord.blocksy.it</a> per assistenza.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-server" style="color: var(--accent-purple);"></i>
                            <strong>Indirizzo di Verifica</strong>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <code class="server-ip-display">verifica.blocksy.it</code>
                            <button class="btn btn-sm btn-outline-secondary" id="copyVerifyIP" style="border-color: var(--border-color);">
                                <i class="bi bi-clipboard"></i> Copia
                            </button>
                        </div>
                        <small class="text-secondary d-block mt-2">Collegati al server e segui le istruzioni per completare la verifica.</small>
                    </div>

                    <div class="mt-4">
                        <a class="btn btn-primary" href="https://discord.blocksy.it" target="_blank" rel="noopener">
                            <i class="bi bi-discord"></i> Apri Discord
                        </a>
                        <a class="btn btn-outline-secondary ms-2" href="https://telegram.blocksy.it" target="_blank" rel="noopener" style="border-color: var(--border-color);">
                            <i class="bi bi-telegram"></i> Apri Telegram
                        </a>
                    </div>

                    <!-- Carosello dimostrativo -->
                    <div id="verifyCarousel" class="carousel slide mt-4" data-bs-ride="carousel">
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#verifyCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                            <button type="button" data-bs-target="#verifyCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                        </div>
                        <div class="carousel-inner" style="border-radius:12px; overflow:hidden;">
                            <div class="carousel-item active">
                                <img src="add-server.png" class="d-block w-100" alt="Dimostrazione: Aggiungere il server alla lista">
                                <div class="carousel-caption d-none d-md-block">
                                    <h5>Come aggiungere il server</h5>
                                    <p>Clicca su Aggiungi Server e compila i campi richiesti.</p>
                                </div>
                            </div>
                            <div class="carousel-item">
                                <img src="direct-access.png" class="d-block w-100" alt="Dimostrazione: Entrare nel server">
                                <div class="carousel-caption d-none d-md-block">
                                    <h5>Come entrare velocemente nel server</h5>
                                    <p>Collegati a <strong>verifica.blocksy.it</strong> e segui le istruzioni.</p>
                                </div>
                            </div>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#verifyCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Precedente</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#verifyCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Successivo</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function copyTextFallback(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        try { document.execCommand('copy'); } catch(e) {}
        document.body.removeChild(ta);
    }
    
    document.getElementById('copyVerifyIP')?.addEventListener('click', function() {
        const text = 'verifica.blocksy.it';
        const btn = document.getElementById('copyVerifyIP');
        const original = btn.innerHTML;
        
        function showCopied() {
            btn.innerHTML = '<i class="bi bi-check2"></i> Copiato!';
            setTimeout(() => { btn.innerHTML = original; }, 1500);
        }
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(showCopied).catch(function(){
                copyTextFallback(text);
                showCopied();
            });
        } else {
            copyTextFallback(text);
            showCopied();
        }
    });
</script>

<?php include 'footer.php'; ?>