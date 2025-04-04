<?php

namespace Diji\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Task\Models\Project;
use Diji\Task\Resources\ProjectResource;

class ProjectController extends Controller
{
    public function index()
    {
        $query = Project::query();

        return ProjectResource::collection($query->get())->response();
    }
}
