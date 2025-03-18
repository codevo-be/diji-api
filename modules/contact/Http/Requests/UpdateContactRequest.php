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
            'firstname' => 'sometimes|nullable|string|required_without_all:company_name,vat_number,',
            'lastname' => 'sometimes|nullable|string|required_without_all:company_name,vat_number,',
            'email' => 'sometimes|nullable|email|max:150|unique:contacts,email,' . $this->contact,
            'phone' => 'sometimes|nullable|string|max:150',
            'company_name' => 'sometimes|nullable|string|required_with:vat_number',
            'vat_number' => 'sometimes|nullable|string|max:12',
            'iban' => 'sometimes|nullable|string',
            'billing_address' => 'sometimes|array|nullable',
            'billing_address.street' => 'string|nullable',
            'billing_address.street_number' => 'string|nullable',
            'billing_address.city' => 'string|nullable',
            'billing_address.zipcode' => 'string|nullable',
            'billing_address.country' => 'string|nullable',
        ];
    }

    public function messages()
    {
        return [
            'firstname.required_without_all' => 'Le prénom est requis si ni le numéro de TVA ni le nom de l\'entreprise ne sont renseignés.',
            'lastname.required_without_all' => 'Le nom de famille est requis si ni le numéro de TVA ni le nom de l\'entreprise ne sont renseignés.',
            'company_name.required_with' => 'Le nom de l\'entreprise est requis si un numéro de TVA est fourni.',
            'email.email' => 'L\'email doit être une adresse valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'phone.max' => 'Le numéro de téléphone ne peut pas dépasser 150 caractères.',
            'vat_number.max' => 'Le numéro de TVA ne peut pas dépasser 12 caractères.',
        ];
    }
}
