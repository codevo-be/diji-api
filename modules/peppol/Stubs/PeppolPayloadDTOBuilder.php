<?php

namespace Diji\Peppol\Stubs;

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
    /**
     * Modèle d’exemple pour construire un objet PeppolPayloadDTO.
     * À compléter en fonction des données réelles.
     */
    public static function build(array $data): PeppolPayloadDTO
    {
        // Document
        $document = new DocumentDTO(
            documentType: '',           // Format : "Invoice" ou "CreditNote"
            billName: '',               // Exemple : "FACT-2025-001"
            issueDate: '',              // Format : YYYY-MM-DD
            dueDate: '',                // Format : YYYY-MM-DD
            currency: '',               // Exemple : "EUR"
            buyerReference: '',         // Exemple : "PO-2025-XYZ"
            structuredCommunication: '' // Exemple : "+++123/4567/89012+++"
        );

        // Émetteur
        $senderAddress = new AddressDTO(
            line1: '',                  // Exemple : "Rue des Champs 12"
            city: '',                   // Exemple : "Liège"
            zipCode: '',                // Format : code postal, Exemple : "4000"
            country: ''                 // Format : code ISO 2 lettres, Exemple : "BE"
        );

        $sender = new SenderDTO(
            name: '',                   // Exemple : "Codevo SPRL"
            vatNumber: '',              // Exemple : "BE0123456789"
            iban: '',                   // Exemple : "BE68539007547034"
            address: $senderAddress
        );

        // Destinataire
        $receiverAddress = new AddressDTO(
            line1: '',                  // Exemple : "Avenue du Client 10"
            city: '',                   // Exemple : "Namur"
            zipCode: '',                // Exemple : "5000"
            country: ''                 // Exemple : "BE"
        );

        $receiverContact = new ReceiverContactDTO(
            name: '',                   // Exemple : "Jean Client"
            phone: '',                  // Exemple : "+32 478 00 00 00"
            email: ''                   // Exemple : "client@exemple.com"
        );

        $receiver = new ReceiverDTO(
            name: '',                   // Exemple : "Client SPRL"
            peppolIdentifier: '',       // Format : "0208:BE0123456789"
            vatNumber: '',              // Format : "BE0123456789"
            contact: $receiverContact,
            address: $receiverAddress
        );

        // Livraison
        $delivery = new DeliveryDTO(
            date: ''                    // Format : YYYY-MM-DD
        );

        // Paiement
        $payment = new PaymentDTO(
            paymentDelay: 30            // Format : entier, Délai en jours. Exemple : 30
        );

        // Lignes de facture
        $lines = [
            new InvoiceLineDTO(
                description: '',        // Exemple : "Service de développement"
                quantity: 1,            // Format : nombre, Exemple : 2
                unitPrice: 0.0,         // Format : nombre, Exemple : 150.00
                taxableAmount: 0.0,     // Exemple : 300.00
                vatCode: '',            // Exemple : "S"
                taxPercentage: 21       // Format : nombre, Exemple : 21
            )
            // Ajouter d'autres lignes si nécessaire
        ];

        // Taxes
        $taxes = [
            new TaxDTO(
                vatCode: '',            // Exemple : "S"
                taxPercentage: 21,      // Format : nombre, Exemple : 21
                taxableAmount: 0.0,     // Exemple : 300.00
                taxAmount: 0.0          // Exemple : 63.00
            )
            // Ajouter d'autres lignes si nécessaire
        ];

        // Totaux
        $totals = new MonetaryTotalDTO(
            totalTaxableAmount: 0.0,    // Exemple : 300.00
            totalAmount: 0.0            // Exemple : 363.00
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
