<?php

namespace Diji\Peppol\Services;

use Diji\History\Models\History;
use DOMDocument;
use DOMXPath;
use Diji\Peppol\Models\PeppolDocument;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PeppolDocumentProcessor
{
    /**
     * Traite un document Peppol reçu via webhook, selon son type (facture ou note de crédit).
     */
    public function handle(Request $request): void
    {
        $changeType = $request->input('changeType');

        match ($changeType) {
            'INVOICE_PUBLISHED' => $this->handleInvoicePublished($request),
            'INVOICE_RECEIVED' => $this->handleInvoice($request),
            'CREDIT_NOTE_RECEIVED' => $this->handleCreditNote($request),
            default => throw new InvalidArgumentException("Type de document non supporté : $changeType")
        };
    }

    private function handleInvoice(Request $request): void
    {
        $xmlString = base64_decode($request->peppolFileContent);

        $xpath = $this->getXpath($xmlString);

        $data = $this->handleBaseData($request, $xpath, $xmlString);
        $data['lines'] = $this->handleInvoiceLines($xpath);

        PeppolDocument::create($data);
    }

    private function handleCreditNote(Request $request): void
    {
        $xmlString = base64_decode($request->peppolFileContent);

        $xpath = $this->getXpath($xmlString);

        $data = $this->handleBaseData($request, $xpath, $xmlString);
        $data['lines'] = $this->handleCreditNoteLines($xpath);

        PeppolDocument::create($data);
    }

    private function handleInvoicePublished(Request $request): void
    {
        $xmlString = base64_decode($request->peppolFileContent);

        $xpath = $this->getXpath($xmlString);

        $data = $this->handleBaseData($request, $xpath, $xmlString);
        $data['lines'] = $this->handleInvoiceLines($xpath);

        History::create([
            'model_type' => 'invoice',
            'model_id' => $data['document_identifier'],
            'message' => 'Facture publiée vers Peppol',
            'type' => 'success',
        ]);
    }

    private function handleBaseData(Request $request, DOMXPath $xpath, string $xmlString): array
    {
        return [
            'document_identifier' => $xpath->evaluate('string(//cbc:ID)'),
            'document_type' => match ($request->input('changeType')) {
                'INVOICE_RECEIVED' => 'INVOICE',
                'CREDIT_NOTE_RECEIVED' => 'CREDIT_NOTE',
                default => null,
            },
            'issue_date' => $xpath->evaluate('string(//cbc:IssueDate)'),
            'due_date' => $xpath->evaluate('string(//cbc:DueDate)') ?: null,
            'currency' => $xpath->evaluate('string(//cbc:DocumentCurrencyCode)'),
            'structured_communication' => $xpath->evaluate('string(//cbc:PaymentID)'),
            'subtotal' => (float)$xpath->evaluate('string(//cbc:TaxExclusiveAmount)') ?: 0,
            'total' => (float)$xpath->evaluate('string(//cbc:PayableAmount)') ?: 0,
            'taxes' => collect($xpath->query('//cac:TaxSubtotal'))->mapWithKeys(function ($node) use ($xpath) {
                $percent = (string)$xpath->evaluate('string(cac:TaxCategory/cbc:Percent)', $node);
                $amount = (float)$xpath->evaluate('string(cbc:TaxAmount)', $node);
                return [$percent => $amount];
            })->all(),
            'sender' => [
                'name' => $xpath->evaluate('string(//cac:AccountingSupplierParty/cac:Party/cac:PartyName/cbc:Name)'),
                'vatNumber' => $xpath->evaluate('string(//cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID)'),
                'iban' => $xpath->evaluate('string(//cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:ID)'),
            ],
            'sender_address' => [
                'line1' => $xpath->evaluate('string(//cac:AccountingSupplierParty//cac:PostalAddress/cbc:StreetName)'),
                'zipCode' => $xpath->evaluate('string(//cac:AccountingSupplierParty//cac:PostalAddress/cbc:PostalZone)'),
                'city' => $xpath->evaluate('string(//cac:AccountingSupplierParty//cac:PostalAddress/cbc:CityName)'),
                'country' => $xpath->evaluate('string(//cac:AccountingSupplierParty//cac:PostalAddress/cac:Country/cbc:IdentificationCode)'),
            ],
            'recipient' => [
                'name' => $xpath->evaluate('string(//cac:AccountingCustomerParty/cac:Party/cac:PartyName/cbc:Name)'),
                'vatNumber' => $xpath->evaluate('string(//cac:AccountingCustomerParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID)'),
            ],
            'recipient_address' => [
                'line1' => $xpath->evaluate('string(//cac:AccountingCustomerParty//cac:PostalAddress/cbc:StreetName)'),
                'zipCode' => $xpath->evaluate('string(//cac:AccountingCustomerParty//cac:PostalAddress/cbc:PostalZone)'),
                'city' => $xpath->evaluate('string(//cac:AccountingCustomerParty//cac:PostalAddress/cbc:CityName)'),
                'country' => $xpath->evaluate('string(//cac:AccountingCustomerParty//cac:PostalAddress/cac:Country/cbc:IdentificationCode)'),
            ],
            'raw_xml' => $xmlString,
        ];
    }

    private function handleInvoiceLines(DOMXPath $xpath): array
    {
        $lines = [];

        foreach ($xpath->query('//cac:InvoiceLine') as $line) {
            $lines[] = [
                'name' => $xpath->evaluate('string(cac:Item/cbc:Name)', $line),
                'quantity' => (float)$xpath->evaluate('string(cbc:InvoicedQuantity)', $line),
                'price' => (float)$xpath->evaluate('string(cac:Price/cbc:PriceAmount)', $line),
                'vat' => (float)$xpath->evaluate('string(cac:Item/cac:ClassifiedTaxCategory/cbc:Percent)', $line),
            ];
        }

        return $lines;
    }

    private function handleCreditNoteLines(DOMXPath $xpath): array
    {
        $lines = [];

        foreach ($xpath->query('//cac:CreditNoteLine') as $line) {
            $lines[] = [
                'name' => $xpath->evaluate('string(cac:Item/cbc:Name)', $line),
                'quantity' => (float)$xpath->evaluate('string(cbc:CreditedQuantity)', $line),
                'price' => (float)$xpath->evaluate('string(cac:Price/cbc:PriceAmount)', $line),
                'vat' => (float)$xpath->evaluate('string(cac:Item/cac:ClassifiedTaxCategory/cbc:Percent)', $line),
            ];
        }

        return $lines;
    }

    /**
     * Charge un XML et retourne un XPath configuré avec les namespaces UBL nécessaires.
     */
    private function getXpath(string $xmlString): DOMXPath
    {
        $dom = new DOMDocument();
        $dom->loadXML($xmlString);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

        return $xpath;
    }
}
