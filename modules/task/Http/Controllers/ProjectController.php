<?php

namespace Diji\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Task\Http\Requests\StoreItemRequest;
use Diji\Task\Http\Requests\StoreProjectRequest;
use Diji\Task\Models\Item;
use Diji\Task\Models\Project;
use Diji\Task\Resources\ItemResource;
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
}
