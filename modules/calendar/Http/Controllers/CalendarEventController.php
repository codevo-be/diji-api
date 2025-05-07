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
        $query = CalendarEvent::query();

        $query->orderBy('start');

        $events = $request->has('page')
            ? $query->paginate()
            : $query->get();

        return CalendarEventResource::collection($events)->response();
    }

    public function show(int $event_id): JsonResponse
    {
        $event = CalendarEvent::findOrFail($event_id);

        return response()->json([
            'data' => new CalendarEventResource($event)
        ]);
    }

    public function store(StoreCalendarEventRequest $request)
    {
        $data = $request->validated();

        $event = CalendarEvent::create($data);

        return response()->json([
            'data' => new CalendarEventResource($event)
        ], 201);
    }

    public function update(UpdateCalendarEventRequest $request, int $event_id): JsonResponse
    {
        $data = $request->validated();

        $event = CalendarEvent::findOrFail($event_id);

        $event->update($data);

        return response()->json([
            'data' => new CalendarEventResource($event)
        ]);
    }

    public function destroy(int $event_id): Response
    {
        $event = CalendarEvent::findOrFail($event_id);

        $event->delete();

        return response()->noContent();
    }
}
