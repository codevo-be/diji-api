<?php

namespace Diji\Billing\Services;

use App\Models\Meta;
use Barryvdh\DomPDF\Facade\Pdf;
use Diji\Billing\Models\Invoice;

class PdfService
{
    public static function generateEstimate($estimate): string
    {
        return self::generate('billing::estimate', [
            ...$estimate->toArray(),
            "logo" => Meta::getValue('tenant_billing_details')['logo'] ?? null
        ]);
    }

    public static function generateInvoice($invoice): string
    {
        return self::generate('billing::invoice', [
            ...$invoice->toArray(),
            "logo" => Meta::getValue('tenant_billing_details')['logo'] ?? null,
            "qrcode" => $invoice->status !== Invoice::STATUS_DRAFT ?  \Diji\Billing\Helpers\Invoice::generateQrCode(
                $invoice->issuer["name"],
                $invoice->issuer["iban"],
                $invoice->total ?? 0,
                $invoice->structured_communication ?? ''
            ) : false
        ]);
    }

    public static function generateCreditNote($credit_note): string
    {
        return self::generate('billing::credit-note', [
            ...$credit_note->toArray(),
            "logo" => Meta::getValue('tenant_billing_details')["logo"] ?? null
        ]);
    }

    public static function generateSelfInvoice($self_invoice): string
    {
        return self::generate('billing::self-invoice', [
            ...$self_invoice->toArray(),
            "logo" => Meta::getValue('tenant_billing_details')["logo"] ?? null
        ]);
    }


    public static function generate(string $view, array $data): string
    {
        return Pdf::loadView($view, $data)->output();
    }

}
