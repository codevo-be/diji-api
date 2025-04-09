<?php

namespace Diji\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Diji\Task\Enums\TaskStatus;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'task_column_id' => ['required', 'integer', 'exists:task_columns,id'],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', 'string', Rule::in(TaskStatus::values())],
            'priority' => 'required|integer|min:1|max:5',
            'order' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'task_column_id.required' => 'L\'ID de la colonne de tâche est obligatoire.',
            'task_column_id.exists' => 'La colonne de tâche sélectionnée est invalide.',

            'name.required' => 'Le nom est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne doit pas dépasser 255 caractères.',

            'description.string' => 'La description doit être une chaîne de caractères.',

            'status.required' => 'Le statut est obligatoire.',
            'status.string' => 'Le statut doit être une chaîne de caractères.',
            'status.in' => 'Le statut doit être valide.',

            'priority.required' => 'La priorité est obligatoire.',
            'priority.integer' => 'La priorité doit être un nombre entier.',
            'priority.min' => 'La priorité doit être au minimum 1.',
            'priority.max' => 'La priorité doit être au maximum 5.',

            'order.integer' => 'L’ordre doit être un nombre entier.',
        ];
    }
}
