<?php

namespace Diji\Peppol\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Peppol\Helpers\PeppolBuilder;
use Diji\Peppol\Helpers\PeppolPayloadBuilder;
use Diji\Peppol\Models\PeppolDocument;
use Diji\Peppol\Requests\PeppolSendRequest;
use Diji\Peppol\Services\PeppolService;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        Log::info('[HOOK PEPPOL] Données reçues :' . json_encode($request->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $xmlString = base64_decode($request->peppolFileContent);

        try {
            $dom = new DOMDocument();
            $dom->loadXML($xmlString);
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
            $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

            // Récupération des données principales
            $documentIdentifier = $xpath->evaluate('string(//cbc:ID)');
            $documentType = match ($xpath->evaluate('string(//cbc:InvoiceTypeCode)')) {
                '380' => 'INVOICE',
                '381' => 'CREDIT_NOTE',
                default => null,
            };
            $issueDate = $xpath->evaluate('string(//cbc:IssueDate)');
            $dueDate = $xpath->evaluate('string(//cbc:DueDate)');
            $currency = $xpath->evaluate('string(//cbc:DocumentCurrencyCode)');
            $structuredCommunication = $xpath->evaluate('string(//cbc:PaymentID)');

            // Montants
            $subtotal = (float) $xpath->evaluate('string(//cbc:TaxExclusiveAmount)') ?: 0;
            $total = (float) $xpath->evaluate('string(//cbc:PayableAmount)') ?: 0;

            // TVA
            $taxAmount = $xpath->evaluate('string(//cac:TaxTotal/cbc:TaxAmount)');

            // Expéditeur
            $sender = [
                'name' => $xpath->evaluate('string(//cac:AccountingSupplierParty/cac:Party/cac:PartyName/cbc:Name)'),
                'vatNumber' => $xpath->evaluate('string(//cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID)'),
                'iban' => $xpath->evaluate('string(//cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:ID)'),
            ];
            $senderAddress = [
                'line1' => $xpath->evaluate('string(//cac:AccountingSupplierParty//cac:PostalAddress/cbc:StreetName)'),
                'zipCode' => $xpath->evaluate('string(//cac:AccountingSupplierParty//cac:PostalAddress/cbc:PostalZone)'),
                'city' => $xpath->evaluate('string(//cac:AccountingSupplierParty//cac:PostalAddress/cbc:CityName)'),
                'country' => $xpath->evaluate('string(//cac:AccountingSupplierParty//cac:PostalAddress/cac:Country/cbc:IdentificationCode)'),
            ];

            // Destinataire
            $recipient = [
                'name' => $xpath->evaluate('string(//cac:AccountingCustomerParty/cac:Party/cac:PartyName/cbc:Name)'),
                'vatNumber' => $xpath->evaluate('string(//cac:AccountingCustomerParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID)'),
            ];
            $recipientAddress = [
                'line1' => $xpath->evaluate('string(//cac:AccountingCustomerParty//cac:PostalAddress/cbc:StreetName)'),
                'zipCode' => $xpath->evaluate('string(//cac:AccountingCustomerParty//cac:PostalAddress/cbc:PostalZone)'),
                'city' => $xpath->evaluate('string(//cac:AccountingCustomerParty//cac:PostalAddress/cbc:CityName)'),
                'country' => $xpath->evaluate('string(//cac:AccountingCustomerParty//cac:PostalAddress/cac:Country/cbc:IdentificationCode)'),
            ];

            // Lignes de facturation
            $lines = [];
            foreach ($xpath->query('//cac:InvoiceLine') as $line) {
                $lines[] = [
                    'name' => $xpath->evaluate('string(cac:Item/cbc:Name)', $line),
                    'quantity' => (float) $xpath->evaluate('string(cbc:InvoicedQuantity)', $line),
                    'price' => (float) $xpath->evaluate('string(cac:Price/cbc:PriceAmount)', $line),
                    'vat' => (float) $xpath->evaluate('string(cac:Item/cac:ClassifiedTaxCategory/cbc:Percent)', $line),
                ];
            }

            PeppolDocument::create([
                'document_identifier' => $documentIdentifier,
                'document_type' => $documentType,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'currency' => $currency,
                'structured_communication' => $structuredCommunication,
                'subtotal' => $subtotal,
                'total' => $total,
                'taxes' => ['total' => $taxAmount],
                'sender' => $sender,
                'recipient' => $recipient,
                'sender_address' => $senderAddress,
                'recipient_address' => $recipientAddress,
                'lines' => $lines,
                'raw_xml' => $xmlString,
            ]);

        } catch (Exception $e) {
            Log::error('[HOOK PEPPOL] Erreur DOM/XML : ' . $e->getMessage());

            return response()->json([
                'message' => 'Erreur lors du traitement du XML.',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Hook reçu et document Peppol enregistré avec succès.',
        ]);
    }
}
