<?php

namespace App\Services;

use App\Models\Upload;
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

    public function save($file, string $tenantId, string $model, $modelId)
    {
        //Suppression de toutes les anciennes occurrences
        $this->delete($model, $modelId);

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

    public function delete(string $model, string $modelId)
        /* TODO Future amélioration : vérifier si le fichier créé est l'identique du futur supprimé*/
    {
        $modelClass = $this->getModelClass($model);

        // Récupérer tous les fichiers liés à ce modèle
        $uploads = Upload::where('model_type', $modelClass)
            ->where('model_id', $modelId)
            ->get();

        foreach ($uploads as $upload) {
            // Supprimer le fichier du disque
            $path = $upload->path;
            $folderPath = dirname($path);

            Storage::disk('uploads')->delete($path);

            // Supprimer la ligne de la base de données
            $upload->delete();

            $this->deleteEmptyParentDirectories($folderPath);
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
