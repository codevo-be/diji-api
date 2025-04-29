<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ZipService
{
    /**
     * @throws \Exception
     */
    public static function createZip(array $files, string $zipPath): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception('Could not create ZIP file.');
        }

        foreach ($files as $fileName => $fileContent) {
            $zip->addFromString($fileName, $fileContent);
        }

        $zip->close();
    }

    /**
     * @throws \Exception
     */
    public static function createTempZip(array $files): string
    {
        $zipFileName = 'invoices_' . now()->format('Ymd_His') . '.zip';
        $zipPath = storage_path("app/tmp/{$zipFileName}");

        Storage::makeDirectory('tmp');

        self::createZip($files,  $zipPath);

        return $zipPath;
    }

    public static function deleteTempZip(string $zipPath): void
    {
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }
    }
}
