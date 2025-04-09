<?php

namespace Diji\Billing\Services;

use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ZipService
{
    /**
     * @throws \Exception
     */
    public static function createZip(array $files, string $zipPath): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception('Could not create ZIP file.');
        }

        foreach ($files as $fileName => $fileContent) {
            $zip->addFromString($fileName, $fileContent);
        }

        $zip->close();
    }
}
