<?php

namespace Diji\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Task\Http\Requests\StoreItemRequest;
use Diji\Task\Models\Item;
use Diji\Task\Resources\ItemResource;
use Illuminate\Http\JsonResponse;

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
}
