<?php

namespace Diji\Billing\Helpers;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Log;

class Invoice {
    public static function generateStructuredCommunication(int $invoice_identifier)
    {
        $currentDate = \Carbon\Carbon::now()->format('ymd');

        $cleanedIdentifier = preg_replace('/\D/', '', $invoice_identifier);
        $cleanedIdentifier = str_pad(substr($cleanedIdentifier, 0, 4), 4, '0', STR_PAD_LEFT);

        $base = $cleanedIdentifier . $currentDate;

        $modulus = $base % 97;
        $modulus = ($modulus > 0) ? $modulus : 97;

        return $base . str_pad($modulus, 2, '0', STR_PAD_LEFT);
    }

    public static function formatStructuredCommunication(string $value): string
    {
        return "+++" . substr($value, 0, 3) . '/' . substr($value, 3, 4) . '/' . substr($value, 7) . "+++";
    }

    public static function generateQrCode(string $recipient, string $iban, float $amount, string $structured_communication): string
    {
        $structured_communication = self::formatStructuredCommunication($structured_communication);

        $data = implode("\n", [
            "BCD",
            "001",
            "1",
            "SCT",
            "",
            $recipient,
            $iban,
            "EUR" . PricingHelper::formatCurrency($amount),
            $structured_communication,
            $structured_communication
        ]);

        $options = new QROptions([
            'version'      => 10,
            'outputType'   => "png",
            'eccLevel'     => EccLevel::H,
            'scale'        => 10,
            'imageBase64'  => true
        ]);

        return (new QRCode($options))->render($data);
    }
}
