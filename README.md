# Minecraft Server List - PHP/MySQL

Un sito web completo per la gestione di una lista di server Minecraft, con sistema di votazione avanzato, autenticazione utente, pannello di amministrazione e interfaccia moderna completamente responsive.

## 🚀 Caratteristiche Principali

### ✨ **Interfaccia Utente Avanzata**
✅ **Design Moderno** - Interfaccia dark theme con glassmorphism  
✅ **Completamente Responsive** - Ottimizzato per desktop, tablet e mobile  
✅ **Animazioni Fluide** - Transizioni CSS3 e micro-interazioni  
✅ **Sistema di Filtri** - Filtri modalità con indicatori visivi  
✅ **Ordinamento Dinamico** - Ordina per voti, nome server o giocatori online  
✅ **Dropdown Floating** - Menu ordinamento con z-index ottimizzato  

### 🎯 **Sistema di Votazione Intelligente**
✅ **Voto Giornaliero** - Un voto ogni 24 ore per utente registrato  
✅ **Ranking Dinamico** - Posizioni aggiornate in tempo reale  
✅ **Avatar Minecraft** - Visualizzazione skin degli ultimi votanti  
✅ **Statistiche Live** - Conteggio giocatori online in tempo reale  
✅ **Toast Notifications** - Feedback visivo per ogni azione  

### 🔐 **Sicurezza e Autenticazione**
✅ **Login/Register Moderni** - Pagine auth con design a due colonne  
✅ **Password Toggle** - Visualizzazione/nascondere password  
✅ **CAPTCHA Matematico** - Protezione anti-bot integrata  
✅ **Validazione Avanzata** - Controlli client-side e server-side  
✅ **Sessioni Sicure** - Gestione sessioni con protezione CSRF  

### 🛠️ **Pannello Amministrazione**
✅ **Dashboard Completo** - Gestione server, utenti e statistiche  
✅ **CRUD Operations** - Crea, modifica, elimina server  
✅ **Gestione Utenti** - Promozione admin e moderazione  
✅ **Statistiche Dettagliate** - Analytics e metriche di utilizzo  

### 🌐 **Navigazione e Struttura**
✅ **Navbar Dinamica** - Link attivi basati sulla pagina corrente  
✅ **Forum (Coming Soon)** - Sezione community in sviluppo  
✅ **Annunci (Coming Soon)** - Sistema notifiche e comunicazioni  
✅ **Footer Moderno** - Link social e informazioni  

### 📱 **Ottimizzazioni Mobile**
✅ **Touch-Friendly** - Elementi dimensionati per touch interfaces  
✅ **iOS Zoom Prevention** - Font-size 16px per prevenire zoom automatico  
✅ **Layout Adattivo** - Colonne che si adattano alla dimensione schermo  
✅ **Performance Ottimizzate** - Caricamento veloce su connessioni lente  

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

## 📁 Struttura File

```
ServerList/
├── 📄 config.php                    # Configurazione database e funzioni core
├── 🏠 index.php                     # Homepage con lista server e filtri avanzati
├── 🔐 login.php                     # Pagina login con design moderno
├── 📝 register.php                  # Pagina registrazione responsive
├── 🖥️ server.php                    # Pagina dettaglio server con ranking dinamico
├── ⚙️ admin.php                     # Pannello amministrazione completo
├── 🗳️ vote.php                      # Sistema votazione AJAX
├── 🚪 logout.php                    # Script logout sicuro
├── 💬 forum.php                     # Pagina forum (in sviluppo)
├── 📢 annunci.php                   # Pagina annunci (in sviluppo)
├── 📋 header.php                    # Template header con navbar dinamica
├── 🦶 footer.php                    # Template footer moderno
├── 🗄️ database.sql                  # Script SQL completo
├── 🎨 assets/
│   ├── css/
│   │   ├── improvements.css         # Stili principali e responsive
│   │   └── auth-improvements.css    # Stili specifici per pagine auth
│   └── js/                          # JavaScript per interazioni
└── 📖 README.md                     # Documentazione completa
```

## 🎮 Utilizzo

### 👤 **Per gli Utenti**

1. **🔐 Registrati** con il tuo nickname Minecraft usando il form moderno
2. **🚪 Accedi** al tuo account con sistema di autenticazione sicuro
3. **🔍 Esplora** i server usando filtri modalità e ordinamento dinamico
4. **🗳️ Vota** il tuo server preferito (una volta ogni 24 ore)
5. **📱 Naviga** facilmente da qualsiasi dispositivo mobile
6. **👀 Visualizza** dettagli server con ranking in tempo reale
7. **📋 Copia** IP server con un semplice click

### 🛠️ **Per gli Amministratori**

1. **🔑 Accedi** con l'account admin al pannello di controllo
2. **➕ Aggiungi** nuovi server con form completo e validazione
3. **✏️ Modifica** informazioni server esistenti in tempo reale
4. **🗑️ Elimina** server non più attivi con conferma sicura
5. **📊 Monitora** statistiche dettagliate di voti e utenti
6. **👥 Gestisci** utenti e promuovi nuovi amministratori
7. **🎯 Analizza** metriche di performance e engagement

### 🎨 **Funzionalità Avanzate**

- **🔄 Ordinamento Live**: Ordina per voti, nome o giocatori online
- **🏷️ Filtri Intelligenti**: Filtra per modalità di gioco con indicatori visivi
- **📱 Design Responsive**: Layout ottimizzato per ogni dispositivo
- **🌙 Dark Theme**: Interfaccia moderna con tema scuro
- **⚡ Performance**: Caricamento veloce e animazioni fluide
- **🔔 Notifiche**: Toast notifications per feedback immediato

## 🎨 Personalizzazione

### 🌈 **Cambio Aspetto**
- **🎨 Colori**: Modifica le variabili CSS in `header.php` (--primary-bg, --accent-purple, ecc.)
- **🏷️ Logo**: Sostituisci il testo "Blocksy" nel header con il tuo brand
- **📐 Layout**: Modifica le classi Bootstrap e CSS custom nei file PHP
- **🖼️ Immagini**: Personalizza banner, loghi e icone
- **🌙 Tema**: Adatta i colori del dark theme alle tue preferenze

### ⚡ **Aggiunta Funzionalità**
- **📊 API**: Il file `vote.php` include endpoint per statistiche
- **💾 Cache**: Sistema di cache Redis/Memcached per performance
- **📈 Analytics**: Integrazione Google Analytics nel footer
- **🔔 Notifiche**: Sistema push notifications per nuovi server
- **🌐 Multi-lingua**: Supporto internazionalizzazione
- **🎯 SEO**: Meta tags dinamici e sitemap XML

### 🛠️ **Configurazioni Avanzate**
- **🔐 reCAPTCHA**: Integrazione Google reCAPTCHA per sicurezza extra
- **📧 Email**: Sistema invio email per notifiche e recupero password
- **🔄 Backup**: Script automatici per backup database
- **📱 PWA**: Trasforma in Progressive Web App
- **🚀 CDN**: Integrazione CDN per assets statici

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

## 🚀 Roadmap e Aggiornamenti

### ✅ **Completato di Recente**
- [x] **Sistema filtri avanzato** con indicatori visivi
- [x] **Ordinamento dinamico** per voti, nome e giocatori
- [x] **Design responsive** ottimizzato per mobile
- [x] **Pagine auth moderne** con layout a due colonne
- [x] **Ranking dinamico** con aggiornamento in tempo reale
- [x] **Navbar dinamica** con stati attivi
- [x] **Toast notifications** per feedback utente
- [x] **Floating dropdown** con z-index ottimizzato

### 🔄 **In Sviluppo**
- [ ] **Sistema Forum** completo con thread e risposte
- [ ] **Sezione Annunci** con categorie e notifiche
- [ ] **Dashboard Analytics** per amministratori
- [ ] **Sistema Recensioni** testuali per server
- [ ] **API REST** per sviluppatori terzi

### 🎯 **Pianificato**
- [ ] **Multi-lingua Support** (EN, IT, ES, FR, DE)
- [ ] **Tema Light/Dark** toggle dinamico
- [ ] **Sistema Notifiche** push e email
- [ ] **Integrazione Discord** bot e webhook
- [ ] **Mobile App** React Native companion
- [ ] **Sistema Rewards** punti e achievement
- [ ] **Advanced Search** con filtri geografici
- [ ] **Server Monitoring** uptime e performance

### 💡 **Idee Future**
- [ ] **AI Recommendations** server suggeriti
- [ ] **Social Features** amicizie e gruppi
- [ ] **Event System** tornei e competizioni
- [ ] **Marketplace** per plugin e risorse
- [ ] **Live Chat** supporto in tempo reale

## 🏆 Caratteristiche Tecniche

### 💻 **Stack Tecnologico**
- **Backend**: PHP 7.4+ con PDO per sicurezza
- **Database**: MySQL 5.7+ / MariaDB 10.2+
- **Frontend**: HTML5, CSS3, JavaScript ES6+
- **Framework**: Bootstrap 5 per responsive design
- **Icons**: Bootstrap Icons per interfaccia coerente
- **Animations**: CSS3 transforms e transitions

### 🔧 **Architettura**
- **MVC Pattern**: Separazione logica tra presentazione e business logic
- **Responsive First**: Design mobile-first con breakpoints ottimizzati
- **Progressive Enhancement**: Funzionalità base senza JavaScript
- **Graceful Degradation**: Fallback per browser meno recenti
- **SEO Friendly**: Meta tags dinamici e URL semantici

### ⚡ **Performance**
- **Lazy Loading**: Immagini e contenuti caricati on-demand
- **CSS/JS Minification**: Assets ottimizzati per velocità
- **Database Indexing**: Query ottimizzate con indici appropriati
- **Caching Strategy**: Headers HTTP per cache browser
- **CDN Ready**: Assets serviti da CDN per velocità globale

---

## 📄 Licenza e Crediti

**📜 Licenza**: Questo progetto è open source sotto licenza MIT. Sentiti libero di modificarlo, distribuirlo e utilizzarlo per progetti commerciali.

**🛠️ Creato con**:
- PHP 8.0+ & MySQL per il backend robusto
- Bootstrap 5 & CSS3 per il design moderno
- JavaScript ES6+ per interazioni fluide
- Bootstrap Icons per iconografia coerente
- Glassmorphism design per estetica moderna

**🌐 Compatibilità**:
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

**🙏 Ringraziamenti**:
- Community Minecraft per l'ispirazione
- Bootstrap team per il framework eccellente
- Contribuitori open source per librerie utilizzate

---

## 🎮 Conclusione

Questo Minecraft Server List rappresenta una soluzione completa e moderna per gestire una community di server Minecraft. Con il suo design responsive, sistema di votazione avanzato e interfaccia utente intuitiva, offre un'esperienza premium sia per gli utenti che per gli amministratori.

**🚀 Inizia subito**: Segui la guida di installazione e avrai la tua lista server online in pochi minuti!

**💬 Supporto**: Per domande, suggerimenti o contributi, non esitare a contattarci.

**Buon divertimento con la tua community Minecraft!** 🎮⛏️🏗️