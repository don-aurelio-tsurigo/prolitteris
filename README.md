# ProLitteris Reporter für Tsüri.ch

Automatische Meldung von Artikeln von **tsri.ch** an ProLitteris gemäss offizieller API-Spezifikation.

## Zweck

Dieses Skript dient der periodischen Erfassung und Meldung aller veröffentlichten Artikel von tsri.ch an ProLitteris (Urheberrechtsvergütung für Online-Texte).

## Features

- Automatisches Einlesen von Artikel-URLs aus Datei  
- Scraping von Titel, Text und Autor(en)  
- Erkennung der eingebetteten ProLitteris-Zählmarke (Pixel)  
- Validierung gemäss ProLitteris-Spezifikation  
- Meldung via ProLitteris REST API  
- Robustes Error-Handling (Timeouts, Duplikate, Validierungsfehler)  
- Statistik-Ausgabe (erfolgreich / übersprungen / fehlgeschlagen)  

## Installation

```bash
composer install
cp .env.example .env

```

## Konfiguration

Bearbeite die `.env` Datei mit deinen ProLitteris Zugangsdaten:

```env
PROLITTERIS_MEMBER_ID=DEINE_MEMBER_ID
PROLITTERIS_USERNAME=DEINE_EMAIL
PROLITTERIS_PASSWORD=DEIN_PASSWORT
PROLITTERIS_DOMAIN=pl01.owen.prolitteris.ch
PROLITTERIS_API_URL=https://owen.prolitteris.ch/rest/api/1/message
```

## Verwendung

### 1. URLs-Datei erstellen

Erstelle eine Datei `urls.txt` mit einer URL pro Zeile:

```
https://tsri.ch/a/...
https://tsri.ch/a/...
```

### 2. Script ausführen

```bash
php report.php urls.txt
```

### 3. Logs prüfen

Die Logs werden in `logs/report.log` gespeichert.

## Was macht das Script?

Für jede URL:

1. **Scraping**: Lädt den Artikel von der URL
2. **Zählmarke finden**: Sucht nach dem ProLitteris Pixel-Tag
   - Format: `http://pl01.owen.prolitteris.ch/na/plzm.xxx` oder `vzm.xxx`
3. **Daten extrahieren**:
   - Titel (h1, meta tags)
   - Artikeltext (bereinigt, ohne Navigation/Footer)
   - Autoren (verschiedene Selektoren)
4. **Validierung**:
   - Mindestens 1500 Zeichen
   - Mindestens ein Autor
   - Zählmarke vorhanden
5. **API-Aufruf**: Meldet den Artikel an ProLitteris
6. **Logging**: Schreibt Erfolg/Fehler in Logs

## API Endpoints

Das Script nutzt folgende ProLitteris Endpoints:

- `POST /rest/api/1/message` - Meldung erstellen
- `GET /rest/api/1/message` - Meldungen recherchieren

## Fehlerbehandlung

Das Script handhabt folgende Fehler:

- **Keine Zählmarke gefunden**: Artikel wird übersprungen
- **Zu kurzer Text** (< 1500 Zeichen): Artikel wird übersprungen
- **Keine Autoren gefunden**: Verwendet Platzhalter "Unbekannt"
- **API-Fehler**: Logged Error-Details und macht weiter

## Fehlercodes von ProLitteris

Laut API-Spec können folgende Fehler auftreten:

- **10**: Zählmarke gehört anderem Verlag
- **11**: ProLitteris Zählmarke nicht gefunden
- **12**: Für diese Zählmarke existiert bereits eine Meldung
- **20**: Text zu kurz (< 2000 Zeichen, aber mit Warnung)
- **31-37**: Fehler bei Urheber-Angaben
- **99**: Feldvalidierung fehlgeschlagen


## Struktur

```
prolitteris/
├── src/
│   ├── ProLitterisClient.php   # API Client für ProLitteris
│   └── ArticleScraper.php       # Web Scraper für Artikel
├── logs/                        # Log-Dateien
├── report.php                   # Hauptscript
├── urls.txt                     # Ihre URLs (nicht im Repo)
├── .env                         # Konfiguration (nicht im Repo)
└── composer.json                # Dependencies
```

## Anforderungen

- PHP >= 8.0
- Composer
- Internet-Verbindung
- ProLitteris Account mit API-Zugang

## Sicherheit

⚠️ **WICHTIG**: Committen Sie niemals:
- `.env` (enthält Credentials)
- `urls.txt` (kann interne URLs enthalten)
- `logs/` (kann sensible Daten enthalten)

## Support

Bei Problemen mit:
- **ProLitteris API**: support@prolitteris.ch
- **Diesem Script**: Logs prüfen oder Code anpassen

## Lizenz

Internes Tool für Tsüri.ch
