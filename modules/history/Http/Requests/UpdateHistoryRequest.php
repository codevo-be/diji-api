<?php

namespace Diji\History\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Set to false if only authorized users can update suppliers
    }

    public function rules(): array
    {
        return [
            'model_id' => 'sometimes|integer',
            'model_type' => 'sometimes|string',
            'message' => 'sometimes|string',
            'type' => 'sometimes|string',
        ];
    }

    public function messages()
    {
        return [];
    }
}
