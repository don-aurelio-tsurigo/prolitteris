<?php

namespace Bajour\ProLitteris;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Psr\Log\LoggerInterface;

class ArticleScraper
{
    private Client $httpClient;
    private ?LoggerInterface $logger;
    private string $expectedDomain;
    private array $authorMemberIds = [];

    public function __construct(string $expectedDomain = 'pl02.owen.prolitteris.ch', ?LoggerInterface $logger = null)
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; ProLitteris-Reporter/1.0)',
            ],
        ]);
        $this->expectedDomain = $expectedDomain;
        $this->logger = $logger;

        // Lade Author Member IDs aus authors.json
        $this->loadAuthorMemberIds();
    }

    /**
     * Lädt die Author Member IDs aus authors.json
     */
    private function loadAuthorMemberIds(): void
    {
        $authorsFile = __DIR__ . '/../authors.json';
        if (file_exists($authorsFile)) {
            $json = file_get_contents($authorsFile);
            $data = json_decode($json, true);
            if (isset($data['authors']) && is_array($data['authors'])) {
                $this->authorMemberIds = $data['authors'];
                $this->log('info', "Author Member IDs geladen: " . count($this->authorMemberIds) . " Autoren");
            }
        }
    }

    /**
     * Scraped einen Artikel und extrahiert alle relevanten Daten
     *
     * @param string $url URL des Artikels
     * @return array|null Array mit articleData oder null bei Fehler
     */
    public function scrapeArticle(string $url): ?array
    {
        $this->log('info', "Scrape Artikel: {$url}");

        try {
            $response = $this->httpClient->get($url);
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html, $url);

            // Zählmarke finden
            $pixelUid = $this->extractPixelUid($crawler, $html);
            if (!$pixelUid) {
                $this->log('warning', "Keine ProLitteris Zählmarke gefunden in: {$url}");
                return null;
            }

            // Titel extrahieren
            $title = $this->extractTitle($crawler);
            if (!$title) {
                $this->log('error', "Kein Titel gefunden in: {$url}");
                return null;
            }

            // Artikeltext extrahieren
            $plainText = $this->extractArticleText($crawler);
            if (!$plainText || strlen($plainText) < 100) {
                $this->log('error', "Kein ausreichender Text gefunden in: {$url}");
                return null;
            }

            // Autoren extrahieren
            $participants = $this->extractAuthors($crawler);
            if (empty($participants)) {
                $this->log('warning', "Keine Autoren gefunden in: {$url}");
                // Erstelle einen Platzhalter-Autor
                $participants = [
                    [
                        'participation' => 'AUTHOR',
                        'firstName' => 'Unbekannt',
                        'surName' => 'Unbekannt'
                    ]
                ];
            }

            $articleData = [
                'url' => $url,
                'title' => $title,
                'plainText' => $plainText,
                'participants' => $participants,
                'pixelUid' => $pixelUid,
                'textLength' => strlen($plainText)
            ];

            $this->log('info', "Artikel erfolgreich gescraped", [
                'title' => $title,
                'textLength' => strlen($plainText),
                'authors' => count($participants),
                'pixelUid' => $pixelUid
            ]);

            return $articleData;

        } catch (\Exception $e) {
            $this->log('error', "Fehler beim Scrapen von {$url}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extrahiert die ProLitteris Zählmarke aus dem HTML
     */
    private function extractPixelUid(Crawler $crawler, string $html): ?string
    {
        // Methode 1: Suche nach IMG-Tag mit ProLitteris Domain
        try {
            $imgNode = $crawler->filter("img[src*='{$this->expectedDomain}']")->first();
            if ($imgNode->count() > 0) {
                $src = $imgNode->attr('src');
                // Extrahiere Zählmarke aus URL: http://pl02.owen.prolitteris.ch/na/plzm.xxx oder /na/vzm.xxx
                if (preg_match('#/(?:na|pw)/((?:plzm|vzm)\.[a-zA-Z0-9\-]+)#', $src, $matches)) {
                    return $matches[1];
                }
            }
        } catch (\Exception $e) {
            // Weiter mit anderen Methoden
        }

        // Methode 2: Suche direkt im HTML mit Regex
        if (preg_match('#' . preg_quote($this->expectedDomain, '#') . '/(?:na|pw)/((?:plzm|vzm)\.[a-zA-Z0-9\-]+)#', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extrahiert den Titel des Artikels
     */
    private function extractTitle(Crawler $crawler): ?string
    {
        // Versuche verschiedene Selektoren für Bajour.ch
        $selectors = [
            'h1.article-title',
            'h1.entry-title',
            '.article-header h1',
            'article h1',
            'h1',
            'meta[property="og:title"]' // OpenGraph Fallback
        ];

        foreach ($selectors as $selector) {
            try {
                $node = $crawler->filter($selector)->first();
                if ($node->count() > 0) {
                    if ($selector === 'meta[property="og:title"]') {
                        return trim($node->attr('content'));
                    }
                    return trim($node->text());
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Extrahiert den Artikeltext (nur Haupttext, ohne Navigation etc.)
     */
    private function extractArticleText(Crawler $crawler): string
    {
        $selectors = [
            '.article-content',
            '.entry-content',
            'article .content',
            'article',
            '.post-content',
            '[itemprop="articleBody"]'
        ];

        foreach ($selectors as $selector) {
            try {
                $node = $crawler->filter($selector)->first();
                if ($node->count() > 0) {
                    // Entferne unerwünschte Elemente
                    $clonedNode = clone $node;
                    $clonedNode->filter('script, style, nav, footer, aside, .comments, .related-posts')->each(function (Crawler $crawler) {
                        foreach ($crawler as $node) {
                            $node->parentNode->removeChild($node);
                        }
                    });

                    // Text extrahieren und bereinigen
                    $text = $clonedNode->text();
                    $text = preg_replace('/\s+/', ' ', $text); // Multiple Spaces entfernen
                    return trim($text);
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return '';
    }

    /**
     * Extrahiert Autoren aus dem Artikel
     */
    private function extractAuthors(Crawler $crawler): array
    {
        $authors = [];

        // Verschiedene Selektoren für Autoren-Informationen
        $selectors = [
            '.author-name',
            '.byline .author',
            '[rel="author"]',
            '.entry-author',
            'meta[name="author"]',
            '[itemprop="author"]'
        ];

        foreach ($selectors as $selector) {
            try {
                $nodes = $crawler->filter($selector);
                if ($nodes->count() > 0) {
                    $nodes->each(function (Crawler $node) use (&$authors) {
                        $authorName = null;

                        if ($node->nodeName() === 'meta') {
                            $authorName = trim($node->attr('content'));
                        } else {
                            $authorName = trim($node->text());
                        }

                        if ($authorName && strlen($authorName) > 2) {
                            // Versuche Namen zu splitten (Vorname Nachname)
                            $nameParts = $this->splitName($authorName);

                            $authorData = [
                                'participation' => 'AUTHOR',
                                'firstName' => $nameParts['firstName'],
                                'surName' => $nameParts['surName']
                            ];

                            // Prüfe, ob eine Member ID für diesen Autor hinterlegt ist
                            $memberId = $this->findMemberIdForAuthor($authorName);
                            if ($memberId) {
                                $authorData['memberId'] = $memberId;
                                $this->log('info', "Member ID gefunden für {$authorName}: {$memberId}");
                            }

                            $authors[] = $authorData;
                        }
                    });

                    if (!empty($authors)) {
                        break;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return array_unique($authors, SORT_REGULAR);
    }

    /**
     * Findet die Member ID für einen Autor anhand des Namens
     */
    private function findMemberIdForAuthor(string $authorName): ?string
    {
        // Normalisiere den Namen (Leerzeichen entfernen, Kleinbuchstaben)
        $normalizedName = strtolower(trim($authorName));

        foreach ($this->authorMemberIds as $fullName => $authorData) {
            $normalizedRegisteredName = strtolower(trim($fullName));

            // Exakter Match
            if ($normalizedName === $normalizedRegisteredName) {
                return $authorData['memberId'] ?? null;
            }

            // Prüfe auch umgekehrte Reihenfolge (Nachname Vorname vs. Vorname Nachname)
            $parts = explode(' ', $normalizedName);
            if (count($parts) === 2) {
                $reversed = $parts[1] . ' ' . $parts[0];
                if ($reversed === $normalizedRegisteredName) {
                    return $authorData['memberId'] ?? null;
                }
            }
        }

        return null;
    }

    /**
     * Splittet einen Namen in Vorname und Nachname
     */
    private function splitName(string $fullName): array
    {
        // Entferne "Von:", "By:", etc.
        $fullName = preg_replace('/^(von|by|autor|author):\s*/i', '', $fullName);
        $fullName = trim($fullName);

        $parts = explode(' ', $fullName, 2);

        return [
            'firstName' => $parts[0] ?? 'Unbekannt',
            'surName' => $parts[1] ?? $parts[0] ?? 'Unbekannt'
        ];
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }
}
