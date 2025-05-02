<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostUpload extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $acceptedTypes = array(
            "expense",
            "invoice"
        );

        return [
            'model' => ["required", "string", Rule::in($acceptedTypes)],
            'model_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'files' => "nullable|array",
            'files.*' => "file|mimes:pdf,jpeg,png",
        ];
    }

    private function getModelClass(string $modelType): ?string
    {
        $models = [
            'expense' => \Diji\Billing\Models\Transaction::class,
            'invoice' => \Diji\Billing\Models\Invoice::class,
        ];

        return $models[$modelType] ?? null;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire',
            "model.required" => "Le type de modèle est obligatoire",
            "model_id.required" => "L'ID du modèle est obligatoire",
            "files.*.mimes" => "Le fichier doit être au format PDF, JPEG ou PNG",
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $modelType = $this->input('model');
            $modelId = $this->input('model_id');

            $modelClass = $this->getModelClass($modelType);

            if ($modelClass && ! $modelClass::find($modelId)) {
                $validator->errors()->add('model_id', "Aucun enregistrement trouvé pour {$modelType} avec l'ID {$modelId}.");
            }
        });
    }

}
