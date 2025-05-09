<?php

namespace Diji\Calendar\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Calendar\Models\CalendarEvent;
use Diji\Calendar\Http\Requests\StoreCalendarEventRequest;
use Diji\Calendar\Http\Requests\UpdateCalendarEventRequest;
use Diji\Calendar\Resources\CalendarEventResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CalendarEventController extends Controller
{
    public function index(Request $request)
    {
        $query = CalendarEvent::with('assignedUsers')->orderBy('start');

        $events = $request->has('page')
            ? $query->paginate()
            : $query->get();

        return CalendarEventResource::collection($events)->response();
    }

    public function show(int $event_id): JsonResponse
    {
        $event = CalendarEvent::with('assignedUsers')->findOrFail($event_id);

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
            $event->assignedUsers()->sync($assignedUserIds);
        }

        return response()->json([
            'data' => new CalendarEventResource($event->fresh(['assignedUsers']))
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
            $event->assignedUsers()->sync($assignedUserIds);
        }

        return response()->json([
            'data' => new CalendarEventResource($event->fresh(['assignedUsers']))
        ]);
    }

    public function destroy(int $event_id): Response
    {
        $event = CalendarEvent::findOrFail($event_id);

        $event->assignedUsers()->detach();
        $event->delete();

        return response()->noContent();
    }
}
