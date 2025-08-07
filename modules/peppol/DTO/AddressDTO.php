<?php

namespace Diji\Peppol\DTO;

class AddressDTO
{
    public function __construct(
        public string $line1,
        public string $city,
        public string $zipCode,
        public string $country
    ) {}
}
