<?php
/**
 * Test-Script zum Testen des Scrapers ohne API-Aufruf
 */

require_once __DIR__ . '/vendor/autoload.php';

use Bajour\ProLitteris\ArticleScraper;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Setup Logging
$logger = new Logger('test');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

// URL zum Testen
$testUrl = $argv[1] ?? null;

if (!$testUrl) {
    echo "Usage: php test-scraper.php <url>\n";
    echo "Beispiel: php test-scraper.php https://bajour.ch/a/...\n";
    exit(1);
}

echo "=== Test Scraper ===\n";
echo "URL: {$testUrl}\n\n";

$scraper = new ArticleScraper('pl02.owen.prolitteris.ch', $logger);
$articleData = $scraper->scrapeArticle($testUrl);

if (!$articleData) {
    echo "\n❌ FEHLER: Artikel konnte nicht gescraped werden\n";
    exit(1);
}

echo "\n=== Ergebnis ===\n";
echo "✓ Artikel erfolgreich gescraped\n\n";

echo "Titel: {$articleData['title']}\n";
echo "Zählmarke: {$articleData['pixelUid']}\n";
echo "Textlänge: " . number_format($articleData['textLength']) . " Zeichen\n";
echo "Autoren: " . count($articleData['participants']) . "\n\n";

echo "=== Autoren ===\n";
foreach ($articleData['participants'] as $author) {
    echo "- {$author['firstName']} {$author['surName']} ({$author['participation']})\n";
}

echo "\n=== Text-Vorschau (erste 500 Zeichen) ===\n";
echo substr($articleData['plainText'], 0, 500) . "...\n";

echo "\n=== Validierung ===\n";
$valid = true;

if (strlen($articleData['plainText']) < 1500) {
    echo "⚠️  WARNUNG: Text ist kürzer als 1500 Zeichen (min. für ProLitteris)\n";
    $valid = false;
}

if (empty($articleData['pixelUid'])) {
    echo "❌ FEHLER: Keine Zählmarke gefunden\n";
    $valid = false;
}

if (empty($articleData['participants'])) {
    echo "❌ FEHLER: Keine Autoren gefunden\n";
    $valid = false;
}

if ($valid) {
    echo "✓ Artikel erfüllt alle Anforderungen für ProLitteris\n";
} else {
    echo "\n❌ Artikel erfüllt NICHT alle Anforderungen\n";
}

echo "\n=== JSON Export ===\n";
echo json_encode([
    'title' => $articleData['title'],
    'pixelUid' => $articleData['pixelUid'],
    'textLength' => $articleData['textLength'],
    'participants' => $articleData['participants'],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n";
