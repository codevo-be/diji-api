<?php

namespace Diji\Peppol\DTO;

class DocumentDTO
{
    public function __construct(
        public string $documentType,
        public string $billName,
        public string $issueDate,
        public string $dueDate,
        public string $currency,
        public string $buyerReference,
        public string $structuredCommunication
    ) {}
}
