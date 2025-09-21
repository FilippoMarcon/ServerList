# Guida per Risolvere l'Errore del Database

## Problema
Il plugin Blocksy sta cercando di distribuire ricompense automatiche, ma riceve l'errore:
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'site_5907.vote_codes' doesn't exist
```

## Soluzione Completa

### 1. Esegui le Query di Compatibilità
Esegui lo script PHP per creare le viste necessarie:
```bash
php execute_compatibility_fix.php
```

### 2. Inserisci un Voto di Test (Opzionale)
Per testare il sistema, inserisci un voto di test:
```bash
php insert_test_vote.php
```

### 3. Verifica le Tabelle
Le seguenti tabelle e viste dovrebbero esistere:
- `sl_vote_codes` - Tabella principale dei codici
- `sl_servers` - Tabella dei server
- `sl_users` - Tabella degli utenti
- `sl_reward_logs` - Log delle ricompense
- `vote_codes` - Vista di compatibilità
- `servers` - Vista di compatibilità

### 4. Testa l'API
Puoi testare l'API direttamente:
```bash
curl -X POST https://www.islandmc.it/ServerList/check_pending_votes.php \
  -H "Content-Type: application/json" \
  -d '{"player_name":"Ph1llyOn_","server_id":6}'

curl -X POST https://www.islandmc.it/ServerList/auto_distribute_reward.php \
  -H "Content-Type: application/json" \
  -d '{"player_name":"Ph1llyOn_","server_id":6}'
```

### 5. Verifica i Log
Controlla i log del plugin Minecraft per vedere se le ricompense vengono distribuite correttamente.

## Schema Database Aggiornato
```sql
-- Tabella principale (esiste già)
sl_vote_codes: id, vote_id, server_id, user_id, code, status, created_at, used_at, expires_at

-- Vista di compatibilità (creata dallo script)
vote_codes: id, vote_id, server_id, user_id, code, is_used, created_at, used_at, expires_at, player_name, server_name
```

## Note Importanti
- Il plugin ora invia `player_name` invece di `username`
- Gli script PHP restituiscono `rewards` array con i codici
- Le viste garantiscono compatibilità con entrambi i sistemi