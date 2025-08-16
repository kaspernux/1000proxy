<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class CreateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            // New multi-item style (optional if legacy fields used)
            'items' => 'sometimes|required_without:server_id|array|min:1|max:10',
            'items.*.server_plan_id' => 'required_with:items|integer|exists:server_plans,id',
            'items.*.quantity' => 'required_with:items|integer|min:1|max:50',
            // Legacy single-item style (server + optional plan)
            'server_id' => 'required_without:items|integer|exists:servers,id',
            'plan_id' => 'nullable|integer|exists:server_plans,id',
            'quantity' => 'required_without:items|integer|min:1|max:10',
            'duration' => 'sometimes|integer|min:1|max:12',
            'payment_method' => 'nullable|string|in:wallet,stripe,paypal,nowpayments',
            'notes' => 'nullable|string|max:500',
            'currency' => 'nullable|string|size:3|in:USD,EUR,GBP,BTC,XMR,SOL',
        ];

        // Conditional rule: if plan_id present with server_id, ensure plan belongs to server
        if ($this->filled('plan_id') && $this->filled('server_id')) {
            $rules['plan_id'] .= '|exists:server_plans,id,server_id,' . (int) $this->input('server_id');
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required when legacy fields are not provided',
            'items.array' => 'Items must be an array',
            'items.min' => 'At least one item is required',
            'items.max' => 'Maximum 10 items allowed per order',
            'items.*.server_plan_id.required' => 'Server plan ID is required for each item',
            'items.*.server_plan_id.exists' => 'Invalid server plan selected',
            'items.*.quantity.required' => 'Quantity is required for each item',
            'items.*.quantity.min' => 'Minimum quantity is 1',
            'items.*.quantity.max' => 'Maximum quantity is 50 per item',
            'server_id.required_without' => 'Server ID is required when items array is not provided',
            'server_id.exists' => 'Selected server does not exist',
            'plan_id.exists' => 'Invalid server plan selected',
            'quantity.required_without' => 'Quantity is required',
            'payment_method.required' => 'Payment method is required',
            'payment_method.in' => 'Invalid payment method selected',
            'currency.size' => 'Currency must be 3 characters',
            'currency.in' => 'Unsupported currency',
        ];
    }

    /**
     * Handle failed validation.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors()
        ], 422));
    }
}
