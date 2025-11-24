# Sistema di Voti Blocksy (Identico a MinecraftITALIA)

## Come Funziona

Questo sistema replica esattamente l'architettura di **MinecraftITALIA-Votifier**, evitando completamente il protocollo Votifier diretto.

### Architettura

```
[Utente vota sul sito]
         ↓
[Sito salva voto nel database]
         ↓
[Plugin fa polling API ogni 5 secondi]
         ↓
[API restituisce voti pendenti]
         ↓
[Plugin crea evento Votifier]
         ↓
[Altri plugin ricevono l'evento]
```

### Vantaggi

✅ **Nessuna configurazione porte/firewall**  
✅ **Nessun protocollo RSA complesso**  
✅ **Autenticazione semplice con API key**  
✅ **Affidabile (polling attivo)**  
✅ **Funziona dietro NAT/proxy**  
✅ **Compatibile con tutti i plugin Votifier**

---

## Installazione

### 1. Configurazione Database

Il database viene configurato automaticamente da `config.php`. Le tabelle necessarie sono:

- `sl_votes` - Voti degli utenti (con campo `processed`)
- `sl_servers` - Server (con campo `api_key`)
- `sl_minecraft_links` - Collegamenti account verificati

### 2. Generazione API Key

Ogni server deve avere una API key univoca. Puoi generarla dal pannello admin o manualmente:

```php
// Genera API key per un server
$api_key = bin2hex(random_bytes(32));

// Salva nel database
$stmt = $pdo->prepare("UPDATE sl_servers SET api_key = ? WHERE id = ?");
$stmt->execute([$api_key, $server_id]);
```

### 3. Configurazione Plugin

1. Compila il plugin:
```bash
cd Blocksy
mvn clean package
```

2. Copia `target/Blocksy-2.3.jar` nella cartella `plugins/` del server

3. Avvia il server per generare `config.yml`

4. Modifica `plugins/Blocksy/config.yml`:
```yaml
api-key: "TUA_API_KEY_QUI"
check-interval: 5
```

5. Riavvia il server o usa `/blocksy reload`

---

## API Endpoint

### GET `/api/vote/fetch`

Recupera i voti pendenti per un server.

**Parametri:**
- `apiKey` (string, required) - API key del server

**Risposta Success (200):**
```json
[
  {
    "id": 123,
    "serverId": 1,
    "username": "Notch",
    "timestamp": "2024-11-24 15:30:00"
  },
  {
    "id": 124,
    "serverId": 1,
    "username": "Herobrine",
    "timestamp": "2024-11-24 15:31:00"
  }
]
```

**Risposta Error:**
```json
{
  "error": "API key non valida"
}
```

**Note:**
- I voti vengono automaticamente marcati come `processed = 1` dopo il recupero
- Massimo 100 voti per richiesta
- Solo voti non processati vengono restituiti

---

## Flusso Completo

### 1. Utente Vota

```php
// vote.php
$stmt = $pdo->prepare("INSERT INTO sl_votes (server_id, user_id, data_voto, processed) 
                       VALUES (?, ?, UTC_TIMESTAMP(), 0)");
$stmt->execute([$server_id, $user_id]);
```

### 2. Plugin Fa Polling

```java
// VoteChecker.java
private void checkForVotes() {
    List<BlocksyVote> votes = api.fetchVotes(apiKey);
    
    for (BlocksyVote vote : votes) {
        processVote(vote);
    }
}
```

### 3. API Restituisce Voti

```php
// api/vote/fetch.php
$stmt = $pdo->prepare("SELECT * FROM sl_votes 
                       WHERE server_id = ? AND processed = 0");
$stmt->execute([$server_id]);
$votes = $stmt->fetchAll();

// Marca come processati
$stmt = $pdo->prepare("UPDATE sl_votes SET processed = 1 WHERE id IN (...)");
```

### 4. Plugin Crea Evento Votifier

```java
// VoteChecker.java
Vote votifierVote = new Vote();
votifierVote.setUsername(vote.getUsername());
votifierVote.setServiceName("Blocksy");
votifierVote.setAddress("blocksy.it");

VotifierEvent event = new VotifierEvent(votifierVote);
Bukkit.getPluginManager().callEvent(event);
```

### 5. Altri Plugin Ricevono Evento

```java
// Qualsiasi plugin con listener Votifier
@EventHandler
public void onVote(VotifierEvent event) {
    String player = event.getVote().getUsername();
    // Dai ricompense, ecc.
}
```

---

## Struttura Codice

### PHP (Sito Web)

```
api/
├── vote/
│   └── fetch.php          # Endpoint API per polling
config.php                 # Configurazione DB + migrazioni
vote.php                   # Sistema di voto utenti
```

### Java (Plugin)

```
src/main/java/me/ph1llyon/blocksy/
├── Blocksy.java           # Classe principale
├── VoteChecker.java       # Sistema di polling
├── api/
│   ├── BlocksyAPI.java    # Client HTTP per API
│   └── BlocksyVote.java   # Modello dati voto
└── listeners/
    └── VotifierListener.java  # Listener eventi
```

---

## Confronto con MinecraftITALIA

| Componente | MinecraftITALIA | Blocksy |
|------------|-----------------|---------|
| API Endpoint | `/vote/fetch` | `/api/vote/fetch` |
| Parametro API | `apiKey` | `apiKey` |
| Classe API | `McItaAPI` | `BlocksyAPI` |
| Classe Voto | `McItaVote` | `BlocksyVote` |
| Checker | `VoteChecker` | `VoteChecker` |
| Intervallo | Configurabile | Configurabile (default 5s) |
| Dipendenza | `nuvotifier` | `nuvotifier` |

**Differenze:**
- MinecraftITALIA supporta Bukkit + BungeeCord
- Blocksy è solo Bukkit (per ora)
- MinecraftITALIA usa Retrofit, Blocksy usa HttpURLConnection
- Stessa logica, stessa affidabilità

---

## Testing

### Test API Manualmente

```bash
curl "https://blocksy.it/api/vote/fetch?apiKey=TUA_API_KEY"
```

### Test con Plugin

1. Vota sul sito
2. Controlla i log del server:
```
[Blocksy] Trovati 1 voti pendenti
[Blocksy] Processando voto: Notch (ID: 123)
[Blocksy] ✓ Voto inviato a Votifier per Notch
```

### Debug Mode

Abilita debug in `config.yml`:
```yaml
debug: true
```

---

## Troubleshooting

### Plugin non riceve voti

1. Verifica API key corretta
2. Controlla che `processed = 0` nel database
3. Verifica connessione internet del server
4. Controlla log per errori HTTP

### Voti duplicati

- Impossibile: i voti vengono marcati `processed = 1` immediatamente

### Voti non arrivano ad altri plugin

- Verifica che NuVotifier sia installato
- Controlla che gli altri plugin ascoltino `VotifierEvent`

---

## Sicurezza

✅ **API Key univoca per server**  
✅ **HTTPS obbligatorio**  
✅ **Voti marcati come processati**  
✅ **Timeout connessione (10s)**  
✅ **Rate limiting sul sito**  

---

## Performance

- **Polling ogni 5 secondi** = 12 richieste/minuto
- **Massimo 100 voti per richiesta**
- **Timeout 10 secondi**
- **Task asincrono** (non blocca il server)

---

## Conclusione

Questo sistema è **identico** a MinecraftITALIA e offre:
- ✅ Massima affidabilità
- ✅ Zero configurazione di rete
- ✅ Compatibilità totale con plugin Votifier
- ✅ Facile da debuggare
- ✅ Scalabile

**Non serve più Votifier diretto!** 🎉
