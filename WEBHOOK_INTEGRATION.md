# Integrazione Webhook Minecraft

Questo documento descrive come integrare il tuo server Minecraft con il sistema di votazione usando webhook.

## Come Funziona

Quando un utente vota il tuo server, il sistema invia automaticamente una notifica webhook al tuo server Minecraft con le informazioni del voto. Il plugin Minecraft può quindi eseguire comandi specificati (come dare premi al giocatore).

## Configurazione nel Pannello Admin

1. **Accedi al pannello admin** del sito
2. **Vai alla sezione Webhook** cliccando sul pulsante "Webhook" nel menu
3. **Seleziona il server** che vuoi configurare dal dropdown
4. **Compila i campi**:
   - **URL Webhook**: L'indirizzo dove il plugin riceverà le notifiche (es: `https://tuo-server.com:8080/webhook`)
   - **Secret Key**: Una chiave segreta per verificare l'autenticità delle richieste
   - **Template Comando**: I comandi da eseguire quando qualcuno vota (usa `{player}` per il nickname)
   - **Webhook Attivo**: Spunta per abilitare/disabilitare l'invio webhook

## Esempio di Template Comando

```
give {player} diamond 5
eco give {player} 1000
say {player} ha votato e ricevuto 5 diamanti + $1000!
```

## Plugin Minecraft Richiesto

Per ricevere i webhook, hai bisogno di un plugin che possa:
1. Ricevere richieste HTTP POST
2. Verificare la firma HMAC
3. Eseguire i comandi ricevuti

### Plugin Consigliato: Votifier o Similar

Puoi usare plugin come:
- **NuVotifier** (più moderno)
- **Votifier** (classico)
- Plugin custom (vedi esempio sotto)

## Struttura del Webhook

### Richiesta Inviata (dal sito al server Minecraft)

**Metodo**: `POST`  
**URL**: Il tuo URL webhook configurato  
**Headers**:
```
Content-Type: application/json
X-Webhook-Signature: sha256=<firma_hmac>
User-Agent: MinecraftServerList/1.0
```

**Body JSON**:
```json
{
  "server_id": 1,
  "player_name": "Steve",
  "timestamp": "2024-01-20T10:30:00Z",
  "vote_id": 123
}
```

### Risposta Attesa (dal server Minecraft al sito)

**Status Code**: `200 OK` per successo, `4xx/5xx` per errori  
**Body JSON**:
```json
{
  "success": true,
  "message": "Webhook processato con successo",
  "player": "Steve",
  "commands": [
    "give Steve diamond 5",
    "eco give Steve 1000",
    "say Steve ha votato!"
  ],
  "timestamp": "2024-01-20T10:30:01Z"
}
```

## Esempio di Plugin Custom (Bukkit/Spigot)

```java
// Plugin semplice per ricevere webhook
@Plugin(name = "VoteWebhook", version = "1.0")
public class VoteWebhook extends JavaPlugin {
    
    private String webhookSecret = "tua-chiave-segreta";
    private int webhookPort = 8080;
    
    @Override
    public void onEnable() {
        // Salva configurazione di default
        getConfig().addDefault("webhook-secret", webhookSecret);
        getConfig().addDefault("webhook-port", webhookPort);
        getConfig().options().copyDefaults(true);
        saveConfig();
        
        webhookSecret = getConfig().getString("webhook-secret");
        webhookPort = getConfig().getInt("webhook-port");
        
        // Avvia server HTTP per ricevere webhook
        startWebhookServer();
        
        getLogger().info("VoteWebhook abilitato!");
    }
    
    private void startWebhookServer() {
        // Implementa un semplice server HTTP che ascolta le richieste POST
        // Questo è un esempio semplificato - in produzione usa una libreria HTTP più robusta
        
        new Thread(() -> {
            try {
                ServerSocket serverSocket = new ServerSocket(webhookPort);
                getLogger().info("Webhook server in ascolto sulla porta " + webhookPort);
                
                while (true) {
                    Socket clientSocket = serverSocket.accept();
                    handleWebhookRequest(clientSocket);
                }
            } catch (IOException e) {
                getLogger().severe("Errore nel webhook server: " + e.getMessage());
            }
        }).start();
    }
    
    private void handleWebhookRequest(Socket socket) {
        try {
            BufferedReader in = new BufferedReader(new InputStreamReader(socket.getInputStream()));
            PrintWriter out = new PrintWriter(socket.getOutputStream(), true);
            
            // Leggi richiesta
            String line;
            StringBuilder request = new StringBuilder();
            while ((line = in.readLine()) != null && !line.isEmpty()) {
                request.append(line).append("\n");
            }
            
            // Leggi body
            StringBuilder body = new StringBuilder();
            while (in.ready()) {
                body.append((char) in.read());
            }
            
            // Processa webhook
            processWebhook(body.toString(), out);
            
            socket.close();
        } catch (IOException e) {
            getLogger().warning("Errore nella gestione webhook: " + e.getMessage());
        }
    }
    
    private void processWebhook(String body, PrintWriter out) {
        try {
            // Parse JSON
            JsonObject json = JsonParser.parseString(body).getAsJsonObject();
            String playerName = json.get("player_name").getAsString();
            
            // Verifica firma (semplificato)
            // In produzione: verifica l'header X-Webhook-Signature
            
            // Esegui comandi
            Bukkit.getScheduler().runTask(this, () -> {
                Player player = Bukkit.getPlayer(playerName);
                if (player != null && player.isOnline()) {
                    // Esegui comandi di premio
                    Bukkit.dispatchCommand(Bukkit.getConsoleSender(), "give " + playerName + " diamond 5");
                    Bukkit.dispatchCommand(Bukkit.getConsoleSender(), "eco give " + playerName + " 1000");
                    Bukkit.broadcastMessage(playerName + " ha votato e ricevuto premi!");
                }
            });
            
            // Rispondi con successo
            String response = "{\"success\":true,\"message\":\"Voto ricevuto!\",\"player\":\"" + playerName + "\"}";
            out.println("HTTP/1.1 200 OK");
            out.println("Content-Type: application/json");
            out.println("Content-Length: " + response.length());
            out.println();
            out.println(response);
            
        } catch (Exception e) {
            // Rispondi con errore
            String response = "{\"success\":false,\"error\":\"" + e.getMessage() + "\"}";
            out.println("HTTP/1.1 400 Bad Request");
            out.println("Content-Type: application/json");
            out.println("Content-Length: " + response.length());
            out.println();
            out.println(response);
        }
    }
}
```

## Debug e Risoluzione Problemi

### Log Webhook

Il sistema salva tutti i tentativi di webhook nella tabella `sl_webhook_logs`. Puoi controllare:
- Response code ricevuto
- Response body
- Timestamp dell'invio
- Successo o fallimento

### Test Manuale

Puoi testare il webhook manualmente con curl:

```bash
curl -X POST https://tuo-server.com/webhook \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: sha256=$(echo -n '{"server_id":1,"player_name":"Steve","timestamp":"2024-01-20T10:30:00Z","vote_id":123}' | openssl dgst -sha256 -hmac "tua-chiave-segreta" | cut -d' ' -f2)" \
  -d '{"server_id":1,"player_name":"Steve","timestamp":"2024-01-20T10:30:00Z","vote_id":123}'
```

### Problemi Comuni

1. **Webhook non inviato**: Controlla che sia attivo nel pannello admin
2. **Connessione rifiutata**: Verifica firewall e porta del server Minecraft
3. **Firma non valida**: Controlla che la secret key sia uguale in entrambi i sistemi
4. **Player non trovato**: Assicurati che il player sia online quando vota

## Sicurezza

- Usa sempre **HTTPS** per i webhook in produzione
- Mantieni la **secret key** segreta e cambiala periodicamente
- Implementa **rate limiting** nel tuo plugin
- **Valida sempre** i dati ricevuti prima di eseguire comandi
- Considera l'uso di **IP whitelisting** per maggiore sicurezza

## Supporto

Per problemi con l'integrazione:
1. Controlla i log in `sl_webhook_logs` nel database
2. Verifica la configurazione nel pannello admin
3. Testa manualmente con curl
4. Contatta il supporto con i dettagli dell'errore