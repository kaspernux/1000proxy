{{-- Multi-Step Wizard Component --}}
{{-- Advanced form wizard with progress tracking, validation, and step management --}}

<div x-data="multiStepWizard()" 
     x-init="init()"
     class="multi-step-wizard bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700"
     data-persistence-key="{{ $persistenceKey ?? 'default_wizard' }}"
     data-validate-on-change="{{ $validateOnChange ?? true }}"
     data-show-progress="{{ $showProgress ?? true }}"
     data-allow-step-skip="{{ $allowStepSkip ?? false }}">

    {{-- Progress Header --}}
    <div class="wizard-header bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600 p-6">
        {{-- Step Indicators --}}
        <div class="flex items-center justify-between mb-4">
            @if(isset($steps) && is_array($steps))
                @foreach($steps as $index => $step)
                    <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                        <div data-step-indicator="{{ $index + 1 }}"
                             class="step-indicator flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-300
                                    {{ $index === 0 ? 'current bg-blue-600 border-blue-600 text-white' : 'pending bg-gray-100 dark:bg-gray-600 border-gray-300 dark:border-gray-500 text-gray-400' }}">
                            <div class="step-number">{{ $index + 1 }}</div>
                            <div class="step-check hidden">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        
                        {{-- Step Label --}}
                        <div class="ml-3 hidden md:block">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $step['title'] ?? "Step {$index + 1}" }}</div>
                            @if(isset($step['description']))
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $step['description'] }}</div>
                            @endif
                        </div>
                        
                        {{-- Connector Line --}}
                        @if(!$loop->last)
                            <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-600 mx-4 hidden md:block">
                                <div class="h-full bg-blue-600 transition-all duration-300" style="width: 0%"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
        
        {{-- Progress Bar --}}
        <div x-show="showProgress" class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
            <div data-progress-bar 
                 class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all duration-500 ease-out"
                 :style="`width: ${progressPercentage}%`"></div>
        </div>
        
        {{-- Progress Text --}}
        <div class="flex items-center justify-between mt-2 text-sm text-gray-600 dark:text-gray-400">
            <span>Step <span x-text="currentStep"></span> of <span x-text="totalSteps"></span></span>
            <span x-text="`${Math.round(progressPercentage)}% Complete`"></span>
        </div>
    </div>

    {{-- Form Content --}}
    <div class="wizard-content relative overflow-hidden">
        {{-- Step 1: Personal Information --}}
        <div data-step="1" class="step-content p-6 space-y-6">
            <div class="step-header mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Personal Information</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Please provide your basic information to get started.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- First Name --}}
                <div class="form-group">
                    <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        First Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="first_name" 
                           name="first_name"
                           x-model="formData.first_name"
                           @input="clearFieldError('first_name')"
                           data-validation='[{"type":"required","message":"First name is required"},{"type":"min","value":2,"message":"First name must be at least 2 characters"}]'
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors"
                           placeholder="Enter your first name">
                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                </div>
                
                {{-- Last Name --}}
                <div class="form-group">
                    <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Last Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="last_name" 
                           name="last_name"
                           x-model="formData.last_name"
                           @input="clearFieldError('last_name')"
                           data-validation='[{"type":"required","message":"Last name is required"},{"type":"min","value":2,"message":"Last name must be at least 2 characters"}]'
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors"
                           placeholder="Enter your last name">
                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                </div>
                
                {{-- Email --}}
                <div class="form-group md:col-span-2">
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email"
                           x-model="formData.email"
                           @input="clearFieldError('email')"
                           data-validation='[{"type":"required","message":"Email is required"},{"type":"email","message":"Please enter a valid email address"}]'
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors"
                           placeholder="Enter your email address">
                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                </div>
                
                {{-- Phone --}}
                <div class="form-group">
                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Phone Number
                    </label>
                    <input type="tel" 
                           id="wizard_phone" 
                           name="phone"
                           x-model="formData.phone"
                           @input="clearFieldError('phone')"
                           data-validation='[{"type":"pattern","pattern":"^[+]?[0-9\\s\\-\\(\\)]{10,}$","message":"Please enter a valid phone number"}]'
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors"
                           placeholder="Enter your phone number">
                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                </div>
                
                {{-- Country --}}
                <div class="form-group">
                    <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Country <span class="text-red-500">*</span>
                    </label>
                    <select id="country" 
                            name="country"
                            x-model="formData.country"
                            @change="clearFieldError('country')"
                            data-validation='[{"type":"required","message":"Please select your country"}]'
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors">
                        <option value="">Select your country</option>
                        <option value="US">🇺🇸 United States</option>
                        <option value="GB">🇬🇧 United Kingdom</option>
                        <option value="DE">🇩🇪 Germany</option>
                        <option value="FR">🇫🇷 France</option>
                        <option value="JP">🇯🇵 Japan</option>
                        <option value="AU">🇦🇺 Australia</option>
                        <option value="CA">🇨🇦 Canada</option>
                    </select>
                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                </div>
            </div>
        </div>

        {{-- Step 2: Server Preferences --}}
        <div data-step="2" class="step-content p-6 space-y-6 hidden">
            <div class="step-header mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Server Preferences</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Choose your preferred server configuration and location.</p>
            </div>
            
            <div class="space-y-6">
                {{-- Server Location --}}
                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Preferred Server Location <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="radio-card">
                            <input type="radio" 
                                   id="location_us" 
                                   name="server_location" 
                                   value="US"
                                   x-model="formData.server_location"
                                   @change="clearFieldError('server_location')"
                                   data-validation='[{"type":"required","message":"Please select a server location"}]'
                                   class="sr-only">
                            <label for="location_us" class="radio-card-label flex items-center p-4 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <span class="text-2xl mr-3">🇺🇸</span>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">United States</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Low latency for US users</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="radio-card">
                            <input type="radio" 
                                   id="location_eu" 
                                   name="server_location" 
                                   value="EU"
                                   x-model="formData.server_location"
                                   @change="clearFieldError('server_location')"
                                   class="sr-only">
                            <label for="location_eu" class="radio-card-label flex items-center p-4 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <span class="text-2xl mr-3">🇪🇺</span>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Europe</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">GDPR compliant servers</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="radio-card">
                            <input type="radio" 
                                   id="location_asia" 
                                   name="server_location" 
                                   value="ASIA"
                                   x-model="formData.server_location"
                                   @change="clearFieldError('server_location')"
                                   class="sr-only">
                            <label for="location_asia" class="radio-card-label flex items-center p-4 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <span class="text-2xl mr-3">🌏</span>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Asia Pacific</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Fast connections across Asia</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="radio-card">
                            <input type="radio" 
                                   id="location_global" 
                                   name="server_location" 
                                   value="GLOBAL"
                                   x-model="formData.server_location"
                                   @change="clearFieldError('server_location')"
                                   class="sr-only">
                            <label for="location_global" class="radio-card-label flex items-center p-4 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <span class="text-2xl mr-3">🌍</span>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Global</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Access to all locations</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                </div>
                
                {{-- Protocol Selection --}}
                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Preferred Protocol <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                        @foreach(['VLESS' => 'Fast & secure', 'VMESS' => 'Reliable & stable', 'TROJAN' => 'High performance', 'SHADOWSOCKS' => 'Lightweight'] as $protocol => $description)
                            <div class="checkbox-card">
                                <input type="checkbox" 
                                       id="protocol_{{ strtolower($protocol) }}" 
                                       name="protocols[]" 
                                       value="{{ $protocol }}"
                                       x-model="formData.protocols"
                                       @change="clearFieldError('protocols')"
                                       class="sr-only">
                                <label for="protocol_{{ strtolower($protocol) }}" class="checkbox-card-label block p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-center">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $protocol }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $description }}</div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                </div>
                
                {{-- Usage Type --}}
                <div class="form-group">
                    <label for="usage_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Primary Usage <span class="text-red-500">*</span>
                    </label>
                    <select id="usage_type" 
                            name="usage_type"
                            x-model="formData.usage_type"
                            @change="clearFieldError('usage_type')"
                            data-validation='[{"type":"required","message":"Please select your primary usage"}]'
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors">
                        <option value="">Select primary usage</option>
                        <option value="streaming">🎬 Streaming & Entertainment</option>
                        <option value="gaming">🎮 Gaming & Low Latency</option>
                        <option value="business">💼 Business & Professional</option>
                        <option value="privacy">🔒 Privacy & Security</option>
                        <option value="general">🌐 General Browsing</option>
                    </select>
                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                </div>
            </div>
        </div>

        {{-- Step 3: Payment & Billing --}}
        <div data-step="3" class="step-content p-6 space-y-6 hidden">
            <div class="step-header mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Payment & Billing</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Choose your payment method and billing preferences.</p>
            </div>
            
            <div class="space-y-6">
                {{-- Payment Method --}}
                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Payment Method <span class="text-red-500">*</span>
                    </label>
                    <div class="space-y-3">
                        <div class="payment-method">
                            <input type="radio" 
                                   id="payment_stripe" 
                                   name="payment_method" 
                                   value="stripe"
                                   x-model="formData.payment_method"
                                   @change="clearFieldError('payment_method')"
                                   data-validation='[{"type":"required","message":"Please select a payment method"}]'
                                   class="sr-only">
                            <label for="payment_stripe" class="payment-method-label flex items-center p-4 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <div class="flex items-center justify-center w-12 h-8 bg-blue-600 rounded mr-3">
                                    <span class="text-white font-bold text-sm">💳</span>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Credit/Debit Card</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Visa, Mastercard, American Express</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="payment-method">
                            <input type="radio" 
                                   id="payment_crypto" 
                                   name="payment_method" 
                                   value="crypto"
                                   x-model="formData.payment_method"
                                   @change="clearFieldError('payment_method')"
                                   class="sr-only">
                            <label for="payment_crypto" class="payment-method-label flex items-center p-4 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <div class="flex items-center justify-center w-12 h-8 bg-orange-500 rounded mr-3">
                                    <span class="text-white font-bold text-sm">₿</span>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Cryptocurrency</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Bitcoin, Ethereum, Monero</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="payment-method">
                            <input type="radio" 
                                   id="payment_paypal" 
                                   name="payment_method" 
                                   value="paypal"
                                   x-model="formData.payment_method"
                                   @change="clearFieldError('payment_method')"
                                   class="sr-only">
                            <label for="payment_paypal" class="payment-method-label flex items-center p-4 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <div class="flex items-center justify-center w-12 h-8 bg-blue-500 rounded mr-3">
                                    <span class="text-white font-bold text-sm">PP</span>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">PayPal</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Pay with your PayPal account</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                </div>
                
                {{-- Billing Cycle --}}
                <div class="form-group">
                    <label for="billing_cycle" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Billing Cycle <span class="text-red-500">*</span>
                    </label>
                    <select id="billing_cycle" 
                            name="billing_cycle"
                            x-model="formData.billing_cycle"
                            @change="clearFieldError('billing_cycle')"
                            data-validation='[{"type":"required","message":"Please select a billing cycle"}]'
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors">
                        <option value="">Select billing cycle</option>
                        <option value="monthly">Monthly - Pay every month</option>
                        <option value="quarterly">Quarterly - Save 10% (every 3 months)</option>
                        <option value="yearly">Yearly - Save 20% (every 12 months)</option>
                    </select>
                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                </div>
                
                {{-- Special Requests --}}
                <div class="form-group">
                    <label for="special_requests" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Special Requests or Notes
                    </label>
                    <textarea id="special_requests" 
                              name="special_requests"
                              x-model="formData.special_requests"
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors"
                              placeholder="Any special requirements or additional information..."></textarea>
                </div>
                
                {{-- Terms Agreement --}}
                <div class="form-group">
                    <div class="flex items-start">
                        <input type="checkbox" 
                               id="terms_agreement" 
                               name="terms_agreement"
                               x-model="formData.terms_agreement"
                               @change="clearFieldError('terms_agreement')"
                               data-validation='[{"type":"required","message":"You must agree to the terms and conditions"}]'
                               class="mt-1 h-4 w-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500">
                        <label for="terms_agreement" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                            I agree to the <a href="#" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Terms of Service</a> 
                            and <a href="#" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Privacy Policy</a>
                            <span class="text-red-500">*</span>
                        </label>
                    </div>
                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                </div>
                
                {{-- Newsletter Subscription --}}
                <div class="form-group">
                    <div class="flex items-start">
                        <input type="checkbox" 
                               id="newsletter" 
                               name="newsletter"
                               x-model="formData.newsletter"
                               class="mt-1 h-4 w-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500">
                        <label for="newsletter" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                            Subscribe to our newsletter for updates and special offers
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Navigation Footer --}}
    <div class="wizard-footer bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600 p-6">
        <div class="flex items-center justify-between">
            {{-- Previous Button --}}
            <button type="button"
                    @click="prevStep()"
                    x-show="currentStep > 1"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Previous
            </button>
            
            {{-- Step Navigation Helper --}}
            <div class="hidden md:flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-600 rounded text-xs">←</kbd>
                <span>Previous</span>
                <span class="mx-2">•</span>
                <span>Next</span>
                <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-600 rounded text-xs">→</kbd>
            </div>
            
            {{-- Next/Submit Button --}}
            <div class="flex items-center space-x-3">
                {{-- Reset Button --}}
                <button type="button"
                        @click="reset()"
                        class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                    Reset
                </button>
                
                {{-- Next/Submit Button --}}
                <button type="button"
                        @click="currentStep < totalSteps ? nextStep() : submitForm()"
                        :disabled="isSubmitting"
                        class="inline-flex items-center px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    
                    {{-- Loading Spinner --}}
                    <svg x-show="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    
                    <span x-show="!isSubmitting">
                        <span x-show="currentStep < totalSteps">
                            Next
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </span>
                        <span x-show="currentStep === totalSteps">
                            Complete Setup
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </span>
                    </span>
                    
                    <span x-show="isSubmitting">Processing...</span>
                </button>
            </div>
        </div>
        
        {{-- Quick Actions --}}
        <div class="flex items-center justify-center mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
            <div class="flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                <button @click="exportData()" class="hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                    📄 Export Data
                </button>
                <span>•</span>
                <button @click="clearPersistedData()" class="hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                    🗑️ Clear Saved Data
                </button>
                <span>•</span>
                <span>Auto-saved: <span x-text="new Date().toLocaleTimeString()"></span></span>
            </div>
        </div>
    </div>
</div>

{{-- Wizard Styles --}}
<style>
.multi-step-wizard {
    --wizard-primary: theme('colors.blue.600');
    --wizard-primary-hover: theme('colors.blue.700');
    --wizard-success: theme('colors.green.600');
    --wizard-error: theme('colors.red.600');
    --wizard-warning: theme('colors.yellow.600');
}

/* Step Transitions */
.step-content {
    transition: all 0.3s ease-in-out;
}

.step-enter {
    opacity: 1;
    transform: translateX(0);
}

.step-enter-forward {
    transform: translateX(0);
    animation: slideInRight 0.3s ease-out;
}

.step-enter-backward {
    transform: translateX(0);
    animation: slideInLeft 0.3s ease-out;
}

.step-exit-forward {
    animation: slideOutLeft 0.3s ease-in;
}

.step-exit-backward {
    animation: slideOutRight 0.3s ease-in;
}

@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideInLeft {
    from { transform: translateX(-100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOutLeft {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(-100%); opacity: 0; }
}

@keyframes slideOutRight {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}

/* Step Indicators */
.step-indicator.current {
    @apply bg-blue-600 border-blue-600 text-white;
}

.step-indicator.completed {
    @apply bg-green-600 border-green-600 text-white;
}

.step-indicator.completed .step-number {
    @apply hidden;
}

.step-indicator.completed .step-check {
    @apply block;
}

.step-indicator.pending {
    @apply bg-gray-100 border-gray-300 text-gray-400;
}

/* Form Elements */
.radio-card input:checked + .radio-card-label {
    @apply border-blue-600 bg-blue-50 dark:bg-blue-900/20;
}

.checkbox-card input:checked + .checkbox-card-label {
    @apply border-blue-600 bg-blue-50 dark:bg-blue-900/20;
}

.payment-method input:checked + .payment-method-label {
    @apply border-blue-600 bg-blue-50 dark:bg-blue-900/20;
}

/* Error States */
.form-group input.error,
.form-group select.error,
.form-group textarea.error {
    @apply border-red-500 focus:border-red-500 focus:ring-red-500;
}

/* Progress Bar Animation */
@keyframes progressFill {
    from { width: 0%; }
    to { width: var(--progress-width); }
}

[data-progress-bar] {
    animation: progressFill 0.5s ease-out;
}

/* Mobile Optimizations */
@media (max-width: 768px) {
    .step-indicator {
        @apply w-8 h-8 text-sm;
    }
    
    .wizard-header {
        @apply p-4;
    }
    
    .wizard-content {
        @apply px-4;
    }
    
    .wizard-footer {
        @apply p-4;
    }
}

/* Loading States */
.wizard-content:has(.step-content:not(.hidden)) {
    min-height: 400px;
}

/* Print Styles */
@media print {
    .wizard-footer,
    .wizard-header .step-indicator {
        @apply hidden;
    }
    
    .step-content {
        @apply block !important;
    }
}
</style>

@script
<script>
    function multiStepWizard() {
        return window.multiStepWizard();
    }
</script>
@endscript
