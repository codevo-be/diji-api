<?php

namespace App\Http\Controllers;


use App\Http\Requests\StoreMetaRequest;
use App\Models\Meta;
use App\Resources\MetaResource;

class MetaController extends Controller
{
    public function show(string $meta): \Illuminate\Http\JsonResponse
    {
        $model = Meta::where('key', $meta)->firstOrFail();

        return response()->json([
            'data' => new MetaResource($model)
        ]);
    }

    public function update(StoreMetaRequest $request, string $meta)
    {
        $data = $request->validated();

        $meta = Meta::updateOrCreate([
            "key" => $meta
        ], $data);

        return response()->json([
            'data' => new MetaResource($meta),
        ], 201);
    }
}
