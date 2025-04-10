<?php

namespace Diji\Peppol\DTO;
class ReceiverInfo
{
    public function __construct(
        public string $peppolIdentifier,
        public string $name,
        public string $addressLine1,
        public string $city,
        public string $zipCode,
        public string $country,
        public string $vatNumber,
        public ?string $contactName = null,
        public ?string $contactPhone = null,
        public ?string $contactEmail = null
    ) {}
}
