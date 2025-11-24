<?php
/**
 * Setup veloce API per test
 * Genera API key e mostra come testarla
 */

require_once 'config.php';

// Solo admin
if (!isAdmin()) {
    die("Accesso negato - Solo admin");
}

$message = '';
$api_key = '';
$server_id = null;

// Genera API key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $server_id = (int)$_POST['server_id'];
    
    try {
        // Genera nuova API key
        $api_key = bin2hex(random_bytes(32));
        
        $stmt = $pdo->prepare("UPDATE sl_servers SET api_key = ? WHERE id = ?");
        $stmt->execute([$api_key, $server_id]);
        
        $message = "✓ API key generata con successo!";
        
    } catch (Exception $e) {
        $message = "✗ Errore: " . $e->getMessage();
    }
}

// Recupera lista server
try {
    $stmt = $pdo->query("SELECT id, nome, ip, api_key FROM sl_servers ORDER BY nome");
    $servers = $stmt->fetchAll();
} catch (Exception $e) {
    die("Errore database: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup API Veloce - Blocksy</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 20px 0;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 20px 0;
        }
        .api-key {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            word-break: break-all;
            margin: 10px 0;
            border: 2px solid #28a745;
        }
        .test-section {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .code {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: monospace;
            margin: 10px 0;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover { background: #0056b3; }
        select {
            padding: 10px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ddd;
            width: 100%;
            max-width: 400px;
        }
        .step {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Setup API Veloce</h1>
        
        <?php if ($message): ?>
            <div class="<?= strpos($message, '✓') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($api_key): ?>
            <div class="success">
                <h2>✓ API Key Generata!</h2>
                <div class="api-key">
                    <strong>API Key:</strong><br>
                    <?= htmlspecialchars($api_key) ?>
                </div>
                <button onclick="copyKey('<?= htmlspecialchars($api_key) ?>')">📋 Copia API Key</button>
            </div>
            
            <div class="test-section">
                <h3>🧪 Test API</h3>
                
                <div class="step">
                    <strong>Step 1:</strong> Test accessibilità API
                    <div class="code">curl <?= SITE_URL ?>/api/test.php</div>
                    <button onclick="testAPI('<?= SITE_URL ?>/api/test.php')">▶ Test</button>
                    <div id="test-result-1"></div>
                </div>
                
                <div class="step">
                    <strong>Step 2:</strong> Test endpoint voti
                    <div class="code">curl "<?= SITE_URL ?>/api/vote/fetch?apiKey=<?= htmlspecialchars($api_key) ?>"</div>
                    <button onclick="testAPI('<?= SITE_URL ?>/api/vote/fetch?apiKey=<?= htmlspecialchars($api_key) ?>')">▶ Test</button>
                    <div id="test-result-2"></div>
                </div>
                
                <div class="step">
                    <strong>Step 3:</strong> Configura plugin
                    <div class="code">
# plugins/Blocksy/config.yml
api-key: "<?= htmlspecialchars($api_key) ?>"
check-interval: 5
debug: true
                    </div>
                </div>
            </div>
        <?php else: ?>
            <h2>Seleziona Server</h2>
            <form method="POST">
                <select name="server_id" required>
                    <option value="">-- Seleziona un server --</option>
                    <?php foreach ($servers as $server): ?>
                        <option value="<?= $server['id'] ?>">
                            <?= htmlspecialchars($server['nome']) ?> (<?= htmlspecialchars($server['ip']) ?>)
                            <?= $server['api_key'] ? ' - ✓ Ha già una key' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <br><br>
                <button type="submit" name="generate">🔑 Genera API Key</button>
            </form>
        <?php endif; ?>
        
        <hr style="margin: 30px 0;">
        
        <h3>📋 Server Esistenti</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="background: #f8f9fa;">
                <th style="padding: 10px; text-align: left;">ID</th>
                <th style="padding: 10px; text-align: left;">Nome</th>
                <th style="padding: 10px; text-align: left;">IP</th>
                <th style="padding: 10px; text-align: left;">API Key</th>
            </tr>
            <?php foreach ($servers as $server): ?>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 10px;"><?= $server['id'] ?></td>
                    <td style="padding: 10px;"><?= htmlspecialchars($server['nome']) ?></td>
                    <td style="padding: 10px;"><?= htmlspecialchars($server['ip']) ?></td>
                    <td style="padding: 10px; font-family: monospace; font-size: 12px;">
                        <?php if ($server['api_key']): ?>
                            <?= substr($server['api_key'], 0, 16) ?>...
                        <?php else: ?>
                            <span style="color: #999;">Non generata</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <script>
        function copyKey(key) {
            navigator.clipboard.writeText(key).then(() => {
                alert('✓ API key copiata negli appunti!');
            });
        }
        
        function testAPI(url) {
            const resultDiv = event.target.nextElementSibling;
            resultDiv.innerHTML = '<p style="color: #666;">⏳ Testing...</p>';
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    resultDiv.innerHTML = '<pre style="background: #d4edda; padding: 10px; border-radius: 5px; margin-top: 10px;">✓ Success:\n' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    resultDiv.innerHTML = '<pre style="background: #f8d7da; padding: 10px; border-radius: 5px; margin-top: 10px;">✗ Error:\n' + error + '</pre>';
                });
        }
    </script>
</body>
</html>
