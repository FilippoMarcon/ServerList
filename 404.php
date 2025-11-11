<?php
/**
 * Pagina 404 - Not Found
 */

http_response_code(404);
require_once 'config.php';

$page_title = "404 - Pagina Non Trovata";
include 'header.php';
?>

<style>
.error-404-container {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.error-404-content {
    text-align: center;
    max-width: 600px;
}

.error-404-code {
    font-size: 8rem;
    font-weight: 900;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
    margin-bottom: 1rem;
}

.error-404-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 1rem;
}

.error-404-message {
    font-size: 1.1rem;
    color: var(--text-secondary);
    margin-bottom: 2rem;
    line-height: 1.6;
}

.error-404-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.error-404-btn {
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.error-404-btn-primary {
    background: var(--gradient-primary);
    color: white;
}

.error-404-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    color: white;
}

.error-404-btn-secondary {
    background: var(--card-bg);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}

.error-404-btn-secondary:hover {
    background: var(--primary-bg);
    border-color: var(--accent-purple);
    color: var(--text-primary);
}

.error-404-icon {
    font-size: 6rem;
    color: var(--accent-purple);
    margin-bottom: 1rem;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}

@media (max-width: 768px) {
    .error-404-code {
        font-size: 5rem;
    }
    
    .error-404-title {
        font-size: 1.5rem;
    }
    
    .error-404-message {
        font-size: 1rem;
    }
    
    .error-404-actions {
        flex-direction: column;
    }
    
    .error-404-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="error-404-container">
    <div class="error-404-content">
        <div class="error-404-icon">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <div class="error-404-code">404</div>
        <h1 class="error-404-title">Pagina Non Trovata</h1>
        <p class="error-404-message">
            Ops! La pagina che stai cercando non esiste o è stata spostata.
            Torna alla homepage o esplora i nostri server Minecraft.
        </p>
        <div class="error-404-actions">
            <a href="/" class="error-404-btn error-404-btn-primary">
                <i class="bi bi-house"></i> Torna alla Homepage
            </a>
            <a href="/forum" class="error-404-btn error-404-btn-secondary">
                <i class="bi bi-chat-dots"></i> Vai al Forum
            </a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
