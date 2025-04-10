<?php

namespace Peppol\Controllers;

use App\Http\Controllers\Controller;
use Billing\Helpers\PeppolBuilder;
use Billing\Requests\Peppol\PeppolSendRequest;
use Billing\Services\PeppolPayloadAssembler;
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
            ->withDocumentType('Invoice')
            ->withDocumentInfo($payload->document)
            ->withBuyerReference($payload->buyerReference)
            ->withSender($payload->sender)
            ->withReceiver($payload->receiver)
            ->withDelivery($payload->delivery)
            ->withPayment($payload->payment)
            ->withTaxes($payload->taxes)
            ->withMonetaryTotal($payload->totals)
            ->withLines($payload->lines)
            ->build();

        // Enregistrement local pour debug/audit
        Storage::disk('local')->put('peppol/peppol.xml', $xml);

        return response()->json([
            'message' => 'Document Peppol généré avec succès',
            'xml' => $xml
        ]);
    }
}
