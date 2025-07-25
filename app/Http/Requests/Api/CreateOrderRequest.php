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
        return [
            'items' => 'required|array|min:1|max:10',
            'items.*.server_plan_id' => 'required|integer|exists:server_plans,id',
            'items.*.quantity' => 'required|integer|min:1|max:50',
            'payment_method' => 'required|string|in:wallet,stripe,paypal,nowpayments',
            'notes' => 'nullable|string|max:500',
            'currency' => 'nullable|string|size:3|in:USD,EUR,GBP,BTC,XMR,SOL',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required',
            'items.array' => 'Items must be an array',
            'items.min' => 'At least one item is required',
            'items.max' => 'Maximum 10 items allowed per order',
            'items.*.server_plan_id.required' => 'Server plan ID is required for each item',
            'items.*.server_plan_id.exists' => 'Invalid server plan selected',
            'items.*.quantity.required' => 'Quantity is required for each item',
            'items.*.quantity.min' => 'Minimum quantity is 1',
            'items.*.quantity.max' => 'Maximum quantity is 50 per item',
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
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422));
    }
}
