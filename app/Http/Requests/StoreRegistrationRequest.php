<?php

namespace App\Http\Requests;

use App\Http\Requests\Rules\ValidRegistrationToken;
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
            'company' => 'required|string|max:255|unique:mysql.tenants,name',
            'email' => 'required|email|max:255|unique:mysql.users,email',
            'password' => 'required|string|min:8',
            'token' => ['required', 'string', new ValidRegistrationToken()],
        ];
    }


    public function messages(): array
    {
        return [
            'company.required' => 'Le nom de la société est requis.',
            'company.unique' => 'Ce nom de société est déjà utilisé.',

            'email.required' => 'L’adresse email est requise.',
            'email.email' => 'L’adresse email n’est pas valide.',
            'email.unique' => 'Cette adresse email est déjà enregistrée.',

            'password.required' => 'Le mot de passe est requis.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',

            'token.required' => 'Le lien d’inscription est manquant.',
            'token.exists' => 'Le lien d’inscription est invalide ou expiré.',
        ];
    }
}
