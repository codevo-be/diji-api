<?php

namespace Diji\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Diji\Task\Enums\TaskStatus;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'task_column_id' => ['sometimes', 'integer', 'exists:task_columns,id'],
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'status' => ['sometimes', 'string', Rule::in(TaskStatus::values())],
            'priority' => 'sometimes|integer|min:1|max:5',
            'order' => 'sometimes|integer',
            'done' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'task_column_id.exists' => 'La colonne de tâche sélectionnée est invalide.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'status.string' => 'Le statut doit être une chaîne de caractères.',
            'status.in' => 'Le statut doit être valide.',
            'priority.integer' => 'La priorité doit être un nombre entier.',
            'priority.min' => 'La priorité doit être au minimum 1.',
            'priority.max' => 'La priorité doit être au maximum 5.',
            'order.integer' => 'L’ordre doit être un nombre entier.',
            'done.boolean' => 'Le champ terminé doit être vrai ou faux.',
        ];
    }
}
