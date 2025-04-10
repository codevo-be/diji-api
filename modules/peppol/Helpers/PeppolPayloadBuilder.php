<?php

namespace Diji\Peppol\Helpers;

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
use Diji\Peppol\Requests\PeppolSendRequest;

class PeppolPayloadBuilder
{
    public static function fromRequest(PeppolSendRequest $request): PeppolPayloadDTO
    {
        $document = new DocumentDTO(
            documentType: $request->input('document.document_type'),
            billName: $request->input('document.bill_name'),
            issueDate: $request->input('document.issue_date'),
            dueDate: $request->input('document.due_date'),
            currency: $request->input('document.currency'),
            buyerReference: $request->input('document.buyer_reference'),
            structuredCommunication: $request->input('document.structured_communication')
        );

        $senderAddress = new AddressDTO(
            line1: $request->input('sender.address.line1'),
            city: $request->input('sender.address.city'),
            zipCode: $request->input('sender.address.zip_code'),
            country: $request->input('sender.address.country')
        );

        $sender = new SenderDTO(
            name: $request->input('sender.name'),
            vatNumber: $request->input('sender.vat_number'),
            iban: $request->input('sender.iban'),
            address: $senderAddress
        );

        $receiverAddress = new AddressDTO(
            line1: $request->input('receiver.address.line1'),
            city: $request->input('receiver.address.city'),
            zipCode: $request->input('receiver.address.zip_code'),
            country: $request->input('receiver.address.country')
        );

        $receiverContact = new ReceiverContactDTO(
            name: $request->input('receiver.contact.name'),
            phone: $request->input('receiver.contact.phone'),
            email: $request->input('receiver.contact.email')
        );

        $receiver = new ReceiverDTO(
            name: $request->input('receiver.name'),
            peppolIdentifier: $request->input('receiver.peppol_identifier'),
            vatNumber: $request->input('receiver.vat_number'),
            contact: $receiverContact,
            address: $receiverAddress
        );

        $delivery = new DeliveryDTO(
            date: $request->input('delivery.date')
        );

        $payment = new PaymentDTO(
            paymentDelay: $request->input('payment.payment_delay')
        );

        $lines = collect($request->input('lines', []))
            ->map(fn(array $line) => new InvoiceLineDTO(
                description: $line['description'],
                quantity: $line['quantity'],
                unitPrice: $line['unit_price'],
                taxableAmount: $line['taxable_amount'],
                vatCode: $line['vat_code'],
                taxPercentage: $line['tax_percentage'],
            ))
            ->all();

        $taxes = collect($request->input('taxes', []))
            ->map(fn(array $tax) => new TaxDTO(
                vatCode: $tax['vat_code'],
                taxPercentage: $tax['tax_percentage'],
                taxableAmount: $tax['taxable_amount'],
                taxAmount: $tax['tax_amount'],
            ))
            ->all();

        $totals = new MonetaryTotalDTO(
            totalTaxableAmount: $request->input('totals.total_taxable_amount'),
            totalAmount: $request->input('totals.total_amount')
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
}
