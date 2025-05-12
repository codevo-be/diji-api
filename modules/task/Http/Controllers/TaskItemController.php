<?php

namespace Diji\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Task\Http\Requests\BulkUpdateTaskItemRequest;
use Diji\Task\Http\Requests\StoreTaskItemRequest;
use Diji\Task\Http\Requests\UpdateTaskItemRequest;
use Diji\Task\Models\TaskItem;
use Diji\Task\Resources\TaskItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function bulkUpdate(BulkUpdateTaskItemRequest $request, int $project): JsonResponse
    {
        $validated = $request->validated();

        $updated = [];

        foreach ($validated['tasks'] as $taskData) {
            $task = TaskItem::find($taskData['id']);
            if ($task) {
                $task->update([
                    'position' => $taskData['position'],
                    'task_group_id' => $taskData['task_group_id'],
                ]);
                $updated[] = new TaskItemResource($task);
            }
        }

        return response()->json([
            'data' => $updated,
        ]);
    }
}
