# Guida Configurazione Votifier con Blocksy

## 📋 Requisiti

- Plugin Blocksy installato
- NuVotifier installato sul server Minecraft
- Licenza server configurata in `config.yml`

## 🔧 Configurazione

### 1. Configura NuVotifier

Nel file `plugins/Votifier/config.yml`:

```yaml
host: 0.0.0.0
port: 8192
disable-v1-protocol: false

tokens:
  default: IL_TUO_TOKEN_QUI
  Blocksy: IL_TUO_TOKEN_QUI
```

### 2. Configura Blocksy

Nel file `plugins/Blocksy/config.yml`:

```yaml
server-license: "TUA_LICENZA_24_CARATTERI"

auto-reward-commands:
  - "crate key give {player} vote 1"
  - "voteparty addvote {player} true 1"
  - "bc &b{player} ha votato il server su &nBlocksy"
```

### 3. Configura il Server sul Sito

1. Vai su https://blocksy.it/admin
2. Modifica il tuo server
3. Nella sezione "Configurazione Votifier":
   - **Host**: `play.tuoserver.it` (o IP)
   - **Porta**: `8192`
   - **Token**: Il token che hai messo in `config.yml` di Votifier

### 4. Apri la Porta nel Firewall

**Linux (ufw)**:
```bash
sudo ufw allow 8192/tcp
sudo ufw reload
```

**Linux (iptables)**:
```bash
sudo iptables -A INPUT -p tcp --dport 8192 -j ACCEPT
```

### 5. Riavvia il Server Minecraft

```
/stop
```

Poi riavvia il server.

## ✅ Test

1. Vai su https://blocksy.it/test_votifier_debug.php
2. Inserisci:
   - Host: `play.tuoserver.it`
   - Porta: `8192`
   - Token: Il tuo token
3. Clicca "Invia Voto Test"

Se vedi "✅ Successo", tutto funziona!

## 🔄 Come Funziona

1. Un player vota sul sito Blocksy
2. Il sito invia il voto a Votifier
3. Votifier genera un evento `VotifierEvent`
4. Il plugin Blocksy cattura l'evento
5. Blocksy invia il voto al sito via webhook
6. Il sito registra il voto e genera un codice
7. Quando il player entra, Blocksy controlla i voti pendenti
8. Blocksy esegue i comandi reward configurati

## 🐛 Debug

Abilita il debug in `plugins/Blocksy/config.yml`:

```yaml
debug: true
```

Poi controlla i log:
```bash
tail -f logs/latest.log | grep Blocksy
```

## 📝 Note

- Il token deve essere esattamente 26 caratteri
- La porta 8192 deve essere aperta e raggiungibile
- Il plugin Blocksy deve avere la licenza configurata
- I voti vengono processati automaticamente ogni 20 secondi

## ❓ Problemi Comuni

**"Connection timed out"**
- La porta 8192 non è aperta nel firewall
- Soluzione: Apri la porta come indicato sopra

**"Signature is not valid"**
- Il token non corrisponde tra sito e server
- Soluzione: Usa lo stesso token in entrambi i posti

**"Server not found"**
- La licenza non è configurata o non è valida
- Soluzione: Verifica la licenza in `config.yml`

## 🆘 Supporto

Se hai problemi, contatta il supporto su Discord o apri un ticket sul sito.
