<?php

namespace Diji\Billing\Helpers;

use Diji\Billing\Resources\CreditNoteResource;
use Diji\Billing\Resources\InvoiceResource;
use Diji\Peppol\DTO\AddressDTO;
use Diji\Peppol\DTO\DeliveryDTO;
use Diji\Peppol\DTO\DocumentDTO;
use Diji\Peppol\DTO\InvoiceLineDTO;
use Diji\Peppol\DTO\MonetaryTotalDTO;
use Diji\Peppol\DTO\PaymentDTO;
use Diji\Peppol\DTO\PeppolPayloadDTO;
use Diji\Peppol\DTO\ReceiverContactDTO;
use Diji\Peppol\DTO\ReceiverDTO;
use Diji\Peppol\DTO\SenderDTO;
use Diji\Peppol\DTO\TaxDTO;

class PeppolPayloadDTOBuilder
{
    public static function fromInvoice(InvoiceResource $invoice): array
    {
        // Document
        $document = new DocumentDTO(
            documentType: 'Invoice',
            billName: $invoice["identifier"],
            issueDate: $invoice["date"],
            dueDate: $invoice["due_date"],
            currency: 'EUR',
            buyerReference: $invoice['identifier'],
            structuredCommunication: $invoice['structured_communication']
        );

        // Émetteur
        $issuer = (array)$invoice->issuer;

        $senderAddress = new AddressDTO(
            line1: $issuer['street'] . ' ' . $issuer['street_number'],
            city: $issuer['city'],
            zipCode: $issuer['zipcode'],
            country: strtoupper($issuer['country'])
        );

        $sender = new SenderDTO(
            name: $issuer['name'],
            vatNumber: $issuer['vat_number'],
            iban: $issuer['iban'],
            address: $senderAddress
        );

        // Destinataire
        $receiverRaw = (array)$invoice->recipient;

        $receiverAddress = new AddressDTO(
            line1: $receiverRaw['street'] . ' ' . $receiverRaw['street_number'],
            city: $receiverRaw["city"],
            zipCode: $receiverRaw["zipcode"],
            country: strtoupper($receiverRaw['country'])
        );

        $receiverContact = new ReceiverContactDTO(
            name: $receiverRaw['name'],
            phone: $receiverRaw['phone'],
            email: $receiverRaw['email']
        );

        $vatNumber = $receiverRaw['vat_number'] ?? null;
        $cleanVat = $vatNumber ? strtoupper(preg_replace('/\D/', '', $vatNumber)) : null;

        $identifiers = [];

        // 1. Identifiant personnalisé
        if (!empty($invoice['contact']['peppol_identifier'])) {
            $identifiers[] = $invoice['contact']['peppol_identifier'];
        }

        // 2. Numéro BCE (0208)
        if ($cleanVat && strlen($cleanVat) === 10) {
            $identifiers[] = '0208:' . $cleanVat;
        }

        // 3. Numéro de TVA (9925)
        if ($vatNumber) {
            $identifiers[] = '9925:' . strtoupper($vatNumber);
        }

        // Lignes de facture
        $lines = collect($invoice['items'] ?? [])
            ->map(function ($item) {
                return new InvoiceLineDTO(
                    description: $item['name'],
                    quantity: $item['quantity'],
                    unitPrice: $item['retail']['subtotal'],
                    taxableAmount: $item['retail']['subtotal'] * $item['quantity'],
                    vatCode: self::getPeppolVatCode($item['vat']),
                    taxPercentage: $item['vat']
                );
            })
            ->all();

        // Taxes
        $taxes = collect($lines)
            ->groupBy(fn(InvoiceLineDTO $line) => $line->taxPercentage)
            ->map(function ($groupedLines, $percentage) {
                $taxPercentage = (float) $percentage;
                $taxableAmount = collect($groupedLines)
                    ->sum(fn(InvoiceLineDTO $line) => $line->taxableAmount);
                $taxAmount = round(($taxableAmount * $taxPercentage) / 100, 2);

                return new TaxDTO(
                    vatCode: PeppolPayloadDTOBuilder::getPeppolVatCode($taxPercentage),
                    taxPercentage: $taxPercentage,
                    taxableAmount: $taxableAmount,
                    taxAmount: $taxAmount
                );
            })
            ->values()
            ->all();

        // Totaux
        $totals = new MonetaryTotalDTO(
            totalTaxableAmount: (float) $invoice['subtotal'],
            totalAmount: (float) $invoice['total']
        );

        // Générer un payload par identifiant
        return collect($identifiers)
            ->unique()
            ->map(function ($identifier) use (
                $document,
                $sender,
                $receiverRaw,
                $receiverAddress,
                $receiverContact,
                $lines,
                $taxes,
                $totals,
                $invoice
            ) {
                $receiver = new ReceiverDTO(
                    name: $receiverRaw['name'],
                    peppolIdentifier: $identifier,
                    vatNumber: $receiverRaw['vat_number'],
                    contact: $receiverContact,
                    address: $receiverAddress
                );

                $delivery = new DeliveryDTO(date: $invoice["date"]);
                $payment = new PaymentDTO(paymentDelay: 30);

                return new PeppolPayloadDTO(
                    document: $document,
                    sender: $sender,
                    receiver: $receiver,
                    delivery: $delivery,
                    payment: $payment,
                    lines: $lines,
                    taxes: $taxes,
                    totals: $totals
                );
            })
            ->all();
    }

    public static function fromCreditNote(CreditNoteResource $creditNote, string $referenceInvoiceId): array
    {
        // Document
        $document = new DocumentDTO(
            documentType: 'CreditNote',
            billName: $creditNote["identifier"],
            issueDate: $creditNote["date"],
            dueDate: "", // Les notes de crédit n'ont pas toujours de dueDate
            currency: 'EUR',
            buyerReference: $creditNote['identifier'],
            structuredCommunication: "",
            referenceInvoiceId: $referenceInvoiceId
        );

        // Émetteur
        $issuer = (array)$creditNote->issuer;

        $senderAddress = new AddressDTO(
            line1: $issuer['street'] . ' ' . $issuer['street_number'],
            city: $issuer['city'],
            zipCode: $issuer['zipcode'],
            country: strtoupper($issuer['country'])
        );

        $sender = new SenderDTO(
            name: $issuer['name'],
            vatNumber: $issuer['vat_number'],
            iban: $issuer['iban'],
            address: $senderAddress
        );

        // Destinataire
        $receiverRaw = (array)$creditNote->recipient;

        $receiverAddress = new AddressDTO(
            line1: $receiverRaw['street'] . ' ' . $receiverRaw['street_number'],
            city: $receiverRaw["city"],
            zipCode: $receiverRaw["zipcode"],
            country: strtoupper($receiverRaw['country'])
        );

        $receiverContact = new ReceiverContactDTO(
            name: $receiverRaw['name'],
            phone: $receiverRaw['phone'],
            email: $receiverRaw['email']
        );

        $vatNumber = $receiverRaw['vat_number'] ?? null;
        $cleanVat = $vatNumber ? strtoupper(preg_replace('/\D/', '', $vatNumber)) : null;

        $identifiers = [];

        // 1. Identifiant personnalisé
        if (!empty($creditNote['contact']['peppol_identifier'])) {
            $identifiers[] = $creditNote['contact']['peppol_identifier'];
        }

        // 2. Numéro BCE (0208)
        if ($cleanVat && strlen($cleanVat) === 10) {
            $identifiers[] = '0208:' . $cleanVat;
        }

        // 3. Numéro de TVA (9925)
        if ($vatNumber) {
            $identifiers[] = '9925:' . strtoupper($vatNumber);
        }

        // Lignes
        $lines = collect($creditNote['items'] ?? [])
            ->map(function ($item) {
                return new InvoiceLineDTO(
                    description: $item['name'],
                    quantity: $item['quantity'],
                    unitPrice: $item['retail']['subtotal'],
                    taxableAmount: $item['retail']['subtotal'] * $item['quantity'],
                    vatCode: PeppolPayloadDTOBuilder::getPeppolVatCode($item['vat']),
                    taxPercentage: $item['vat']
                );
            })
            ->all();

        // Taxes
        $taxes = collect($lines)
            ->groupBy(fn(InvoiceLineDTO $line) => $line->taxPercentage)
            ->map(function ($groupedLines, $percentage) {
                $taxPercentage = (float) $percentage;
                $taxableAmount = collect($groupedLines)
                    ->sum(fn(InvoiceLineDTO $line) => $line->taxableAmount);
                $taxAmount = round(($taxableAmount * $taxPercentage) / 100, 2);

                return new TaxDTO(
                    vatCode: PeppolPayloadDTOBuilder::getPeppolVatCode($taxPercentage),
                    taxPercentage: $taxPercentage,
                    taxableAmount: $taxableAmount,
                    taxAmount: $taxAmount
                );
            })
            ->values()
            ->all();

        // Totaux
        $totals = new MonetaryTotalDTO(
            totalTaxableAmount: (float) $creditNote['subtotal'],
            totalAmount: (float) $creditNote['total']
        );

        // Générer un payload par identifiant
        return collect($identifiers)
            ->unique()
            ->map(function ($identifier) use (
                $document,
                $sender,
                $receiverRaw,
                $receiverAddress,
                $receiverContact,
                $lines,
                $taxes,
                $totals
            ) {
                $receiver = new ReceiverDTO(
                    name: $receiverRaw['name'],
                    peppolIdentifier: $identifier,
                    vatNumber: $receiverRaw['vat_number'],
                    contact: $receiverContact,
                    address: $receiverAddress
                );

                $delivery = new DeliveryDTO(date: now()->toDateString());
                $payment = new PaymentDTO(paymentDelay: 30);

                return new PeppolPayloadDTO(
                    document: $document,
                    sender: $sender,
                    receiver: $receiver,
                    delivery: $delivery,
                    payment: $payment,
                    lines: $lines,
                    taxes: $taxes,
                    totals: $totals
                );
            })
            ->all();
    }

    public static function getPeppolVatCode(float $taxPercentage): string
    {
        return match ((int) $taxPercentage) {
            0 => 'K',
            default => 'S',
        };
    }
}
