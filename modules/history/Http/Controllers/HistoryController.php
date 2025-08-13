<?php

namespace Diji\History\Http\Controllers;

use App\Models\User;
use Diji\History\Http\Requests\StoreHistoryRequest;
use Diji\History\Http\Requests\UpdateHistoryRequest;
use Diji\History\Models\History;
use Diji\History\Resources\HistoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoryController
{
    public function index(Request $request): JsonResponse
    {
        $model_type = $request->input('model_type');
        $model_id = $request->input('model_id');

        $query = History::query()->orderBy('created_at', 'desc');

        $query->where('model_type', $model_type)
            ->where('model_id', $model_id);

        $users = $request->has('page')
            ? $query->paginate()
            : $query->get();


        return HistoryResource::collection($users)->response();
    }

    public function show(int $id): \Illuminate\Http\JsonResponse
    {
        $history = History::where('id', $id)
            ->firstOrFail();

        return response()->json([
            'data' => new HistoryResource($history)
        ]);
    }

    public function store(StoreHistoryRequest $request, string $model_type, int $model_id)
    {
        $history = History::create([
            'model_type' => $model_type,
            'model_id' => $model_id,
            'message' => $request->message,
            'type' => $request->type,
        ]);

        return response()->json([
            'data' => new HistoryResource($history),
        ], 201);
    }

    public function update(UpdateHistoryRequest $request, int $id): \Illuminate\Http\JsonResponse
    {
        $history = History::where('id', $id)
            ->firstOrFail();

        $history->update($request->validated());

        return response()->json([
            'data' => new HistoryResource($history)
        ]);
    }

    public function destroy(int $id): \Illuminate\Http\Response
    {
        $history = History::where('id', $id)
            ->firstOrFail();

        $history->delete();

        return response()->noContent();
    }
}
