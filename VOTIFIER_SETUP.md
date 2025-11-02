# Guida Configurazione Votifier

## Cos'è Votifier?

Votifier è il protocollo standard per inviare notifiche di voto ai server Minecraft. Quando un utente vota sul tuo sito, il sistema invia automaticamente una notifica al server Minecraft tramite Votifier.

## Setup Lato Server Minecraft

### 1. Installa Votifier/NuVotifier

Scarica e installa uno di questi plugin sul tuo server:
- **NuVotifier** (consigliato, versione moderna): https://www.spigotmc.org/resources/nuvotifier.13449/
- **Votifier** (versione classica): https://dev.bukkit.org/projects/votifier

### 2. Configura Votifier

Dopo il primo avvio, troverai il file di configurazione in `plugins/Votifier/config.yml` (o `plugins/NuVotifier/config.yml`):

```yaml
# Porta su cui Votifier ascolta (default 8192)
port: 8192

# Host (lascia 0.0.0.0 per accettare connessioni da qualsiasi IP)
host: 0.0.0.0

# Debug (utile per testare)
debug: false
```

### 3. Ottieni la Chiave Pubblica

La chiave pubblica RSA si trova in `plugins/Votifier/rsa/public.pem` (o `plugins/NuVotifier/rsa/public.pem`).

Il contenuto sarà simile a:
```
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA...
-----END PUBLIC KEY-----
```

**Copia TUTTO il contenuto** (inclusi BEGIN e END).

### 4. Apri la Porta nel Firewall

Assicurati che la porta 8192 (o quella configurata) sia aperta nel firewall del server:

```bash
# UFW (Ubuntu/Debian)
sudo ufw allow 8192/tcp

# Firewalld (CentOS/RHEL)
sudo firewall-cmd --permanent --add-port=8192/tcp
sudo firewall-cmd --reload

# iptables
sudo iptables -A INPUT -p tcp --dport 8192 -j ACCEPT
```

### 5. Installa un Plugin per le Ricompense (Opzionale)

Votifier da solo riceve solo le notifiche. Per dare ricompense ai giocatori, installa uno di questi plugin:

- **VotingPlugin**: https://www.spigotmc.org/resources/votingplugin.15358/
- **VoteRewards**: https://www.spigotmc.org/resources/voterewards.16687/
- **GAListener**: https://www.spigotmc.org/resources/galistener.6699/

Esempio configurazione VotingPlugin:
```yaml
# Ricompense per voto
rewards:
  - command: "give %player% diamond 1"
  - command: "eco give %player% 100"
  - broadcast: "&a%player% ha votato per il server!"
```

## Setup Lato Sito Web (Admin Panel)

### 1. Accedi al Pannello Admin

Vai su: `https://blocksy.it/admin?action=servers`

### 2. Modifica il Server

Clicca su "Modifica" per il server che vuoi configurare.

### 3. Compila i Campi Votifier

Nella sezione "Configurazione Votifier":

- **Host Votifier**: Indirizzo IP del server Minecraft (es. `123.45.67.89` o `play.tuoserver.it`)
- **Porta Votifier**: Porta configurata (default `8192`)
- **Chiave Pubblica**: Incolla la chiave pubblica copiata dal file `public.pem`

### 4. Salva

Clicca su "Salva Modifiche".

## Test della Configurazione

### Test Manuale

Puoi testare la connessione Votifier creando un file `test_votifier.php`:

```php
<?php
require_once 'config.php';
require_once 'Votifier.php';

$host = 'IP_DEL_TUO_SERVER';
$port = 8192;
$publicKey = '-----BEGIN PUBLIC KEY-----
...TUA_CHIAVE_PUBBLICA...
-----END PUBLIC KEY-----';

$votifier = new Votifier($host, $port, $publicKey);

// Test connessione
$test = $votifier->testConnection();
if ($test['success']) {
    echo "✅ Connessione riuscita! Banner: " . $test['banner'] . "\n";
} else {
    echo "❌ Errore: " . $test['error'] . "\n";
}

// Test invio voto
$result = $votifier->sendVote('TestPlayer', 'Blocksy', 'blocksy.it');
if ($result) {
    echo "✅ Voto inviato con successo!\n";
} else {
    echo "❌ Errore nell'invio del voto\n";
}
?>
```

Esegui: `php test_votifier.php`

### Verifica nei Log

Controlla i log del server Minecraft in `logs/latest.log`:

```
[Votifier] Received vote from Blocksy for TestPlayer
```

## Troubleshooting

### Errore: "Impossibile connettersi"

- ✅ Verifica che la porta sia aperta nel firewall
- ✅ Controlla che Votifier sia avviato sul server
- ✅ Verifica l'indirizzo IP/hostname

### Errore: "Banner non valido"

- ✅ Assicurati di usare NuVotifier o Votifier aggiornato
- ✅ Verifica che la porta sia quella corretta

### Errore: "Chiave pubblica non valida"

- ✅ Copia l'intera chiave inclusi `-----BEGIN PUBLIC KEY-----` e `-----END PUBLIC KEY-----`
- ✅ Non aggiungere spazi o caratteri extra
- ✅ Usa la chiave dal file `public.pem`, non `private.pem`

### Il voto viene registrato ma non arriva al server

- ✅ Controlla i log PHP in `/var/log/apache2/error.log` o `/var/log/nginx/error.log`
- ✅ Verifica che i campi Votifier siano compilati nell'admin panel
- ✅ Testa la connessione con lo script di test

## Vantaggi di Votifier

✅ **Standard universale** - Compatibile con tutti i plugin di ricompense
✅ **Sicuro** - Comunicazione criptata con RSA
✅ **Affidabile** - Protocollo testato e usato da migliaia di server
✅ **Automatico** - Nessun comando manuale, tutto automatico
✅ **Real-time** - Il giocatore riceve la ricompensa immediatamente

## Supporto

Per problemi o domande:
- Documentazione NuVotifier: https://github.com/nuvotifier/NuVotifier/wiki
- Discord Blocksy: [link discord]
