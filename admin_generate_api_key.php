<?php
/**
 * Script per generare API key per i server
 * Usato dagli admin per configurare il sistema di polling voti
 */

require_once 'config.php';

// Solo admin
if (!isAdmin()) {
    die("Accesso negato");
}

$message = '';
$server_list = [];

// Recupera lista server
try {
    $stmt = $pdo->query("SELECT id, nome, ip, api_key FROM sl_servers ORDER BY nome");
    $server_list = $stmt->fetchAll();
} catch (Exception $e) {
    $message = "Errore: " . $e->getMessage();
}

// Genera o rigenera API key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['server_id'])) {
    $server_id = (int)$_POST['server_id'];
    
    try {
        // Genera nuova API key
        $api_key = bin2hex(random_bytes(32));
        
        $stmt = $pdo->prepare("UPDATE sl_servers SET api_key = ? WHERE id = ?");
        $stmt->execute([$api_key, $server_id]);
        
        $message = "API key generata con successo!";
        
        // Ricarica lista
        $stmt = $pdo->query("SELECT id, nome, ip, api_key FROM sl_servers ORDER BY nome");
        $server_list = $stmt->fetchAll();
        
    } catch (Exception $e) {
        $message = "Errore: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione API Keys - Blocksy Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .api-key-box {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            word-break: break-all;
        }
        .copy-btn {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">🔑 Gestione API Keys</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5>Server Registrati</h5>
            </div>
            <div class="card-body">
                <?php if (empty($server_list)): ?>
                    <p>Nessun server trovato.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome Server</th>
                                <th>IP</th>
                                <th>API Key</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($server_list as $server): ?>
                                <tr>
                                    <td><?= $server['id'] ?></td>
                                    <td><?= htmlspecialchars($server['nome']) ?></td>
                                    <td><?= htmlspecialchars($server['ip']) ?></td>
                                    <td>
                                        <?php if ($server['api_key']): ?>
                                            <div class="api-key-box">
                                                <span id="key-<?= $server['id'] ?>"><?= htmlspecialchars($server['api_key']) ?></span>
                                                <button class="btn btn-sm btn-outline-secondary copy-btn ms-2" 
                                                        onclick="copyToClipboard('key-<?= $server['id'] ?>')">
                                                    📋 Copia
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Non generata</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="server_id" value="<?= $server['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <?= $server['api_key'] ? '🔄 Rigenera' : '➕ Genera' ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>📖 Istruzioni</h5>
            </div>
            <div class="card-body">
                <h6>Come configurare il plugin:</h6>
                <ol>
                    <li>Genera una API key per il server cliccando "Genera"</li>
                    <li>Copia la API key generata</li>
                    <li>Nel server Minecraft, modifica <code>plugins/Blocksy/config.yml</code>:</li>
                </ol>
                <pre class="bg-light p-3 rounded"><code>api-key: "LA_TUA_API_KEY_QUI"
check-interval: 5</code></pre>
                <ol start="4">
                    <li>Riavvia il server o usa <code>/blocksy reload</code></li>
                    <li>Verifica nei log: <code>[Blocksy] Avvio sistema di polling voti...</code></li>
                </ol>
                
                <h6 class="mt-3">Test API:</h6>
                <pre class="bg-light p-3 rounded"><code>curl "https://blocksy.it/api/vote/fetch?apiKey=TUA_API_KEY"</code></pre>
                
                <h6 class="mt-3">Sistema identico a MinecraftITALIA:</h6>
                <ul>
                    <li>✅ Polling ogni 5 secondi</li>
                    <li>✅ Nessuna configurazione porte</li>
                    <li>✅ Compatibile con tutti i plugin Votifier</li>
                    <li>✅ Affidabile e sicuro</li>
                </ul>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="admin.php" class="btn btn-secondary">← Torna al Pannello Admin</a>
        </div>
    </div>
    
    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                alert('API key copiata negli appunti!');
            }).catch(err => {
                console.error('Errore nella copia:', err);
            });
        }
    </script>
</body>
</html>
