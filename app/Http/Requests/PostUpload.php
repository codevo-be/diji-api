<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostUpload extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'files' => "required|array",
            //'files.*' => "mimes:pdf,jpeg,png",
        ];
    }

    public function message(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire',
            //'files.required' => "Vous devez télécharger des fichiers."
        ];
    }

}
