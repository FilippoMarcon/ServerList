# 🧪 Guida Test Sistema Voti Blocksy

## 📋 Checklist Pre-Test

- ✅ Plugin compilato con IntelliJ
- ⬜ API key generata
- ⬜ Plugin installato sul server
- ⬜ Config.yml configurato
- ⬜ Server avviato
- ⬜ Test voto effettuato

---

## Step 1: Genera API Key

### 1.1 Accedi al Pannello Admin

Vai su: `https://blocksy.it/admin_generate_api_key.php`

(Devi essere loggato come admin)

### 1.2 Genera la Key

1. Trova il tuo server nella lista
2. Clicca **"Genera"** (o "Rigenera" se esiste già)
3. **Copia** la API key (64 caratteri)

Esempio:
```
a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2
```

---

## Step 2: Installa Plugin sul Server

### 2.1 Trova il JAR

Dopo la compilazione con IntelliJ, il JAR è in:
```
Blocksy/target/Blocksy-2.3.jar
```

### 2.2 Copia nel Server

```cmd
REM Copia il JAR nella cartella plugins del server
copy "F:\Phill Sites\WOK\ServerList\Blocksy\target\Blocksy-2.3.jar" "PERCORSO_SERVER\plugins\"
```

Esempio:
```cmd
copy "F:\Phill Sites\WOK\ServerList\Blocksy\target\Blocksy-2.3.jar" "C:\MinecraftServer\plugins\"
```

---

## Step 3: Configura il Plugin

### 3.1 Avvia il Server (Prima Volta)

Avvia il server per generare il file di configurazione:
```
plugins/Blocksy/config.yml
```

### 3.2 Ferma il Server

Ferma il server per modificare la configurazione.

### 3.3 Modifica config.yml

Apri `plugins/Blocksy/config.yml` e incolla la tua API key:

```yaml
# API Key del tuo server (copiata dallo step 1)
api-key: "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2"

# Intervallo di controllo in secondi
check-interval: 5

# Ricompense (opzionale per test)
rewards:
  enabled: true
  commands:
    - "say {player} ha votato! TEST FUNZIONANTE!"
    - "give {player} diamond 1"
  message: "§a§lTEST VOTO RICEVUTO! Il sistema funziona!"

# Debug mode (utile per test)
debug: true
```

**IMPORTANTE:** Sostituisci `api-key` con la tua vera API key!

---

## Step 4: Avvia Server e Verifica

### 4.1 Avvia il Server

Avvia il server Minecraft.

### 4.2 Controlla i Log

Cerca queste righe nei log:

```
[Blocksy] §aBlocksy Vote Plugin abilitato con successo!
[Blocksy] §eAvvio sistema di polling voti...
[Blocksy] §eAPI Key: a1b2c3d4...
[Blocksy] §eIntervallo controllo: 5 secondi
```

✅ **Se vedi questi messaggi, il plugin è caricato correttamente!**

❌ **Se vedi errori:**
- Verifica che la API key sia corretta
- Controlla che non ci siano errori di sintassi in config.yml
- Verifica che Java sia versione 21+

---

## Step 5: Test Manuale API

Prima di votare, testa l'API manualmente:

### 5.1 Apri Browser o PowerShell

```powershell
# PowerShell
Invoke-WebRequest -Uri "https://blocksy.it/api/vote/fetch?apiKey=TUA_API_KEY_QUI"
```

O apri nel browser:
```
https://blocksy.it/api/vote/fetch?apiKey=TUA_API_KEY_QUI
```

### 5.2 Verifica Risposta

**Risposta OK (nessun voto pendente):**
```json
[]
```

**Risposta OK (con voti):**
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

**Errore API key:**
```json
{
  "error": "API key non valida"
}
```

✅ Se ricevi `[]` o voti, l'API funziona!

---

## Step 6: Test Voto Completo

### 6.1 Prepara Account di Test

1. Crea un account sul sito (se non ce l'hai)
2. **IMPORTANTE:** Collega il nickname Minecraft su `/verifica-nickname`
3. Verifica che il collegamento sia attivo

### 6.2 Vota sul Sito

1. Vai su `https://blocksy.it`
2. Trova il tuo server
3. Clicca **"Vota"**
4. Conferma il voto

### 6.3 Monitora i Log del Server

**Entro 5 secondi** dovresti vedere nei log:

```
[Blocksy] Trovati 1 voti pendenti
[Blocksy] Processando voto: TuoNickname (ID: 123)
[Blocksy] ✓ Voto inviato a Votifier per TuoNickname
[VotifierListener] §a[Votifier] Voto ricevuto per: TuoNickname
[VotifierListener] §a[Votifier] Reward dato a TuoNickname
```

### 6.4 Verifica In-Game

Se sei online sul server, dovresti:
- Vedere il messaggio: "§a§lTEST VOTO RICEVUTO! Il sistema funziona!"
- Ricevere 1 diamante
- Vedere broadcast: "TuoNickname ha votato! TEST FUNZIONANTE!"

---

## Step 7: Verifica Database

### 7.1 Controlla Voto Processato

Connettiti al database e verifica:

```sql
SELECT * FROM sl_votes 
WHERE user_id = TUO_USER_ID 
ORDER BY data_voto DESC 
LIMIT 1;
```

Dovresti vedere:
- `processed = 1` ✅
- `data_voto` = timestamp recente

### 7.2 Verifica API Key

```sql
SELECT id, nome, api_key 
FROM sl_servers 
WHERE id = TUO_SERVER_ID;
```

Verifica che `api_key` sia popolato.

---

## 🔍 Troubleshooting

### Plugin non si carica

**Sintomo:** Nessun messaggio "[Blocksy]" nei log

**Soluzioni:**
1. Verifica Java 21+: `java -version`
2. Controlla errori nei log
3. Verifica che il JAR sia in `plugins/`
4. Riavvia server

### "API key non configurata"

**Sintomo:** Log mostra errore API key

**Soluzioni:**
1. Verifica che `api-key` sia in config.yml
2. Controlla che non ci siano spazi extra
3. Verifica che sia tra virgolette: `"key_qui"`
4. Usa `/blocksy reload` dopo modifiche

### Plugin non riceve voti

**Sintomo:** Voti sul sito ma nessun log "[Blocksy] Trovati X voti"

**Soluzioni:**
1. Test API manualmente (Step 5)
2. Verifica firewall (HTTPS in uscita)
3. Controlla che `processed = 0` nel database
4. Verifica API key corretta
5. Abilita debug: `debug: true`

### Voti ricevuti ma nessun reward

**Sintomo:** Log mostra voto ricevuto ma player non riceve nulla

**Soluzioni:**
1. Verifica che player sia online
2. Controlla comandi in config.yml
3. Verifica permessi comandi
4. Controlla log per errori comandi

### "Player offline, reward salvato"

**Sintomo:** Player non online quando vota

**Soluzione:** Normale! Il sistema salverà il reward per il prossimo login.

---

## ✅ Test Completato con Successo

Se hai visto:
- ✅ Plugin caricato nei log
- ✅ Sistema di polling avviato
- ✅ Voto ricevuto dopo votazione
- ✅ Reward dati al player
- ✅ Database aggiornato (`processed = 1`)

**Il sistema funziona perfettamente!** 🎉

---

## 📊 Monitoraggio Continuo

### Log da Controllare

```
[Blocksy] Avvio sistema di polling voti...
[Blocksy] Trovati X voti pendenti
[Blocksy] ✓ Voto inviato a Votifier per PlayerName
```

### Comandi Utili

```
/blocksy reload    # Ricarica configurazione
/blocksy status    # Mostra stato (se implementato)
/plugins           # Verifica plugin caricato
```

### Debug Mode

Se hai problemi, abilita debug:
```yaml
debug: true
```

Vedrai log dettagliati:
```
[Blocksy] [DEBUG] Checking for votes...
[Blocksy] [DEBUG] API Response: [...]
[Blocksy] [DEBUG] Processing vote ID 123
```

---

## 🎯 Prossimi Passi

1. **Disabilita debug** in produzione: `debug: false`
2. **Configura reward** definitivi
3. **Testa con più utenti**
4. **Monitora performance**
5. **Configura backup** database

---

## 📞 Supporto

Se hai problemi:
1. Controlla log: `logs/latest.log`
2. Abilita debug mode
3. Testa API manualmente
4. Verifica database
5. Controlla documentazione: `SISTEMA_VOTI_MINECRAFTITALIA.md`

**Buon test!** 🚀
