<?php
/**
 * Votifier PHP Client
 * Invia notifiche di voto ai server Minecraft tramite protocollo Votifier
 */

class Votifier {
    private $host;
    private $port;
    private $publicKey;
    
    /**
     * @param string $host Indirizzo IP del server Minecraft
     * @param int $port Porta Votifier (default 8192)
     * @param string $publicKey Chiave pubblica RSA del server (formato PEM)
     */
    public function __construct($host, $port, $publicKey) {
        $this->host = $host;
        $this->port = $port;
        $this->publicKey = $publicKey;
    }
    
    /**
     * Invia un voto al server Minecraft
     * 
     * @param string $username Username Minecraft del votante
     * @param string $serviceName Nome del servizio di voto (es. "Blocksy")
     * @param string $address Indirizzo del sito (es. "blocksy.it")
     * @param string $timestamp Timestamp del voto (opzionale)
     * @return bool True se il voto è stato inviato con successo
     */
    public function sendVote($username, $serviceName = 'Blocksy', $address = 'blocksy.it', $timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        // Formato del payload Votifier v1
        $payload = "VOTE\n";
        $payload .= "$serviceName\n";
        $payload .= "$username\n";
        $payload .= "$address\n";
        $payload .= "$timestamp\n";
        
        // Padding del payload a 256 bytes
        $payload = str_pad($payload, 256, "\x00");
        
        try {
            // Cripta il payload con la chiave pubblica RSA
            $encrypted = $this->encryptPayload($payload);
            
            if ($encrypted === false) {
                error_log("Votifier: Errore nella criptazione del payload");
                return false;
            }
            
            // Invia il pacchetto al server
            return $this->sendPacket($encrypted);
            
        } catch (Exception $e) {
            error_log("Votifier: Errore durante l'invio del voto - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cripta il payload con RSA
     */
    private function encryptPayload($payload) {
        $key = openssl_pkey_get_public($this->publicKey);
        
        if ($key === false) {
            error_log("Votifier: Chiave pubblica non valida");
            return false;
        }
        
        $encrypted = '';
        $result = openssl_public_encrypt($payload, $encrypted, $key, OPENSSL_PKCS1_PADDING);
        
        openssl_free_key($key);
        
        return $result ? $encrypted : false;
    }
    
    /**
     * Invia il pacchetto criptato al server Votifier
     */
    private function sendPacket($data) {
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, 5);
        
        if (!$socket) {
            error_log("Votifier: Impossibile connettersi a {$this->host}:{$this->port} - $errstr ($errno)");
            return false;
        }
        
        // Leggi il banner di Votifier (es. "VOTIFIER 2.9")
        $banner = fgets($socket, 64);
        
        if (strpos($banner, 'VOTIFIER') === false) {
            error_log("Votifier: Banner non valido ricevuto: $banner");
            fclose($socket);
            return false;
        }
        
        // Invia il pacchetto criptato
        $written = fwrite($socket, $data);
        fclose($socket);
        
        return $written !== false;
    }
    
    /**
     * Testa la connessione al server Votifier
     */
    public function testConnection() {
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, 5);
        
        if (!$socket) {
            return [
                'success' => false,
                'error' => "$errstr ($errno)"
            ];
        }
        
        $banner = fgets($socket, 64);
        fclose($socket);
        
        if (strpos($banner, 'VOTIFIER') !== false) {
            return [
                'success' => true,
                'banner' => trim($banner)
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Banner non valido: ' . trim($banner)
        ];
    }
}
