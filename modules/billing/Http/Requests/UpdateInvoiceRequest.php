<?php

namespace Diji\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Set to false if only authorized users can update suppliers
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes', // todo enum

            'issuer' => 'sometimes|array',
            'issuer.name' => 'required_with:issuer|string',
            'issuer.vat_number' => 'sometimes|string',
            'issuer.phone' => 'sometimes|string',
            'issuer.email' => 'sometimes|string|email',
            'issuer.iban' => 'required_with:issuer|string',
            'issuer.street' => 'required_with:issuer|string',
            'issuer.street_number' => 'required_with:issuer|string',
            'issuer.city' => 'required_with:issuer|string',
            'issuer.zipcode' => 'required_with:issuer|string',
            'issuer.country' => 'required_with:issuer|string',

            'recipient' => 'sometimes|array',
            'recipient.name' => 'required_with:recipient|string',
            'recipient.vat_number' => 'sometimes|string',
            'recipient.street' => 'required_with:recipient|string',
            'recipient.street_number' => 'required_with:recipient|string',
            'recipient.city' => 'required_with:recipient|string',
            'recipient.zipcode' => 'required_with:recipient|string',
            'recipient.country' => 'required_with:recipient|string',

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
