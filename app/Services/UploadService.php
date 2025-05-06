<?php

namespace App\Services;

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
        ];

        return $models[$modelType] ?? null;
    }

    public function getFiles(string $model, string $modelId)
    {
        $modelClass = $this->getModelClass($model);

        // Récupérer tous les fichiers liés à ce modèle
        return Upload::where('model_type', $modelClass)
            ->where('model_id', $modelId)
            ->get(['id', 'filename', 'mime_type', 'path'])
            ->toArray();
    }

    public function save($file, string $tenantId, string $model, $modelId)
    {
        //Suppression de toutes les anciennes occurrences
        $year = Carbon::now()->year;
        $month = Carbon::now()->month;

        //Enregistrer le fichier sur le disque
        $path = Storage::disk('uploads')->putFileAs(
            "/{$tenantId}/uploads/{$model}/{$year}/{$month}",
            $file,
            sha1($file->getClientOriginalName()) . '.' . $file->getClientOriginalExtension()
        );

        // Variables du fichier
        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $mimeType = $file->getMimeType();
        $modelClass = $this->getModelClass($model);

        //Retourner le modèle créé
        return Upload::create([
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
            Storage::disk('uploads')->delete($path);
            $this->deleteEmptyParentDirectories($folderPath);
        } catch (ModelNotFoundException $exception) {
            throw new Exception("Impossible de trouver le fichier avec l'ID {$uploadId}.");
        }
    }

    private function deleteEmptyParentDirectories(string $path): void
    {
        $disk = Storage::disk('uploads');

        // Tant que le dossier est vide et qu'on n'est pas à la racine
        while ($path && empty($disk->files($path)) && empty($disk->directories($path))) {
            $disk->deleteDirectory($path);
            $path = dirname($path); // remonter d'un niveau
        }
    }
}
