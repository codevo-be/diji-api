<?php

namespace Diji\Team\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Set to false if only authorized users can update suppliers
    }

    public function rules(): array
    {
        return [
            'display_name' => 'sometimes|nullable|string',
            'firstname' => 'sometimes|nullable|string',
            'lastname' => 'sometimes|nullable|string',
            'email' => 'sometimes|nullable|email|max:150',
        ];
    }

    public function messages()
    {
        return [];
    }
}
