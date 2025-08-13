<?php

namespace Diji\History\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                        => $this->id,
            'model_id'                  => $this->model_id,
            'model_type'                => $this->model_type,
            'message'                   => $this->message,
            'type'                      => $this->type,
            'created_at'                => $this->created_at,
        ];
    }
}
