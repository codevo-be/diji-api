<?php

namespace Diji\Peppol\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Peppol\Helpers\PeppolBuilder;
use Diji\Peppol\Requests\PeppolSendRequest;
use Diji\Peppol\Services\PeppolPayloadAssembler;
use Diji\Peppol\Services\PeppolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PeppolController extends Controller
{
    public function convertToUbl(PeppolSendRequest $request): JsonResponse
    {
        // 1. Construction des DTO à partir de la requête
        $payload = PeppolPayloadAssembler::fromRequest($request);

        // 2. Génération du XML UBL
        $xml = (new PeppolBuilder())
            ->withPayload($payload)
            ->build();

        // 3. Sauvegarde locale du fichier pour audit/debug
        $filename = $payload->document->billName . '.xml';
        Storage::disk('local')->put("peppol/{$filename}", $xml);

        // 4. Envoi à Digiteal via le service Peppol
        $result = (new PeppolService())->sendInvoice($xml, $filename);
        Log::info("Résultat de l'envoi à Digiteal", $result);

        // 5. Réponse JSON avec le résultat
        return response()->json([
            'message' => 'Document Peppol généré et envoyé avec succès.',
            'digiteal_response' => $result,
            'filename' => $filename
        ]);
    }
}
