<?php

namespace Diji\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreColumnRequest extends FormRequest
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
            'project_id' => ['required', 'integer', 'exists:task_project,id'],
            'name' => 'required|string|max:255',
            'order' => 'required|integer|min:1'
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

            'order.required' => 'L\'ordre est obligatoire.',
            'order.integer' => 'L\'ordre doit être un nombre entier.',
            'order.min' => 'L\'ordre doit être au minimum 1.'
        ];
    }
}
