<?php

namespace Diji\Peppol\DTO;
class MonetaryTotalInfo
{
    public function __construct(
        public float $totalTaxableAmount,
        public float $totalAmount
    ) {}
}
