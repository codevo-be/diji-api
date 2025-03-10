<?php

namespace Diji\Contact\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Set to false if only authorized users can update suppliers
    }

    public function rules(): array
    {
        return [
            'firstname' => 'sometimes|nullable|string|required_without_all:vat_number,',
            'lastname' => 'sometimes|nullable|string|required_without_all:vat_number,',
            'email' => 'sometimes|nullable|email|max:150|unique:contacts,email,' . $this->contact,
            'phone' => 'sometimes|nullable|string|max:150',
            'company_name' => 'sometimes|nullable|string|required_with:vat_number',
            'vat_number' => 'sometimes|nullable|string|max:12',
            'billing_address' => 'sometimes|array|nullable',
            'billing_address.street' => 'required_with:billing_address|string',
            'billing_address.street_number' => 'required_with:billing_address|string',
            'billing_address.city' => 'required_with:billing_address|string',
            'billing_address.zipcode' => 'required_with:billing_address|string',
            'billing_address.country' => 'required_with:billing_address|string',
        ];
    }
}
