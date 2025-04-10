<?php

namespace Diji\Peppol\DTO;

class PaymentDTO
{
    public function __construct(
        public int $paymentDelay,
        public string $iban,
        public string $senderName,
        public ?string $structuredCommunication = null,
        public ?string $fallbackPaymentId = null
    ) {}
}
