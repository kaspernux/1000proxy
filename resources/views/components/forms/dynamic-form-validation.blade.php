{{-- Dynamic Form Validation Component --}}
{{-- Real-time form validation with advanced rules and visual feedback --}}

<div x-data="dynamicFormValidation()" 
     x-init="init()"
     class="dynamic-form-validation"
     data-validate-on-change="{{ $validateOnChange ?? true }}"
     data-validate-on-blur="{{ $validateOnBlur ?? true }}"
     data-show-errors-on-input="{{ $showErrorsOnInput ?? true }}"
     data-show-success-indicators="{{ $showSuccessIndicators ?? true }}">

    {{-- Validation Summary (Optional) --}}
    @if($showSummary ?? false)
        <div class="validation-summary bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 rounded-full" 
                         :class="isValid ? 'bg-green-500' : 'bg-gray-300'"></div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Form Validation
                    </span>
                </div>
                
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span x-text="Object.values(fields).filter(f => f.isValid).length"></span> 
                    of 
                    <span x-text="Object.keys(fields).length"></span> 
                    fields valid
                </div>
            </div>
            
            {{-- Progress Bar --}}
            <div class="mt-2 w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                <div class="h-2 rounded-full transition-all duration-300" 
                     :class="isValid ? 'bg-green-500' : 'bg-blue-500'"
                     :style="`width: ${getValidationSummary().validationPercentage}%`"></div>
            </div>
            
            {{-- Error Summary --}}
            <div x-show="Object.keys(errors).length > 0" class="mt-3">
                <div class="text-sm font-medium text-red-600 dark:text-red-400 mb-2">
                    Please fix the following errors:
                </div>
                <ul class="text-sm text-red-600 dark:text-red-400 space-y-1">
                    <template x-for="[field, message] in Object.entries(errors)" :key="field">
                        <li class="flex items-center space-x-2">
                            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <span x-text="`${getFieldLabel(field)}: ${message}`"></span>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    @endif

    {{-- Sample Form Fields --}}
    <form @submit.prevent="validateForm().then(valid => valid ? $dispatch('form-submit', getValidationSummary()) : null)"
          class="space-y-6">
          
        {{-- Basic Text Input with Real-time Validation --}}
        <div class="form-group">
            <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Username <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <input type="text" 
                       id="username" 
                       name="username"
                       data-validation='[
                           {"type":"required","message":"Username is required"},
                           {"type":"min_length","value":3,"message":"Username must be at least 3 characters"},
                           {"type":"max_length","value":20,"message":"Username must not exceed 20 characters"},
                           {"type":"pattern","pattern":"^[a-zA-Z0-9_-]+$","message":"Username can only contain letters, numbers, underscores, and hyphens"},
                           {"type":"async","endpoint":"/api/validate/username","message":"Username is already taken"}
                       ]'
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                       placeholder="Enter your username"
                       autocomplete="username">
            </div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                3-20 characters, letters, numbers, underscores, and hyphens only
            </div>
        </div>

        {{-- Email with Custom Validation --}}
        <div class="form-group">
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Email Address <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <input type="email" 
                       id="email" 
                       name="email"
                       data-validation='[
                           {"type":"required","message":"Email address is required"},
                           {"type":"email","message":"Please enter a valid email address"},
                           {"type":"async","endpoint":"/api/validate/email","message":"Email is already registered"}
                       ]'
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                       placeholder="Enter your email address"
                       autocomplete="email">
            </div>
        </div>

        {{-- Password with Strength Validation --}}
        <div class="form-group">
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Password <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <input type="password" 
                       id="password" 
                       name="password"
                       data-validation='[
                           {"type":"required","message":"Password is required"},
                           {"type":"password_strength","minLength":8,"requireUppercase":true,"requireLowercase":true,"requireNumbers":true,"requireSpecial":true}
                       ]'
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                       placeholder="Enter a strong password"
                       autocomplete="new-password">
                
                {{-- Password Strength Indicator --}}
                <div class="password-strength mt-2" x-show="fields.password && fields.password.value">
                    <div class="flex space-x-1 mb-2">
                        <div class="h-1 flex-1 rounded" 
                             :class="getPasswordStrength(fields.password?.value || '').score >= 1 ? 'bg-red-500' : 'bg-gray-200'"></div>
                        <div class="h-1 flex-1 rounded" 
                             :class="getPasswordStrength(fields.password?.value || '').score >= 2 ? 'bg-yellow-500' : 'bg-gray-200'"></div>
                        <div class="h-1 flex-1 rounded" 
                             :class="getPasswordStrength(fields.password?.value || '').score >= 3 ? 'bg-blue-500' : 'bg-gray-200'"></div>
                        <div class="h-1 flex-1 rounded" 
                             :class="getPasswordStrength(fields.password?.value || '').score >= 4 ? 'bg-green-500' : 'bg-gray-200'"></div>
                    </div>
                    <div class="text-xs" 
                         :class="getPasswordStrength(fields.password?.value || '').color"
                         x-text="getPasswordStrength(fields.password?.value || '').label"></div>
                </div>
            </div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Must contain uppercase, lowercase, numbers, and special characters
            </div>
        </div>

        {{-- Confirm Password with Match Validation --}}
        <div class="form-group">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Confirm Password <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <input type="password" 
                       id="password_confirmation" 
                       name="password_confirmation"
                       data-validation='[
                           {"type":"required","message":"Please confirm your password"},
                           {"type":"match","field":"password","message":"Passwords do not match"}
                       ]'
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                       placeholder="Confirm your password"
                       autocomplete="new-password">
            </div>
        </div>

        {{-- Phone Number with Pattern Validation --}}
        <div class="form-group">
            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Phone Number
            </label>
            <div class="relative">
                <input type="tel" 
                       id="phone" 
                       name="phone"
                       data-validation='[
                           {"type":"pattern","pattern":"^[\\+]?[1-9]\\d{1,14}$","message":"Please enter a valid phone number"}
                       ]'
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                       placeholder="+1234567890"
                       autocomplete="tel">
            </div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Include country code (e.g., +1 for US)
            </div>
        </div>

        {{-- Credit Card with Custom Validation --}}
        <div class="form-group">
            <label for="credit_card" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Credit Card Number
            </label>
            <div class="relative">
                <input type="text" 
                       id="credit_card" 
                       name="credit_card"
                       data-validation='[
                           {"type":"credit_card","message":"Please enter a valid credit card number"}
                       ]'
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                       placeholder="1234 5678 9012 3456"
                       autocomplete="cc-number"
                       maxlength="19">
            </div>
        </div>

        {{-- Select with Required Validation --}}
        <div class="form-group">
            <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Country <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <select id="country" 
                        name="country"
                        data-validation='[
                            {"type":"required","message":"Please select your country"}
                        ]'
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                    <option value="">Select your country</option>
                    <option value="US">🇺🇸 United States</option>
                    <option value="GB">🇬🇧 United Kingdom</option>
                    <option value="DE">🇩🇪 Germany</option>
                    <option value="FR">🇫🇷 France</option>
                    <option value="JP">🇯🇵 Japan</option>
                    <option value="AU">🇦🇺 Australia</option>
                    <option value="CA">🇨🇦 Canada</option>
                </select>
            </div>
        </div>

        {{-- Checkbox with Required Validation --}}
        <div class="form-group">
            <div class="flex items-start">
                <input type="checkbox" 
                       id="terms" 
                       name="terms"
                       data-validation='[
                           {"type":"required","message":"You must agree to the terms and conditions"}
                       ]'
                       class="mt-1 h-4 w-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500">
                <label for="terms" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                    I agree to the <a href="#" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Terms of Service</a> 
                    and <a href="#" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Privacy Policy</a>
                    <span class="text-red-500">*</span>
                </label>
            </div>
        </div>

        {{-- URL Input with URL Validation --}}
        <div class="form-group">
            <label for="website" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Website URL
            </label>
            <div class="relative">
                <input type="url" 
                       id="website" 
                       name="website"
                       data-validation='[
                           {"type":"url","message":"Please enter a valid URL"}
                       ]'
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                       placeholder="https://example.com"
                       autocomplete="url">
            </div>
        </div>

        {{-- Number Input with Min/Max Validation --}}
        <div class="form-group">
            <label for="age" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Age
            </label>
            <div class="relative">
                <input type="number" 
                       id="age" 
                       name="age"
                       min="18"
                       max="120"
                       data-validation='[
                           {"type":"min_number","value":18,"message":"You must be at least 18 years old"},
                           {"type":"max_number","value":120,"message":"Please enter a valid age"}
                       ]'
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                       placeholder="Enter your age">
            </div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Must be between 18 and 120 years old
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-600">
            <div class="flex items-center space-x-4">
                {{-- Real-time Validation Status --}}
                <div class="flex items-center space-x-2 text-sm">
                    <div class="w-3 h-3 rounded-full" 
                         :class="isValid ? 'bg-green-500' : (Object.keys(errors).length > 0 ? 'bg-red-500' : 'bg-gray-300')"></div>
                    <span class="text-gray-600 dark:text-gray-400" x-text="
                        isValid ? 'All fields valid' : 
                        Object.keys(errors).length > 0 ? `${Object.keys(errors).length} error(s)` : 
                        'Enter information'
                    "></span>
                </div>
                
                {{-- Validation Progress --}}
                <div x-show="isValidating" class="flex items-center space-x-2 text-sm text-blue-600 dark:text-blue-400">
                    <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-blue-600"></div>
                    <span>Validating...</span>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                {{-- Reset Button --}}
                <button type="button"
                        @click="reset()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                    Reset Form
                </button>

                {{-- Submit Button --}}
                <button type="submit"
                        :disabled="!isValid || isValidating"
                        class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    
                    <span x-show="!isValidating">
                        Submit Form
                    </span>
                    
                    <span x-show="isValidating" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Validating...
                    </span>
                </button>
            </div>
        </div>
    </form>

    {{-- Debug Panel (Development Only) --}}
    @if(config('app.debug'))
        <div class="debug-panel mt-8 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600">
            <details>
                <summary class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                    🔧 Debug Information
                </summary>
                <div class="mt-4 space-y-4 text-xs">
                    <div>
                        <strong>Form State:</strong>
                        <pre class="mt-1 p-2 bg-gray-200 dark:bg-gray-700 rounded text-xs overflow-auto" x-text="JSON.stringify({
                            isValid: isValid,
                            isValidating: isValidating,
                            errorCount: Object.keys(errors).length,
                            fieldCount: Object.keys(fields).length
                        }, null, 2)"></pre>
                    </div>
                    
                    <div>
                        <strong>Current Errors:</strong>
                        <pre class="mt-1 p-2 bg-gray-200 dark:bg-gray-700 rounded text-xs overflow-auto" x-text="JSON.stringify(errors, null, 2)"></pre>
                    </div>
                    
                    <div>
                        <strong>Field States:</strong>
                        <pre class="mt-1 p-2 bg-gray-200 dark:bg-gray-700 rounded text-xs overflow-auto max-h-40" x-text="JSON.stringify(
                            Object.fromEntries(
                                Object.entries(fields).map(([name, field]) => [
                                    name, 
                                    {
                                        value: field.value,
                                        isValid: field.isValid,
                                        isDirty: field.isDirty,
                                        isTouched: field.isTouched,
                                        isValidating: field.isValidating
                                    }
                                ])
                            ), null, 2
                        )"></pre>
                    </div>
                </div>
            </details>
        </div>
    @endif
</div>

{{-- Form Validation Styles --}}
<style>
.dynamic-form-validation {
    --validation-success: theme('colors.green.500');
    --validation-error: theme('colors.red.500');
    --validation-warning: theme('colors.yellow.500');
    --validation-info: theme('colors.blue.500');
}

/* Error Animations */
@keyframes errorSlideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fieldShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-4px); }
    75% { transform: translateX(4px); }
}

/* Field States */
.form-group input.error,
.form-group select.error,
.form-group textarea.error {
    animation: fieldShake 0.3s ease-in-out;
}

/* Password Strength Colors */
.password-strength .text-red-500 { color: var(--validation-error); }
.password-strength .text-yellow-500 { color: var(--validation-warning); }
.password-strength .text-blue-500 { color: var(--validation-info); }
.password-strength .text-green-500 { color: var(--validation-success); }

/* Focus States */
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Loading States */
.validating input {
    background-image: url("data:image/svg+xml,%3csvg width='100' height='100' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='50' cy='50' r='20' fill='none' stroke='%23d1d5db' stroke-width='4'/%3e%3ccircle cx='50' cy='50' r='20' fill='none' stroke='%233b82f6' stroke-width='4' stroke-dasharray='31.416' stroke-dashoffset='31.416'%3e%3canimateTransform attributeName='transform' dur='2s' type='rotate' values='0 50 50;360 50 50' repeatCount='indefinite'/%3e%3c/circle%3e%3c/svg%3e");
    background-size: 16px 16px;
    background-position: right 8px center;
    background-repeat: no-repeat;
}

/* Mobile Optimizations */
@media (max-width: 768px) {
    .form-group {
        margin-bottom: 1rem;
    }
    
    .field-icon {
        right: 0.75rem !important;
    }
}

/* Print Styles */
@media print {
    .debug-panel,
    .validation-summary {
        display: none !important;
    }
}
</style>

@script
<script>
    function dynamicFormValidation() {
        const component = window.dynamicFormValidation();
        
        // Add password strength helper
        component.getPasswordStrength = (password) => {
            if (!password) return { score: 0, label: 'No password', color: 'text-gray-400' };
            
            let score = 0;
            
            // Length check
            if (password.length >= 8) score++;
            
            // Character variety checks
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/\d/.test(password)) score++;
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score++;
            
            const labels = [
                { label: 'Very Weak', color: 'text-red-500' },
                { label: 'Weak', color: 'text-red-500' },
                { label: 'Fair', color: 'text-yellow-500' },
                { label: 'Good', color: 'text-blue-500' },
                { label: 'Strong', color: 'text-green-500' }
            ];
            
            return { 
                score: Math.max(0, score - 1), 
                ...labels[Math.max(0, score - 1)] 
            };
        };
        
        return component;
    }
</script>
@endscript
