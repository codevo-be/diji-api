<?php

namespace Diji\Billing\Services;

use App\Models\Meta;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    public static function generateInvoice($invoice): string
    {
        return self::generate('billing::invoice', [
            ...$invoice->toArray(),
            "logo" => Meta::getValue('tenant_billing_details')['logo'] ?? null,
            "qrcode" => \Diji\Billing\Helpers\Invoice::generateQrCode(
                $invoice->issuer["name"],
                $invoice->issuer["iban"],
                $invoice->total,
                $invoice->structured_communication
            )
        ]);
    }

    public static function generateCreditNote($credit_note): string
    {
        return self::generate('billing::credit-note', [
            ...$credit_note->toArray(),
            "logo" => Meta::getValue('tenant_billing_details')["logo"] ?? null
        ]);
    }

    public static function generate(string $view, array $data): string
    {
        return Pdf::loadView($view, $data)->output();
    }


}
