<?php

namespace Diji\Peppol\DTO;

class TaxDTO
{
    public function __construct(
        public string $vatCode,
        public float $taxPercentage,
        public float $taxableAmount,
        public float $taxAmount
    ) {}
}
