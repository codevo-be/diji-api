<?php

namespace Diji\Peppol\DTO;
use Billing\dto\PeppolAttachment;

class PeppolDocumentAttachments
{
    public function __construct(
        public ?string $mainPdfBase64 = null,
        public ?string $billName = null,
        public ?string $senderName = null,
        /** @var PeppolAttachment[] */
        public array $attachments = []
    ) {}
}
