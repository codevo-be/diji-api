<?php

namespace Diji\Peppol\Helpers;

use Diji\Peppol\DTO\PeppolPayloadDTO;

use DOMDocument;
use DOMElement;


class PeppolBuilder
{
    protected PeppolPayloadDTO $payload;
    protected DOMDocument $doc;
    protected DOMElement $documentElement;


    public function withPayload(PeppolPayloadDTO $payload): self
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * Construit le XML UBL à partir des données injectées.
     */
    public function build(): string
    {
        $this->doc = new DOMDocument('1.0', 'UTF-8');
        $this->doc->formatOutput = true;

        $this->prepareRootElement();
        $this->addPeppolMetadata();
        $this->addDocumentInfo();
        $this->addBuyerReference();
        $this->addAttachments(); // Optionnel
        $this->addSender();
        $this->addReceiver();
        $this->addDelivery();
        $this->addPayment();
        $this->addTaxes();
        $this->addMonetaryTotal();
        $this->addInvoiceLines();

        return $this->doc->saveXML();
    }

    protected function prepareRootElement(): void
    {
        $root = $this->doc->createElementNS(
            'urn:oasis:names:specification:ubl:schema:xsd:' . $this->documentType . '-2',
            $this->documentType
        );

        $root->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:cbc',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2'
        );

        $root->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:cac',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2'
        );

        $this->doc->appendChild($root);
        $this->documentElement = $root;
    }
    protected function addPeppolMetadata(): void
    {
        $this->documentElement->appendChild(
            $this->doc->createElement('cbc:CustomizationID', 'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0')
        );

        $this->documentElement->appendChild(
            $this->doc->createElement('cbc:ProfileID', 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0')
        );
    }
    protected function addDocumentInfo(): void
    {
        if (!isset($this->documentInfo)) {
            throw new \RuntimeException('Aucune donnée de document définie. Utilisez withDocumentInfo().');
        }

        $this->documentElement->appendChild($this->doc->createElement('cbc:ID', $this->documentInfo->id));
        $this->documentElement->appendChild($this->doc->createElement('cbc:IssueDate', $this->documentInfo->issueDate));
        $this->documentElement->appendChild($this->doc->createElement('cbc:DueDate', $this->documentInfo->dueDate));

        $typeCode = $this->documentType === 'Invoice' ? '380' : '381';
        $this->documentElement->appendChild($this->doc->createElement('cbc:InvoiceTypeCode', $typeCode));

        $this->documentElement->appendChild($this->doc->createElement('cbc:DocumentCurrencyCode', $this->documentInfo->currency));
    }
    protected function addBuyerReference(): void
    {
        if (!empty($this->buyerReference)) {
            $this->documentElement->appendChild(
                $this->doc->createElement('cbc:BuyerReference', $this->buyerReference)
            );
        }
    }
    protected function addAttachments(): void
    {
        if (!$this->attachmentsData) {
            return;
        }

        // PDF principal
        if (!empty($this->attachmentsData->mainPdfBase64) && $this->attachmentsData->billName && $this->attachmentsData->senderName) {
            $reference = $this->doc->createElement('cac:AdditionalDocumentReference');
            $reference->appendChild($this->doc->createElement('cbc:ID', $this->attachmentsData->billName . '-InvoicePDF'));

            $attachment = $this->doc->createElement('cac:Attachment');
            $embedded = $this->doc->createElement('cbc:EmbeddedDocumentBinaryObject', $this->attachmentsData->mainPdfBase64);
            $embedded->setAttribute('mimeCode', 'application/pdf');
            $embedded->setAttribute('filename', $this->attachmentsData->senderName . '-' . $this->attachmentsData->billName . '.pdf');

            $attachment->appendChild($embedded);
            $reference->appendChild($attachment);
            $this->documentElement->appendChild($reference);
        }

        // Autres fichiers
        foreach ($this->attachmentsData->attachments as $att) {
            if ($att instanceof PeppolAttachment) {
                $reference = $this->doc->createElement('cac:AdditionalDocumentReference');
                $reference->appendChild($this->doc->createElement('cbc:ID', $att->fileName));

                $attachment = $this->doc->createElement('cac:Attachment');
                $embedded = $this->doc->createElement('cbc:EmbeddedDocumentBinaryObject', $att->base64);
                $embedded->setAttribute('mimeCode', $att->mimeType);
                $embedded->setAttribute('filename', $att->fileName);

                $attachment->appendChild($embedded);
                $reference->appendChild($attachment);
                $this->documentElement->appendChild($reference);
            }
        }
    }
    protected function addSender(): void
    {
        if (!isset($this->sender)) {
            throw new \RuntimeException("Aucune information sur le fournisseur. Veuillez appeler withSender().");
        }

        $party = $this->doc->createElement('cac:AccountingSupplierParty');
        $partyNode = $this->doc->createElement('cac:Party');

        // EndpointID
        $endpointId = $this->doc->createElement('cbc:EndpointID', strtoupper($this->sender->vatNumber));
        $endpointId->setAttribute('schemeID', $this->getPeppolSchemeId(strtoupper($this->sender->vatNumber)));
        $partyNode->appendChild($endpointId);

        // Nom
        $partyName = $this->doc->createElement('cac:PartyName');
        $partyName->appendChild($this->doc->createElement('cbc:Name', $this->sender->name));
        $partyNode->appendChild($partyName);

        // Adresse
        $address = $this->doc->createElement('cac:PostalAddress');
        $address->appendChild($this->doc->createElement('cbc:StreetName', $this->sender->addressLine1));
        $address->appendChild($this->doc->createElement('cbc:CityName', $this->sender->city));
        $address->appendChild($this->doc->createElement('cbc:PostalZone', $this->sender->zipCode));

        $country = $this->doc->createElement('cac:Country');
        $country->appendChild($this->doc->createElement('cbc:IdentificationCode', $this->sender->country));
        $address->appendChild($country);
        $partyNode->appendChild($address);

        // TaxScheme
        $taxScheme = $this->doc->createElement('cac:PartyTaxScheme');
        $taxScheme->appendChild($this->doc->createElement('cbc:CompanyID', strtoupper($this->sender->vatNumber)));
        $tax = $this->doc->createElement('cac:TaxScheme');
        $tax->appendChild($this->doc->createElement('cbc:ID', 'VAT'));
        $taxScheme->appendChild($tax);
        $partyNode->appendChild($taxScheme);

        // Legal Entity
        $legal = $this->doc->createElement('cac:PartyLegalEntity');
        $legal->appendChild($this->doc->createElement('cbc:RegistrationName', $this->sender->name));
        $legal->appendChild($this->doc->createElement('cbc:CompanyID', strtoupper($this->sender->vatNumber)));
        $partyNode->appendChild($legal);

        $party->appendChild($partyNode);
        $this->documentElement->appendChild($party);
    }
    protected function addReceiver(): void
    {
        if (!isset($this->receiver)) {
            throw new \RuntimeException("Aucune information sur le client. Veuillez appeler withRe().");
        }

        $party = $this->doc->createElement('cac:AccountingCustomerParty');
        $partyNode = $this->doc->createElement('cac:Party');

        // EndpointID
        $endpointId = $this->doc->createElement('cbc:EndpointID', $this->getIdFromPeppolIdentifier(strtoupper($this->receiver->peppolIdentifier)));
        $endpointId->setAttribute('schemeID', $this->getSchemeIdFromPeppolIdentifier(strtoupper($this->receiver->peppolIdentifier)));
        $partyNode->appendChild($endpointId);

        // Nom
        $partyName = $this->doc->createElement('cac:PartyName');
        $partyName->appendChild($this->doc->createElement('cbc:Name', $this->receiver->name));
        $partyNode->appendChild($partyName);

        // Adresse
        $address = $this->doc->createElement('cac:PostalAddress');
        $address->appendChild($this->doc->createElement('cbc:StreetName', $this->receiver->addressLine1));
        $address->appendChild($this->doc->createElement('cbc:CityName', $this->receiver->city));
        $address->appendChild($this->doc->createElement('cbc:PostalZone', $this->receiver->zipCode));
        $country = $this->doc->createElement('cac:Country');
        $country->appendChild($this->doc->createElement('cbc:IdentificationCode', $this->receiver->country));
        $address->appendChild($country);
        $partyNode->appendChild($address);

        // TVA
        $taxScheme = $this->doc->createElement('cac:PartyTaxScheme');
        $taxScheme->appendChild($this->doc->createElement('cbc:CompanyID', strtoupper($this->receiver->vatNumber)));
        $tax = $this->doc->createElement('cac:TaxScheme');
        $tax->appendChild($this->doc->createElement('cbc:ID', 'VAT'));
        $taxScheme->appendChild($tax);
        $partyNode->appendChild($taxScheme);

        // Legal Entity
        $legal = $this->doc->createElement('cac:PartyLegalEntity');
        $legal->appendChild($this->doc->createElement('cbc:RegistrationName', $this->receiver->name));
        $legal->appendChild($this->doc->createElement('cbc:CompanyID', strtoupper($this->receiver->vatNumber)));
        $partyNode->appendChild($legal);

        // Contact (optionnel)
        if (
            !empty($this->receiver->contactName) ||
            !empty($this->receiver->contactPhone) ||
            !empty($this->receiver->contactEmail)
        ) {
            $contact = $this->doc->createElement('cac:Contact');

            if (!empty($this->receiver->contactName)) {
                $contact->appendChild($this->doc->createElement('cbc:Name', $this->receiver->contactName));
            }

            if (!empty($this->receiver->contactPhone)) {
                $contact->appendChild($this->doc->createElement('cbc:Telephone', $this->receiver->contactPhone));
            }

            if (!empty($this->receiver->contactEmail)) {
                $contact->appendChild($this->doc->createElement('cbc:ElectronicMail', $this->receiver->contactEmail));
            }

            $partyNode->appendChild($contact);
        }

        $party->appendChild($partyNode);
        $this->documentElement->appendChild($party);
    }
    protected function addDelivery(): void
    {
        if (!isset($this->delivery)) {
            throw new \RuntimeException("Aucune information de livraison définie. Veuillez appeler withDelivery().");
        }

        $delivery = $this->doc->createElement('cac:Delivery');
        $delivery->appendChild(
            $this->doc->createElement('cbc:ActualDeliveryDate', $this->delivery->date)
        );

        $location = $this->doc->createElement('cac:DeliveryLocation');
        $address = $this->doc->createElement('cac:Address');
        $country = $this->doc->createElement('cac:Country');
        $country->appendChild(
            $this->doc->createElement('cbc:IdentificationCode', $this->delivery->countryCode)
        );

        $address->appendChild($country);
        $location->appendChild($address);
        $delivery->appendChild($location);

        $this->documentElement->appendChild($delivery);
    }
    protected function addTaxes(): void
    {
        if (empty($this->taxes)) {
            throw new \RuntimeException("Aucune information sur les taxes. Veuillez appeler withTax().");
        }

        $taxTotal = $this->doc->createElement('cac:TaxTotal');

        $totalTaxAmount = array_reduce(
            $this->taxes,
            fn($carry, TaxInfo $t) => $carry + $t->taxAmount,
            0.0
        );

        $taxTotal->appendChild(
            $this->createElementWithCurrency('cbc:TaxAmount', $totalTaxAmount)
        );

        foreach ($this->taxes as $tax) {
            $subtotal = $this->doc->createElement('cac:TaxSubtotal');

            $subtotal->appendChild($this->createElementWithCurrency('cbc:TaxableAmount', $tax->taxableAmount));
            $subtotal->appendChild($this->createElementWithCurrency('cbc:TaxAmount', $tax->taxAmount));

            $category = $this->doc->createElement('cac:TaxCategory');
            $category->appendChild($this->doc->createElement('cbc:ID', $tax->vatCode));
            $category->appendChild($this->doc->createElement('cbc:Percent', $tax->taxPercentage));

            if ((float)$tax->taxPercentage === 0.0) {
                $category->appendChild($this->doc->createElement('cbc:TaxExemptionReasonCode', 'VATEX-EU-IC'));
            }

            $scheme = $this->doc->createElement('cac:TaxScheme');
            $scheme->appendChild($this->doc->createElement('cbc:ID', 'VAT'));
            $category->appendChild($scheme);

            $subtotal->appendChild($category);
            $taxTotal->appendChild($subtotal);
        }

        $this->documentElement->appendChild($taxTotal);
    }
    protected function addMonetaryTotal(): void
    {
        if (!isset($this->monetaryTotal)) {
            throw new \RuntimeException("Les totaux monétaires n'ont pas été définis. Appelle withMonetaryTotal().");
        }

        $total = $this->doc->createElement('cac:LegalMonetaryTotal');

        $taxable = $this->monetaryTotal->totalTaxableAmount;
        $totalAmount = $this->monetaryTotal->totalAmount;

        $total->appendChild($this->createElementWithCurrency('cbc:LineExtensionAmount', $taxable));
        $total->appendChild($this->createElementWithCurrency('cbc:TaxExclusiveAmount', $taxable));
        $total->appendChild($this->createElementWithCurrency('cbc:TaxInclusiveAmount', $totalAmount));
        $total->appendChild($this->createElementWithCurrency('cbc:PayableAmount', $totalAmount));

        $this->documentElement->appendChild($total);
    }
    protected function addInvoiceLines(): void
    {
        if (empty($this->lines)) {
            throw new \RuntimeException("Aucune ligne de facture définie. Appelle withLines().");
        }

        foreach ($this->lines as $index => $line) {
            $invoiceLine = $this->doc->createElement('cac:InvoiceLine');

            $invoiceLine->appendChild($this->doc->createElement('cbc:ID', $index + 1));

            $quantity = $this->doc->createElement('cbc:InvoicedQuantity', $line->quantity);
            $quantity->setAttribute('unitCode', '1I');
            $invoiceLine->appendChild($quantity);

            $invoiceLine->appendChild($this->createElementWithCurrency('cbc:LineExtensionAmount', $line->taxableAmount));

            // <cac:Item>
            $item = $this->doc->createElement('cac:Item');
            $item->appendChild($this->doc->createElement('cbc:Name', $line->description));

            $taxCategory = $this->doc->createElement('cac:ClassifiedTaxCategory');
            $taxCategory->appendChild($this->doc->createElement('cbc:ID', $line->vatCode));
            $taxCategory->appendChild($this->doc->createElement('cbc:Percent', $line->taxPercentage));

            $taxScheme = $this->doc->createElement('cac:TaxScheme');
            $taxScheme->appendChild($this->doc->createElement('cbc:ID', 'VAT'));
            $taxCategory->appendChild($taxScheme);

            $item->appendChild($taxCategory);
            $invoiceLine->appendChild($item);

            // <cac:Price>
            $price = $this->doc->createElement('cac:Price');
            $price->appendChild($this->createElementWithCurrency('cbc:PriceAmount', $line->unitPrice));
            $invoiceLine->appendChild($price);

            $this->documentElement->appendChild($invoiceLine);
        }
    }
    protected function getPeppolSchemeId(string $vatNumber): ?string
    {
        $countryCode = strtoupper(substr($vatNumber, 0, 2));

        return match ($countryCode) {
            'AD' => '9922',
            'AL' => '9923',
            'BA' => '9924',
            'BE' => '9925',
            'BG' => '9926',
            'CH' => '9927',
            'CY' => '9928',
            'CZ' => '9929',
            'DE' => '9930',
            'EE' => '9931',
            'GB' => '9932',
            'GR' => '9933',
            'HR' => '9934',
            'IE' => '9935',
            'LI' => '9936',
            'LT' => '9937',
            'LU' => '9938',
            'LV' => '9939',
            'MC' => '9940',
            'ME' => '9941',
            'MK' => '9942',
            'MT' => '9943',
            'NL' => '9944',
            'PO' => '9945',
            'PT' => '9946',
            'RO' => '9947',
            'RS' => '9948',
            'SI' => '9949',
            'SK' => '9950',
            'SM' => '9951',
            'TR' => '9952',
            'VA' => '9953',
            'SE' => '9955',
            'FR' => '9957',
            default => '9925',
        };
    }
    protected function getIdFromPeppolIdentifier(string $identifier): string
    {
        return explode(':', $identifier)[1] ?? '';
    }
    protected function getSchemeIdFromPeppolIdentifier(string $identifier): string
    {
        return explode(':', $identifier)[0] ?? '';
    }
    protected function addPayment(): void
    {
        if (!isset($this->payment)) {
            throw new \RuntimeException("Aucune information de paiement définie. Veuillez appeler withPayment().");
        }

        // <cac:PaymentMeans>
        $paymentMeans = $this->doc->createElement('cac:PaymentMeans');

        $meansCode = $this->doc->createElement('cbc:PaymentMeansCode', $this->payment->paymentDelay);
        $meansCode->setAttribute('name', 'Credit transfer');
        $paymentMeans->appendChild($meansCode);

        $paymentId = $this->payment->structuredCommunication ?? $this->payment->fallbackPaymentId;

        $paymentMeans->appendChild(
            $this->doc->createElement('cbc:PaymentID', $paymentId)
        );

        $account = $this->doc->createElement('cac:PayeeFinancialAccount');
        $account->appendChild($this->doc->createElement('cbc:ID', $this->payment->iban));
        $account->appendChild($this->doc->createElement('cbc:Name', $this->payment->senderName));

        $paymentMeans->appendChild($account);
        $this->documentElement->appendChild($paymentMeans);

        // <cac:PaymentTerms>
        $paymentTerms = $this->doc->createElement('cac:PaymentTerms');
        $noteText = 'Net within ' . $this->payment->paymentDelay . ' days';
        $paymentTerms->appendChild($this->doc->createElement('cbc:Note', $noteText));
        $this->documentElement->appendChild($paymentTerms);
    }
    protected function createElementWithCurrency(string $name, float $value): DOMElement
    {
        $el = $this->doc->createElement($name, number_format($value, 2, '.', ''));
        $el->setAttribute('currencyID', $this->documentInfo->currency);
        return $el;
    }
}
