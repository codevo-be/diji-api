<?php

namespace Diji\Billing\Resources;

use Diji\Contact\Resources\ContactResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class RecurringInvoiceResource extends JsonResource
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
            'status'                    => $this->status,
            'start_date'                => $this->start_date,
            'frequency'                 => $this->frequency,
            'next_run_at'               => $this->next_run_at,
            'issuer'                    => $this->issuer,
            'recipient'                 => $this->recipient,
            'subtotal'                  => $this->subtotal,
            'taxes'                     => (object) $this->taxes,
            'total'                     => $this->total,
            'contact_id'                => $this->contact_id,

            // Relations
            'contact' => new ContactResource($this->whenLoaded('contact')),
            'items' => BillingItemResource::collection($this->whenLoaded('items'))
        ];
    }
}
