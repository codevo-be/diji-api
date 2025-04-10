<?php

namespace Diji\Peppol\DTO;

class PeppolAttachmentDTO
{
    public function __construct(
        public string $fileName,
        public string $mimeType,
        public string $base64
    ) {}
}
