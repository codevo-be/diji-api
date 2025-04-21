<?php

namespace Diji\Project\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Le nom du projet doit être une chaîne de caractères.'
        ];
    }
}
