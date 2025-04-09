<?php

namespace Diji\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Task\Http\Requests\StoreItemRequest;
use Diji\Task\Http\Requests\UpdateItemRequest;
use Diji\Task\Models\Item;
use Diji\Task\Resources\ItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function store(StoreItemRequest $request): JsonResponse
    {
        $data = $request->validated();
        $item = Item::create($data);

        return response()->json([
            'data' => new ItemResource($item),
        ], 201);
    }

    public function update(UpdateItemRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $item = Item::findOrFail($id);
        $item->update($data);

        return response()->json([
            'data' => new ItemResource($item),
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $tasks = $request->input('tasks');

        if (!is_array($tasks)) {
            return response()->json(['error' => 'Le format des données est invalide.'], 400);
        }

        foreach ($tasks as $taskData) {
            if (!isset($taskData['id'])) {
                continue;
            }

            $task = Item::find($taskData['id']);

            if ($task) {
                $validFields = array_intersect_key($taskData, array_flip([
                    'name', 'description', 'order', 'task_column_id', 'status', 'priority'
                ]));

                $task->update($validFields);
            }
        }

        return response()->json(['message' => 'Mise à jour des tâches réussie.']);
    }
}
