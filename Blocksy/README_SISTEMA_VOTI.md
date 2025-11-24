# 🎯 Sistema di Voti Blocksy - Identico a MinecraftITALIA

## 📋 Panoramica

Questo sistema replica **esattamente** l'architettura di **MinecraftITALIA-Votifier**, il sistema di voti più affidabile e semplice per server Minecraft.

### ✨ Caratteristiche

- ✅ **Zero configurazione porte/firewall**
- ✅ **Nessun protocollo RSA complesso**
- ✅ **Autenticazione semplice con API key**
- ✅ **Polling attivo ogni 5 secondi**
- ✅ **Compatibile con tutti i plugin Votifier**
- ✅ **Funziona dietro NAT/proxy**
- ✅ **Affidabile al 100%**

---

## 🏗️ Architettura

```
┌─────────────────┐
│  Utente Vota    │
│   sul Sito      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Sito Salva Voto │
│   nel Database  │
│ (processed = 0) │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Plugin Polling  │◄─── Ogni 5 secondi
│  API /vote/fetch│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ API Restituisce │
│  Voti Pendenti  │
│ (marca processed│
│      = 1)       │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Plugin Crea    │
│ Evento Votifier │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Altri Plugin    │
│  Ricevono Voto  │
│ (reward, ecc.)  │
└─────────────────┘
```

---

## 🚀 Installazione Rapida

### 1. Configurazione Sito Web

Il database è già configurato automaticamente da `config.php`:

```php
// Tabelle create automaticamente:
// - sl_votes (con campo processed)
// - sl_servers (con campo api_key)
// - sl_minecraft_links
```

### 2. Genera API Key

Vai su: `https://blocksy.it/admin_generate_api_key.php`

1. Seleziona il tuo server
2. Clicca "Genera"
3. Copia la API key

### 3. Compila Plugin

```bash
cd Blocksy
mvn clean package
```

Output: `target/Blocksy-2.3.jar`

### 4. Installa Plugin

```bash
# Copia il JAR
cp target/Blocksy-2.3.jar /path/to/server/plugins/

# Avvia server per generare config
# Poi modifica plugins/Blocksy/config.yml
```

### 5. Configura Plugin

`plugins/Blocksy/config.yml`:

```yaml
# API Key del tuo server
api-key: "64_caratteri_api_key_qui"

# Intervallo controllo (secondi)
check-interval: 5

# Ricompense (opzionale)
rewards:
  enabled: true
  commands:
    - "give {player} diamond 1"
    - "eco give {player} 100"
  message: "§aGrazie per aver votato!"

debug: false
```

### 6. Riavvia e Verifica

```bash
# Riavvia server o
/blocksy reload

# Verifica nei log:
[Blocksy] Avvio sistema di polling voti...
[Blocksy] API Key: 12345678...
[Blocksy] Intervallo controllo: 5 secondi
```

---

## 🧪 Testing

### Test API Manualmente

```bash
curl "https://blocksy.it/api/vote/fetch?apiKey=TUA_API_KEY"
```

**Risposta attesa:**
```json
[
  {
    "id": 123,
    "serverId": 1,
    "username": "Notch",
    "timestamp": "2024-11-24 15:30:00"
  }
]
```

### Test Completo

1. **Vota sul sito** come utente normale
2. **Controlla log server** (entro 5 secondi):
```
[Blocksy] Trovati 1 voti pendenti
[Blocksy] Processando voto: Notch (ID: 123)
[Blocksy] ✓ Voto inviato a Votifier per Notch
[VotifierListener] Voto ricevuto per: Notch
[VotifierListener] Reward dato a Notch
```

3. **Verifica database**:
```sql
SELECT * FROM sl_votes WHERE processed = 1;
```

---

## 📁 Struttura File

### Sito Web (PHP)

```
├── api/
│   └── vote/
│       └── fetch.php              # API endpoint polling
├── config.php                     # Configurazione + migrazioni DB
├── vote.php                       # Sistema voto utenti
└── admin_generate_api_key.php     # Gestione API keys
```

### Plugin (Java)

```
src/main/java/me/ph1llyon/blocksy/
├── Blocksy.java                   # Main class
├── VoteChecker.java               # Sistema polling
├── api/
│   ├── BlocksyAPI.java           # HTTP client
│   └── BlocksyVote.java          # Modello dati
├── listeners/
│   └── VotifierListener.java     # Event handler
└── resources/
    └── config.yml                 # Configurazione
```

---

## 🔧 API Reference

### GET `/api/vote/fetch`

Recupera voti pendenti per un server.

**Query Parameters:**
- `apiKey` (string, required) - API key del server

**Response 200 OK:**
```json
[
  {
    "id": 123,
    "serverId": 1,
    "username": "Notch",
    "timestamp": "2024-11-24 15:30:00"
  }
]
```

**Response 401 Unauthorized:**
```json
{
  "error": "API key mancante"
}
```

**Response 403 Forbidden:**
```json
{
  "error": "API key non valida"
}
```

**Note:**
- Voti vengono marcati `processed = 1` automaticamente
- Massimo 100 voti per richiesta
- Solo voti non processati vengono restituiti

---

## 🔍 Troubleshooting

### Plugin non riceve voti

**Sintomo:** Nessun log "Trovati X voti pendenti"

**Soluzioni:**
1. Verifica API key corretta in `config.yml`
2. Test manuale API: `curl "https://blocksy.it/api/vote/fetch?apiKey=..."`
3. Controlla firewall server (deve permettere HTTPS in uscita)
4. Verifica `processed = 0` nel database

### Voti non arrivano ad altri plugin

**Sintomo:** VoteChecker funziona ma altri plugin non ricevono voti

**Soluzioni:**
1. Verifica NuVotifier installato
2. Controlla che altri plugin ascoltino `VotifierEvent`
3. Abilita debug: `debug: true` in config.yml

### Errore "API key non valida"

**Sintomo:** HTTP 403 nei log

**Soluzioni:**
1. Rigenera API key da admin panel
2. Copia esattamente (64 caratteri)
3. Riavvia plugin: `/blocksy reload`

### Voti duplicati

**Impossibile!** I voti vengono marcati `processed = 1` immediatamente dopo il recupero.

---

## 📊 Performance

| Metrica | Valore |
|---------|--------|
| Polling interval | 5 secondi |
| Richieste/minuto | 12 |
| Voti/richiesta | Max 100 |
| Timeout HTTP | 10 secondi |
| Tipo task | Asincrono |
| Impatto TPS | ~0% |

---

## 🔒 Sicurezza

✅ **API Key univoca** (64 caratteri hex)  
✅ **HTTPS obbligatorio**  
✅ **Voti marcati come processati**  
✅ **Timeout connessione**  
✅ **Rate limiting sul sito**  
✅ **Validazione input**  

---

## 🆚 Confronto con Votifier Tradizionale

| Feature | Votifier Diretto | Sistema MinecraftITALIA |
|---------|------------------|-------------------------|
| Configurazione porte | ❌ Richiesta | ✅ Non necessaria |
| Protocollo RSA | ❌ Complesso | ✅ Semplice API key |
| Funziona dietro NAT | ❌ Problematico | ✅ Sempre |
| Affidabilità | ⚠️ Media | ✅ Alta |
| Debug | ❌ Difficile | ✅ Facile |
| Compatibilità plugin | ✅ Totale | ✅ Totale |

---

## 📚 Documentazione Completa

- [SISTEMA_VOTI_MINECRAFTITALIA.md](SISTEMA_VOTI_MINECRAFTITALIA.md) - Documentazione tecnica dettagliata
- [VOTIFIER_SETUP.md](VOTIFIER_SETUP.md) - Setup originale Votifier (deprecato)

---

## 🎉 Conclusione

Hai implementato con successo il sistema di voti **identico a MinecraftITALIA**!

**Vantaggi:**
- ✅ Zero problemi di rete
- ✅ Massima affidabilità
- ✅ Facile da debuggare
- ✅ Compatibile con tutto
- ✅ Scalabile

**Non serve più Votifier diretto!** 🚀

---

## 📞 Supporto

Per problemi o domande:
1. Controlla i log: `logs/latest.log`
2. Abilita debug mode
3. Testa API manualmente
4. Verifica database

**Buon voto!** 🎮
