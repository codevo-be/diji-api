<?php

namespace Diji\Peppol\DTO;

class ReceiverContactDTO
{
    public function __construct(
        public string $name,
        public string $phone,
        public string $email
    ) {}
}
