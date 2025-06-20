<?php

namespace Diji\Billing\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingItemResource extends JsonResource
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
            'position' => $this->position,
            'type'     => $this->type,
            'name'     => $this->name,
            'quantity' => $this->quantity,
            'vat'      => $this->vat,
            'cost'     => $this->cost,
            'retail'   => $this->retail,
        ];
    }
}
