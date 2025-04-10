<?php

namespace Diji\Peppol\DTO;
class TaxDTO
{
    public function __construct(
        public float $taxableAmount,
        public float $taxAmount,
        public float $taxPercentage,
        public string $vatCode
    ) {}
}
