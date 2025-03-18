<?php

namespace Diji\Contact\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
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
            'phone'                     => $this->phone,
            'company_name'              => $this->company_name,
            'vat_number'                => $this->vat_number,
            'iban'                      => $this->iban,
            'billing_address'           => $this->billing_address,
        ];
    }
}
