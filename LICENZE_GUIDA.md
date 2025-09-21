# Sistema di Licenze Server - Guida

## Panoramica
Il sistema di licenze sostituisce l'uso dell'ID server con una chiave univoca da 24 caratteri per identificare in modo sicuro ogni server.

## Vantaggi
- **Sicurezza**: Nessun rischio di conflitti di ID
- **Univocità**: Ogni server ha una chiave unica
- **Flessibilità**: Possibile cambiare ID senza perdere la configurazione
- **Tracciabilità**: Maggiore controllo sui server registrati

## Come Generare una Licenza

1. Vai su: `https://www.islandmc.it/ServerList/generate_server_license.php`
2. Inserisci l'ID del tuo server
3. Clicca "Genera Licenza"
4. Copia la licenza generata (24 caratteri)

## Configurazione Plugin Blocksy

### Opzione 1: Usa la Licenza (Consigliato)
```yaml
# config.yml
server-license: "ABC123-DEF456-GHI789-JKL012"  # La tua licenza
server-id: 6  # Opzionale, usato solo se la licenza è vuota
```

### Opzione 2: Usa solo l'ID Server (Legacy)
```yaml
# config.yml
server-license: ""  # Lascia vuoto
server-id: 6  # Usa l'ID server
```

## Aggiornamento Database

Esegui lo script SQL per creare le tabelle necessarie:
```bash
mysql -u username -p database_name < add_server_licenses.sql
```

## Script PHP Aggiornati

I seguenti script supportano ora le licenze:
- `validate_vote_code.php` - Valida codici di voto
- `check_pending_votes.php` - Controlla voti pendenti
- `generate_server_license.php` - Genera nuove licenze

## Fallback Compatibilità

Se un server non ha una licenza configurata, il sistema userà automaticamente l'ID server per mantenere la compatibilità con le configurazioni esistenti.

## Sicurezza

- Le licenze sono univoche e non possono essere duplicate
- Ogni licenza è legata a un solo server
- Le licenze possono essere revocate cambiando lo status
- Le richieste API verificano sempre la validità della licenza

## Supporto

In caso di problemi:
1. Verifica che la licenza sia corretta
2. Controlla che il server sia registrato nel database
3. Assicurati che la licenza sia attiva
4. Contatta il supporto se necessario