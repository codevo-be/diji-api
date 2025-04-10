<?php

namespace Diji\Peppol\DTO;

class PeppolDocumentAttachmentsDTO
{
    public function __construct(
        public ?string $mainPdfBase64 = null,
        public ?string $billName = null,
        public ?string $senderName = null,
        /** @var PeppolAttachmentDTO[] */
        public array $attachments = []
    ) {}
}
