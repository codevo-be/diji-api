<?php

namespace App\Http\Controllers;


use App\Http\Requests\GetModelUpload;
use App\Http\Requests\Upload\PostUpload;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    private UploadService $uploadService;

    public function __construct()
    {
        $this->uploadService = new UploadService();
    }

    //region Public Endpoints

    public function publicPreview(Request $request, string $tenantId, string $model, string $year, string $month, string $filename)
    {
        try {
            tenancy()->initialize($tenantId);
            $data = $this->uploadService->getUploadFile('public', $model, $year, $month, $filename);
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }

        $disposition = $request->header('X-Disposition') ?? 'inline';
        if (!in_array($disposition, ['inline', 'attachment'])) {
            abort(400, 'X-Disposition header must be either "inline" or "attachment".');
        }

        return response($data['file'], 200)
            ->header('Content-Type', $data['mime'])
            ->header('Content-Disposition', "{$disposition}; filename=\"{$data['filename']}\"");
    }
    //endregion

    //region Private Endpoints
    public function store(PostUpload $request): JsonResponse
    {
        $data = $request->validated();

        $tenant = tenant();
        $model = $data['model'];
        $modelId = $data['model_id'];
        $public = $data['public'] ?? false;

        $disk = $public ? 'public' : 'private';

        $files = $data['files'] ?? [];
        foreach ($files as $file) {
            $this->uploadService->save($disk, $file, $tenant->id, $model, $modelId, $data['name'] ?? null);
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
        try {
            $data = $this->uploadService->getUploadFile('private', $model, $year, $month, $filename);
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }

        $disposition = $request->header('X-Disposition') ?? 'inline';
        if (!in_array($disposition, ['inline', 'attachment'])) {
            abort(400, 'X-Disposition header must be either "inline" or "attachment".');
        }

        return response($data['file'], 200)
            ->header('Content-Type', $data['mime'])
            ->header('Content-Disposition', "{$disposition}; filename=\"{$data['filename']}\"");
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
    //endregion
}
