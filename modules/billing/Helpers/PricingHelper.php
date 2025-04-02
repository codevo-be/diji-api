<?php

namespace Diji\Billing\Helpers;

class PricingHelper
{
    public static function getPricingDetails($subtotal = 0, $vat = 21)
    {
        $tax = floatval($subtotal) * (floatval($vat) / 100);
        $total = floatval($subtotal) + floatval($tax);

        return [
            "subtotal" => $subtotal,
            "tax" => $tax,
            "total" => $total
        ];
    }

    public static function formatCurrency(float $value): string
    {
        return number_format($value, 2, ',', ' ') . '€';
    }
}
