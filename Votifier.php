<?php
/**
 * NuVotifier PHP Client (Protocol v2)
 * Invia notifiche di voto ai server Minecraft tramite protocollo NuVotifier v2
 */

class Votifier {
    private $host;
    private $port;
    private $publicKey;
    private $token;

    /**
     * @param string $host Indirizzo IP del server Minecraft
     * @param int $port Porta Votifier (default 8192)
     * @param string $publicKey Chiave pubblica RSA del server (formato PEM) o Token per v2
     */
    public function __construct($host, $port = 8192, $publicKeyOrToken) {
        $this->host = $host;
        $this->port = $port;

        if (strpos($publicKeyOrToken, '-----BEGIN') !== false) {
            $this->publicKey = $publicKeyOrToken;
            $this->token = null;
        } else {
            $this->token = trim($publicKeyOrToken);
            $this->publicKey = null;
            error_log("Votifier: Token configurato (lunghezza: " . strlen($this->token) . ")");
        }
    }

    /**
     * Invia un voto al server Minecraft
     */
    /**
     * Invia un voto al server Minecraft
     */
    public function sendVote($username, $serviceName = 'Blocksy', $address = 'blocksy.it', $timestamp = null) {
        if ($timestamp === null) $timestamp = time();

        try {
            $stream = @stream_socket_client('tcp://' . $this->host . ':' . $this->port, $errno, $errstr, 5);
            if (!$stream) {
                error_log("Votifier: Impossibile connettersi a {$this->host}:{$this->port} - $errstr ($errno)");
                return false;
            }

            stream_set_timeout($stream, 5);
            $header = fread($stream, 64);
            if ($header === false) { fclose($stream); return false; }

            $header = trim($header);
            error_log("Votifier: Header ricevuto: " . $header);
            
            $header_parts = explode(' ', $header);
            $is_v2 = (count($header_parts) >= 3 && ($header_parts[1] === '2' || strpos($header_parts[1], '2.') === 0));

            // Decision Logic:
            // 1. If we have a Token AND Server is V2 -> Use V2
            // 2. If we have a Public Key -> Use V1 (works on V1 servers and V2 servers with V1 backward compat)
            
            $result = false;

            if ($this->token && $is_v2) {
                $challenge = rtrim($header_parts[2], "\n");
                $result = $this->sendVoteV2($stream, $username, $serviceName, $address, $timestamp, $challenge);
            } elseif ($this->publicKey) {
                // Fallback to V1 if we have a key, regardless of server version (NuVotifier supports V1)
                $result = $this->sendVoteV1($stream, $username, $serviceName, $address, $timestamp);
            } else {
                if ($is_v2 && !$this->token) {
                    error_log("Votifier: Server richiede V2 (Token) ma è stata fornita una Chiave Pubblica (o nulla). Provo comunque V1...");
                    // Try V1 anyway if we have a key (handled by elseif above). If we are here, we don't have a key either?
                    // Wait, if we are here: (!Token OR !V2) AND !PublicKey.
                    // If !PublicKey and !Token, we can't do anything.
                    error_log("Votifier: Credenziali mancanti (Né Token né Chiave Pubblica configurati).");
                } else {
                    error_log("Votifier: Protocollo non supportato o credenziali errate.");
                }
                $result = false;
            }

            fclose($stream);
            return $result;

        } catch (Exception $e) {
            error_log("Votifier: Errore durante l'invio del voto - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Invia voto usando protocollo Votifier v1 (RSA)
     */
    private function sendVoteV1($stream, $username, $serviceName, $address, $timestamp) {
        if (!$this->publicKey) return false;

        error_log("Votifier v1: Invio voto per $username (Legacy RSA)");

        $vote = "VOTE\n" . $serviceName . "\n" . $username . "\n" . $address . "\n" . $timestamp . "\n";
        
        $pkey = openssl_get_publickey($this->publicKey);
        if (!$pkey) {
            error_log("Votifier v1: Chiave pubblica non valida");
            return false;
        }

        $encrypted = '';
        if (!openssl_public_encrypt($vote, $encrypted, $pkey)) {
            error_log("Votifier v1: Errore cifratura RSA - " . openssl_error_string());
            return false;
        }

        $written = fwrite($stream, $encrypted);
        if ($written === false) {
            error_log("Votifier v1: Errore invio pacchetto");
            return false;
        }

        error_log("Votifier v1: Pacchetto inviato ($written bytes). Nessuna risposta attesa.");
        
        // Votifier v1 non invia risposta, assumiamo successo se la scrittura è andata a buon fine
        return true;
    }

    /**
     * Invia voto usando protocollo NuVotifier v2 (JSON + HMAC-SHA256)
     */
    private function sendVoteV2($stream, $username, $serviceName, $address, $timestamp, $challenge) {
        if (!$this->token) return false;

        error_log("Votifier v2: Invio voto per $username con challenge: $challenge");

        $payload_array = [
            'username'    => $username,
            'serviceName' => $serviceName,
            'timestamp'   => $timestamp,
            'address'     => $address,
            'challenge'   => $challenge
        ];

        $payload_json = json_encode($payload_array, JSON_UNESCAPED_SLASHES);
        $signature = base64_encode(hash_hmac('sha256', $payload_json, $this->token, true));

        $message_json = json_encode([
            'payload'   => $payload_array,
            'signature' => $signature
        ], JSON_UNESCAPED_SLASHES);

        error_log("Votifier v2: Message JSON finale: $message_json");

        $packet = pack('nn', 0x733a, strlen($message_json)) . $message_json;
        $written = fwrite($stream, $packet);
        if ($written === false) return false;

        error_log("Votifier v2: Scritti $written bytes");

        $response = fread($stream, 256);
        if (!$response) return false;

        error_log("Votifier v2: Risposta ricevuta: $response");

        $result = json_decode($response, true);
        if (isset($result['status']) && $result['status'] === 'ok') {
            error_log("Votifier v2: Voto inviato con successo!");
            return true;
        } else {
            $error = $result['error'] ?? 'Unknown error';
            $cause = $result['cause'] ?? '';
            error_log("Votifier v2: Errore dal server - $cause: $error");
            return false;
        }
    }

    /**
     * Test connessione al server
     */
    public function testConnection() {
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, 5);
        if (!$socket) return ['success' => false, 'error' => "$errstr ($errno)"];

        $banner = fgets($socket, 64);
        fclose($socket);

        if (strpos($banner, 'VOTIFIER') !== false) {
            $version = 'v1';
            if (strpos($banner, 'VOTIFIER 2') !== false) $version = 'v2';
            else if (strpos($banner, 'VOTIFIER 3') !== false) $version = 'v2/v3';

            return ['success' => true, 'banner' => trim($banner), 'version' => $version];
        }

        return ['success' => false, 'error' => 'Banner non valido: ' . trim($banner)];
    }
}
