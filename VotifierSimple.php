<?php
/**
 * Votifier Simple Client
 * Versione semplificata che funziona con NuVotifier v2
 */

class VotifierSimple {
    private $host;
    private $port;
    private $token;
    
    public function __construct($host, $port, $token) {
        $this->host = $host;
        $this->port = $port;
        $this->token = trim($token);
    }
    
    public function sendVote($username, $serviceName = 'Blocksy', $address = 'blocksy.it') {
        try {
            $timestamp = time();
            
            // Connetti
            $stream = @stream_socket_client(
                "tcp://{$this->host}:{$this->port}",
                $errno,
                $errstr,
                5
            );
            
            if (!$stream) {
                error_log("Votifier: Connessione fallita - $errstr ($errno)");
                return false;
            }
            
            stream_set_timeout($stream, 5);
            
            // Leggi header
            $header = fread($stream, 64);
            if (!$header) {
                fclose($stream);
                return false;
            }
            
            $parts = explode(' ', trim($header));
            
            // Verifica che sia v2
            if (count($parts) < 3 || $parts[1] !== '2') {
                error_log("Votifier: Server non v2");
                fclose($stream);
                return false;
            }
            
            $challenge = trim($parts[2]);
            
            // Crea payload (ordine alfabetico)
            $payload = [
                'address' => $address,
                'challenge' => $challenge,
                'serviceName' => $serviceName,
                'timestamp' => $timestamp,
                'username' => $username
            ];
            
            $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
            
            // Genera signature
            $signature = base64_encode(hash_hmac('sha256', $payloadJson, $this->token, true));
            
            // Aggiungi signature
            $payload['signature'] = $signature;
            $messageJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
            
            // Crea packet
            $packet = pack('nn', 0x733a, strlen($messageJson)) . $messageJson;
            
            // Invia
            fwrite($stream, $packet);
            
            // Leggi risposta
            $response = fread($stream, 256);
            fclose($stream);
            
            if ($response) {
                $result = json_decode($response, true);
                if (isset($result['status']) && $result['status'] === 'ok') {
                    return true;
                }
                error_log("Votifier: Errore - " . ($result['error'] ?? 'Unknown'));
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Votifier: Exception - " . $e->getMessage());
            return false;
        }
    }
}
