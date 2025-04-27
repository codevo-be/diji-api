<?php

namespace Diji\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillingItemRequest extends FormRequest
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
            'name' => 'required|string',
            'type' => 'required|string',
        ];

        if ($type === 'product') {
            return array_merge($common, [
                'quantity' => 'required|numeric|min:0',
                'vat' => 'required|numeric|min:0|max:100',
                'cost' => 'sometimes|array|nullable',
                'cost.subtotal' => 'sometimes|numeric|min:0',
                'retail' => 'required|array',
                'retail.subtotal' => 'required|numeric|min:0',
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
