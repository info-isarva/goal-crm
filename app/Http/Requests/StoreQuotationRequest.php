<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Update with proper authorization logic if needed
    }

    public function rules(): array
    {
        return [
            'deal_id' => 'required|exists:deals,id',
            'valid_until' => 'required|date|after:today',
            'status' => 'required|in:Draft,Sent,Accepted,Declined',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'The customer field is required.',
            'valid_until.after' => 'The valid until date must be a future date.',
            'items.required' => 'At least one item is required.',
            'items.*.product_id.exists' => 'The selected product does not exist.',
        ];
    }
}
