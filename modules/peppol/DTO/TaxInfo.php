<?php

namespace Diji\Peppol\DTO;
class TaxInfo
{
    public function __construct(
        public float $taxableAmount,
        public float $taxAmount,
        public float $taxPercentage,
        public string $vatCode
    ) {}
}
