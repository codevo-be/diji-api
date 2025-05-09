<?php

namespace Diji\Calendar\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Diji\Calendar\Models\CalendarEvent;
use Diji\Calendar\Http\Requests\StoreCalendarEventRequest;
use Diji\Calendar\Http\Requests\UpdateCalendarEventRequest;
use Diji\Calendar\Resources\CalendarEventResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CalendarEventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CalendarEvent::orderBy('start');

        $events = $request->has('page')
            ? $query->paginate()
            : $query->get();

        // Ajout manuel des utilisateurs assignés
        foreach ($events as $event) {
            $event->assigned_users = User::on('mysql')
                ->whereIn('id', DB::connection('tenant')
                    ->table('calendar_event_user')
                    ->where('calendar_event_id', $event->id)
                    ->pluck('user_id')
                )->get();
        }

        return CalendarEventResource::collection($events)->response();
    }

    public function show(int $event_id): JsonResponse
    {
        $event = CalendarEvent::findOrFail($event_id);

        $event->assigned_users = User::on('mysql')
            ->whereIn('id', DB::connection('tenant')
                ->table('calendar_event_user')
                ->where('calendar_event_id', $event->id)
                ->pluck('user_id')
            )->get();

        return response()->json([
            'data' => new CalendarEventResource($event)
        ]);
    }

    public function store(StoreCalendarEventRequest $request): JsonResponse
    {
        $data = $request->validated();

        $assignedUserIds = $data['assigned_user_ids'] ?? [];
        unset($data['assigned_user_ids']);

        $event = CalendarEvent::create($data);

        if (!empty($assignedUserIds)) {
            $rows = collect($assignedUserIds)->map(fn ($id) => [
                'calendar_event_id' => $event->id,
                'user_id' => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();

            DB::connection('tenant')->table('calendar_event_user')->insert($rows);
        }

        $event->assigned_users = User::on('mysql')
            ->whereIn('id', $assignedUserIds)
            ->get();

        return response()->json([
            'data' => new CalendarEventResource($event)
        ], 201);
    }

    public function update(UpdateCalendarEventRequest $request, int $event_id): JsonResponse
    {
        $data = $request->validated();

        $event = CalendarEvent::findOrFail($event_id);

        $assignedUserIds = $data['assigned_user_ids'] ?? null;
        unset($data['assigned_user_ids']);

        $event->update($data);

        if (is_array($assignedUserIds)) {
            DB::connection('tenant')
                ->table('calendar_event_user')
                ->where('calendar_event_id', $event->id)
                ->delete();

            if (!empty($assignedUserIds)) {
                $rows = collect($assignedUserIds)->map(fn ($id) => [
                    'calendar_event_id' => $event->id,
                    'user_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->toArray();

                DB::connection('tenant')->table('calendar_event_user')->insert($rows);
            }
        }

        $event->assigned_users = User::on('mysql')
            ->whereIn('id', DB::connection('tenant')
                ->table('calendar_event_user')
                ->where('calendar_event_id', $event->id)
                ->pluck('user_id')
            )->get();

        return response()->json([
            'data' => new CalendarEventResource($event)
        ]);
    }

    public function destroy(int $event_id): Response
    {
        DB::connection('tenant')
            ->table('calendar_event_user')
            ->where('calendar_event_id', $event_id)
            ->delete();

        CalendarEvent::findOrFail($event_id)->delete();

        return response()->noContent();
    }
}
