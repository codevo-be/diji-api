<?php

namespace Diji\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'name' => 'required|string|max:255',
            'position' => 'sometimes|integer|min:1'
        ];
    }

    /**
     * Get the custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'project_id.required' => 'L\'ID du projet est obligatoire.',
            'project_id.exists' => 'Le projet sélectionné est invalide.',

            'name.required' => 'Le nom de la colonne est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne doit pas dépasser 255 caractères.',

            'position.required' => 'L\'ordre est obligatoire.',
            'position.integer' => 'L\'ordre doit être un nombre entier.',
            'position.min' => 'L\'ordre doit être au minimum 1.'
        ];
    }
}
