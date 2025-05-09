<?php

namespace Diji\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Task\Http\Requests\StoreTaskItemRequest;
use Diji\Task\Http\Requests\UpdateTaskItemRequest;
use Diji\Task\Models\TaskItem;
use Diji\Task\Resources\TaskItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TaskItemController extends Controller
{
    public function store(StoreTaskItemRequest $request): JsonResponse
    {
        $data = $request->validated();

        $assignedUserIds = $data['assigned_user_ids'] ?? [];
        unset($data['assigned_user_ids']);

        $item = TaskItem::create($data);

        if (!empty($assignedUserIds)) {
            $rows = collect($assignedUserIds)->map(fn ($userId) => [
                'task_item_id' => $item->id,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::connection('tenant')->table('task_user')->insert($rows->toArray());
        }

        return response()->json([
            'data' => new TaskItemResource($item),
        ], 201);
    }

    public function update(UpdateTaskItemRequest $request, int $project, int $group, int $item): JsonResponse
    {
        $data = $request->validated();

        $taskItem = TaskItem::findOrFail($item);

        $assignedUserIds = $data['assigned_user_ids'] ?? null;
        unset($data['assigned_user_ids']);

        $taskItem->update($data);

        if (is_array($assignedUserIds)) {
            DB::connection('tenant')->table('task_user')->where('task_item_id', $taskItem->id)->delete();

            $rows = collect($assignedUserIds)->map(fn ($userId) => [
                'task_item_id' => $taskItem->id,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::connection('tenant')->table('task_user')->insert($rows->toArray());
        }

        return response()->json([
            'data' => new TaskItemResource($taskItem),
        ]);
    }

    public function destroy(int $project, int $group, int $item): Response
    {
        $taskItem = TaskItem::findOrFail($item);

        DB::connection('tenant')->table('task_user')->where('task_item_id', $taskItem->id)->delete();
        $taskItem->delete();

        return response()->noContent();
    }
}
