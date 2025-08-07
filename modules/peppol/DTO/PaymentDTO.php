<?php

namespace Diji\Peppol\DTO;

class PaymentDTO
{
    public function __construct(
        public int $paymentDelay
    ) {}
}
