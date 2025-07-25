<?php

namespace Diji\Peppol\DTO;

class SenderDTO
{
    public function __construct(
        public string $name,
        public string $vatNumber,
        public string $iban,
        public AddressDTO $address
    ) {}
}
