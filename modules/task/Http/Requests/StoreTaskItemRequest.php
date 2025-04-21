<?php

namespace Diji\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'task_group_id' => ['required', 'integer', 'exists:task_groups,id'],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['sometimes', 'string'],
            'priority' => 'sometimes|integer|min:1|max:5',
            'position' => 'sometimes|integer'
        ];
    }

    public function messages(): array
    {
        return [
            'task_group_id.required' => 'L\'ID de la liste de tâche est obligatoire.',
            'task_group_id.exists' => 'La liste de tâche sélectionnée est invalide.',

            'name.required' => 'Le nom est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne doit pas dépasser 255 caractères.',

            'description.string' => 'La description doit être une chaîne de caractères.',

            'status.string' => 'Le statut doit être une chaîne de caractères.',
            'status.in' => 'Le statut doit être valide.',

            'priority.integer' => 'La priorité doit être un nombre entier.',
            'priority.min' => 'La priorité doit être au minimum 1.',
            'priority.max' => 'La priorité doit être au maximum 5.',

            'order.integer' => 'L’ordre doit être un nombre entier.',
        ];
    }
}
