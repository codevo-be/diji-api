<?php

namespace App\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MetaResource extends JsonResource
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
            'id'       => $this->id,
            'model_type' => $this->model_type,
            'model_id'     => $this->model_id,
            'key' => $this->key,
            'value'      => $this->value,
            'type'     => $this->type
        ];
    }
}
