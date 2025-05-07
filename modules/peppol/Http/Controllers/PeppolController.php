<?php

namespace Diji\Peppol\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Diji\Peppol\Helpers\PeppolBuilder;
use Diji\Peppol\Helpers\PeppolPayloadBuilder;
use Diji\Peppol\Requests\PeppolSendRequest;
use Diji\Peppol\Services\PeppolDocumentProcessor;
use Diji\Peppol\Services\PeppolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PeppolController extends Controller
{
    public function convertToUbl(PeppolSendRequest $request): JsonResponse
    {
        // 1. Construction des DTO à partir de la requête
        $payload = PeppolPayloadBuilder::fromRequest($request);

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

    public function hook(Request $request): JsonResponse
    {
        try {
            $receiverPeppolId = strtoupper($request->input('recipientPeppolIdentifier'));
            $tenant = Tenant::where('peppol_identifier', $receiverPeppolId)->first();

            if (!$tenant) {
                Log::warning("Aucun tenant trouvé avec le Peppol ID : {$receiverPeppolId}");
                return response()->json([
                    'error' => true,
                    'message' => "Aucun client ne correspond à l'identifiant Peppol reçu.",
                    'peppol_identifier' => $receiverPeppolId,
                ], 404);
            }

            tenancy()->initialize($tenant);
            app(PeppolDocumentProcessor::class)->handle($request);
            return response()->json([
                'message' => 'Hook reçu et document Peppol enregistré avec succès.',
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Erreur lors du traitement du XML.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
