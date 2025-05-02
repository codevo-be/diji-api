<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetModelUpload extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'model' => 'required|string',
            'model_id' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'model.required' => 'Le type de modèle est obligatoire',
            'model_id.required' => "L'ID du modèle est obligatoire",
        ];
    }
}
