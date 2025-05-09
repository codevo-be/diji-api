<?php

namespace Diji\Calendar\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class CalendarEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'start' => $this->start,
            'end' => $this->end,
            'allDay' => $this->all_day,
            'assigned_user_ids' => DB::connection('tenant')
                ->table('calendar_event_user')
                ->where('calendar_event_id', $this->id)
                ->pluck('user_id')
                ->toArray(),
        ];
    }
}
