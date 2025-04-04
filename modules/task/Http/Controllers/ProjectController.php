<?php

namespace Diji\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Task\Models\Project;
use Diji\Task\Resources\ProjectResource;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query();

        return ProjectResource::collection($query->get())->response();
    }
}
