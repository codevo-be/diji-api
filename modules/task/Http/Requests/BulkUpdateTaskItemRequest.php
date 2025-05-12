<?php

namespace Diji\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateTaskItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tasks' => ['required', 'array'],
            'tasks.*.id' => ['required', 'integer', 'exists:task_items,id'],
            'tasks.*.task_group_id' => ['required', 'integer', 'exists:task_groups,id'],
            'tasks.*.position' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'tasks.required' => 'La liste des tâches est requise.',
            'tasks.array' => 'Les tâches doivent être un tableau.',
            'tasks.*.id.required' => 'Chaque tâche doit avoir un identifiant.',
            'tasks.*.id.integer' => 'L’identifiant de la tâche doit être un nombre entier.',
            'tasks.*.id.exists' => 'Une des tâches spécifiées est introuvable.',
            'tasks.*.task_group_id.required' => 'Chaque tâche doit appartenir à une liste.',
            'tasks.*.task_group_id.integer' => 'L’ID de la liste doit être un entier.',
            'tasks.*.task_group_id.exists' => 'Une des listes de tâches spécifiées est invalide.',
            'tasks.*.position.required' => 'Chaque tâche doit avoir une position.',
            'tasks.*.position.integer' => 'La position doit être un entier.',
        ];
    }
}
