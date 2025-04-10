<?php

namespace Diji\Peppol\DTO;
class InvoiceLine
{
    public function __construct(
        public int $quantity,
        public float $taxableAmount,
        public float $unitPrice,
        public float $taxPercentage,
        public string $vatCode,
        public string $description
    ) {}
}
