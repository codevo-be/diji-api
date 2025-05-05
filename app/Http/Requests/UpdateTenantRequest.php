<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings' => 'sometimes|array',
            'peppol_identifier' => 'sometimes|string|nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'settings.array' => 'Le champ settings doit être un objet JSON valide.',
            'peppol_identifier.string' => 'L’identifiant Peppol doit être une chaîne de caractères.',
        ];
    }
}
