@echo off
REM Automatisches Setup-Script für Windows

echo ========================================
echo ProLitteris Reporter - Setup
echo ========================================
echo.

REM Pruefe ob PHP installiert ist
where php >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [FEHLER] PHP ist nicht installiert oder nicht im PATH!
    echo.
    echo Bitte installiere PHP:
    echo 1. XAMPP: https://www.apachefriends.org/download.html
    echo    ODER
    echo 2. PHP direkt: https://windows.php.net/download/
    echo.
    echo Danach fuege PHP zum System PATH hinzu.
    pause
    exit /b 1
)

echo [OK] PHP gefunden:
php --version | findstr "PHP"
echo.

REM Pruefe ob Composer installiert ist
where composer >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [FEHLER] Composer ist nicht installiert!
    echo.
    echo Bitte installiere Composer:
    echo https://getcomposer.org/Composer-Setup.exe
    echo.
    pause
    exit /b 1
)

echo [OK] Composer gefunden:
composer --version | findstr "Composer"
echo.

REM Installiere Dependencies
echo [1/3] Installiere PHP Dependencies...
composer install --no-interaction
if %ERRORLEVEL% NEQ 0 (
    echo [FEHLER] Composer Install fehlgeschlagen!
    pause
    exit /b 1
)
echo [OK] Dependencies installiert
echo.

REM Erstelle .env wenn nicht vorhanden
if not exist ".env" (
    echo [2/3] Erstelle .env Datei...
    copy .env.example .env
    echo [OK] .env erstellt
    echo.
    echo WICHTIG: Bearbeite jetzt die .env Datei und trage deine Zugangsdaten ein!
    echo Oeffne: .env
    echo.
) else (
    echo [2/3] .env Datei existiert bereits
    echo.
)

REM Erstelle logs Verzeichnis
if not exist "logs" mkdir logs
echo [3/3] Logs Verzeichnis erstellt
echo.

echo ========================================
echo Setup abgeschlossen!
echo ========================================
echo.
echo Naechste Schritte:
echo 1. Bearbeite .env mit deinen ProLitteris Zugangsdaten
echo 2. Teste mit: php test-scraper.php [URL]
echo 3. Melde Artikel: php report-csv.php artikel.csv
echo.
echo Siehe SETUP.md fuer Details
echo.
pause
