<?php

require_once __DIR__ . '/vendor/autoload.php';

use Bajour\ProLitteris\ProLitterisClient;
use Bajour\ProLitteris\ArticleScraper;
use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;

// Lade Umgebungsvariablen
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Setup Logging
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$logger = new Logger('prolitteris');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));
$logger->pushHandler(new RotatingFileHandler($logDir . '/report.log', 30, Logger::DEBUG));

$logger->info("=== ProLitteris Reporter gestartet ===");

// Prüfe ob .env existiert
if (!isset($_ENV['PROLITTERIS_USERNAME'])) {
    $logger->error("Keine .env Datei gefunden oder unvollständig!");
    echo "\n❌ FEHLER: Bitte erstelle eine .env Datei mit deinen Zugangsdaten\n";
    echo "Kopiere .env.example zu .env und fülle die Werte aus.\n\n";
    exit(1);
}

// Initialisiere Clients
$scraper = new ArticleScraper($_ENV['PROLITTERIS_DOMAIN'], $logger);
$client = new ProLitterisClient(
    $_ENV['PROLITTERIS_MEMBER_ID'],
    $_ENV['PROLITTERIS_USERNAME'],
    $_ENV['PROLITTERIS_PASSWORD'],
    $_ENV['PROLITTERIS_API_URL'],
    $logger
);

// Lade URLs aus CSV
$csvFile = $argv[1] ?? 'artikel.csv';

if (!file_exists($csvFile)) {
    $logger->error("Datei nicht gefunden: {$csvFile}");
    echo "Usage: php report-csv.php [artikel.csv]\n";
    exit(1);
}

// Lese CSV/TXT Datei
$urls = file($csvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$urls = array_filter(array_map('trim', $urls));

// Entferne Kommentare und leere Zeilen
$urls = array_filter($urls, function($line) {
    return !empty($line) && $line[0] !== '#';
});

$logger->info("Gefunden: " . count($urls) . " URLs zum Verarbeiten");
echo "\n📋 Gefunden: " . count($urls) . " URLs\n\n";

// Frage Benutzer ob er fortfahren möchte
if (count($urls) > 10) {
    echo "⚠️  Du bist dabei " . count($urls) . " Artikel zu melden!\n";
    echo "Möchtest du fortfahren? (j/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (strtolower(trim($line)) !== 'j') {
        echo "Abgebrochen.\n";
        exit(0);
    }
    fclose($handle);
    echo "\n";
}

// Statistiken
$stats = [
    'total' => count($urls),
    'success' => 0,
    'failed' => 0,
    'skipped' => 0,
    'already_exists' => 0
];

$errors = [];

// Verarbeite jede URL
foreach ($urls as $index => $url) {
    $progress = sprintf("[%d/%d]", $index + 1, count($urls));

    echo "{$progress} Verarbeite: {$url}\n";
    $logger->info("{$progress} Verarbeite URL: {$url}");

    // Scrape Artikel
    $articleData = $scraper->scrapeArticle($url);

    if (!$articleData) {
        echo "  ⚠️  Konnte nicht gescraped werden\n\n";
        $logger->warning("Artikel konnte nicht gescraped werden: {$url}");
        $stats['skipped']++;
        $errors[] = ['url' => $url, 'error' => 'Scraping fehlgeschlagen'];
        continue;
    }

    // Zeige Artikel-Info
    echo "  📄 Titel: {$articleData['title']}\n";
    echo "  🏷️  Zählmarke: {$articleData['pixelUid']}\n";
    echo "  📏 Text: " . number_format($articleData['textLength']) . " Zeichen\n";
    echo "  ✍️  Autoren: " . count($articleData['participants']) . "\n";

    // Textlänge prüfen
    if (strlen($articleData['plainText']) < 1500) {
        echo "  ⚠️  WARNUNG: Artikel zu kurz (" . strlen($articleData['plainText']) . " Zeichen)\n\n";
        $logger->warning("Artikel zu kurz", [
            'url' => $url,
            'length' => strlen($articleData['plainText'])
        ]);
        $stats['skipped']++;
        $errors[] = ['url' => $url, 'error' => 'Text zu kurz (< 1500 Zeichen)'];
        continue;
    }

    // Melde an ProLitteris
    try {
        $response = $client->submitArticle($articleData);
        echo "  ✅ Erfolgreich gemeldet!\n\n";
        $logger->info("Erfolgreich gemeldet: {$articleData['title']}", [
            'createdAt' => $response['createdAt'] ?? null
        ]);
        $stats['success']++;

        // Kleine Pause zwischen Requests (Rate Limiting)
        sleep(2);

    } catch (\Exception $e) {
        $errorMsg = $e->getMessage();

        // Prüfe ob Artikel bereits existiert (Fehlercode 12)
        if (strpos($errorMsg, '"code":12') !== false || strpos($errorMsg, 'bereits eine Meldung') !== false) {
            echo "  ℹ️  Bereits gemeldet\n\n";
            $logger->info("Artikel bereits gemeldet: {$articleData['title']}");
            $stats['already_exists']++;
        } else {
            echo "  ❌ Fehler: {$errorMsg}\n\n";
            $logger->error("Fehler beim Melden: {$articleData['title']}", [
                'error' => $errorMsg
            ]);
            $stats['failed']++;
            $errors[] = ['url' => $url, 'error' => $errorMsg];
        }
    }
}

// Zusammenfassung
echo "\n" . str_repeat("=", 60) . "\n";
echo "ZUSAMMENFASSUNG\n";
echo str_repeat("=", 60) . "\n";
echo "Gesamt:              {$stats['total']}\n";
echo "✅ Erfolgreich:      {$stats['success']}\n";
echo "ℹ️  Bereits gemeldet: {$stats['already_exists']}\n";
echo "❌ Fehlgeschlagen:   {$stats['failed']}\n";
echo "⚠️  Übersprungen:    {$stats['skipped']}\n";
echo str_repeat("=", 60) . "\n";

$logger->info("=== Verarbeitung abgeschlossen ===", $stats);

// Zeige Fehler-Details
if (!empty($errors)) {
    echo "\n📋 Fehler-Details:\n";
    echo str_repeat("-", 60) . "\n";
    foreach ($errors as $error) {
        echo "URL: {$error['url']}\n";
        echo "Fehler: {$error['error']}\n";
        echo str_repeat("-", 60) . "\n";
    }
}

echo "\n📝 Detaillierte Logs siehe: logs/report.log\n";

// Exit Code basierend auf Erfolg
exit($stats['failed'] > 0 ? 1 : 0);
