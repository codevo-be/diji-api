<?php

namespace App\Http\Controllers;


use App\Http\Requests\GetModelUpload;
use App\Http\Requests\PostUpload;
use App\Http\Requests\StoreMetaRequest;
use App\Models\Meta;
use App\Models\Tenant;
use App\Models\Upload;
use App\Resources\MetaResource;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    private UploadService $uploadService;

    public function __construct()
    {
        $this->uploadService = new UploadService();
    }

    public function store(PostUpload $request): JsonResponse
    {
        $data = $request->validated();

        $tenant = tenant();
        $model = $data['model'];
        $modelId = $data['model_id'];

        if ($model === 'metas')
        {
            $modelId = Meta::findByKey($data['model_id'])->id;
        }

        $files = $data['files'] ?? [];
        foreach ($files as $file) {
            $this->uploadService->save($file, $tenant->id, $model, $modelId);
        }

        return response()->json([
            "message" => "Les fichiers ont été téléchargés avec succès",
        ], 201);
    }

    public function show(string $model, string $modelId): JsonResponse
    {
        $tenant = tenant();

        $files = $this->uploadService->getFiles($model, $modelId);

        return response()->json(
            $files
        );
    }

    public function preview(Request $request, $model, $year, $month, $filename)
    {
        $tenantId = tenant()->id;
        $path = "{$tenantId}/uploads/{$model}/{$year}/{$month}/{$filename}";

        if (!Storage::disk('uploads')->exists($path)) {
            abort(404, "Fichier introuvable");
        }

        $disposition = $request->header('X-Disposition') ?? 'inline';
        if ($disposition !== 'inline' && $disposition !== 'attachment') {
            abort(400, 'X-Disposition header must be either "inline" or "attachment".');
        }

        $file = Storage::disk('uploads')->get($path);
        $mimeType = Storage::disk('uploads')->mimeType($path);

        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', "{$disposition}; filename=\"{$filename}\"");
    }


    public function destroy(string $uploadId)
    {
        try {
            $this->uploadService->delete($uploadId);
            return response()->noContent();
        } catch (\Exception $exception) {
            return response()->json([
                "message" => "Erreur lors de la suppression du fichier : " . $exception->getMessage(),
            ], 500);
        }
    }
}
