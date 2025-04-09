<?php

namespace Diji\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Task\Http\Requests\StoreColumnRequest;
use Diji\Task\Http\Requests\StoreItemRequest;
use Diji\Task\Http\Requests\UpdateColumnRequest;
use Diji\Task\Models\Column;
use Diji\Task\Models\Item;
use Diji\Task\Resources\ColumnResource;
use Diji\Task\Resources\ItemResource;
use Illuminate\Http\Request;

class ColumnController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(int $project)
    {
        $columns = Column::where('project_id', $project)
            ->orderBy('order')
            ->with('items')
            ->get();

        return ColumnResource::collection($columns)->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreColumnRequest $request)
    {
        $data = $request->validated();

        $item = Column::create($data);

        return response()->json([
            'data' => new ColumnResource($item),
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateColumnRequest $request, string $id)
    {
        $data = $request->validated();

        $item = Column::findOrFail($id);

        $item->update($data);

        return response()->json([
            'data' => new ColumnResource($item),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
