<?php

require_once __DIR__ . '/vendor/autoload.php';

use Bajour\ProLitteris\ProLitterisClient;
use Bajour\ProLitteris\ArticleScraper;
use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;

// Lade Umgebungsvariablen
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Setup Logging
$logger = new Logger('prolitteris');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));
$logger->pushHandler(new RotatingFileHandler(__DIR__ . '/logs/report.log', 30, Logger::DEBUG));

$logger->info("=== ProLitteris Reporter gestartet ===");

// Initialisiere Clients
$scraper = new ArticleScraper($_ENV['PROLITTERIS_DOMAIN'], $logger);
$client = new ProLitterisClient(
    $_ENV['PROLITTERIS_MEMBER_ID'],
    $_ENV['PROLITTERIS_USERNAME'],
    $_ENV['PROLITTERIS_PASSWORD'],
    $_ENV['PROLITTERIS_API_URL'],
    $logger
);

// Lade URLs aus Datei oder Kommandozeilenargument
$urlsFile = $argv[1] ?? 'urls.txt';

if (!file_exists($urlsFile)) {
    $logger->error("Datei nicht gefunden: {$urlsFile}");
    echo "Usage: php report.php [urls.txt]\n";
    echo "Erstelle eine Datei 'urls.txt' mit einer URL pro Zeile.\n";
    exit(1);
}

$urls = file($urlsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$urls = array_filter(array_map('trim', $urls));

$logger->info("Gefunden: " . count($urls) . " URLs zum Verarbeiten");

// Statistiken
$stats = [
    'total' => count($urls),
    'success' => 0,
    'failed' => 0,
    'skipped' => 0
];

// Verarbeite jede URL
foreach ($urls as $index => $url) {
    $logger->info("Verarbeite URL " . ($index + 1) . "/" . count($urls) . ": {$url}");

    // Scrape Artikel
    $articleData = $scraper->scrapeArticle($url);

    if (!$articleData) {
        $logger->warning("Artikel konnte nicht gescraped werden: {$url}");
        $stats['skipped']++;
        continue;
    }

    // Optional: Textlänge prüfen
    if (strlen($articleData['plainText']) < 1500) {
        $logger->warning("Artikel zu kurz (" . strlen($articleData['plainText']) . " Zeichen): {$url}");
        $stats['skipped']++;
        continue;
    }

    // Melde an ProLitteris
    try {
        $response = $client->submitArticle($articleData);
        $logger->info("✓ Erfolgreich gemeldet: {$articleData['title']}", [
            'createdAt' => $response['createdAt'] ?? null
        ]);
        $stats['success']++;

        // Optional: Kleine Pause zwischen Requests
        sleep(1);

    } catch (\Exception $e) {
        $logger->error("✗ Fehler beim Melden: {$articleData['title']}", [
            'error' => $e->getMessage()
        ]);
        $stats['failed']++;
    }
}

// Zusammenfassung
$logger->info("=== Verarbeitung abgeschlossen ===");
$logger->info("Gesamt: {$stats['total']}");
$logger->info("Erfolgreich: {$stats['success']}");
$logger->info("Fehlgeschlagen: {$stats['failed']}");
$logger->info("Übersprungen: {$stats['skipped']}");

echo "\n=== Zusammenfassung ===\n";
echo "Gesamt:          {$stats['total']}\n";
echo "Erfolgreich:     {$stats['success']}\n";
echo "Fehlgeschlagen:  {$stats['failed']}\n";
echo "Übersprungen:    {$stats['skipped']}\n";
echo "\nDetails siehe: logs/report.log\n";
