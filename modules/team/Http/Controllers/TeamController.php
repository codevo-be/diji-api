<?php

namespace Diji\Team\Http\Controllers;

use App\Models\User;
use Diji\Team\Http\Requests\StoreTeamRequest;
use Diji\Team\Http\Requests\UpdateTeamRequest;
use Diji\Team\Resources\TeamResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController
{
    public function index(Request $request): JsonResponse
    {
        $tenant = tenant();

        $query = User::query();

        $query->whereHas('tenants', function ($q) use ($tenant) {
            $q->where('tenants.id', $tenant->id);
        });

        $query->filter(['firstname', 'lastname', 'email']);

        $users = $request->has('page')
            ? $query->paginate()
            : $query->get();


        return TeamResource::collection($users)->response();
    }

    public function show(int $user_id): \Illuminate\Http\JsonResponse
    {
        $tenant = tenant();

        $user = User::where('id', $user_id)
            ->whereHas('tenants', function ($q) use ($tenant) {
                $q->where('tenants.id', $tenant->id);
            })
            ->firstOrFail();

        return response()->json([
            'data' => new TeamResource($user)
        ]);
    }

    public function store(StoreTeamRequest $request)
    {
        $tenant = tenant();
        $data = $request->validated();

        $user = User::create($data);

        $user->tenants()->attach($tenant->id);

        return response()->json([
            'data' => new TeamResource($user),
        ], 201);
    }

    public function update(UpdateTeamRequest $request, int $user_id): \Illuminate\Http\JsonResponse
    {
        $tenant = tenant();

        $data = $request->validated();

        $user = User::where('id', $user_id)
            ->whereHas('tenants', function ($q) use ($tenant) {
                $q->where('tenants.id', $tenant->id);
            })
            ->firstOrFail();

        $user->update($data);

        return response()->json([
            'data' => new TeamResource($user)
        ]);
    }

    public function destroy(int $user_id): \Illuminate\Http\Response
    {
        $tenant = tenant();

        $user = User::where('id', $user_id)
            ->whereHas('tenants', function ($q) use ($tenant) {
                $q->where('tenants.id', $tenant->id);
            })
            ->firstOrFail();

        $user->delete();
        $user->tenants()->detach($tenant->id);

        return response()->noContent();
    }
}
