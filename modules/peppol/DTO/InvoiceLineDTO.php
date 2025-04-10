<?php

namespace Diji\Peppol\DTO;

class InvoiceLineDTO
{
    public function __construct(
        public string $description,
        public int $quantity,
        public float $unitPrice,
        public float $taxableAmount,
        public string $vatCode,
        public float $taxPercentage
    ) {}
}
