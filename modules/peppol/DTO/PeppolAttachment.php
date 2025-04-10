<?php

namespace Diji\Peppol\DTO;
class PeppolAttachment
{
    public function __construct(
        public string $fileName,
        public string $mimeType,
        public string $base64
    ) {}
}
