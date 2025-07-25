<?php

namespace Diji\Peppol\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class PeppolService
{
    protected Client $client;
    protected string $username;
    protected string $password;

    public function __construct()
    {
        $this->username = env("PEPPOL_DIGITEAL_USERNAME");
        $this->password = env("PEPPOL_DIGITEAL_PASSWORD");

        $this->client = new Client([
            'base_uri' => 'https://test.digiteal.eu/api/v1/peppol/',
        ]);
    }

    /**
     * Envoie un document UBL à Digiteal via Peppol.
     */
    public function sendInvoice(string $ublXml, string $filename = 'invoice.xml'): array
    {
        $authHeader = 'Basic ' . base64_encode("{$this->username}:{$this->password}");

        try {
            $response = $this->client->request('POST', 'outbound-ubl-documents', [
                'headers' => [
                    'Authorization' => $authHeader,
                    'Accept' => 'application/json',
                ],
                'multipart' => [
                    [
                        'name' => 'document',
                        'contents' => $ublXml,
                        'filename' => $filename,
                        'headers' => ['Content-Type' => 'application/xml'],
                    ]
                ]
            ]);

            return [
                'status' => 'success',
                'response' => $response->getBody()->getContents(),
            ];
        } catch (RequestException $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ];
        }
    }
}
