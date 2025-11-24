# 🔨 Build Instructions - Blocksy Plugin

## Requisiti

- **Java 21** o superiore
- **Maven 3.6+**
- **Git** (opzionale)

---

## Build Rapido

```bash
cd Blocksy
mvn clean package
```

Output: `target/Blocksy-2.3.jar`

---

## Build Dettagliato

### 1. Verifica Java

```bash
java -version
# Output atteso: java version "21.x.x"
```

Se non hai Java 21:
- **Windows:** https://adoptium.net/
- **Linux:** `sudo apt install openjdk-21-jdk`
- **Mac:** `brew install openjdk@21`

### 2. Verifica Maven

```bash
mvn -version
# Output atteso: Apache Maven 3.x.x
```

Se non hai Maven:
- **Windows:** https://maven.apache.org/download.cgi
- **Linux:** `sudo apt install maven`
- **Mac:** `brew install maven`

### 3. Clone Repository (se necessario)

```bash
git clone https://github.com/tuousername/blocksy.git
cd blocksy/Blocksy
```

### 4. Build

```bash
# Clean + Compile + Package
mvn clean package

# Solo compile (più veloce per test)
mvn compile

# Skip tests (se presenti)
mvn clean package -DskipTests
```

### 5. Verifica Output

```bash
ls -lh target/Blocksy-2.3.jar
# Dovrebbe essere ~50-100 KB
```

---

## Dipendenze

Il plugin include automaticamente:

- **Spigot API 1.21.4** (provided)
- **Gson 2.10.1** (shaded)
- **NuVotifier API 2.7.3** (provided)

### Nota su Gson

Gson viene **incluso** nel JAR finale tramite Maven Shade Plugin. Non serve installarlo separatamente sul server.

---

## Installazione

```bash
# Copia il JAR nel server
cp target/Blocksy-2.3.jar /path/to/server/plugins/

# Riavvia il server
# Il plugin genererà config.yml automaticamente
```

---

## Sviluppo

### IDE Setup

#### IntelliJ IDEA

1. File → Open → Seleziona cartella `Blocksy`
2. Maven si importa automaticamente
3. Build → Build Project

#### Eclipse

1. File → Import → Maven → Existing Maven Projects
2. Seleziona cartella `Blocksy`
3. Project → Build Project

#### VS Code

1. Apri cartella `Blocksy`
2. Installa estensione "Java Extension Pack"
3. Maven si importa automaticamente

### Hot Reload (Sviluppo)

Per testare modifiche senza riavviare:

```bash
# Build
mvn clean package

# Copia nel server di test
cp target/Blocksy-2.3.jar /path/to/test-server/plugins/

# Usa PlugMan per reload
/plugman reload Blocksy
```

---

## Troubleshooting Build

### Errore: "Java version mismatch"

```
[ERROR] Source option 21 is no longer supported. Use 21 or later.
```

**Soluzione:** Aggiorna Java a versione 21+

### Errore: "Cannot resolve dependencies"

```
[ERROR] Failed to execute goal on project Blocksy: Could not resolve dependencies
```

**Soluzione:**
```bash
# Pulisci cache Maven
mvn dependency:purge-local-repository

# Riprova
mvn clean package
```

### Errore: "NuVotifier not found"

```
[ERROR] package com.vexsoftware.votifier.model does not exist
```

**Soluzione:** NuVotifier non è in Maven Central. Hai due opzioni:

**Opzione 1:** Installa NuVotifier localmente
```bash
# Scarica NuVotifier JAR
wget https://github.com/nuvotifier/NuVotifier/releases/download/v2.7.3/nuvotifier-universal-2.7.3.jar

# Installa in Maven locale
mvn install:install-file \
  -Dfile=nuvotifier-universal-2.7.3.jar \
  -DgroupId=com.vexsoftware \
  -DartifactId=nuvotifier-universal \
  -Dversion=2.7.3 \
  -Dpackaging=jar
```

**Opzione 2:** Commenta temporaneamente il codice Votifier
```java
// Commenta import e uso di VotifierEvent
// Il sistema di polling funzionerà comunque
```

### Build lento

```bash
# Usa thread multipli
mvn clean package -T 4

# Skip tests
mvn clean package -DskipTests

# Offline mode (se hai già le dipendenze)
mvn clean package -o
```

---

## Build Profiles

### Development

```bash
mvn clean package -P dev
```

- Debug abilitato
- Logging verbose
- No ottimizzazioni

### Production (default)

```bash
mvn clean package
```

- Ottimizzato
- Logging normale
- Shaded dependencies

---

## Versioning

Per cambiare versione:

```xml
<!-- pom.xml -->
<version>2.4</version>
```

Poi rebuild:
```bash
mvn clean package
```

Output: `target/Blocksy-2.4.jar`

---

## CI/CD

### GitHub Actions

Crea `.github/workflows/build.yml`:

```yaml
name: Build Blocksy

on: [push, pull_request]

jobs:
  build:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Set up JDK 21
      uses: actions/setup-java@v3
      with:
        java-version: '21'
        distribution: 'temurin'
    
    - name: Build with Maven
      run: |
        cd Blocksy
        mvn clean package
    
    - name: Upload artifact
      uses: actions/upload-artifact@v3
      with:
        name: Blocksy-Plugin
        path: Blocksy/target/Blocksy-*.jar
```

---

## Pulizia

```bash
# Rimuovi file compilati
mvn clean

# Rimuovi anche cache Maven
mvn clean -Dmaven.clean.failOnError=false

# Reset completo
rm -rf target/
rm -rf ~/.m2/repository/me/ph1llyon/blocksy/
```

---

## Build Automatico

Script bash per build automatico:

```bash
#!/bin/bash
# build.sh

set -e

echo "🔨 Building Blocksy Plugin..."

cd Blocksy

echo "📦 Cleaning..."
mvn clean

echo "🏗️ Compiling..."
mvn package

echo "✅ Build completato!"
echo "📁 Output: target/Blocksy-2.3.jar"

# Opzionale: copia automaticamente
if [ -d "/path/to/server/plugins" ]; then
    echo "📋 Copiando nel server..."
    cp target/Blocksy-2.3.jar /path/to/server/plugins/
    echo "✅ Plugin aggiornato!"
fi
```

Uso:
```bash
chmod +x build.sh
./build.sh
```

---

## Conclusione

Build completato con successo! 🎉

**Next steps:**
1. Copia `target/Blocksy-2.3.jar` nel server
2. Configura `config.yml` con API key
3. Riavvia server
4. Verifica log per conferma

**Happy coding!** 🚀
