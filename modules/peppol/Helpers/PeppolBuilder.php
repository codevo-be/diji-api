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

        // Ajout de la référence de facturation uniquement pour les notes de crédit
        if ($this->payload->document->documentType === 'CreditNote') {
            $this->addBillingReference();
        }

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
            'urn:oasis:names:specification:ubl:schema:xsd:' . $this->payload->document->documentType . '-2',
            $this->payload->document->documentType
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
        $document = $this->payload->document;

        $this->documentElement->appendChild($this->doc->createElement('cbc:ID', $document->billName));
        $this->documentElement->appendChild($this->doc->createElement('cbc:IssueDate', $document->issueDate));

        if ($document->documentType === 'Invoice' && !empty($document->dueDate)) {
            $this->documentElement->appendChild($this->doc->createElement('cbc:DueDate', $document->dueDate));
        }

        $typeCode = $document->documentType === 'Invoice' ? '380' : '381';
        $elementName = $document->documentType === 'Invoice'
            ? 'cbc:InvoiceTypeCode'
            : 'cbc:CreditNoteTypeCode';

        $this->documentElement->appendChild(
            $this->doc->createElement($elementName, $typeCode)
        );

        $this->documentElement->appendChild($this->doc->createElement('cbc:DocumentCurrencyCode', $document->currency));
    }
    protected function addBuyerReference(): void
    {
        $reference = $this->payload->document->buyerReference;

        if (!empty($reference)) {
            $this->documentElement->appendChild(
                $this->doc->createElement('cbc:BuyerReference', $reference)
            );
        }
    }
    protected function addAttachments(): void
    {
    }
    protected function addSender(): void
    {
        $sender = $this->payload->sender;
        $address = $sender->address;

        $party = $this->doc->createElement('cac:AccountingSupplierParty');
        $partyNode = $this->doc->createElement('cac:Party');

        // EndpointID
        $endpointId = $this->doc->createElement('cbc:EndpointID', strtoupper($sender->vatNumber));
        $endpointId->setAttribute('schemeID', $this->getPeppolSchemeId(strtoupper($sender->vatNumber)));
        $partyNode->appendChild($endpointId);

        // Nom
        $partyName = $this->doc->createElement('cac:PartyName');
        $partyName->appendChild($this->doc->createElement('cbc:Name', $sender->name));
        $partyNode->appendChild($partyName);

        // Adresse
        $addressNode = $this->doc->createElement('cac:PostalAddress');
        $addressNode->appendChild($this->doc->createElement('cbc:StreetName', $address->line1));
        $addressNode->appendChild($this->doc->createElement('cbc:CityName', $address->city));
        $addressNode->appendChild($this->doc->createElement('cbc:PostalZone', $address->zipCode));

        $country = $this->doc->createElement('cac:Country');
        $country->appendChild($this->doc->createElement('cbc:IdentificationCode', $address->country));
        $addressNode->appendChild($country);
        $partyNode->appendChild($addressNode);

        // TaxScheme
        $taxScheme = $this->doc->createElement('cac:PartyTaxScheme');
        $taxScheme->appendChild($this->doc->createElement('cbc:CompanyID', strtoupper($sender->vatNumber)));
        $tax = $this->doc->createElement('cac:TaxScheme');
        $tax->appendChild($this->doc->createElement('cbc:ID', 'VAT'));
        $taxScheme->appendChild($tax);
        $partyNode->appendChild($taxScheme);

        // Legal Entity
        $legal = $this->doc->createElement('cac:PartyLegalEntity');
        $legal->appendChild($this->doc->createElement('cbc:RegistrationName', $sender->name));
        $legal->appendChild($this->doc->createElement('cbc:CompanyID', strtoupper($sender->vatNumber)));
        $partyNode->appendChild($legal);

        $party->appendChild($partyNode);
        $this->documentElement->appendChild($party);
    }
    protected function addReceiver(): void
    {
        $receiver = $this->payload->receiver;
        $address = $receiver->address;
        $contact = $receiver->contact;

        $party = $this->doc->createElement('cac:AccountingCustomerParty');
        $partyNode = $this->doc->createElement('cac:Party');

        // EndpointID (custom ou basé sur la TVA)
        $vatNumber = strtoupper($receiver->vatNumber);
        $identifier = $receiver->peppolIdentifier ?? null;

        if ($identifier && str_contains($identifier, ':')) {
            [$scheme, $id] = explode(':', $identifier, 2);
            $endpointId = $this->doc->createElement('cbc:EndpointID', $id);
            $endpointId->setAttribute('schemeID', $scheme);
        } else {
            $endpointId = $this->doc->createElement('cbc:EndpointID', $vatNumber);
            $endpointId->setAttribute('schemeID', $this->getPeppolSchemeId($vatNumber));
        }

        $partyNode->appendChild($endpointId);

        // Nom
        $partyName = $this->doc->createElement('cac:PartyName');
        $partyName->appendChild($this->doc->createElement('cbc:Name', $receiver->name));
        $partyNode->appendChild($partyName);

        // Adresse
        $addressNode = $this->doc->createElement('cac:PostalAddress');
        $addressNode->appendChild($this->doc->createElement('cbc:StreetName', $address->line1));
        $addressNode->appendChild($this->doc->createElement('cbc:CityName', $address->city));
        $addressNode->appendChild($this->doc->createElement('cbc:PostalZone', $address->zipCode));

        $country = $this->doc->createElement('cac:Country');
        $country->appendChild($this->doc->createElement('cbc:IdentificationCode', $address->country));
        $addressNode->appendChild($country);
        $partyNode->appendChild($addressNode);

        // TVA
        $taxScheme = $this->doc->createElement('cac:PartyTaxScheme');
        $taxScheme->appendChild($this->doc->createElement('cbc:CompanyID', $vatNumber));
        $tax = $this->doc->createElement('cac:TaxScheme');
        $tax->appendChild($this->doc->createElement('cbc:ID', 'VAT'));
        $taxScheme->appendChild($tax);
        $partyNode->appendChild($taxScheme);

        // Legal Entity
        $legal = $this->doc->createElement('cac:PartyLegalEntity');
        $legal->appendChild($this->doc->createElement('cbc:RegistrationName', $receiver->name));
        $legal->appendChild($this->doc->createElement('cbc:CompanyID', $vatNumber));
        $partyNode->appendChild($legal);

        // Contact (optionnel)
        if (
            !empty($contact?->name) ||
            !empty($contact?->phone) ||
            !empty($contact?->email)
        ) {
            $contactNode = $this->doc->createElement('cac:Contact');

            if (!empty($contact->name)) {
                $contactNode->appendChild($this->doc->createElement('cbc:Name', $contact->name));
            }

            if (!empty($contact->phone)) {
                $contactNode->appendChild($this->doc->createElement('cbc:Telephone', $contact->phone));
            }

            if (!empty($contact->email)) {
                $contactNode->appendChild($this->doc->createElement('cbc:ElectronicMail', $contact->email));
            }

            $partyNode->appendChild($contactNode);
        }

        $party->appendChild($partyNode);
        $this->documentElement->appendChild($party);
    }
    protected function addDelivery(): void
    {
        $deliveryData = $this->payload->delivery;

        $delivery = $this->doc->createElement('cac:Delivery');
        $delivery->appendChild(
            $this->doc->createElement('cbc:ActualDeliveryDate', $deliveryData->date)
        );

        $location = $this->doc->createElement('cac:DeliveryLocation');
        $address = $this->doc->createElement('cac:Address');
        $country = $this->doc->createElement('cac:Country');
        $country->appendChild(
            $this->doc->createElement('cbc:IdentificationCode', $this->payload->sender->address->country)
        );

        $address->appendChild($country);
        $location->appendChild($address);
        $delivery->appendChild($location);

        $this->documentElement->appendChild($delivery);
    }
    protected function addTaxes(): void
    {
        $taxTotal = $this->doc->createElement('cac:TaxTotal');

        $totalTaxAmount = array_reduce(
            $this->payload->taxes,
            fn($carry, \Diji\Peppol\DTO\TaxDTO $t) => $carry + $t->taxAmount,
            0.0
        );

        $taxTotal->appendChild(
            $this->createElementWithCurrency('cbc:TaxAmount', $totalTaxAmount)
        );

        foreach ($this->payload->taxes as $tax) {
            $subtotal = $this->doc->createElement('cac:TaxSubtotal');

            $subtotal->appendChild($this->createElementWithCurrency('cbc:TaxableAmount', $tax->taxableAmount));
            $subtotal->appendChild($this->createElementWithCurrency('cbc:TaxAmount', $tax->taxAmount));

            $category = $this->doc->createElement('cac:TaxCategory');
            $category->appendChild($this->doc->createElement('cbc:ID', $tax->vatCode));
            $category->appendChild($this->doc->createElement('cbc:Percent', $tax->taxPercentage));

            if ((float) $tax->taxPercentage === 0.0) {
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
        $total = $this->doc->createElement('cac:LegalMonetaryTotal');

        $taxable = $this->payload->totals->totalTaxableAmount;
        $totalAmount = $this->payload->totals->totalAmount;

        $total->appendChild($this->createElementWithCurrency('cbc:LineExtensionAmount', $taxable));
        $total->appendChild($this->createElementWithCurrency('cbc:TaxExclusiveAmount', $taxable));
        $total->appendChild($this->createElementWithCurrency('cbc:TaxInclusiveAmount', $totalAmount));
        $total->appendChild($this->createElementWithCurrency('cbc:PayableAmount', $totalAmount));

        $this->documentElement->appendChild($total);
    }
    protected function addInvoiceLines(): void
    {
        foreach ($this->payload->lines as $index => $line) {
            $lineTag = 'cac:' . $this->payload->document->documentType . 'Line';
            $itemLine = $this->doc->createElement($lineTag);

            $itemLine->appendChild($this->doc->createElement('cbc:ID', $index + 1));

            $quantityElementName = $this->payload->document->documentType === 'CreditNote'
                ? 'cbc:CreditedQuantity'
                : 'cbc:InvoicedQuantity';

            $quantity = $this->doc->createElement($quantityElementName, $line->quantity);
            $quantity->setAttribute('unitCode', '1I');
            $itemLine->appendChild($quantity);

            $itemLine->appendChild($this->createElementWithCurrency('cbc:LineExtensionAmount', $line->taxableAmount));

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
            $itemLine->appendChild($item);

            // <cac:Price>
            $price = $this->doc->createElement('cac:Price');
            $price->appendChild($this->createElementWithCurrency('cbc:PriceAmount', $line->unitPrice));

            $baseQuantity = $this->doc->createElement('cbc:BaseQuantity', 1);
            $baseQuantity->setAttribute('unitCode', '1I');
            $price->appendChild($baseQuantity);

            $itemLine->appendChild($price);


            $this->documentElement->appendChild($itemLine);
        }
    }
    protected function addPayment(): void
    {
        $payment = $this->payload->payment;
        $sender = $this->payload->sender;

        // <cac:PaymentMeans>
        $paymentMeans = $this->doc->createElement('cac:PaymentMeans');

        $meansCode = $this->doc->createElement('cbc:PaymentMeansCode', $payment->paymentDelay);
        $meansCode->setAttribute('name', 'Credit transfer');
        $paymentMeans->appendChild($meansCode);

        $paymentId = !empty($this->payload->document->structuredCommunication)
            ? $this->payload->document->structuredCommunication
            : $this->payload->document->billName;

        $paymentMeans->appendChild(
            $this->doc->createElement('cbc:PaymentID', $paymentId)
        );

        $account = $this->doc->createElement('cac:PayeeFinancialAccount');
        $account->appendChild($this->doc->createElement('cbc:ID', $sender->iban));
        $account->appendChild($this->doc->createElement('cbc:Name', $sender->name));

        $paymentMeans->appendChild($account);
        $this->documentElement->appendChild($paymentMeans);

        // <cac:PaymentTerms>
        $paymentTerms = $this->doc->createElement('cac:PaymentTerms');
        $noteText = 'Net within ' . $payment->paymentDelay . ' days';
        $paymentTerms->appendChild($this->doc->createElement('cbc:Note', $noteText));

        $this->documentElement->appendChild($paymentTerms);
    }
    protected function addBillingReference(): void
    {
        $reference = $this->payload->document->referenceInvoiceId;

        $billingReference = $this->doc->createElement('cac:BillingReference');
        $invoiceRef = $this->doc->createElement('cac:InvoiceDocumentReference');
        $invoiceRef->appendChild($this->doc->createElement('cbc:ID', $reference));

        $billingReference->appendChild($invoiceRef);
        $this->documentElement->appendChild($billingReference);
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
    protected function createElementWithCurrency(string $name, float $value): DOMElement
    {
        $el = $this->doc->createElement($name, number_format($value, 2, '.', ''));
        $el->setAttribute('currencyID', $this->payload->document->currency);
        return $el;
    }
}
