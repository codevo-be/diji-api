<?php

namespace Diji\History\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'model_id' => 'required|integer',
            'model_type' => 'required|string',
            'message' => 'required|string',
            'type' => 'required|string',
        ];
    }

    public function messages()
    {
        return [];
    }
}
