<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServerPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('update server plans');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $planId = $this->route('plan') ?? $this->route('server_plan');
        
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('server_plans')->ignore($planId),
            ],
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0|max:999999.99',
            'currency' => 'required|string|size:3|in:USD,EUR,GBP,CAD,AUD,JPY,CNY,BTC,ETH',
            'duration_days' => 'required|integer|min:1|max:3650',
            'max_connections' => 'required|integer|min:1|max:10000',
            'bandwidth_limit_gb' => 'nullable|integer|min:1|max:10000',
            'server_category_id' => 'required|integer|exists:server_categories,id',
            'server_brand_id' => 'required|integer|exists:server_brands,id',
            'features' => 'nullable|array',
            'features.*' => 'string|max:100',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
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
            'name.required' => 'The plan name is required.',
            'name.unique' => 'A server plan with this name already exists.',
            'price.required' => 'The price is required.',
            'price.numeric' => 'The price must be a valid number.',
            'price.min' => 'The price cannot be negative.',
            'currency.required' => 'The currency is required.',
            'currency.in' => 'The currency must be one of: USD, EUR, GBP, CAD, AUD, JPY, CNY, BTC, ETH.',
            'duration_days.required' => 'The duration in days is required.',
            'duration_days.min' => 'The duration must be at least 1 day.',
            'duration_days.max' => 'The duration cannot exceed 10 years.',
            'max_connections.required' => 'The maximum connections limit is required.',
            'max_connections.min' => 'At least 1 connection must be allowed.',
            'server_category_id.required' => 'Please select a server category.',
            'server_category_id.exists' => 'The selected server category is invalid.',
            'server_brand_id.required' => 'Please select a server brand.',
            'server_brand_id.exists' => 'The selected server brand is invalid.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Convert string booleans to actual booleans
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('is_featured')) {
            $this->merge([
                'is_featured' => filter_var($this->is_featured, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // Clean and normalize tags
        if ($this->has('tags') && is_array($this->tags)) {
            $this->merge([
                'tags' => array_filter(array_map('trim', $this->tags)),
            ]);
        }

        // Clean and normalize features
        if ($this->has('features') && is_array($this->features)) {
            $this->merge([
                'features' => array_filter(array_map('trim', $this->features)),
            ]);
        }
    }
}
