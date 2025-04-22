<?php

namespace Diji\Billing\Resources;

use Diji\Contact\Resources\ContactResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'id'                       => $this->id,
            'date'                     => $this->date,
            'transaction_id'           => $this->transaction_id,
            'structured_communication' => $this->structured_communication,
            'creditor_name'            => $this->creditor_name,
            'creditor_account'         => $this->creditor_account,
            'debtor_name'              => $this->debtor_name,
            'debtor_account'           => $this->debtor_account,
            'amount'                   => $this->amount,
            'status'                   => $this->status,
        ];
    }
}
