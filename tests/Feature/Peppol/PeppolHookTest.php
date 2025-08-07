<?php

namespace Tests\Feature\Peppol;

use Illuminate\Http\Request;
use Diji\Peppol\Http\Controllers\PeppolController;
use Tests\TestCase;

class PeppolHookTest extends TestCase
{
    public function test_all_invoice_fixtures_return_200()
    {
        $this->processFixturesIn('tests/Fixtures/Peppol/Invoice');
    }

    public function test_all_credit_note_fixtures_return_200()
    {
        $this->processFixturesIn('tests/Fixtures/Peppol/CreditNote');
    }

    private function processFixturesIn(string $path): void
    {
        $files = glob(base_path($path . '/*.json'));

        foreach ($files as $file) {
            $payload = json_decode(file_get_contents($file), true);

            $request = new Request();
            $request->replace($payload);

            $response = (new PeppolController())->hook($request);

            $this->assertTrue($response->isSuccessful(), "Erreur dans le fichier : {$file}");
        }
    }
}

