<?php

namespace Diji\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Set to false if only authorized users can update suppliers
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes'
        ];
    }
}
