<?php

namespace Diji\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSelfInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Set to false if only authorized users can update suppliers
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes', // todo enum

            'issuer' => 'sometimes|array|nullable',
            'issuer.name' => 'sometimes|string|nullable',
            'issuer.vat_number' => 'sometimes|string|nullable',
            'issuer.phone' => 'sometimes|string|nullable',
            'issuer.email' => 'sometimes|string|nullable',
            'issuer.iban' => 'sometimes|string|nullable',
            'issuer.street' => 'sometimes|string|nullable',
            'issuer.street_number' => 'sometimes|string|nullable',
            'issuer.city' => 'sometimes|string|nullable',
            'issuer.zipcode' => 'sometimes|string|nullable',
            'issuer.country' => 'sometimes|string|nullable',

            'recipient' => 'sometimes|array|nullable',
            'recipient.name' => 'sometimes|string|nullable',
            'recipient.vat_number' => 'sometimes|string|nullable',
            'recipient.phone' => 'sometimes|string|nullable',
            'recipient.email' => 'sometimes|string|nullable|email',
            'recipient.street' => 'sometimes|string|nullable',
            'recipient.street_number' => 'sometimes|string|nullable',
            'recipient.city' => 'sometimes|string|nullable',
            'recipient.zipcode' => 'sometimes|string|nullable',
            'recipient.country' => 'sometimes|string|nullable',

            'contact_id' => 'nullable|exists:contacts,id',

            'date' => 'sometimes|date',
            'due_date' => 'sometimes|nullable|date',
            'payment_date' => 'sometimes|nullable|date',
            'subtotal' => 'sometimes|nullable|numeric',
            'taxes' => 'sometimes|nullable|array',
            'total' => 'sometimes|nullable|numeric',
            'items' => 'sometimes|nullable|array',
            'items.*.' => (new UpdateBillingItemRequest())->rules()
        ];
    }
}
