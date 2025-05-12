<?php

namespace Diji\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Task\Http\Requests\StoreTaskGroupRequest;
use Diji\Task\Http\Requests\UpdateTaskGroupRequest;
use Diji\Task\Models\TaskGroup;
use Diji\Task\Resources\TaskGroupResource;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class TaskGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, int $project)
    {
        $query = TaskGroup::with(['items' => function ($query) {
            $query->orderBy('position');
        }])->where('project_id', $project)->orderBy('position');

        $groups = $request->has('page')
            ? $query->paginate()
            : $query->get();

        return TaskGroupResource::collection($groups)->response();
    }


    public function show(int $project_id, int $group_id)
    {
        $group = TaskGroup::find($group_id);

        return response()->json([
            'data' => new TaskGroupResource($group)
        ]);
    }

    public function store(StoreTaskGroupRequest $request)
    {
        $data = $request->validated();

        $group = TaskGroup::create($data);

        return response()->json([
            'data' => new TaskGroupResource($group),
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskGroupRequest $request, int $project, int $group_id)
    {
        $data = $request->validated();

        $group = TaskGroup::findOrFail($group_id);

        $group->update($data);

        return response()->json([
            'data' => new TaskGroupResource($group),
        ]);
    }

    public function destroy(int $project, int $group_id)
    {
        try {
            TaskGroup::findOrFail($group_id)->delete();

            return response()->json([
                'message' => 'Colonne supprimée avec succès.'
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Impossible de supprimer cette colonne car elle contient encore des éléments.'
                ], 409);
            }

            throw $e;
        }
    }
}
