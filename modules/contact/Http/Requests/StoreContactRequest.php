<?php

namespace Diji\Contact\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firstname' => 'nullable|string|required_without_all:vat_number,',
            'lastname' => 'nullable|string|required_without_all:vat_number,',
            'email' => 'nullable|email|max:150|unique:contacts,email',
            'phone' => 'nullable|string|max:150',
            'company_name' => 'nullable|string|required_with:vat_number',
            'vat_number' => 'nullable|string|max:12',
            'billing_address' => 'array|nullable',
            'billing_address.street' => 'required_with:billing_address|string',
            'billing_address.street_number' => 'required_with:billing_address|string',
            'billing_address.city' => 'required_with:billing_address|string',
            'billing_address.zipcode' => 'required_with:billing_address|string',
            'billing_address.country' => 'required_with:billing_address|string',
        ];
    }
}
