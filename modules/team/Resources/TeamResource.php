<?php

namespace Diji\Team\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
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
            'display_name'              => $this->display_name,
            'firstname'                 => $this->firstname,
            'lastname'                  => $this->lastname,
            'email'                     => $this->email,
        ];
    }
}
