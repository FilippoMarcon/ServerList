<?php
/**
 * Test Votifier Debug Web Interattivo
 * Inserisci host, porta, username e token direttamente nella pagina
 */

class Votifier {
    private $host;
    private $port;
    private $token;
    private $publicKey;
    public $logs = [];

    public function __construct($host, $port, $publicKeyOrToken) {
        $this->host = $host;
        $this->port = $port;
        
        if (strpos($publicKeyOrToken, '-----BEGIN') !== false) {
            $this->publicKey = $publicKeyOrToken;
            $this->token = null;
            $this->log("Chiave Pubblica RSA configurata");
        } else {
            $this->token = trim($publicKeyOrToken);
            $this->publicKey = null;
            $this->log("Token configurato (lunghezza: " . strlen($this->token) . ")");
        }
    }

    private function log($msg, $type = 'info') {
        $this->logs[] = ['msg' => $msg, 'type' => $type];
    }

    public function sendVote($username, $serviceName = 'Blocksy', $address = 'blocksy.it', $timestamp = null) {
        if ($timestamp === null) $timestamp = time();

        $this->log("Connessione a {$this->host}:{$this->port}...");
        $stream = @stream_socket_client("tcp://{$this->host}:{$this->port}", $errno, $errstr, 5);
        if (!$stream) {
            $this->log("Impossibile connettersi - $errstr ($errno)", 'error');
            return false;
        }
        stream_set_timeout($stream, 5);

        $header = fread($stream, 64);
        if (!$header) {
            $this->log("Impossibile leggere header", 'error');
            fclose($stream);
            return false;
        }
        
        $header = trim($header);
        $this->log("Header ricevuto: " . $header);

        $header_parts = explode(' ', $header);
        $is_v2 = (count($header_parts) >= 3 && ($header_parts[1] === '2' || strpos($header_parts[1], '2.') === 0));

        $result = false;

        if ($this->token && $is_v2) {
            $challenge = rtrim($header_parts[2], "\n");
            $result = $this->sendVoteV2($stream, $username, $serviceName, $address, $timestamp, $challenge);
        } elseif ($this->publicKey) {
            $result = $this->sendVoteV1($stream, $username, $serviceName, $address, $timestamp);
        } else {
            if ($is_v2 && !$this->token) {
                $this->log("Server richiede V2 (Token) ma è stata fornita una Chiave Pubblica (o nulla). Provo comunque V1...", 'warning');
            } else {
                $this->log("Protocollo non supportato o credenziali errate", 'error');
            }
            $result = false;
        }
        
        fclose($stream);
        return $result;
    }

    private function sendVoteV1($stream, $username, $serviceName, $address, $timestamp) {
        if (!$this->publicKey) return false;

        $this->log("Invio voto V1 (Legacy RSA) per $username");

        $vote = "VOTE\n" . $serviceName . "\n" . $username . "\n" . $address . "\n" . $timestamp . "\n";
        
        $pkey = openssl_get_publickey($this->publicKey);
        if (!$pkey) {
            $this->log("Chiave pubblica non valida", 'error');
            return false;
        }

        $encrypted = '';
        if (!openssl_public_encrypt($vote, $encrypted, $pkey)) {
            $this->log("Errore cifratura RSA: " . openssl_error_string(), 'error');
            return false;
        }

        $written = fwrite($stream, $encrypted);
        if ($written === false) {
            $this->log("Errore invio pacchetto", 'error');
            return false;
        }

        $this->log("Pacchetto V1 inviato ($written bytes). Nessuna risposta attesa.", 'success');
        return true;
    }

    private function sendVoteV2($stream, $username, $serviceName, $address, $timestamp, $challenge) {
        $this->log("Invio voto V2 per $username con challenge: $challenge");

        $payload = [
            'username' => $username,
            'serviceName' => $serviceName,
            'timestamp' => $timestamp,
            'address' => $address,
            'challenge' => $challenge
        ];

        $payload_json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = base64_encode(hash_hmac('sha256', $payload_json, $this->token, true));

        $message_json = json_encode([
            'payload' => $payload,
            'signature' => $signature
        ], JSON_UNESCAPED_SLASHES);

        $this->log("Payload JSON: $payload_json");
        $this->log("Signature: $signature");

        $packet = pack('nn', 0x733a, strlen($message_json)) . $message_json;
        $written = fwrite($stream, $packet);
        if ($written === false) {
            $this->log("Errore scrittura pacchetto", 'error');
            return false;
        }
        $this->log("Scritti $written bytes");

        $response = fread($stream, 256);
        if (!$response) {
            $this->log("Nessuna risposta dal server", 'error');
            return false;
        }
        $this->log("Risposta ricevuta: $response");

        $result = json_decode($response, true);
        if (isset($result['status']) && $result['status'] === 'ok') {
            $this->log("Voto inviato con successo ✅", 'success');
            return true;
        } else {
            $cause = $result['cause'] ?? '';
            $error = $result['error'] ?? 'Unknown error';
            $this->log("Errore dal server - $cause: $error ❌", 'error');
            return false;
        }
    }
}

// --- LOGICA FORM ---
$logs = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'] ?? '';
    $port = $_POST['port'] ?? 8192;
    $username = $_POST['username'] ?? '';
    $token = $_POST['token'] ?? '';

    $votifier = new Votifier($host, $port, $token);
    $success = $votifier->sendVote($username);
    $logs = $votifier->logs;
}

// --- OUTPUT HTML ---
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Test Votifier Debug</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
input, button { padding: 5px; margin: 5px 0; }
ul { list-style: none; padding-left: 0; }
li { margin-bottom: 4px; }
.info { color: black; }
.error { color: red; }
.success { color: green; }
</style>
</head>
<body>
<h1>Test Votifier Debug Interattivo</h1>

<form method="post">
    <label>Host: <input type="text" name="host" value="<?= htmlspecialchars($_POST['host'] ?? '213.239.219.59') ?>"></label><br>
    <label>Porta: <input type="number" name="port" value="<?= htmlspecialchars($_POST['port'] ?? '8192') ?>"></label><br>
    <label>Username: <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? 'Ph1llyOn_') ?>"></label><br>
    <label>Token: <input type="text" name="token" value="<?= htmlspecialchars($_POST['token'] ?? 'se6ruv7vhcb8j9vv7nqeac2s4f') ?>"></label><br>
    <button type="submit">Invia Voto di Test</button>
</form>

<?php if ($success !== null): ?>
    <h2>Risultato finale: <strong style="color:<?= $success ? 'green' : 'red' ?>"><?= $success ? 'SUCCESSO ✅' : 'FALLITO ❌' ?></strong></h2>
    <h2>Log Dettagliati</h2>
    <ul>
        <?php foreach ($logs as $log): ?>
            <?php
            $class = $log['type'] ?? 'info';
            ?>
            <li class="<?= $class ?>"><?= htmlspecialchars($log['msg']) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
</body>
</html>
