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
use Throwable;

class PeppolController extends Controller
{
    /**
     * Convertit les données fournies en XML UBL et les envoie à Digiteal.
     */
    public function convertToUbl(PeppolSendRequest $request): JsonResponse
    {
        $payload = PeppolPayloadBuilder::fromRequest($request);

        $xml = (new PeppolBuilder())
            ->withPayload($payload)
            ->build();

        $filename = $payload->document->billName . '.xml';
        $result = (new PeppolService())->sendInvoice($xml, $filename);

        return response()->json([
            'message' => 'Document Peppol généré et envoyé avec succès.',
            'digiteal_response' => $result,
            'filename' => $filename
        ]);
    }

    /**
     * Reçoit les hooks envoyés par Digiteal et traite les documents Peppol.
     */
    public function hook(Request $request): JsonResponse
    {
        try {
            $receiverPeppolId = strtoupper($request->input('recipientPeppolIdentifier'));
            $tenant = Tenant::where('peppol_identifier', $receiverPeppolId)->first();

            if (!$tenant) {
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
