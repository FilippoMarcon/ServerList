# 📝 Esempi di Configurazione - Blocksy

## Configurazioni Base

### Configurazione Minima

```yaml
# config.yml - Configurazione minima funzionante
api-key: "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2"
check-interval: 5
```

### Configurazione Completa

```yaml
# config.yml - Tutte le opzioni
api-key: "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2"
check-interval: 5

rewards:
  enabled: true
  commands:
    - "give {player} diamond 1"
    - "give {player} emerald 5"
    - "eco give {player} 100"
    - "broadcast §a{player} ha votato per il server!"
  message: |
    §a§l✓ GRAZIE PER AVER VOTATO!
    §7Hai ricevuto:
    §8• §b1x Diamante
    §8• §b5x Smeraldi
    §8• §e$100

debug: false
```

---

## Esempi Rewards

### Survival Server

```yaml
rewards:
  enabled: true
  commands:
    - "give {player} diamond 2"
    - "give {player} golden_apple 1"
    - "give {player} experience_bottle 5"
    - "eco give {player} 500"
    - "broadcast §6⭐ §e{player} §7ha votato! §a/vote"
  message: |
    §6§l⭐ GRAZIE PER IL TUO VOTO!
    §7
    §7Hai ricevuto:
    §8• §b2x Diamanti
    §8• §61x Mela d'Oro
    §8• §a5x Bottiglie XP
    §8• §e$500
    §7
    §7Vota ogni giorno per più reward!
```

### Skyblock Server

```yaml
rewards:
  enabled: true
  commands:
    - "is level add {player} 100"
    - "give {player} spawner 1"
    - "eco give {player} 1000"
    - "lp user {player} permission set vote.daily true"
    - "broadcast §b{player} §7ha votato! §e/vote per votare anche tu!"
  message: |
    §b§l⚡ VOTO RICEVUTO!
    §7
    §7Reward:
    §8• §d+100 Island Level
    §8• §c1x Spawner Casuale
    §8• §e$1,000
    §8• §aPermesso Giornaliero
```

### Prison Server

```yaml
rewards:
  enabled: true
  commands:
    - "rankup {player}"
    - "give {player} iron_pickaxe 1"
    - "eco give {player} 2500"
    - "crate give {player} vote 1"
    - "broadcast §c⚡ §e{player} §7ha votato! §6/vote"
  message: |
    §c§l⚡ VOTO CONFERMATO!
    §7
    §7Hai ottenuto:
    §8• §aRankup Automatico
    §8• §71x Piccone di Ferro
    §8• §e$2,500
    §8• §d1x Vote Crate Key
```

### Factions Server

```yaml
rewards:
  enabled: true
  commands:
    - "f money {player} 5000"
    - "give {player} tnt 16"
    - "give {player} obsidian 32"
    - "eco give {player} 1000"
    - "broadcast §4⚔ §c{player} §7ha votato per il server!"
  message: |
    §4§l⚔ GRAZIE PER IL VOTO!
    §7
    §7Reward Faction:
    §8• §e$5,000 Faction Money
    §8• §c16x TNT
    §8• §532x Ossidiana
    §8• §e$1,000 Player Money
```

### Creative Server

```yaml
rewards:
  enabled: true
  commands:
    - "plot add {player} 1"
    - "give {player} worldedit_wand 1"
    - "lp user {player} permission set worldedit.selection.expand true"
    - "broadcast §d✨ §e{player} §7ha votato!"
  message: |
    §d§l✨ VOTO RICEVUTO!
    §7
    §7Hai sbloccato:
    §8• §b+1 Plot Extra
    §8• §eWorldEdit Wand
    §8• §aPermesso Expand
```

---

## Configurazioni Avanzate

### Con Vote Streak

```yaml
rewards:
  enabled: true
  
  # Reward base
  commands:
    - "give {player} diamond 1"
    - "eco give {player} 100"
  
  # Bonus streak (richiede plugin streak)
  streak-bonus:
    enabled: true
    commands:
      3: # 3 giorni consecutivi
        - "give {player} diamond 3"
        - "broadcast §a{player} ha votato 3 giorni di fila!"
      7: # 7 giorni consecutivi
        - "give {player} diamond_block 1"
        - "broadcast §6{player} ha votato 7 giorni di fila!"
      30: # 30 giorni consecutivi
        - "give {player} nether_star 1"
        - "broadcast §5{player} ha votato 30 giorni di fila! WOW!"
  
  message: "§aGrazie per aver votato! Streak: {streak} giorni"
```

### Con Chance System

```yaml
rewards:
  enabled: true
  
  # Reward garantiti
  guaranteed:
    - "eco give {player} 100"
    - "give {player} diamond 1"
  
  # Reward casuali (richiede plugin chance)
  chance:
    - chance: 50 # 50%
      commands:
        - "give {player} emerald 1"
    - chance: 25 # 25%
      commands:
        - "give {player} diamond 2"
    - chance: 10 # 10%
      commands:
        - "give {player} diamond_block 1"
    - chance: 1 # 1%
      commands:
        - "give {player} nether_star 1"
        - "broadcast §5{player} ha vinto un Nether Star votando!"
  
  message: "§aVoto ricevuto! Controlla il tuo inventario!"
```

### Multi-Server (BungeeCord)

```yaml
# Server Hub
api-key: "hub_api_key_qui"
check-interval: 5

rewards:
  enabled: true
  commands:
    - "bungee send {player} survival"
    - "bungee broadcast §a{player} ha votato!"
  message: "§aGrazie! Sei stato inviato al server Survival per i reward!"

# Server Survival
api-key: "survival_api_key_qui"
check-interval: 5

rewards:
  enabled: true
  commands:
    - "give {player} diamond 5"
    - "eco give {player} 1000"
  message: "§aEcco i tuoi reward per aver votato!"
```

---

## Configurazioni per Intervalli

### Polling Veloce (3 secondi)

```yaml
api-key: "your_key"
check-interval: 3  # Più veloce, più richieste
```

**Pro:** Voti arrivano quasi istantaneamente  
**Contro:** Più carico sul server web (36 richieste/minuto)

### Polling Standard (5 secondi)

```yaml
api-key: "your_key"
check-interval: 5  # Bilanciato
```

**Pro:** Buon compromesso velocità/carico  
**Contro:** Nessuno (consigliato)

### Polling Lento (10 secondi)

```yaml
api-key: "your_key"
check-interval: 10  # Più lento, meno richieste
```

**Pro:** Minimo carico sul server web  
**Contro:** Voti arrivano con ritardo

---

## Debug Mode

### Abilitare Debug

```yaml
api-key: "your_key"
check-interval: 5
debug: true  # Mostra più informazioni nei log
```

**Output nei log:**
```
[Blocksy] [DEBUG] Checking for votes...
[Blocksy] [DEBUG] API Request: https://blocksy.it/api/vote/fetch?apiKey=...
[Blocksy] [DEBUG] API Response: [{"id":123,"username":"Notch",...}]
[Blocksy] [DEBUG] Processing vote ID 123 for Notch
[Blocksy] [DEBUG] Creating Votifier event...
[Blocksy] [DEBUG] Event dispatched successfully
```

---

## Messaggi Personalizzati

### Semplice

```yaml
message: "§aGrazie per aver votato!"
```

### Multi-linea

```yaml
message: |
  §a§l✓ VOTO RICEVUTO!
  §7Grazie per il supporto!
```

### Con Variabili

```yaml
message: |
  §a§l✓ GRAZIE {player}!
  §7Hai votato per §e{server}
  §7Voti totali: §b{total_votes}
```

### Con Colori Gradient

```yaml
message: |
  §x§F§F§0§0§0§0G§x§F§F§3§3§0§0R§x§F§F§6§6§0§0A§x§F§F§9§9§0§0Z§x§F§F§C§C§0§0I§x§F§F§F§F§0§0E!
  §7Per aver votato!
```

---

## Integrazione con Altri Plugin

### Con Vault (Economy)

```yaml
commands:
  - "eco give {player} 1000"
  - "eco take {player} 0"  # Verifica saldo
```

### Con LuckPerms

```yaml
commands:
  - "lp user {player} permission set vote.daily true"
  - "lp user {player} parent addtemp vip 1d"
```

### Con EssentialsX

```yaml
commands:
  - "give {player} diamond 1"
  - "heal {player}"
  - "feed {player}"
  - "broadcast §a{player} ha votato!"
```

### Con CratesPlus

```yaml
commands:
  - "crate give {player} vote 1"
  - "crate give {player} rare 1"
```

### Con Jobs

```yaml
commands:
  - "jobs boost {player} 1.5 60"  # 50% boost per 60 min
```

---

## Template Pronti

### Server Italiano

```yaml
api-key: "your_key"
check-interval: 5

rewards:
  enabled: true
  commands:
    - "give {player} diamond 2"
    - "eco give {player} 500"
    - "broadcast §6⭐ §e{player} §7ha votato! §a/vote per votare anche tu!"
  message: |
    §6§l⭐ GRAZIE PER IL TUO VOTO!
    §7
    §7Hai ricevuto:
    §8• §b2x Diamanti
    §8• §e$500
    §7
    §7Vota ogni giorno per supportare il server!
    §7Link: §bblocksy.it

debug: false
```

### Server Internazionale

```yaml
api-key: "your_key"
check-interval: 5

rewards:
  enabled: true
  commands:
    - "give {player} diamond 2"
    - "eco give {player} 500"
    - "broadcast §6⭐ §e{player} §7voted! §a/vote to vote too!"
  message: |
    §6§l⭐ THANK YOU FOR VOTING!
    §7
    §7You received:
    §8• §b2x Diamonds
    §8• §e$500
    §7
    §7Vote daily to support the server!
    §7Link: §bblocksy.it

debug: false
```

---

## Conclusione

Scegli la configurazione più adatta al tuo server e personalizzala!

**Tips:**
- Inizia con configurazione semplice
- Testa i comandi prima di aggiungerli
- Usa debug mode per troubleshooting
- Bilancia reward per non rovinare l'economia

**Buon divertimento!** 🎮
