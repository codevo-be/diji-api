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
    public static function fromInvoice(InvoiceResource $invoice): PeppolPayloadDTO
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
        $receiver = (array)$invoice->recipient;

        $receiverAddress = new AddressDTO(
            line1: $receiver['street'] . ' ' . $receiver['street_number'],
            city: $receiver["city"],
            zipCode: $receiver["zipcode"],
            country: strtoupper($receiver['country'])
        );

        $receiverContact = new ReceiverContactDTO(
            name: $receiver['name'],
            phone: $receiver['phone'],
            email: $receiver['email']
        );

        $peppolIdentifier = $invoice['contact']['peppol_identifier'] ?? null;

        if (!$peppolIdentifier && isset($receiver['vat_number'])) {
            $cleanVat = preg_replace('/[^0-9]/', '', $receiver['vat_number']);
            $peppolIdentifier = "0208:" . $cleanVat;
        }

        $receiver = new ReceiverDTO(
            name: $receiver['name'],
            peppolIdentifier: $peppolIdentifier,
            vatNumber: $receiver['vat_number'],
            contact: $receiverContact,
            address: $receiverAddress
        );

        // Livraison
        $delivery = new DeliveryDTO(
            date: $invoice["date"],
        );

        // Paiement
        $payment = new PaymentDTO(
            paymentDelay: 30
        );

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
    }

    public static function fromCreditNote(CreditNoteResource $creditNote, string $referenceInvoiceId): PeppolPayloadDTO
    {
        // Document
        $document = new DocumentDTO(
            documentType: 'CreditNote',
            billName: $creditNote["identifier"],
            issueDate: $creditNote["date"],
            dueDate: "",
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
        $receiver = (array)$creditNote->recipient;

        $receiverAddress = new AddressDTO(
            line1: $receiver['street'] . ' ' . $receiver['street_number'],
            city: $receiver["city"],
            zipCode: $receiver["zipcode"],
            country: strtoupper($receiver['country'])
        );

        $receiverContact = new ReceiverContactDTO(
            name: $receiver['name'],
            phone: $receiver['phone'],
            email: $receiver['email']
        );

        $peppolIdentifier = $invoice['contact']['peppol_identifier'] ?? null;

        if (!$peppolIdentifier && isset($receiver['vat_number'])) {
            $cleanVat = preg_replace('/[^0-9]/', '', $receiver['vat_number']);
            $peppolIdentifier = "0208:" . $cleanVat;
        }

        $receiver = new ReceiverDTO(
            name: $receiver['name'],
            peppolIdentifier: $peppolIdentifier,
            vatNumber: $receiver['vat_number'],
            contact: $receiverContact,
            address: $receiverAddress
        );


        // Livraison
        $delivery = new DeliveryDTO(
            date: $creditNote["date"],
        );

        // Paiement
        $payment = new PaymentDTO(
            paymentDelay: 30
        );

        // Lignes de facture
        $lines = collect($creditNote['items'] ?? [])
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
            totalTaxableAmount: (float) $creditNote['subtotal'],
            totalAmount: (float) $creditNote['total']
        );

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
    }

    public static function getPeppolVatCode(float $taxPercentage): string
    {
        return match ((int) $taxPercentage) {
            0 => 'K',
            default => 'S',
        };
    }
}
