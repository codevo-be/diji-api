<?php

namespace Diji\Peppol\DTO;

class DocumentDTO
{
    public function __construct(
        public string $id,
        public string $issueDate,
        public string $dueDate,
        public string $currency
    ) {}
}
