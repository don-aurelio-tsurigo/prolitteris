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

        // Authorization: OWEN base64(memberId:username:password)
        $authString = "{$memberId}:{$username}:{$password}";
        $this->authHeader = 'OWEN ' . base64_encode($authString);

        $this->httpClient = new Client([
            'timeout' => 30,
            'verify' => true,
        ]);
    }

    /**
     * Meldet einen Artikel an ProLitteris
     */
    public function submitArticle(array $articleData): array
    {
        $this->validateArticleData($articleData);

        $payload = [
            'title' => $articleData['title'],
            'messageText' => [
                'plainText' => base64_encode($articleData['plainText']),
            ],
            'participants' => $articleData['participants'],
            'pixelUid' => $articleData['pixelUid'],
        ];

        $this->log('info', "Melde Artikel an ProLitteris: {$articleData['title']}", [
            'pixelUid' => $articleData['pixelUid'],
            'textLength' => strlen($articleData['plainText']),
            'participants' => count($articleData['participants']),
        ]);

        try {
            $response = $this->httpClient->post($this->apiUrl, [
                'headers' => [
                    'Content-Type'  => 'application/json; charset=UTF-8',
                    'Authorization' => $this->authHeader,
                ],
                'json' => $payload,
            ]);

            $responseData = json_decode((string) $response->getBody(), true);

            $this->log('info', "Artikel erfolgreich gemeldet", [
                'title'     => $articleData['title'],
                'createdAt' => $responseData['createdAt'] ?? null,
            ]);

            return $responseData;

        } catch (GuzzleException $e) {

    // Timeout / Verbindungsproblem → als übersprungen behandeln
    if (str_contains($e->getMessage(), 'cURL error 28')) {
        $this->log('warning', 'Timeout bei ProLitteris API – übersprungen', [
            'article' => $articleData['title'] ?? null,
            'pixelUid' => $articleData['pixelUid'] ?? null,
        ]);
        return ['skipped' => true];
    }

    $errorBody = null;

            if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
                $errorBody = (string) $e->getResponse()->getBody();
                $errorData = json_decode($errorBody, true);

                $errorCode = $errorData['error']['code'] ?? null;
                $errorMessage = $errorData['error']['message'] ?? 'Unbekannt';

                // Fehlercode 12: Zählmarke bereits gemeldet → überspringen
                if ($errorCode === 12) {
                    $this->log('info', 'Zählmarke bereits gemeldet – übersprungen', [
                        'article'  => $articleData['title'] ?? null,
                        'pixelUid' => $articleData['pixelUid'] ?? null,
                    ]);
                    return ['skipped' => true];
                }

                $this->log('error', "ProLitteris API Fehler: {$errorMessage}", [
                    'code'       => $errorCode,
                    'fieldErrors'=> $errorData['error']['fieldErrors'] ?? null,
                    'article'    => $articleData['title'] ?? null,
                ]);
            } else {
                $this->log('error', 'Technischer Fehler bei ProLitteris API', [
                    'exception' => $e->getMessage(),
                    'article'   => $articleData['title'] ?? null,
                ]);
            }

            throw $e;
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
                    'Content-Type'  => 'application/json; charset=UTF-8',
                    'Authorization' => $this->authHeader,
                ],
            ]);

            return json_decode((string) $response->getBody(), true);

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

        if (strlen($data['plainText']) < 1500) {
            $this->log('warning', "Text ist kürzer als 1500 Zeichen", [
                'title' => $data['title'],
            ]);
        }

        if (empty($data['participants'])) {
            throw new \InvalidArgumentException("Mindestens ein Urheber muss angegeben werden");
        }

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
