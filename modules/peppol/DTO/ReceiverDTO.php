<?php

namespace Diji\Peppol\DTO;

class ReceiverDTO
{
    public function __construct(
        public string $name,
        public string $peppolIdentifier,
        public string $vatNumber,
        public ReceiverContactDTO $contact,
        public AddressDTO $address
    ) {}
}
