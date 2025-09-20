# Minecraft Server List - PHP/MySQL

Un sito web completo per la gestione di una lista di server Minecraft, con sistema di votazione, autenticazione utente e pannello di amministrazione.

## Caratteristiche Principali

✅ **Design Responsive** - Interfaccia moderna con Bootstrap 5  
✅ **Sistema di Votazione** - Voto giornaliero per utenti registrati  
✅ **Avatar Minecraft** - Visualizzazione skin degli utenti votanti  
✅ **Sicurezza Avanzata** - Protezione SQL injection con PDO  
✅ **Gestione Sessioni** - Sistema di login/logout sicuro  
✅ **Pannello Admin** - Gestione completa dei server  
✅ **CAPTCHA** - Protezione contro i bot  
✅ **Codice Commentato** - Facile da modificare e mantenere  

## Requisiti di Sistema

- **PHP** 7.4 o superiore
- **MySQL** 5.7+ o MariaDB 10.2+
- **Web Server** (Apache, Nginx, ecc.)
- **PHP Extensions**: PDO, PDO_MySQL, session, json

## Installazione Guidata

### 1. Preparazione File

1. **Scarica** tutti i file in questa cartella
2. **Carica** i file nella directory del tuo server web
3. **Imposta** i permessi corretti (755 per cartelle, 644 per file)

### 2. Configurazione Database

#### Opzione A: Importazione Automatica
1. Accedi al tuo database MySQL
2. Esegui lo script SQL fornito (`database.sql`):

```sql
-- Il file database.sql contiene già tutto il necessario
-- Includerà:
-- - Creazione tabelle: sl_users, sl_servers, sl_votes
-- - Vista server_votes_count per statistiche
-- - Dati di esempio (opzionale)
-- - Indici per performance
```

#### Opzione B: Creazione Manuale
Se preferisci creare manualmente, usa questa struttura base:

```sql
CREATE TABLE sl_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    minecraft_nick VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    data_registrazione DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sl_servers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    banner_url VARCHAR(500),
    descrizione TEXT,
    ip VARCHAR(255) NOT NULL,
    versione VARCHAR(50) NOT NULL,
    logo_url VARCHAR(500),
    data_inserimento DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sl_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_id INT NOT NULL,
    user_id INT NOT NULL,
    data_voto DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES sl_servers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES sl_users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (server_id, user_id, DATE(data_voto))
);
```

### 3. Configurazione Connessione

**Modifica** il file `config.php` con i tuoi dati di connessione:

```php
// Riga 4-7 in config.php
$servername = "phpmyadmin.namedhosting.com";  // Il tuo host MySQL
$username = "user_5907";                      // Il tuo username MySQL
$password = "JyLYLLB3D0Bvh68MaYgn0RYS3RDMtIkpA0o7fPOOEzg";  // La tua password MySQL
$dbname = "site_5907";                        // Il nome del tuo database
```

### 4. Configurazione Sicurezza

#### Opzionale: reCAPTCHA Google
Per una protezione migliore contro i bot:

1. **Registrati** su [Google reCAPTCHA](https://www.google.com/recaptcha/admin)
2. **Ottieni** le chiavi: Site Key e Secret Key
3. **Modifica** login.php e register.php:

```php
// Sostituisci "YOUR_RECAPTCHA_SECRET_KEY" con la tua Secret Key
$secret_key = 'tua-secret-key-qua';
```

#### Password Admin di Default
Il sistema include un account admin di default:
- **Username**: admin
- **Password**: admin123

⚠️ **IMPORTANTE**: Cambia la password dopo il primo accesso!

### 5. Test dell'Installazione

1. **Apri** il tuo browser e vai all'URL del tuo sito
2. **Verifica** che la homepage si carichi correttamente
3. **Testa** la registrazione di un nuovo utente
4. **Prova** ad aggiungere un server dal pannello admin
5. **Controlla** il sistema di voto

## Struttura File

```
ServerList/
├── config.php              # Configurazione e funzioni principali
├── index.php               # Homepage con lista server
├── login.php               # Pagina di login
├── register.php            # Pagina di registrazione
├── server.php              # Pagina singolo server
├── admin.php               # Pannello amministrazione
├── vote.php                # Sistema di votazione (AJAX)
├── logout.php              # Script di logout
├── header.php              # Template header
├── footer.php              # Template footer
├── database.sql            # Script SQL per database
└── README.md               # Questo file
```

## Utilizzo

### Per gli Utenti

1. **Registrati** con il tuo nickname Minecraft
2. **Accedi** al tuo account
3. **Naviga** i server nella lista
4. **Vota** il tuo server preferito (una volta ogni 24 ore)
5. **Condividi** i server su Discord

### Per gli Amministratori

1. **Accedi** con l'account admin
2. **Vai** al pannello di amministrazione
3. **Aggiungi** nuovi server con nome, IP, versione, descrizione
4. **Modifica** le informazioni dei server esistenti
5. **Elimina** server non più attivi
6. **Monitora** le statistiche di voto

## Personalizzazione

### Cambio Aspetto
- **Colori**: Modifica le variabili CSS in `header.php`
- **Logo**: Sostituisci il testo "Minecraft Server List" nel header
- **Layout**: Modifica le classi Bootstrap nei file PHP

### Aggiunta Funzionalità
- **API**: Il file `vote.php` include funzioni per statistiche
- **Cache**: Aggiungi sistema di cache per performance migliori
- **Analytics**: Integra Google Analytics nel footer

## Risoluzione Problemi

### Errore di Connessione Database
```
"Connection failed: ..."
```
**Soluzione**: Verifica credenziali in `config.php` e contatta il tuo hosting

### Pagina Bianca (White Screen)
**Soluzione**: 
1. Controlla errori PHP nel log del server
2. Verifica versione PHP (deve essere ≥ 7.4)
3. Assicurati che tutti i file siano caricati

### Avatar Non Caricano
```
"Failed to load resource: the server responded with a status of 404"
```
**Soluzione**: L'API Minotar potrebbe essere offline. I fallback sono automatici.

### Voto Non Funziona
**Soluzione**: 
1. Verifica che l'utente sia loggato
2. Controlla che non siano passate 24 ore dall'ultimo voto
3. Abilita JavaScript nel browser

## Sicurezza

- ✅ **PDO Prepared Statements** contro SQL injection
- ✅ **Password Hashing** con password_hash()
- ✅ **Input Sanitization** su tutti i dati utente
- ✅ **Session Security** con parametri sicuri
- ✅ **CSRF Protection** nei form (da implementare se necessario)

## Performance

- **Indici Database**: Automaticamente creati per query veloci
- **Vista SQL**: `server_votes_count` per statistiche ottimizzate
- **Lazy Loading**: Immagini caricate solo quando visibili
- **Minified Assets**: Bootstrap e altre librerie da CDN

## Supporto

Per problemi o domande:
1. **Controlla** questa guida prima di tutto
2. **Verifica** i log di errore del server
3. **Assicurati** di aver seguito tutti i passaggi
4. **Testa** su ambiente locale prima di mettere online

## Aggiornamenti Futuri

Possibili miglioramenti:
- [ ] Sistema di recensioni testuali
- [ ] Filtri avanzati per server
- [ ] Sistema di notifiche
- [ ] API REST per sviluppatori
- [ ] Multi-lingua support
- [ ] Tema dark/light toggle

---

**Licenza**: Questo progetto è open source. Sentiti libero di modificarlo e distribuirlo.  
**Creato con**: PHP, MySQL, Bootstrap 5, JavaScript  
**Compatibilità**: Tutti i browser moderni  

Buon divertimento con la tua lista server Minecraft! 🎮