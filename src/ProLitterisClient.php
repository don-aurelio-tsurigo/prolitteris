<?php

namespace Bajour\ProLitteris;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class ProLitterisClient
{
    private Client $httpClient;
    private string $apiUrl;
    private string $authHeader;
    private ?LoggerInterface $logger;

    public function __construct(
        string $memberId,
        string $username,
        string $password,
        string $apiUrl,
        ?LoggerInterface $logger = null
    ) {
        $this->apiUrl = $apiUrl;
        $this->logger = $logger;

        // Basic Authentication nach ProLitteris Spec: base64(memberId:username:password)
        $authString = "{$memberId}:{$username}:{$password}";
        $this->authHeader = 'OWEN ' . base64_encode($authString);

        $this->httpClient = new Client([
            'timeout' => 30,
            'verify' => true, // SSL verification
        ]);
    }

    /**
     * Meldet einen Artikel an ProLitteris
     *
     * @param array $articleData Array mit: title, plainText, participants, pixelUid
     * @return array Response von ProLitteris
     * @throws \Exception Bei Fehlern
     */
    public function submitArticle(array $articleData): array
    {
        $this->validateArticleData($articleData);

        // Text base64 kodieren wie in der Spec verlangt
        $payload = [
            'title' => $articleData['title'],
            'messageText' => [
                'plainText' => base64_encode($articleData['plainText'])
            ],
            'participants' => $articleData['participants'],
            'pixelUid' => $articleData['pixelUid']
        ];

        $this->log('info', "Melde Artikel an ProLitteris: {$articleData['title']}", [
            'pixelUid' => $articleData['pixelUid'],
            'textLength' => strlen($articleData['plainText']),
            'participants' => count($articleData['participants'])
        ]);

        try {
            $response = $this->httpClient->post($this->apiUrl, [
                'headers' => [
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'Authorization' => $this->authHeader,
                ],
                'json' => $payload,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            $this->log('info', "Artikel erfolgreich gemeldet", [
                'title' => $articleData['title'],
                'createdAt' => $responseData['createdAt'] ?? null
            ]);

            return $responseData;

        } catch (GuzzleException $e) {
            $errorBody = null;
            if ($e->hasResponse()) {
                $errorBody = $e->getResponse()->getBody()->getContents();
                $errorData = json_decode($errorBody, true);

                $this->log('error', "ProLitteris API Fehler: " . ($errorData['error']['message'] ?? 'Unbekannt'), [
                    'code' => $errorData['error']['code'] ?? null,
                    'fieldErrors' => $errorData['error']['fieldErrors'] ?? null,
                    'article' => $articleData['title']
                ]);
            }

            throw new \Exception(
                "Fehler beim Melden des Artikels: " . $e->getMessage() .
                ($errorBody ? " | Response: {$errorBody}" : ''),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Recherchiert bereits gemeldete Artikel
     */
    public function searchMessages(array $params = []): array
    {
        $queryString = http_build_query($params);
        $url = rtrim($this->apiUrl, '/message') . '/message' . ($queryString ? "?{$queryString}" : '');

        try {
            $response = $this->httpClient->get($url, [
                'headers' => [
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'Authorization' => $this->authHeader,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (GuzzleException $e) {
            $this->log('error', "Fehler bei der Meldungsrecherche: " . $e->getMessage());
            throw new \Exception("Fehler bei der Recherche: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Validiert die Artikeldaten vor dem Versand
     */
    private function validateArticleData(array $data): void
    {
        $required = ['title', 'plainText', 'participants', 'pixelUid'];

        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Pflichtfeld fehlt: {$field}");
            }
        }

        // Textlänge prüfen (min 1500 Zeichen laut Spec)
        $textLength = strlen($data['plainText']);
        if ($textLength < 1500) {
            $this->log('warning', "Text ist kürzer als 1500 Zeichen ({$textLength})", [
                'title' => $data['title']
            ]);
        }

        // Mindestens ein Urheber erforderlich
        if (empty($data['participants'])) {
            throw new \InvalidArgumentException("Mindestens ein Urheber muss angegeben werden");
        }

        // Prüfe, ob mindestens ein AUTHOR vorhanden ist
        $hasAuthor = false;
        foreach ($data['participants'] as $participant) {
            if (($participant['participation'] ?? '') === 'AUTHOR') {
                $hasAuthor = true;
                break;
            }
        }

        if (!$hasAuthor) {
            throw new \InvalidArgumentException("Mindestens ein Texturheber (AUTHOR) muss angegeben werden");
        }
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }
}
