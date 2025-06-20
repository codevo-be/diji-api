<?php

namespace Diji\Task\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'task_group_id' => $this->task_group_id,
            'task_number' => $this->task_number,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'position' => $this->position,
        ];
    }
}
