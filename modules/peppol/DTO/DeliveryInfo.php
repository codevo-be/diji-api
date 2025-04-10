<?php

namespace Diji\Peppol\DTO;
class DeliveryInfo
{
    public function __construct(
        public string $date,
        public string $countryCode
    ) {}
}
