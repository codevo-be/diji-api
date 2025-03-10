<?php

namespace Diji\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreditNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes', // todo enum
            'invoice_id' => 'sometimes|exists:invoices,id',

            'issuer' => 'sometimes|array|nullable',
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

            'recipient' => 'sometimes|array|nullable',
            'recipient.name' => 'required_with:recipient|string',
            'recipient.vat_number' => 'sometimes|string',
            'recipient.street' => 'required_with:recipient|string',
            'recipient.street_number' => 'required_with:recipient|string',
            'recipient.city' => 'required_with:recipient|string',
            'recipient.zipcode' => 'required_with:recipient|string',
            'recipient.country' => 'required_with:recipient|string',

            'contact_id' => 'nullable|exists:contacts,id',

            'date' => 'sometimes|date',
            'subtotal' => 'nullable|numeric',
            'taxes' => 'nullable|array',
            "taxes.*" => 'numeric',
            'total' => 'nullable|numeric',
            'items' => 'sometimes|nullable|array',
            'items.*.' => (new StoreBillingItemRequest())->rules()
        ];
    }
}
