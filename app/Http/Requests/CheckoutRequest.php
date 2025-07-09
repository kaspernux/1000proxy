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
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'telegram_id' => 'nullable|string|max:255',
            'payment_method' => 'required|exists:payment_methods,slug',
            'terms_accepted' => 'accepted',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your name.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'payment_method.required' => 'Please select a payment method.',
            'payment_method.exists' => 'The selected payment method is invalid.',
            'terms_accepted.accepted' => 'You must accept the terms and conditions to proceed.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'payment_method' => 'payment method',
            'terms_accepted' => 'terms and conditions',
        ];
    }
}