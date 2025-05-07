<?php

namespace Diji\Expense\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
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
            'id' => $this->id,
            'document_identifier' => $this->document_identifier,
            'document_type' => $this->document_type,
            'sender' => [
                'name' => $this->sender['name'] ?? null,
                'vatNumber' => $this->sender['vatNumber'] ?? null,
            ],
            'recipient' => [
                'name' => $this->recipient['name'] ?? null,
                'vatNumber' => $this->recipient['vatNumber'] ?? null,
            ],
            'sender_address' => [
                'line1' => $this->sender_address['line1'] ?? null,
                'city' => $this->sender_address['city'] ?? null,
                'zipCode' => $this->sender_address['zipCode'] ?? null,
                'country' => $this->sender_address['country'] ?? null,
            ],
            'recipient_address' => [
                'line1' => $this->recipient_address['line1'] ?? null,
                'city' => $this->recipient_address['city'] ?? null,
                'zipCode' => $this->recipient_address['zipCode'] ?? null,
                'country' => $this->recipient_address['country'] ?? null,
            ],
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'taxes' => (object) $this->taxes,
            'structured_communication' => $this->structured_communication,
            'lines' => collect($this->lines ?? [])->map(function ($line) {
                return [
                    'name' => $line['name'] ?? '',
                    'price' => $line['price'] ?? 0,
                    'quantity' => $line['quantity'] ?? 0,
                    'vat' => $line['vat'] ?? 0,
                ];
            })->toArray(),
            'raw_xml' => $this->raw_xml,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'payment' => [
                'iban' => $this->sender['iban'] ?? null,
            ],
        ];
    }
}
