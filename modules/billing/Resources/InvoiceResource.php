<?php

namespace Diji\Billing\Resources;

use Diji\Contact\Resources\ContactResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class InvoiceResource extends JsonResource
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
            'identifier'                => $this->identifier,
            'identifier_number'         => $this->identifier_number,
            'status'                    => $this->status,
            'issuer'                    => $this->issuer,
            'recipient'                 => $this->recipient,
            'date'                      => $this->date,
            'due_date'                  => $this->due_date,
            'check_paid_notification'   => $this->check_paid_notification,
            'payment_date'              => $this->payment_date,
            'structured_communication'  => $this->structured_communication, // todo format with +++
            'subtotal'                  => $this->subtotal,
            'taxes'                     => (object) $this->taxes,
            'total'                     => $this->total,
            'contact_id'                => $this->contact_id,

            // Relations
            'contact' => new ContactResource($this->whenLoaded('contact')),
            'items' => BillingItemResource::collection($this->whenLoaded('items')),
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }
}
