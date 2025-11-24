<?php
/**
 * Verifica Signature Votifier
 * Tool per debuggare la generazione della signature
 */

require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    die("Accesso negato");
}

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $username = $_POST['username'] ?? 'TestPlayer';
    $challenge = $_POST['challenge'] ?? 'test123';
    
    if ($token) {
        // Crea il payload esattamente come fa Votifier
        $timestamp = (int)(time() * 1000);
        
        $payload_json = json_encode([
            'username' => $username,
            'serviceName' => 'default',
            'timestamp' => $timestamp,
            'address' => 'blocksy.it',
            'challenge' => $challenge
        ], JSON_UNESCAPED_SLASHES);
        
        // Genera la signature
        $signature = base64_encode(hash_hmac('sha256', $payload_json, $token, true));
        
        $result = [
            'payload' => $payload_json,
            'signature' => $signature,
            'token_length' => strlen($token),
            'token_hex' => bin2hex($token),
            'token_base64' => base64_encode($token)
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifica Signature Votifier</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #0f172a;
            color: #e2e8f0;
            padding: 2rem;
        }
        .container {
            max-width: 900px;
        }
        .code-box {
            background: #1e293b;
            padding: 1rem;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.9rem;
            word-break: break-all;
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Verifica Signature Votifier</h1>
        <p class="text-secondary">Tool per debuggare la generazione della signature HMAC-SHA256</p>
        
        <form method="POST" class="mt-4">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Token</label>
                    <input type="text" name="token" class="form-control" value="<?= htmlspecialchars($_POST['token'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? 'TestPlayer') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Challenge</label>
                    <input type="text" name="challenge" class="form-control" value="<?= htmlspecialchars($_POST['challenge'] ?? 'test123') ?>" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">🔍 Genera Signature</button>
                    <a href="/test_votifier_debug.php" class="btn btn-secondary">← Torna al Test</a>
                </div>
            </div>
        </form>
        
        <?php if ($result): ?>
        <div class="mt-4">
            <h3>📊 Risultati</h3>
            
            <h5 class="mt-3">Token Info:</h5>
            <div class="code-box">
                <strong>Lunghezza:</strong> <?= $result['token_length'] ?> caratteri<br>
                <strong>Hex:</strong> <?= $result['token_hex'] ?><br>
                <strong>Base64:</strong> <?= $result['token_base64'] ?>
            </div>
            
            <h5 class="mt-3">Payload JSON:</h5>
            <div class="code-box"><?= htmlspecialchars($result['payload']) ?></div>
            
            <h5 class="mt-3">Signature (Base64):</h5>
            <div class="code-box"><?= htmlspecialchars($result['signature']) ?></div>
            
            <div class="alert alert-info mt-3">
                <h6>Come usare:</h6>
                <ol>
                    <li>Copia il token ESATTO dal config.yml del server</li>
                    <li>Usa lo stesso challenge che vedi nei log del test</li>
                    <li>Confronta la signature generata con quella nei log</li>
                    <li>Se sono diverse, il token è sbagliato</li>
                </ol>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="mt-5">
            <h3>💡 Suggerimenti</h3>
            <div class="alert alert-warning">
                <h6>Se la signature non corrisponde:</h6>
                <ul>
                    <li>Il token potrebbe avere spazi invisibili - copialo di nuovo</li>
                    <li>Verifica che il token nel config.yml sia sotto <code>tokens: default:</code></li>
                    <li>Riavvia il server Minecraft dopo aver modificato il config</li>
                    <li>Prova a rigenerare il token: elimina il file <code>plugins/Votifier/config.yml</code> e riavvia</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
