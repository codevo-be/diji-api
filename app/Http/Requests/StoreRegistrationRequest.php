<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company' => 'required|string|max:255|unique:tenants,name',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'company.unique' => 'Ce nom de société est déjà utilisé.',
            'email.unique' => 'Cette adresse email est déjà enregistrée.',
        ];
    }
}
