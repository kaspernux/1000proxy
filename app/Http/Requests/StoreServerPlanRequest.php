<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServerPlanRequest extends FormRequest
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
            'slug' => 'required|string|unique:server_plans,slug|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0|max:9999.99',
            'currency' => 'required|string|in:USD,EUR,GBP',
            'volume' => 'required|integer|min:1|max:1000',
            'days' => 'required|integer|min:1|max:365',
            'server_id' => 'required|exists:servers,id',
            'type' => 'required|string|in:single,multiple,dedicated,branded',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The plan name is required.',
            'price.numeric' => 'The price must be a valid number.',
            'server_id.exists' => 'The selected server does not exist.',
            'volume.min' => 'Volume must be at least 1 GB.',
            'days.max' => 'Maximum plan duration is 365 days.',
        ];
    }
}
