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
            'all_day' => 'boolean'
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
        ];
    }
}
