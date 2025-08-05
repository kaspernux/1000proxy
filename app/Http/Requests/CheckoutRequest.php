<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->guard('customer')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Billing information
            'first_name' => 'required|string|max:255|min:2',
            'last_name' => 'required|string|max:255|min:2',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'company' => 'nullable|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'postal_code' => 'required|string|max:10',
            'country' => 'required|string|max:255',
            
            // Payment information
            'payment_method' => 'required|in:crypto,stripe,wallet,mir',
            'crypto_currency' => 'required_if:payment_method,crypto|nullable|string|in:btc,eth,xmr,ltc,doge,ada,dot,sol',
            'save_payment_method' => 'boolean',
            'agree_to_terms' => 'accepted',
            'subscribe_newsletter' => 'boolean',
            
            // Order information
            'coupon_code' => 'nullable|string|max:50',
            'discount_amount' => 'nullable|numeric|min:0',
            
            // Cart data
            'cart_items' => 'required|array|min:1',
            'cart_items.*.server_plan_id' => 'required|exists:server_plans,id',
            'cart_items.*.quantity' => 'required|integer|min:1',
            'cart_items.*.unit_price' => 'required|numeric|min:0',
            'cart_items.*.total_price' => 'required|numeric|min:0',
            'order_summary' => 'required|array',
            'order_summary.subtotal' => 'required|numeric|min:0',
            'order_summary.tax' => 'required|numeric|min:0',
            'order_summary.shipping' => 'required|numeric|min:0',
            'order_summary.discount' => 'required|numeric|min:0',
            'order_summary.total' => 'required|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Please enter your first name.',
            'last_name.required' => 'Please enter your last name.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'phone.required' => 'Please enter your phone number.',
            'address.required' => 'Please enter your address.',
            'city.required' => 'Please enter your city.',
            'state.required' => 'Please enter your state/province.',
            'postal_code.required' => 'Please enter your postal code.',
            'country.required' => 'Please enter your country.',
            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in' => 'The selected payment method is invalid.',
            'crypto_currency.required_if' => 'Please select a cryptocurrency when using crypto payment.',
            'crypto_currency.in' => 'The selected cryptocurrency is not supported.',
            'agree_to_terms.accepted' => 'You must accept the terms and conditions to proceed.',
            'cart_items.required' => 'Your cart is empty.',
            'cart_items.min' => 'Your cart must contain at least one item.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'payment_method' => 'payment method',
            'crypto_currency' => 'cryptocurrency',
            'agree_to_terms' => 'terms and conditions',
            'cart_items' => 'cart items',
            'order_summary' => 'order summary',
        ];
    }
}