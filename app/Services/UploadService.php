<?php

namespace App\Services;

use App\Models\Meta;
use App\Models\Upload;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class UploadService
{
    public static function getModelClass(string $modelType): ?string
    {
        $models = [
            'expense' => \Diji\Billing\Models\Transaction::class,
            'invoice' => \Diji\Billing\Models\Invoice::class,
            'metas' => \App\Models\Meta::class,
        ];

        return $models[$modelType] ?? null;
    }

    public function getFiles(string $model, string $modelId)
    {
        $modelClass = $this->getModelClass($model);
        if ($model === 'metas')
        {
            $modelId = Meta::findByKey($modelId)->id;
        }

        // Récupérer tous les fichiers liés à ce modèle
        return Upload::where('model_type', $modelClass)
            ->where('model_id', $modelId)
            ->get(['id', 'filename', 'mime_type', 'path'])
            ->toArray();
    }

    /**
     * @throws Exception
     */
    public function getUploadFile(string $diskName, string $model, string $year, string $month, string $filename): array
    {
        $path = "uploads/{$model}/{$year}/{$month}/{$filename}";
        $disk = Storage::disk($diskName);

        if (!$disk->exists($path)) {
            throw new \Exception("Fichier introuvable", 404);
        }

        return [
            'file' => $disk->get($path),
            'mime' => $disk->mimeType($path),
            'filename' => $filename,
        ];
    }

    public function save(string $diskName, $file, string $tenantId, string $model, $modelId, ?string $name = null)
    {
        $disk = Storage::disk($diskName);

        // Date actuelle pour le chemin
        $year = Carbon::now()->year;
        $month = Carbon::now()->month;

        // Nom du fichier (sans extension)
        $filename = $name ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Type MIME
        $mimeType = $file->getMimeType();

        // Résolution du model class
        $modelClass = $this->getModelClass($model);

        // Spécial pour les metas
        if ($model === 'metas') {
            $modelId = Meta::findByKey($modelId)?->id;
        }

        // Suppression de l'ancien fichier (si existe)
        $existing = Upload::where('model_type', $modelClass)
            ->where('model_id', $modelId)
            ->where('filename', $filename)
            ->first();

        if ($existing) {
            // Supprimer physiquement l'ancien fichier si besoin
            $disk->delete($existing->path);

            // Supprimer la ligne en db
            $existing->delete();
        }

        // Enregistrer le fichier avec un nom unique (par hash)
        $path = $disk->putFileAs(
            "/uploads/{$model}/{$year}/{$month}",
            $file,
            sha1($file->getClientOriginalName()) . '.' . $file->getClientOriginalExtension()
        );

        // Retourner le nouvel enregistrement
        return Upload::create([
            'disk' => $diskName,
            'model_type' => $modelClass,
            'model_id' => $modelId,
            'filename' => $filename,
            'path' => $path,
            'mime_type' => $mimeType,
        ]);
    }


    /**
     * @throws Exception
     */
    public function delete($uploadId): void
    {
        try {
            $upload = Upload::findOrFail($uploadId);
            $path = $upload->path;
            $folderPath = dirname($path);

            // Supprimer la ligne de la base de données
            $upload->delete();
            // Supprimer le fichier du disque
            Storage::disk('private')->delete($path);
            $this->deleteEmptyParentDirectories($folderPath);
        } catch (ModelNotFoundException $exception) {
            throw new Exception("Impossible de trouver le fichier avec l'ID {$uploadId}.");
        }
    }

    private function deleteEmptyParentDirectories(string $path): void
    {
        $disk = Storage::disk('private');

        // Tant que le dossier est vide et qu'on n'est pas à la racine
        while ($path && empty($disk->files($path)) && empty($disk->directories($path))) {
            $disk->deleteDirectory($path);
            $path = dirname($path); // remonter d'un niveau
        }
    }
}
