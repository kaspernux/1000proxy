<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EstimatePriceRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'currency_from' => 'required|string|size:3|in:USD,EUR,GBP,BTC,ETH,XMR,LTC',
            'currency_to' => 'required|string|size:3|in:USD,EUR,GBP,BTC,ETH,XMR,LTC',
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
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Amount must be at least 0.01.',
            'currency_from.required' => 'Source currency is required.',
            'currency_from.in' => 'Unsupported source currency.',
            'currency_to.required' => 'Target currency is required.',
            'currency_to.in' => 'Unsupported target currency.',
        ];
    }
}
