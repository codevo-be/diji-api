<?php

namespace Diji\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Project\Http\Requests\StoreProjectRequest;
use Diji\Project\Http\Requests\UpdateProjectRequest;
use Diji\Project\Models\Project;
use Diji\Project\Resources\ProjectResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query();

        $projects = $request->has('page')
            ? $query->paginate()
            : $query->get();


        return ProjectResource::collection($projects)->response();
    }

    public function show(int $project_id): JsonResponse
    {
        $project = Project::findOrFail($project_id);

        return response()->json([
            'data' => new ProjectResource($project),
        ], 201);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $data = $request->validated();

        $project = Project::create($data);

        return response()->json([
            'data' => new ProjectResource($project),
        ], 201);
    }

    public function update(UpdateProjectRequest $request, int $project_id): JsonResponse
    {
        $data = $request->validated();

        $project = Project::findOrFail($project_id);

        $project->update($data);

        return response()->json([
            'data' => new ProjectResource($project),
        ]);
    }

    public function destroy(int $project_id): \Illuminate\Http\Response
    {
        $project = Project::findOrFail($project_id);

        $project->delete();

        return response()->noContent();
    }
}
