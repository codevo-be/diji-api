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

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file'
        ]);

        $tenant = tenant();

        $file = $request->file('file');
        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $fileName_hashed = sha1($filename) . '.' . $file->getClientOriginalExtension();
        $year = Carbon::now()->year;
        $month = Carbon::now()->month;

        $path = Storage::disk('uploads')->putFileAs("/{$tenant->id}/uploads/{$year}/{$month}", $file, $fileName_hashed);

        $upload = Upload::create([
            'filename' => $filename,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
        ]);

        return response()->json([
            "data" => $upload
        ]);
    }

    public function storeExpenseFiles(PostUpload $request): JsonResponse
    {
        $data = $request->validated();

        $tenant = tenant();
        $model = $data['model'];
        $modelId = $data['model_id'];

        $createdFiles = [];

        $files = $data['files'] ?? [];
        foreach ($files as $file) {
            $createdFiles[] = $this->uploadService->save($file, $tenant->id, $model, $modelId);
        }

        return response()->json([
            "message" => "Les fichiers ont été téléchargés avec succès",
            "data" => $createdFiles,
        ], 201);
    }

    public function show(Request $request, string $model, string $modelId): JsonResponse
    {
        $tenant = tenant();

        $files = $this->uploadService->getFiles($tenant->id, $model, $modelId);

        return response()->json([
            "message" => "Tried to get files",
            "files" => $files,
        ]);
    }

    public function destroy(Request $request)
    {

    }

//    public function show(string $tenant, string $year, string $month, string $filename)
//    {
//       /* $user = Auth::user();
//
//        if(!$user){
//            return response()->json([
//                "message" => "Vous n'êtes pas autorisé à accéder à ce fichier !",
//                "user" => $user
//            ]);
//        }*/
//
//        $path = storage_path("app/private/{$tenant}/uploads");
//
//        if (!file_exists("{$path}/{$year}/{$month}/{$filename}")) {
//            return response()->json(['error' => "Le fichier n'existe pas"], 404);
//        }
//
//        $disk = Storage::build([
//            'driver' => 'local',
//            'root' => $path,
//            'visibility' => 'private',
//        ]);
//
//        $filePath = $disk->path("{$year}/{$month}/{$filename}");
//
//        return response()->file($filePath);
//    }
}
