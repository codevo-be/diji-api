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

        // On extrait les utilisateurs assignés s'ils sont présents
        $assignedUserIds = $data['assigned_user_ids'] ?? [];
        unset($data['assigned_user_ids']);

        $item = TaskItem::create($data);

        if (!empty($assignedUserIds)) {
            $item->assignedUsers()->sync($assignedUserIds);
        }

        return response()->json([
            'data' => new TaskItemResource($item->fresh(['assignedUsers'])),
        ], 201);
    }

    public function update(UpdateTaskItemRequest $request, int $project, int $group, int $item): JsonResponse
    {
        $data = $request->validated();

        $taskItem = TaskItem::findOrFail($item);

        // On extrait les utilisateurs assignés s'ils sont présents
        $assignedUserIds = $data['assigned_user_ids'] ?? null;
        unset($data['assigned_user_ids']);

        $taskItem->update($data);

        if (is_array($assignedUserIds)) {
            $taskItem->assignedUsers()->sync($assignedUserIds);
        }

        return response()->json([
            'data' => new TaskItemResource($taskItem->fresh(['assignedUsers'])),
        ]);
    }

    public function destroy(int $project, int $group, int $item): Response
    {
        $taskItem = TaskItem::findOrFail($item);

        $taskItem->assignedUsers()->detach();
        $taskItem->delete();

        return response()->noContent();
    }
}
