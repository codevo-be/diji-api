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
        return [
            'position' => 'sometimes|numeric',
            'name' => 'sometimes|string',
            'quantity' => 'sometimes|nullable|numeric|min:0',
            'vat' => 'sometimes|nullable|integer|min:0|max:100',
            'cost' => 'sometimes|nullable|array',
            'cost.subtotal' => 'required_with:cost|numeric',
            'retail' => 'sometimes|nullable|array',
            'retail.subtotal' => 'required_with:retail|numeric',
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
