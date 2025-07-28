<?php

namespace App\Console\Commands;

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
use Diji\Peppol\Helpers\PeppolBuilder;
use Diji\Peppol\Services\PeppolService;
use Illuminate\Console\Command;

class GenerateUbl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:ubl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $data = [
            "document" => [
                "document_type" => "INVOICE",
                "bill_name" => "046.2025",
                "issue_date" => "2025-06-14",
                "due_date" => "2025-07-14",
                "currency" => "EUR",
                "buyer_reference" => "BIEN-4500250377",
                "structured_communication" => ""
            ],
            "sender" => [
                "name" => "AP GUSTIN Luxembourg SARL",
                "vat_number" => "LU32279076",
                "iban" => "LU410019575550913000",
                "address" => [
                    "line1" => "Rue de l'Industrie,20",
                    "city" => "Windhof",
                    "zip_code" => "L-8399",
                    "country" => "LU"
                ],
            ],
            "receiver" => [
                "name" => "Ville de Luxembourg",
                "peppol_identifier" => "9938:lu10355144",
                "vat_number" => "LU10355144",
                "contact" => [
                    "name" => "Christophe Cino",
                    "phone" => "",
                    "email" => "",
                ],
                "address" => [
                    "line1" => "3, Rue du Laboratoire",
                    "city" => "Luxembourg",
                    "zip_code" => "L-1911",
                    "country" => "LU",
                ],
            ],
            'delivery' => [
                "date" => "2025-06-20",
            ],
            "payment" => [
                "payment_delay" => 90, // TODO
            ],
            "totals" => [
                "total_taxable_amount" => 2000.00,
                "total_amount" => 2340.00,
            ],
            "taxes" => [
                [
                    "vat_code" => "17",
                    "tax_percentage" => 17,
                    "taxable_amount" => 2000.00,
                    "tax_amount" => 340.00,
                ]
            ],
            "lines" => [
                [
                    "description" => "Nettoyage de votre parcelle à la Rue Clémenceau. Bon de commande: BIEN-4500250377 - Contact: CINO Christophe",
                    "quantity" => 1,
                    "unit_price" => 2000.00,
                    "taxable_amount" => 2000.00,
                    "vat_code" => "17",
                    "tax_percentage" => 17
                ]
            ]
        ];

        $payload = new PeppolPayloadDTO(
            document: new DocumentDTO(
                documentType: $data['document']['document_type'],
                billName: $data['document']['bill_name'],
                issueDate: $data['document']['issue_date'],
                dueDate: $data['document']['due_date'],
                currency: $data['document']['currency'],
                buyerReference: $data['document']['buyer_reference'],
                structuredCommunication: $data['document']['structured_communication'],
            ),
            sender: new SenderDTO(
                name: $data['sender']['name'],
                vatNumber: $data['sender']['vat_number'],
                iban: $data['sender']['iban'],
                address: new AddressDTO(
                    line1: $data['sender']['address']['line1'],
                    city: $data['sender']['address']['city'],
                    zipCode: $data['sender']['address']['zip_code'],
                    country: $data['sender']['address']['country']
                )
            ),
            receiver: new ReceiverDTO(
                name: $data['receiver']['name'],
                peppolIdentifier: $data['receiver']['peppol_identifier'],
                vatNumber: $data['receiver']['vat_number'],
                contact: new ReceiverContactDTO(
                    name: $data['receiver']['contact']['name'],
                    phone: $data['receiver']['contact']['phone'],
                    email: $data['receiver']['contact']['email']
                ),
                address: new AddressDTO(
                    line1: $data['receiver']['address']['line1'],
                    city: $data['receiver']['address']['city'],
                    zipCode: $data['receiver']['address']['zip_code'],
                    country: $data['receiver']['address']['country']
                )
            ),
            delivery: new DeliveryDTO(
                date: $data['delivery']['date']
            ),
            payment: new PaymentDTO(
                paymentDelay: $data['payment']['payment_delay']
            ),
            lines: collect($data['lines'])
                ->map(fn(array $line) => new InvoiceLineDTO(
                    description: $line['description'],
                    quantity: $line['quantity'],
                    unitPrice: $line['unit_price'],
                    taxableAmount: $line['taxable_amount'],
                    vatCode: $line['vat_code'],
                    taxPercentage: $line['tax_percentage']
                ))
                ->all(),
            taxes: collect($data['taxes'])
                ->map(fn(array $tax) => new TaxDTO(
                    vatCode: $tax['vat_code'],
                    taxPercentage: $tax['tax_percentage'],
                    taxableAmount: $tax['taxable_amount'],
                    taxAmount: $tax['tax_amount']
                ))
                ->all(),
            totals: new MonetaryTotalDTO(
                totalTaxableAmount: $data['totals']['total_taxable_amount'],
                totalAmount: $data['totals']['total_amount']
            )
        );

        $xml = (new PeppolBuilder())
            ->withPayload($payload)
            ->build();

        $filename = $payload->document->billName . '.xml';

        $path = storage_path('app/peppol');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        file_put_contents($path . '/' . $filename, $xml);
        $this->info("XML file generated successfully in: $path/$filename");
    }
}
