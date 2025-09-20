<?php
/**
 * Pagina Forum
 * Forum Page
 */

require_once 'config.php';

$page_title = "Forum";
include 'header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <div class="row">
        <div class="col-12">
            <div class="text-center" style="padding: 4rem 2rem;">
                <div style="font-size: 4rem; color: var(--accent-purple); margin-bottom: 2rem;">
                    <i class="bi bi-chat-dots"></i>
                </div>
                <h1 style="color: var(--text-primary); margin-bottom: 1rem;">Forum</h1>
                <p style="color: var(--text-secondary); font-size: 1.2rem; margin-bottom: 2rem;">
                    Il forum della community è in arrivo!
                </p>
                <div style="background: var(--card-bg); padding: 2rem; border-radius: 16px; border: 1px solid var(--border-color); max-width: 600px; margin: 0 auto;">
                    <h3 style="color: var(--text-primary); margin-bottom: 1rem;">
                        <i class="bi bi-tools"></i> In Sviluppo
                    </h3>
                    <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                        Stiamo lavorando per portarti la migliore esperienza di forum per la community Minecraft.
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="index.php" class="btn" style="background: var(--gradient-primary); color: white; padding: 0.75rem 1.5rem; border-radius: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;">
                            <i class="bi bi-list-ul"></i> Lista Server
                        </a>
                        <a href="register.php" class="btn" style="background: var(--gradient-secondary); color: white; padding: 0.75rem 1.5rem; border-radius: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;">
                            <i class="bi bi-person-plus"></i> Registrati
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}
</style>

<?php include 'footer.php'; ?>