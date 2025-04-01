<?php

namespace Diji\Billing\Services;

use App\Models\Meta;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Diji\Billing\Models\NordigenAccount;
use Diji\Billing\Models\NordigenToken;
use Exception;


class NordigenService
{
    protected Client $client;
    protected ?string $token;
    protected ?string $account_id;
    protected ?string $nordigen_secret_id;
    protected ?string $nordigen_secret_key;

    public function __construct()
    {
        $this->nordigen_secret_id = Meta::getValue('nordigen_secret_id');
        $this->nordigen_secret_key = Meta::getValue('nordigen_secret_key');

        if(!$this->nordigen_secret_id || !$this->nordigen_secret_key){
            throw new \InvalidArgumentException("Le compte n'est pas configuré correctement !");
        }

        $this->client = new Client(['base_uri' => 'https://bankaccountdata.gocardless.com/api/v2/']);
        $this->token = $this->getAccessToken();
        $this->account_id = $this->getAccountId();
    }

    private function getAccessToken()
    {
        $validToken = NordigenToken::getValidToken();

        if ($validToken) {
            return $validToken;
        }

        return $this->refreshAccessToken();
    }

    private function refreshAccessToken()
    {
        $latestToken = NordigenToken::latest()->first();

        if (!$latestToken || Carbon::now()->gte($latestToken->refresh_expires_at)) {
            return $this->generateNewToken();
        }

        try {
            $response = $this->client->post('token/refresh/', [
                'json' => ['refresh' => $latestToken->refresh_token]
            ]);

            $data = json_decode($response->getBody(), true);
            NordigenToken::saveToken(
                $data['access'],
                $data['access_expires'],
                $data['refresh'],
                $data['refresh_expires']
            );

            return $data['access'];
        } catch (\Exception $e) {
            return $this->generateNewToken();
        }
    }

    private function generateNewToken()
    {
        $response = $this->client->post('token/new/', [
            'json' => [
                'secret_id' => $this->nordigen_secret_id,
                'secret_key' => $this->nordigen_secret_key,
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        NordigenToken::saveToken(
            $data['access'],
            $data['access_expires'],
            $data['refresh'],
            $data['refresh_expires']
        );

        return $data['access'];
    }

    private function getAccountId()
    {
        return NordigenAccount::getValidAccountId();
    }

    public function getInstitutions()
    {
        $response = $this->client->get("institutions", [
            'headers' => ['Authorization' => "Bearer $this->token"],
            'query' => ['country' => 'BE']
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function getTransactions()
    {
        try{
            $response = $this->client->get("accounts/{$this->account_id}/transactions/", [
                'headers' => ['Authorization' => "Bearer $this->token"],
                'query' => [
                    'date_from' => \Illuminate\Support\Carbon::now()->subDay()->startOfDay()->toDateString()
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        }catch (\Exception $e){
            throw new Exception("Erreur lors de la récupération des transactions. Error : " . $e->getMessage());
        }
    }

    public function createRequisition(string $institution_id)
    {
        $tenant = tenant();

        $response = $this->client->post('requisitions/', [
            'headers' => ['Authorization' => "Bearer $this->token"],
            'json' => [
                'redirect' => env('NORDIGEN_REDIRECT_URL') . "?tenant_id=$tenant->id",
                'institution_id' => $institution_id,
                'user_language' => 'FR',
                'reference' => uniqid()
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    public function getRequisitionByReference(string $reference)
    {
        try {
            $response = $this->client->get("requisitions/", [
                'headers' => ['Authorization' => "Bearer $this->token"]
            ]);

            $data = json_decode($response->getBody(), true);

            if (!isset($data['results']) || empty($data['results'])) {
                return null;
            }

            foreach ($data['results'] as $requisition) {
                if ($requisition['reference'] === $reference) {
                    return $requisition;
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
