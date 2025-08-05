<main class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-blue-900 py-10 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Animated background elements -->
    <div class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/15 to-yellow-500/15 animate-pulse"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-transparent to-gray-900/60"></div>
    </div>

    <!-- Floating shapes with enhanced animations -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-32 w-80 h-80 bg-gradient-to-br from-yellow-400/25 to-blue-400/25 rounded-full blur-3xl animate-bounce duration-[6000ms]"></div>
        <div class="absolute -bottom-40 -left-32 w-80 h-80 bg-gradient-to-br from-blue-400/20 to-yellow-400/15 rounded-full blur-3xl animate-pulse duration-[8000ms]"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-r from-purple-400/10 to-pink-400/10 rounded-full blur-2xl animate-spin duration-[20000ms]"></div>
    </div>

    <!-- Progress Header -->
    <div class="max-w-6xl mx-auto mb-12 relative z-10">
        <div class="text-center mb-8">
            <!-- Breadcrumb -->
            <nav class="flex justify-center items-center space-x-2 text-sm mb-6">
                <a href="/" wire:navigate class="text-gray-400 hover:text-white transition-colors duration-200">Home</a>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <a href="/cart" wire:navigate class="text-gray-400 hover:text-white transition-colors duration-200">Cart</a>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <span class="text-blue-400 font-medium">Checkout</span>
            </nav>

            <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold text-white mb-4 leading-tight">
                <span class="bg-gradient-to-r from-blue-400 via-yellow-400 to-blue-500 bg-clip-text text-transparent">
                    Secure Checkout
                </span>
            </h1>
            <p class="text-lg md:text-xl text-gray-300 font-light">Complete your proxy order in just a few simple steps</p>
        </div>
        
        <!-- Enhanced Progress Steps -->
        <div class="relative">
            <div class="flex items-center justify-center">
                @for($i = 1; $i <= $totalSteps; $i++)
                <div class="flex items-center">
                    <div class="relative">
                        <!-- Step Circle -->
                        <div class="flex items-center justify-center w-16 h-16 rounded-full font-bold text-lg shadow-2xl transition-all duration-300
                            {{ $currentStep >= $i ? 'bg-gradient-to-r from-blue-500 to-yellow-500 text-white scale-110' : 'bg-white/10 backdrop-blur-sm text-white/60 border border-white/20' }}">
                            @if($currentStep > $i)
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @else
                                {{ $i }}
                            @endif
                        </div>
                        
                        <!-- Step Label -->
                        <div class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 text-center min-w-max">
                            <span class="text-sm font-medium {{ $currentStep >= $i ? 'text-white' : 'text-gray-400' }}">
                                @switch($i)
                                    @case(1) Cart Review @break
                                    @case(2) Billing Info @break
                                    @case(3) Payment @break
                                    @case(4) Confirmation @break
                                @endswitch
                            </span>
                        </div>

                        <!-- Animated Ring for Current Step -->
                        @if($currentStep === $i)
                            <div class="absolute inset-0 w-16 h-16 rounded-full border-4 border-blue-400 animate-ping opacity-50"></div>
                        @endif
                    </div>

                    <!-- Enhanced Connector Line -->
                    @if($i < $totalSteps)
                        <div class="w-24 h-1 mx-4 rounded-full transition-all duration-500 {{ $currentStep > $i ? 'bg-gradient-to-r from-blue-500 to-yellow-500' : 'bg-white/20' }}"></div>
                    @endif
                </div>
                @endfor
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Checkout Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Step 1: Enhanced Cart Review -->
                @if($currentStep === 1)
                <div class="bg-white/5 backdrop-blur-md rounded-3xl p-8 shadow-xl border border-white/10">
                    <h2 class="text-3xl font-bold text-white mb-8 flex items-center">
                        <x-custom-icon name="shopping-cart" class="w-8 h-8 mr-4 text-yellow-400" />
                        Review Your Order
                    </h2>

                    <div class="space-y-6">
                        @foreach($cart_items as $item)
                        <div class="group bg-white/5 rounded-2xl p-6 border border-white/10 hover:bg-white/10 transition-all duration-300" 
                             wire:key="cart-{{ $item['server_plan_id'] }}">
                            <div class="flex items-center space-x-6">
                                <!-- Product Image -->
                                <div class="relative">
                                    <img src="{{ url('storage/' . $item['product_image']) }}"
                                         alt="{{ $item['name'] }}"
                                         class="w-20 h-20 object-cover rounded-xl border border-yellow-400/50">
                                    <div class="absolute -top-2 -right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full">
                                        {{ $item['quantity'] }}x
                                    </div>
                                </div>

                                <!-- Product Details -->
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-white group-hover:text-yellow-400 transition-colors">
                                        {{ $item['name'] }}
                                    </h3>
                                    <div class="flex items-center space-x-4 mt-2">
                                        <span class="text-green-200 text-sm">Quantity: {{ $item['quantity'] }}</span>
                                        <span class="text-white/60">•</span>
                                        <span class="text-green-200 text-sm">${{ number_format($item['price'], 2) }} each</span>
                                    </div>
                                </div>

                                <!-- Price -->
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-yellow-400">
                                        ${{ number_format($item['total_amount'], 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Enhanced Coupon Section -->
                    <div class="mt-8 bg-gradient-to-r from-yellow-600/10 to-yellow-500/5 rounded-2xl p-6 border border-yellow-500/30">
                        <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                            <x-custom-icon name="ticket" class="w-6 h-6 mr-3 text-yellow-400" />
                            Promo Code
                        </h3>
                        <div class="flex space-x-3">
                            <input type="text"
                                   wire:model="coupon_code"
                                   placeholder="Enter your promo code"
                                   class="flex-1 px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                            <button wire:click="applyCoupon"
                                    class="px-6 py-3 bg-yellow-600 hover:bg-yellow-500 text-white font-bold rounded-xl transition-all duration-200 disabled:opacity-50"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="applyCoupon">Apply</span>
                                <span wire:loading wire:target="applyCoupon" class="flex items-center">
                                    <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
                                    Applying...
                                </span>
                            </button>
                        </div>
                        
                        @if($applied_coupon)
                        <div class="mt-4 bg-green-600/20 border border-green-500/30 rounded-xl p-4 flex items-center justify-between">
                            <div class="flex items-center">
                                <x-custom-icon name="check-circle" class="w-5 h-5 text-green-400 mr-3" />
                                <span class="text-green-400 font-medium">Coupon Applied: {{ $applied_coupon }}</span>
                            </div>
                            <button wire:click="removeCoupon" class="text-red-400 hover:text-red-300 font-medium">
                                Remove
                            </button>
                        </div>
                        @endif
                    </div>

                    <div class="flex justify-end mt-8">
                        <button wire:click="nextStep"
                                class="px-8 py-4 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-green-400 text-white font-bold text-lg rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105 flex items-center">
                            Continue to Billing
                            <x-custom-icon name="arrow-right" class="w-5 h-5 ml-3" />
                        </button>
                    </div>
                </div>
                @endif

                <!-- Step 2: Enhanced Billing Information -->
                @if($currentStep === 2)
                <div class="bg-white/5 backdrop-blur-md rounded-3xl p-8 shadow-xl border border-white/10">
                    <h2 class="text-3xl font-bold text-white mb-8 flex items-center">
                        <x-custom-icon name="user" class="w-8 h-8 mr-4 text-blue-400" />
                        Billing Information
                    </h2>

                    <form wire:submit.prevent="nextStep" class="space-y-6">
                        <!-- Add a debug section to show validation errors -->
                        @if ($errors->any())
                        <div class="bg-red-500/20 border border-red-500/50 rounded-xl p-4 mb-6">
                            <h4 class="text-red-400 font-medium mb-2">Please fix the following errors:</h4>
                            <ul class="text-red-300 text-sm space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>• {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-white font-medium mb-3">First Name *</label>
                                <input type="text"
                                       wire:model="first_name"
                                       class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                       placeholder="Enter your first name">
                                @error('first_name') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-white font-medium mb-3">Last Name *</label>
                                <input type="text"
                                       wire:model="last_name"
                                       class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                       placeholder="Enter your last name">
                                @error('last_name') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-white font-medium mb-3">Email Address *</label>
                            <input type="email"
                                   wire:model="email"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                   placeholder="Enter your email address">
                            @error('email') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-white font-medium mb-3">Phone Number *</label>
                            <input type="tel"
                                   wire:model="phone"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                   placeholder="Enter your phone number">
                            @error('phone') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-white font-medium mb-3">Address *</label>
                            <input type="text"
                                   wire:model="address"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                   placeholder="Enter your address">
                            @error('address') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-white font-medium mb-3">Country *</label>
                            <select wire:model.live="country"
                                    class="w-full px-4 py-3 bg-white border border-white/20 rounded-xl text-black focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                <option value="" class="text-gray-500">Select Country</option>
                                <option value="US" class="text-black">United States</option>
                                <option value="CA" class="text-black">Canada</option>
                                <option value="GB" class="text-black">United Kingdom</option>
                                <option value="DE" class="text-black">Germany</option>
                                <option value="FR" class="text-black">France</option>
                                <option value="AU" class="text-black">Australia</option>
                                <option value="JP" class="text-black">Japan</option>
                                <option value="KR" class="text-black">South Korea</option>
                                <option value="CN" class="text-black">China</option>
                                <option value="IN" class="text-black">India</option>
                                <option value="BR" class="text-black">Brazil</option>
                                <option value="MX" class="text-black">Mexico</option>
                                <option value="IT" class="text-black">Italy</option>
                                <option value="ES" class="text-black">Spain</option>
                                <option value="NL" class="text-black">Netherlands</option>
                                <option value="SE" class="text-black">Sweden</option>
                                <option value="NO" class="text-black">Norway</option>
                                <option value="DK" class="text-black">Denmark</option>
                                <option value="FI" class="text-black">Finland</option>
                                <option value="CH" class="text-black">Switzerland</option>
                                <option value="AT" class="text-black">Austria</option>
                                <option value="BE" class="text-black">Belgium</option>
                                <option value="NZ" class="text-black">New Zealand</option>
                                <option value="SG" class="text-black">Singapore</option>
                                <option value="HK" class="text-black">Hong Kong</option>
                                <option value="AE" class="text-black">United Arab Emirates</option>
                                <option value="SA" class="text-black">Saudi Arabia</option>
                                <option value="ZA" class="text-black">South Africa</option>
                                <option value="RU" class="text-black">Russia</option>
                                <option value="TR" class="text-black">Turkey</option>
                                <option value="PL" class="text-black">Poland</option>
                                <option value="CZ" class="text-black">Czech Republic</option>
                                <option value="HU" class="text-black">Hungary</option>
                                <option value="RO" class="text-black">Romania</option>
                                <option value="BG" class="text-black">Bulgaria</option>
                                <option value="HR" class="text-black">Croatia</option>
                                <option value="SI" class="text-black">Slovenia</option>
                                <option value="SK" class="text-black">Slovakia</option>
                                <option value="LT" class="text-black">Lithuania</option>
                                <option value="LV" class="text-black">Latvia</option>
                                <option value="EE" class="text-black">Estonia</option>
                                <option value="IE" class="text-black">Ireland</option>
                                <option value="PT" class="text-black">Portugal</option>
                                <option value="GR" class="text-black">Greece</option>
                                <option value="CY" class="text-black">Cyprus</option>
                                <option value="MT" class="text-black">Malta</option>
                                <option value="LU" class="text-black">Luxembourg</option>
                                <option value="IS" class="text-black">Iceland</option>
                                <option value="AR" class="text-black">Argentina</option>
                                <option value="CL" class="text-black">Chile</option>
                                <option value="CO" class="text-black">Colombia</option>
                                <option value="PE" class="text-black">Peru</option>
                                <option value="VE" class="text-black">Venezuela</option>
                                <option value="UY" class="text-black">Uruguay</option>
                                <option value="PY" class="text-black">Paraguay</option>
                                <option value="BO" class="text-black">Bolivia</option>
                                <option value="EC" class="text-black">Ecuador</option>
                                <option value="TH" class="text-black">Thailand</option>
                                <option value="VN" class="text-black">Vietnam</option>
                                <option value="MY" class="text-black">Malaysia</option>
                                <option value="ID" class="text-black">Indonesia</option>
                                <option value="PH" class="text-black">Philippines</option>
                                <option value="TW" class="text-black">Taiwan</option>
                                <option value="IL" class="text-black">Israel</option>
                                <option value="EG" class="text-black">Egypt</option>
                                <option value="MA" class="text-black">Morocco</option>
                                <option value="TN" class="text-black">Tunisia</option>
                                <option value="DZ" class="text-black">Algeria</option>
                                <option value="KE" class="text-black">Kenya</option>
                                <option value="NG" class="text-black">Nigeria</option>
                                <option value="GH" class="text-black">Ghana</option>
                                <option value="ET" class="text-black">Ethiopia</option>
                                <option value="UG" class="text-black">Uganda</option>
                                <option value="TZ" class="text-black">Tanzania</option>
                                <option value="ZW" class="text-black">Zimbabwe</option>
                                <option value="BW" class="text-black">Botswana</option>
                                <option value="NA" class="text-black">Namibia</option>
                                <option value="ZM" class="text-black">Zambia</option>
                                <option value="MW" class="text-black">Malawi</option>
                                <option value="MZ" class="text-black">Mozambique</option>
                            </select>
                            @error('country') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-white font-medium mb-3">State/Province *</label>
                                @if($country === 'US')
                                    <select wire:model.live="state"
                                            class="w-full px-4 py-3 bg-white border border-white/20 rounded-xl text-black focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                        <option value="" class="text-gray-500">Select State</option>
                                        <option value="AL" class="text-black">Alabama</option>
                                        <option value="AK" class="text-black">Alaska</option>
                                        <option value="AZ" class="text-black">Arizona</option>
                                        <option value="AR" class="text-black">Arkansas</option>
                                        <option value="CA" class="text-black">California</option>
                                        <option value="CO" class="text-black">Colorado</option>
                                        <option value="CT" class="text-black">Connecticut</option>
                                        <option value="DE" class="text-black">Delaware</option>
                                        <option value="FL" class="text-black">Florida</option>
                                        <option value="GA" class="text-black">Georgia</option>
                                        <option value="HI" class="text-black">Hawaii</option>
                                        <option value="ID" class="text-black">Idaho</option>
                                        <option value="IL" class="text-black">Illinois</option>
                                        <option value="IN" class="text-black">Indiana</option>
                                        <option value="IA" class="text-black">Iowa</option>
                                        <option value="KS" class="text-black">Kansas</option>
                                        <option value="KY" class="text-black">Kentucky</option>
                                        <option value="LA" class="text-black">Louisiana</option>
                                        <option value="ME" class="text-black">Maine</option>
                                        <option value="MD" class="text-black">Maryland</option>
                                        <option value="MA" class="text-black">Massachusetts</option>
                                        <option value="MI" class="text-black">Michigan</option>
                                        <option value="MN" class="text-black">Minnesota</option>
                                        <option value="MS" class="text-black">Mississippi</option>
                                        <option value="MO" class="text-black">Missouri</option>
                                        <option value="MT" class="text-black">Montana</option>
                                        <option value="NE" class="text-black">Nebraska</option>
                                        <option value="NV" class="text-black">Nevada</option>
                                        <option value="NH" class="text-black">New Hampshire</option>
                                        <option value="NJ" class="text-black">New Jersey</option>
                                        <option value="NM" class="text-black">New Mexico</option>
                                        <option value="NY" class="text-black">New York</option>
                                        <option value="NC" class="text-black">North Carolina</option>
                                        <option value="ND" class="text-black">North Dakota</option>
                                        <option value="OH" class="text-black">Ohio</option>
                                        <option value="OK" class="text-black">Oklahoma</option>
                                        <option value="OR" class="text-black">Oregon</option>
                                        <option value="PA" class="text-black">Pennsylvania</option>
                                        <option value="RI" class="text-black">Rhode Island</option>
                                        <option value="SC" class="text-black">South Carolina</option>
                                        <option value="SD" class="text-black">South Dakota</option>
                                        <option value="TN" class="text-black">Tennessee</option>
                                        <option value="TX" class="text-black">Texas</option>
                                        <option value="UT" class="text-black">Utah</option>
                                        <option value="VT" class="text-black">Vermont</option>
                                        <option value="VA" class="text-black">Virginia</option>
                                        <option value="WA" class="text-black">Washington</option>
                                        <option value="WV" class="text-black">West Virginia</option>
                                        <option value="WI" class="text-black">Wisconsin</option>
                                        <option value="WY" class="text-black">Wyoming</option>
                                    </select>
                                @elseif($country === 'CA')
                                    <select wire:model.live="state"
                                            class="w-full px-4 py-3 bg-white border border-white/20 rounded-xl text-black focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                        <option value="" class="text-gray-500">Select Province</option>
                                        <option value="AB" class="text-black">Alberta</option>
                                        <option value="BC" class="text-black">British Columbia</option>
                                        <option value="MB" class="text-black">Manitoba</option>
                                        <option value="NB" class="text-black">New Brunswick</option>
                                        <option value="NL" class="text-black">Newfoundland and Labrador</option>
                                        <option value="NS" class="text-black">Nova Scotia</option>
                                        <option value="ON" class="text-black">Ontario</option>
                                        <option value="PE" class="text-black">Prince Edward Island</option>
                                        <option value="QC" class="text-black">Quebec</option>
                                        <option value="SK" class="text-black">Saskatchewan</option>
                                        <option value="NT" class="text-black">Northwest Territories</option>
                                        <option value="NU" class="text-black">Nunavut</option>
                                        <option value="YT" class="text-black">Yukon</option>
                                    </select>
                                @elseif($country === 'GB')
                                    <select wire:model.live="state"
                                            class="w-full px-4 py-3 bg-white border border-white/20 rounded-xl text-black focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                        <option value="" class="text-gray-500">Select Region</option>
                                        <option value="England" class="text-black">England</option>
                                        <option value="Scotland" class="text-black">Scotland</option>
                                        <option value="Wales" class="text-black">Wales</option>
                                        <option value="Northern Ireland" class="text-black">Northern Ireland</option>
                                    </select>
                                @else
                                    <input type="text"
                                           wire:model="state"
                                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                           placeholder="Enter state/province/region">
                                @endif
                                @error('state') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-white font-medium mb-3">
                                    @if($country === 'US') ZIP Code *
                                    @elseif($country === 'CA') Postal Code *
                                    @elseif($country === 'GB') Postcode *
                                    @else Postal/ZIP Code *
                                    @endif
                                </label>
                                <input type="text"
                                       wire:model="postal_code"
                                       class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                       placeholder="{{ 
                                           $country === 'US' ? 'Enter ZIP code (e.g., 12345)' : (
                                           $country === 'CA' ? 'Enter postal code (e.g., K1A 0A6)' : (
                                           $country === 'GB' ? 'Enter postcode (e.g., SW1A 1AA)' : 
                                           'Enter postal/ZIP code'
                                           ))
                                       }}">
                                @error('postal_code') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-white font-medium mb-3">City *</label>
                            <input type="text"
                                   wire:model="city"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                   placeholder="Enter city name">
                            @error('city') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-between mt-8">
                            <button type="button"
                                    wire:click="previousStep"
                                    class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white font-medium rounded-xl border border-white/20 transition-all duration-200">
                                <x-custom-icon name="arrow-left" class="w-5 h-5 mr-2 inline" />
                                Back to Cart
                            </button>
                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    wire:target="nextStep"
                                    class="px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white font-bold text-lg rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105 flex items-center disabled:opacity-50">
                                <span wire:loading.remove wire:target="nextStep">Continue to Payment</span>
                                <span wire:loading wire:target="nextStep" class="flex items-center">
                                    <div class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent mr-2"></div>
                                    Processing...
                                </span>
                                <x-custom-icon name="arrow-right" class="w-5 h-5 ml-3" wire:loading.remove wire:target="nextStep" />
                            </button>
                        </div>
                    </form>
                </div>
                @endif

                <!-- Step 3: Enhanced Payment Method -->
                @if($currentStep === 3)
                <div class="bg-white/5 backdrop-blur-md rounded-3xl p-8 shadow-xl border border-white/10">
                    <h2 class="text-3xl font-bold text-white mb-8 flex items-center">
                        <x-custom-icon name="credit-card" class="w-8 h-8 mr-4 text-green-400" />
                        Payment Method
                    </h2>

                    <!-- Payment Options -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        @foreach(['crypto', 'stripe', 'wallet', 'mir'] as $method)
                        <button wire:click="$set('payment_method', '{{ $method }}')"
                                class="relative p-6 rounded-2xl border-2 transition-all duration-300 group
                                {{ $payment_method === $method 
                                    ? 'border-green-500 bg-green-500/20' 
                                    : 'border-white/20 bg-white/5 hover:border-green-400/50 hover:bg-green-400/10' }}">
                            
                            <!-- Payment Method Icon & Name -->
                            <div class="text-center">
                                @switch($method)
                                    @case('crypto')
                                        <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                                            <span class="text-white font-bold text-xl">₿</span>
                                        </div>
                                        <h3 class="text-white font-medium">Cryptocurrency</h3>
                                        <p class="text-gray-400 text-xs mt-1">Bitcoin, Monero, etc.</p>
                                        @break
                                    @case('stripe')
                                        <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                                            <x-custom-icon name="credit-card" class="w-6 h-6 text-white" />
                                        </div>
                                        <h3 class="text-white font-medium">Credit Card</h3>
                                        <p class="text-gray-400 text-xs mt-1">Visa, Mastercard, etc.</p>
                                        @break
                                    @case('wallet')
                                        <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                                            <x-custom-icon name="wallet" class="w-6 h-6 text-white" />
                                        </div>
                                        <h3 class="text-white font-medium">Wallet</h3>
                                        <p class="text-gray-400 text-xs mt-1">Internal balance</p>
                                        @break
                                    @case('mir')
                                        <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                                            <span class="text-white font-bold text-lg">₽</span>
                                        </div>
                                        <h3 class="text-white font-medium">MIR</h3>
                                        <p class="text-gray-400 text-xs mt-1">Russian Rubles</p>
                                        @break
                                @endswitch
                            </div>

                            <!-- Selected Indicator -->
                            @if($payment_method === $method)
                                <div class="absolute -top-2 -right-2 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                                    <x-custom-icon name="check" class="w-4 h-4 text-white" />
                                </div>
                            @endif
                        </button>
                        @endforeach
                    </div>

                    <!-- Payment Method Details -->
                    @if($payment_method)
                        <div class="bg-white/5 rounded-2xl p-6 mb-8 border border-white/10">
                            @switch($payment_method)
                                @case('crypto')
                                    <h4 class="text-xl font-bold text-orange-400 mb-4">Cryptocurrency Payment</h4>
                                    <p class="text-green-200 mb-4">Pay with your preferred cryptocurrency using NowPayments.</p>
                                    
                                    <!-- Crypto Currency Selection -->
                                    <div class="mb-4">
                                        <label class="block text-white font-medium mb-3">Select Cryptocurrency</label>
                                        <select wire:model="crypto_currency" 
                                                class="w-full px-4 py-3 bg-white border border-white/20 rounded-xl text-black focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                            <option value="" class="text-gray-500">Choose a cryptocurrency</option>
                                            <option value="btc" class="text-black">Bitcoin (BTC)</option>
                                            <option value="eth" class="text-black">Ethereum (ETH)</option>
                                            <option value="xmr" class="text-black">Monero (XMR)</option>
                                            <option value="ltc" class="text-black">Litecoin (LTC)</option>
                                            <option value="doge" class="text-black">Dogecoin (DOGE)</option>
                                            <option value="ada" class="text-black">Cardano (ADA)</option>
                                            <option value="dot" class="text-black">Polkadot (DOT)</option>
                                            <option value="sol" class="text-black">Solana (SOL)</option>
                                        </select>
                                        @error('crypto_currency') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <div class="flex items-center text-green-300 text-sm">
                                        <x-custom-icon name="shield-check" class="w-4 h-4 mr-2" />
                                        <span>Secure and anonymous cryptocurrency payment via NowPayments</span>
                                    </div>
                                    @break
                                    
                                @case('stripe')
                                    <h4 class="text-xl font-bold text-purple-400 mb-4">Credit Card Payment</h4>
                                    <p class="text-green-200 mb-4">Pay with your credit or debit card through Stripe. Supports worldwide payments.</p>
                                    <div class="grid grid-cols-4 gap-2 mb-4">
                                        <div class="bg-white/10 rounded-lg p-2 text-center">
                                            <span class="text-xs text-gray-300">VISA</span>
                                        </div>
                                        <div class="bg-white/10 rounded-lg p-2 text-center">
                                            <span class="text-xs text-gray-300">Mastercard</span>
                                        </div>
                                        <div class="bg-white/10 rounded-lg p-2 text-center">
                                            <span class="text-xs text-gray-300">AMEX</span>
                                        </div>
                                        <div class="bg-white/10 rounded-lg p-2 text-center">
                                            <span class="text-xs text-gray-300">Discover</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center text-green-300 text-sm">
                                        <x-custom-icon name="shield-check" class="w-4 h-4 mr-2" />
                                        <span>PCI DSS compliant secure payment processing worldwide</span>
                                    </div>
                                    @break
                                    
                                @case('wallet')
                                    <h4 class="text-xl font-bold text-green-400 mb-4">Wallet Payment</h4>
                                    <p class="text-green-200 mb-4">Pay using your internal wallet balance.</p>
                                    
                                    @if(auth()->guard('customer')->check())
                                        <div class="bg-green-600/20 border border-green-500/30 rounded-xl p-4 mb-4">
                                            <div class="flex items-center justify-between">
                                                <span class="text-green-200">Current Balance:</span>
                                                <span class="text-green-400 font-bold text-lg">
                                                    ${{ number_format(auth()->guard('customer')->user()->wallet_balance ?? 0, 2) }}
                                                </span>
                                            </div>
                                            @if((auth()->guard('customer')->user()->wallet_balance ?? 0) < ($order_summary['total'] ?? 0))
                                                <div class="mt-2 text-red-400 text-sm">
                                                    Insufficient balance. Please top up your wallet first.
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    <div class="flex items-center text-green-300 text-sm">
                                        <x-custom-icon name="shield-check" class="w-4 h-4 mr-2" />
                                        <span>Instant payment from your wallet balance</span>
                                    </div>
                                    @break
                                    
                                @case('mir')
                                    <h4 class="text-xl font-bold text-blue-400 mb-4">MIR Payment</h4>
                                    <p class="text-green-200 mb-4">Pay in Russian Rubles using the MIR payment system.</p>
                                    
                                    <div class="bg-blue-600/20 border border-blue-500/30 rounded-xl p-4 mb-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center mr-3">
                                                <span class="text-white font-bold text-sm">₽</span>
                                            </div>
                                            <div>
                                                <div class="text-blue-200 font-medium">Russian Ruble Payment</div>
                                                <div class="text-blue-300 text-sm">Converted from USD at current rates</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center text-green-300 text-sm">
                                        <x-custom-icon name="shield-check" class="w-4 h-4 mr-2" />
                                        <span>Secure MIR payment system for Russian customers</span>
                                    </div>
                                    @break
                            @endswitch
                        </div>
                    @endif

                    <!-- Terms and Conditions Agreement -->
                    <div class="bg-white/5 rounded-2xl p-6 mb-8 border border-white/10">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" 
                                   wire:model="agree_to_terms" 
                                   id="agree_to_terms"
                                   class="w-5 h-5 text-green-600 bg-white/10 border-white/20 rounded focus:ring-green-500 focus:ring-2">
                            <label for="agree_to_terms" class="text-white font-medium cursor-pointer">
                                I agree to the 
                                <a href="/terms" target="_blank" class="text-blue-400 hover:text-blue-300 underline">Terms and Conditions</a> 
                                and 
                                <a href="/privacy" target="_blank" class="text-blue-400 hover:text-blue-300 underline">Privacy Policy</a>
                            </label>
                        </div>
                        @error('agree_to_terms') 
                            <span class="text-red-400 text-sm mt-2 block">{{ $message }}</span> 
                        @enderror
                    </div>

                    <!-- Rate Limit Helper -->
                    @if($error_message && str_contains($error_message, 'Too many order attempts'))
                    <div class="bg-yellow-600/20 border border-yellow-500/30 rounded-xl p-4 mb-6">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-yellow-600 rounded-full flex items-center justify-center mr-3">
                                <x-custom-icon name="clock" class="w-4 h-4 text-white" />
                            </div>
                            <div>
                                <h4 class="text-yellow-200 font-medium">Rate Limit Reached</h4>
                                <p class="text-yellow-300 text-sm mt-1">You've made too many order attempts. Please wait 30 minutes before trying again for security purposes.</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="flex justify-between mt-8">
                        <button wire:click="previousStep"
                                class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white font-medium rounded-xl border border-white/20 transition-all duration-200">
                            <x-custom-icon name="arrow-left" class="w-5 h-5 mr-2 inline" />
                            Back to Billing
                        </button>
                        <button wire:click="processOrder"
                                wire:loading.attr="disabled"
                                {{ !$payment_method || !$agree_to_terms ? 'disabled' : '' }}
                                class="px-8 py-4 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-green-400 text-white font-bold text-lg rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105 flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                            <x-custom-icon name="lock-closed" class="w-5 h-5 mr-3" wire:loading.remove wire:target="processOrder" />
                            <div class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent mr-3" wire:loading wire:target="processOrder"></div>
                            <span wire:loading.remove wire:target="processOrder">Complete Order</span>
                            <span wire:loading wire:target="processOrder">Processing...</span>
                        </button>
                    </div>
                </div>
                @endif

                <!-- Step 4: Order Confirmation -->
                @if($currentStep === 4)
                <div class="bg-white/5 backdrop-blur-md rounded-3xl p-8 shadow-xl border border-white/10 text-center">
                    <div class="mb-8">
                        <div class="w-24 h-24 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-6">
                            <x-custom-icon name="check" class="w-12 h-12 text-white" />
                        </div>
                        <h2 class="text-4xl font-bold text-white mb-4">Order Confirmed!</h2>
                        <p class="text-xl text-green-200">Thank you for your purchase. Your order has been processed successfully.</p>
                    </div>

                    @if(isset($orderDetails))
                    <div class="bg-white/5 rounded-2xl p-6 mb-8 text-left">
                        <h3 class="text-xl font-bold text-white mb-4">Order Details</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-green-200">Order Number:</span>
                                <span class="text-white font-medium">#{{ $orderDetails['order_number'] ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-green-200">Total Amount:</span>
                                <span class="text-white font-medium">${{ number_format($orderDetails['total'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-green-200">Payment Method:</span>
                                <span class="text-white font-medium capitalize">{{ $payment_method ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="space-y-4">
                        <button wire:click="goToOrders"
                                class="w-full px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white font-bold text-lg rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                            View My Orders
                        </button>
                        <button wire:click="continueShopping"
                                class="w-full px-8 py-4 bg-white/10 hover:bg-white/20 text-white font-medium rounded-xl border border-white/20 transition-all duration-200">
                            Continue Shopping
                        </button>
                    </div>
                </div>
                @endif
            </div>

            <!-- Enhanced Order Summary Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white/5 backdrop-blur-md rounded-3xl p-8 shadow-xl border border-white/10 sticky top-8">
                    <h3 class="text-2xl font-bold text-white mb-6 flex items-center">
                        <x-custom-icon name="document-text" class="w-6 h-6 mr-3 text-yellow-400" />
                        Order Summary
                    </h3>

                    <!-- Cart Items -->
                    <div class="space-y-4 mb-6">
                        @foreach($cart_items as $item)
                        <div class="flex items-center space-x-3 p-4 bg-white/5 rounded-xl" wire:key="summary-{{ $item['server_plan_id'] }}">
                            <img src="{{ url('storage/' . $item['product_image']) }}"
                                 alt="{{ $item['name'] }}"
                                 class="w-12 h-12 object-cover rounded-lg">
                            <div class="flex-1 min-w-0">
                                <h4 class="text-white font-medium text-sm truncate">{{ $item['name'] }}</h4>
                                <p class="text-green-200 text-xs">Qty: {{ $item['quantity'] }}</p>
                            </div>
                            <div class="text-yellow-400 font-bold text-sm">
                                ${{ number_format($item['total_amount'], 2) }}
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Price Breakdown -->
                    <div class="border-t border-white/20 pt-6 space-y-3">
                        <div class="flex justify-between text-green-200">
                            <span>Subtotal:</span>
                            <span>${{ number_format($order_summary['subtotal'] ?? 0, 2) }}</span>
                        </div>
                        
                        @if($applied_coupon)
                        <div class="flex justify-between text-green-400">
                            <span>Discount ({{ $applied_coupon }}):</span>
                            <span>-${{ number_format($discount_amount ?? 0, 2) }}</span>
                        </div>
                        @endif

                        <div class="flex justify-between text-green-200">
                            <span>Tax:</span>
                            <span>${{ number_format($order_summary['tax'] ?? 0, 2) }}</span>
                        </div>

                        <div class="border-t border-white/20 pt-3">
                            <div class="flex justify-between text-white font-bold text-xl">
                                <span>Total:</span>
                                <span class="text-yellow-400">${{ number_format($order_summary['total'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Security Badges -->
                    <div class="mt-8 pt-6 border-t border-white/20">
                        <div class="text-center">
                            <h4 class="text-white font-medium mb-4">Secure Checkout</h4>
                            <div class="flex justify-center space-x-4">
                                <div class="flex items-center text-green-300 text-xs">
                                    <x-custom-icon name="shield-check" class="w-4 h-4 mr-1" />
                                    <span>SSL Encrypted</span>
                                </div>
                                <div class="flex items-center text-green-300 text-xs">
                                    <x-custom-icon name="lock-closed" class="w-4 h-4 mr-1" />
                                    <span>PCI Compliant</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>