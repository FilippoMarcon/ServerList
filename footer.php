<?php
/**
 * Footer template per tutte le pagine
 * Footer template for all pages
 */
?>
    </main>
    
    <!-- Footer -->
    <footer class="modern-footer">
        <div class="footer-content">
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="footer-brand">
                            <h3 class="brand-name">
                                <i class="bi bi-boxes"></i> Blocksy
                            </h3>
                            <p class="brand-description">
                                La piattaforma definitiva per scoprire i migliori server Minecraft. 
                                Connetti, gioca, vota e diventa parte della community!
                            </p>
                            <div class="social-links">
                                <a href="#" class="social-link discord">
                                    <i class="bi bi-discord"></i>
                                </a>
                                <a href="#" class="social-link youtube">
                                    <i class="bi bi-youtube"></i>
                                </a>
                                <a href="#" class="social-link twitter">
                                    <i class="bi bi-twitter"></i>
                                </a>
                                <a href="#" class="social-link github">
                                    <i class="bi bi-github"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-6 mb-4">
                        <div class="footer-section">
                            <h5 class="section-title">Navigazione</h5>
                            <ul class="footer-links">
                                <li><a href="/">Lista Server</a></li>
                                <li><a href="/login">Accedi</a></li>
                                <li><a href="/register">Registrati</a></li>
                                <?php if (isLoggedIn() && isAdmin()): ?>
                                <li><a href="/admin">Admin Panel</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="footer-section">
                            <h5 class="section-title">Modalità Popolari</h5>
                            <ul class="footer-links">
                                <li><a href="/?filter=survival">Survival</a></li>
                                <li><a href="/?filter=roleplay">RolePlay</a></li>
                                <li><a href="/?filter=pvp">PvP</a></li>
                                <li><a href="?filter=minigames">MiniGames</a></li>
                                <li><a href="?filter=skyblock">SkyBlock</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="footer-section">
                            <h5 class="section-title">Statistiche Live</h5>
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <div class="stat-number">
                                        <?php
                                        try {
                                            global $pdo;
                                            $stmt = $pdo->query("SELECT COUNT(*) as total FROM sl_servers WHERE is_active = 1");
                                            $result = $stmt->fetch();
                                            echo number_format($result['total']);
                                        } catch (Exception $e) {
                                            echo '0';
                                        }
                                        ?>
                                    </div>
                                    <div class="stat-label">Server Attivi</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number">
                                        <?php
                                        try {
                                            global $pdo;
                                            $stmt = $pdo->query("SELECT COUNT(*) as total FROM sl_users");
                                            $result = $stmt->fetch();
                                            echo number_format($result['total']);
                                        } catch (Exception $e) {
                                            echo '0';
                                        }
                                        ?>
                                    </div>
                                    <div class="stat-label">Utenti</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number">
                                        <?php
                                        try {
                                            global $pdo;
                                            $stmt = $pdo->query("SELECT COUNT(*) as total FROM sl_votes");
                                            $result = $stmt->fetch();
                                            echo number_format($result['total']);
                                        } catch (Exception $e) {
                                            echo '0';
                                        }
                                        ?>
                                    </div>
                                    <div class="stat-label">Voti Totali</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="copyright">
                            &copy; <?php echo date('Y'); ?> Blocksy. Tutti i diritti riservati.
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="made-with">
                            Realizzato con <span class="heart">❤️</span> per la community Minecraft
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- MC Player Counter Script -->
    <script src="https://cdn.jsdelivr.net/gh/leonardosnt/mc-player-counter/dist/mc-player-counter.min.js"></script>
    
    <!-- Quill.js Editor (solo per pagine che lo richiedono) -->
    <?php if (isset($include_rich_editor) && $include_rich_editor): ?>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Nascondi il textarea originale
            const textarea = document.getElementById('descrizione');
            if (textarea) {
                textarea.style.display = 'none';
                
                // Crea il container per Quill
                const editorContainer = document.createElement('div');
                editorContainer.id = 'quill-editor';
                editorContainer.style.height = '300px';
                editorContainer.style.backgroundColor = '#16213e';
                editorContainer.style.color = 'white';
                textarea.parentNode.insertBefore(editorContainer, textarea);
                
                // Inizializza Quill
                const quill = new Quill('#quill-editor', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            [{ 'header': [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline'],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            [{ 'align': [] }],
                            ['link', 'image'],
                            ['clean']
                        ]
                    }
                });
                
                // Carica il contenuto esistente
                if (textarea.value) {
                    quill.root.innerHTML = textarea.value;
                }
                
                // Sincronizza con il textarea quando il form viene inviato
                const form = textarea.closest('form');
                if (form) {
                    form.addEventListener('submit', function() {
                        textarea.value = quill.root.innerHTML;
                    });
                }
                
                // Sincronizza in tempo reale
                quill.on('text-change', function() {
                    textarea.value = quill.root.innerHTML;
                });
            }
        });
    </script>
    <?php endif; ?>
    
    <!-- Custom JavaScript -->
    <script>
        // Funzione per copiare l'IP del server
        function copyServerIP(ip) {
            navigator.clipboard.writeText(ip).then(function() {
                showToast('IP copiato negli appunti!', 'success');
            }).catch(function() {
                // Fallback per browser più vecchi
                var textArea = document.createElement('textarea');
                textArea.value = ip;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showToast('IP copiato negli appunti!', 'success');
            });
        }
        
        // Funzione per mostrare notifiche toast
        function showToast(message, type = 'info') {
            const toastContainer = document.querySelector('.toast-container');
            const toastId = 'toast-' + Date.now();
            
            const toastHtml = `
                <div class="toast" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header">
                        <i class="bi bi-${type === 'success' ? 'check-circle-fill text-success' : 
                                         type === 'error' ? 'exclamation-triangle-fill text-danger' : 
                                         'info-circle-fill text-info'}"></i>
                        <strong class="me-auto ms-2">Notifica</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            const toast = new bootstrap.Toast(document.getElementById(toastId));
            toast.show();
            
            // Rimuovi il toast dal DOM dopo che è stato nascosto
            document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
                this.remove();
            });
        }
        
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
        
        // Aggiungi animazione al caricamento delle card
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.server-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Initialize MC Player Counter
            if (typeof MinecraftPlayerCounter !== 'undefined') {
                // Find all elements with player counter
                const playerCounters = document.querySelectorAll('[data-playercounter-ip]');
                
                playerCounters.forEach(element => {
                    const serverIP = element.getAttribute('data-playercounter-ip');
                    
                    // Initialize the counter for each server
                    MinecraftPlayerCounter.init({
                        ip: serverIP,
                        element: element,
                        format: '{online}',
                        fallback: '0'
                    });
                });
            }
            
            // Gestione pulsanti licenze rimossa dal footer - ora gestita in profile.php
        });
    </script>
    
</body>
</html>