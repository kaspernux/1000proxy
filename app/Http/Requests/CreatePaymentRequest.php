<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && $this->user()->can('create payments');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => 'required|integer|exists:orders,id',
            'payment_method' => 'required|string|in:crypto,bank_transfer,paypal',
            'currency' => 'required|string|size:3|in:USD,EUR,GBP,BTC,ETH,XMR,LTC',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order_id.required' => 'Order ID is required.',
            'order_id.exists' => 'Invalid order ID.',
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Invalid payment method.',
            'currency.required' => 'Currency is required.',
            'currency.in' => 'Unsupported currency.',
            'amount.required' => 'Amount is required.',
            'amount.min' => 'Amount must be at least 0.01.',
            'amount.numeric' => 'Amount must be a valid number.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure user owns the order or has permission
        if ($this->has('order_id')) {
            $order = \App\Models\Order::find($this->order_id);
            if ($order && !$this->user()->can('access', $order)) {
                abort(403, 'You do not have permission to create payment for this order.');
            }
        }
    }
}
