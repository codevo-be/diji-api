<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMetaRequest extends FormRequest
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
            'model_id' => 'sometimes|numeric',
            'model_type' => 'sometimes|string',
            'value' => 'required',
            'type' => 'sometimes|string', // todo enum
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
