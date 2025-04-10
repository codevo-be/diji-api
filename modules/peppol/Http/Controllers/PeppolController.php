<?php

namespace Diji\Peppol\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Peppol\Helpers\PeppolBuilder;
use Diji\Peppol\Requests\PeppolSendRequest;
use Diji\Peppol\Services\PeppolPayloadAssembler;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class PeppolController extends Controller
{
    public function convertToUbl(PeppolSendRequest $request): JsonResponse
    {
        // Convertit les données de la requête en un ensemble de DTOs
        $payload = PeppolPayloadAssembler::fromRequest($request);

        // Génère le XML UBL
        $xml = (new PeppolBuilder())
            ->withPayload($payload)
            ->build();

        // Enregistrement local pour debug/audit
        Storage::disk('local')->put('peppol/peppol.xml', $xml);

        return response()->json([
            'message' => 'Document Peppol généré avec succès',
            'xml' => $xml
        ]);
    }
}
