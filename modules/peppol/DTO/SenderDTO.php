<?php

namespace Diji\Peppol\DTO;

class SenderDTO
{
    public function __construct(
        public string $vatNumber,
        public string $name,
        public string $addressLine1,
        public string $city,
        public string $zipCode,
        public string $country
    ) {}
}
