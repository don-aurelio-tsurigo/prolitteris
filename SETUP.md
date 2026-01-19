# Setup-Anleitung für ProLitteris Reporter

## Voraussetzungen installieren

### 1. PHP installieren (Windows)

**Option A: XAMPP (empfohlen für Einsteiger)**
1. Download: https://www.apachefriends.org/download.html
2. Installiere XAMPP
3. PHP ist dann unter `C:\xampp\php\php.exe`
4. Füge zu System PATH hinzu: `C:\xampp\php`

**Option B: PHP direkt**
1. Download: https://windows.php.net/download/
2. Wähle "Thread Safe" Version (ZIP)
3. Entpacke nach `C:\PHP`
4. Füge zu System PATH hinzu: `C:\PHP`
5. Kopiere `php.ini-development` zu `php.ini`
6. Aktiviere Extensions in `php.ini`:
   ```ini
   extension=curl
   extension=mbstring
   extension=openssl
   extension=dom
   ```

### 2. Composer installieren

1. Download: https://getcomposer.org/Composer-Setup.exe
2. Installiere Composer (verwendet automatisch dein PHP)
3. Öffne neue CMD und teste: `composer --version`

### 3. Git Bash PHP verfügbar machen

Wenn du Git Bash verwendest, füge zu `~/.bashrc` hinzu:

```bash
# PHP & Composer
export PATH="/c/xampp/php:$PATH"
export PATH="$PATH:/c/ProgramData/ComposerSetup/bin"
```

Dann:
```bash
source ~/.bashrc
```

## Installation des ProLitteris Reporters

```bash
cd c:/Users/shufs/dev/prolitteris

# Dependencies installieren
composer install

# Environment konfigurieren
cp .env.example .env
nano .env  # oder in VSCode öffnen
```

## .env Datei ausfüllen

Öffne `.env` und trage ein:

```env
PROLITTERIS_MEMBER_ID=885830
PROLITTERIS_USERNAME=DEIN_USERNAME
PROLITTERIS_PASSWORD=DEIN_PASSWORD
PROLITTERIS_DOMAIN=pl02.owen.prolitteris.ch
PROLITTERIS_API_URL=https://owen.prolitteris.ch/rest/api/1/message
```

## Test durchführen

### Test 1: Eine einzelne URL scrapen (OHNE API-Aufruf)

```bash
php test-scraper.php https://bajour.ch/a/pflege-elise-chiappini-arbeitet-als-grenzgaengerin-im-basler-unispital
```

Das zeigt dir:
- ✅ Ob die Zählmarke gefunden wurde
- ✅ Ob Titel und Text extrahiert werden
- ✅ Ob Autoren erkannt werden
- ✅ Ob der Artikel die Mindestanforderungen erfüllt

### Test 2: Artikel an ProLitteris melden

**WICHTIG**: Erst testen, wenn Test 1 funktioniert!

```bash
# Erstelle eine Test-Datei mit nur 1-2 URLs
echo "https://bajour.ch/a/pflege-elise-chiappini-arbeitet-als-grenzgaengerin-im-basler-unispital" > test-urls.txt

# Melde diese Test-URLs
php report-csv.php test-urls.txt
```

### Test 3: Alle Artikel melden

```bash
php report-csv.php artikel.csv
```

## Troubleshooting

### "composer: command not found"
- Composer ist nicht installiert oder nicht im PATH
- Siehe Schritt 2 oben
- Neue CMD/Terminal öffnen nach Installation

### "php: command not found"
- PHP ist nicht installiert oder nicht im PATH
- Siehe Schritt 1 oben
- In Git Bash: `~/.bashrc` anpassen

### "Class not found"
- `composer install` wurde nicht ausgeführt
- Führe aus: `composer install`

### "Zählmarke nicht gefunden"
- Die Artikel haben noch keine Zählmarken eingebaut
- Prüfe in einem Browser den HTML-Quellcode
- Suche nach `pl02.owen.prolitteris.ch`

### "Text zu kurz"
- Artikel hat weniger als 1500 Zeichen
- Wird automatisch übersprungen
- Siehe Log-Datei für Details

### "Artikel bereits gemeldet" (Fehlercode 12)
- Artikel wurde schon früher gemeldet
- Ist normal, wird als "bereits gemeldet" gezählt
- Keine Aktion nötig

## Logs

Logs werden gespeichert in:
- `logs/report.log` - Detaillierte Logs (rotiert täglich)
- Konsole - Übersicht während der Ausführung

## Nächste Schritte

Nach erfolgreicher Einrichtung:

1. **Automatisierung**: Erstelle einen Cronjob/Task Scheduler
2. **Integration**: Binde in dein CMS ein
3. **Monitoring**: Überwache die Logs
