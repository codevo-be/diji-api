<?php

namespace Diji\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBillingItemRequest extends FormRequest
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

        $type = $this->input('type');

        $common = [
            'position' => 'sometimes|numeric',
            'name' => 'sometimes|string',
            'type' => 'sometimes|string',
        ];

        if ($type === 'product') {
            return array_merge($common, [
                'quantity' => 'sometimes|numeric|min:0',
                'vat' => 'sometimes|numeric|min:0|max:100',
                'cost' => 'sometimes|array|nullable',
                'cost.subtotal' => 'sometimes|numeric',
                'retail' => 'sometimes|array',
                'retail.subtotal' => 'sometimes|numeric',
            ]);
        }

        if (in_array($type, ['title', 'text'])) {
            return $common;
        }

        return $common;
    }

    public function messages(): array
    {
        return [
            "name.required" => "Le nom est requis !",
            "retail.subtotal.required" => "Le prix est requis !"
        ];
    }
}
