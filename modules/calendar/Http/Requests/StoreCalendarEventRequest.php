<?php

namespace Diji\Calendar\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start' => 'required|date',
            'end' => 'nullable|date|after_or_equal:start',
            'all_day' => 'boolean',
            'assigned_user_ids' => 'nullable|array',
            'assigned_user_ids.*' => 'integer|exists:mysql.users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est requis.',
            'title.max' => 'Le titre ne peut pas dépasser 255 caractères.',
            'start.required' => 'La date de début est requise.',
            'start.date' => 'La date de début doit être une date valide.',
            'end.date' => 'La date de fin doit être une date valide.',
            'end.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'all_day.boolean' => 'Le champ all_day doit être vrai ou faux.',
            'assigned_user_ids.array' => 'Les utilisateurs assignés doivent être une liste.',
            'assigned_user_ids.*.integer' => 'Chaque utilisateur assigné doit être un identifiant valide.',
            'assigned_user_ids.*.exists' => 'Un ou plusieurs utilisateurs sélectionnés n’existent pas.',
        ];
    }
}
