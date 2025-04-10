<?php

namespace Diji\Peppol\DTO;

class MonetaryTotalDTO
{
    public function __construct(
        public float $totalTaxableAmount,
        public float $totalAmount
    ) {}
}
