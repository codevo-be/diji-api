<?php

namespace Diji\Project\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du projet est obligatoire.',
            'name.string' => 'Le nom du projet doit être une chaîne de caractères.'
        ];
    }
}
