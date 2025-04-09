<?php

namespace Diji\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Task\Http\Requests\StoreProjectRequest;
use Diji\Task\Http\Requests\UpdateProjectRequest;
use Diji\Task\Models\Project;
use Diji\Task\Resources\ProjectResource;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    public function index()
    {
        $query = Project::query();
        return ProjectResource::collection($query->get())->response();
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $data = $request->validated();
        $item = Project::create($data);

        return response()->json([
            'data' => new ProjectResource($item),
        ], 201);
    }

    public function update(UpdateProjectRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $item = Project::findOrFail($id);
        $item->update($data);

        return response()->json([
            'data' => new ProjectResource($item),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $item = Project::findOrFail($id);

        return response()->json([
            'data' => new ProjectResource($item),
        ], 201);
    }
}
