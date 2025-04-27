<?php

namespace Diji\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Task\Http\Requests\StoreTaskItemRequest;
use Diji\Task\Http\Requests\UpdateTaskItemRequest;
use Diji\Task\Models\TaskItem;
use Diji\Task\Resources\TaskItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class TaskItemController extends Controller
{
    public function store(StoreTaskItemRequest $request): JsonResponse
    {
        $data = $request->validated();

        $item = TaskItem::create($data);

        return response()->json([
            'data' => new TaskItemResource($item),
        ], 201);
    }

    public function update(UpdateTaskItemRequest $request, int $project, int $group, int $item): JsonResponse
    {
        $data = $request->validated();

        $item = TaskItem::findOrFail($item);

        $item->update($data);

        return response()->json([
            'data' => new TaskItemResource($item),
        ]);
    }

    public function destroy(int $project, int $group, int $item): Response
    {
        $item = TaskItem::findOrFail($item);

        $item->delete();

        return response()->noContent();
    }
}
