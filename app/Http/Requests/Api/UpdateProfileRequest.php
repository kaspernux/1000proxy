<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class UpdateProfileRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'username' => 'sometimes|required|string|max:50|alpha_dash|unique:customers,username,' . auth()->id(),
            'email' => 'sometimes|required|email|max:255|unique:customers,email,' . auth()->id(),
            'phone' => 'nullable|string|max:20|regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'locale' => 'nullable|string|in:en,fr,es,ru,ar,zh',
            'timezone' => 'nullable|string|max:50',
            'theme_mode' => 'nullable|string|in:light,dark,system',
            'email_notifications' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'name.regex' => 'Name can only contain letters and spaces',
            'username.required' => 'Username is required',
            'username.alpha_dash' => 'Username can only contain letters, numbers, dashes and underscores',
            'username.unique' => 'Username is already taken',
            'email.required' => 'Email is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'Email is already taken',
            'phone.regex' => 'Please provide a valid phone number',
            'locale.in' => 'Unsupported language',
            'theme_mode.in' => 'Invalid theme mode',
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
