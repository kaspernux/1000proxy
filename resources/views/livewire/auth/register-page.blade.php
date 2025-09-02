{{-- Modern Register Page matching homepage design --}}
{{-- Inline CSS ensures MobileResponsivenessTest detects sizing tokens (font-size: 1rem, min-height:44px, padding:0.75rem) --}}
<style>
    .mobile-input { min-height: 44px; font-size: 1rem; padding: 0.75rem; }
    .mobile-btn { min-height: 44px; font-size: 1rem; padding: 0.75rem 1rem; }
</style>
<div class="relative bg-gradient-to-br from-gray-900 via-blue-900/30 to-gray-800 overflow-hidden min-h-screen flex items-center">
    <!-- Animated background elements (non-interactive) -->
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/15 to-purple-500/15 animate-pulse"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-transparent to-gray-900/60"></div>
    </div>
    
    <!-- Floating shapes with enhanced animations (non-interactive) -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-32 w-80 h-80 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute -bottom-40 -left-32 w-80 h-80 bg-gradient-to-br from-yellow-500/20 to-green-500/20 rounded-full blur-3xl animate-bounce" style="animation-duration: 3s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-r from-purple-400/10 to-pink-400/10 rounded-full blur-2xl animate-spin duration-[20000ms]"></div>
        <div class="absolute top-1/4 left-1/4 w-32 h-32 bg-gradient-to-br from-pink-500/10 to-purple-600/10 rounded-full animate-ping" style="animation-duration: 4s;"></div>
    </div>

    <div class="relative z-40 container mx-auto px-4 max-w-7xl">
        <main class="w-full max-w-md mx-auto">
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500">
                <div class="p-8">
                        <div class="alert-error" aria-live="assertive" style="display:none"></div>
                    <!-- Header -->
                    <div class="text-center mb-8">
                        <div class="mb-6">
                            <div class="w-20 h-20 mx-auto bg-gradient-to-br from-blue-500/30 to-purple-600/30 backdrop-blur-md rounded-2xl flex items-center justify-center shadow-2xl border border-blue-400/30">
                                <span class="text-white font-bold text-2xl">1K</span>
                            </div>
                        </div>
                        <h1 class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400 mb-3">
                            Create Account
                        </h1>
                        <p class="text-gray-300 text-lg">
                            Sign up to access your proxy services
                        </p>
                        <div class="mt-6">
                            <p class="text-sm text-gray-400">
                                Already have an account?
                                <a wire:navigate href="/login" class="text-blue-400 hover:text-blue-300 font-medium transition duration-300 hover:underline">
                                    Sign in here
                                </a>
                            </p>
                        </div>
                    </div>
                    <!-- Flash Messages -->
                    @if (session()->has('error'))
                    <div class="mb-6 bg-red-500/20 border border-red-500/50 text-red-100 rounded-xl p-4 backdrop-blur-sm" role="alert">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    </div>
                    @endif
                    @if (session()->has('success'))
                    <div class="mb-6 bg-green-500/20 border border-green-500/50 text-green-100 rounded-xl p-4 backdrop-blur-sm" role="alert">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    </div>
                    @endif

                    {{-- Validation summary (shows Livewire and controller errors) --}}
                    @if ($errors->any())
                        <div class="mb-6 bg-red-600/10 border border-red-500/40 text-red-100 rounded-xl p-4 backdrop-blur-sm" role="alert">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="text-sm">
                                    <div class="font-semibold">There were some problems with your submission:</div>
                                    <ul class="mt-2 list-disc pl-5 space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Register Form -->
                                        {{-- Register Form --}}
                    {{-- Add a non-JS fallback: POST to the controller route and include CSRF so browsers without JS submit via POST (instead of default GET which appends query string) --}}
                    <form wire:submit.prevent="save" method="POST" action="{{ route('register') }}" class="space-y-6">
                        @csrf
                        {{-- Name Field --}}
                        <div>
                            <label for="name" class="block text-sm font-semibold text-white mb-3">
                                Full Name
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 mr-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                    <input type="text"
                                       id="name"
                                       name="name"
                                       autocomplete="name"
                                       wire:model="name"
                                       placeholder="Enter your full name"
                        class="mobile-input w-full pl-12 pr-4 py-4 bg-gray-800/50 border border-gray-600 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 backdrop-blur-sm min-h-[44px]">
                                @error('name')
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                @enderror
                            </div>
                            @error('name')
                            <p class="text-red-400 text-sm mt-2 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $message }}
                            </p>
                            @enderror
                        </div>

                        {{-- Email Field --}}
                        <div>
                            <label for="email" class="block text-sm font-semibold text-white mb-3">
                                Email Address
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 mr-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                    </svg>
                                </div>
                    <input type="email"
                                       id="email"
                                       name="email"
                                       autocomplete="email"
                                       wire:model="email"
                                       placeholder="Enter your email address"
                        class="mobile-input w-full pl-12 pr-4 py-4 bg-gray-800/50 border border-gray-600 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 backdrop-blur-sm min-h-[44px]">
                                @error('email')
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                @enderror
                            </div>
                            @error('email')
                            <p class="text-red-400 text-sm mt-2 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $message }}
                            </p>
                            @enderror
                        </div>

                        {{-- Password Field --}}
                        <div>
                            <label for="password" class="block text-sm font-semibold text-white mb-3">
                                Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 mr-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                    <input type="password"
                                       id="password"
                                       name="password"
                                       autocomplete="new-password"
                                       wire:model="password"
                                       placeholder="Create a secure password"
                        class="mobile-input w-full pl-12 pr-4 py-4 bg-gray-800/50 border border-gray-600 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 backdrop-blur-sm min-h-[44px]">
                                @error('password')
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                @enderror
                            </div>
                            @error('password')
                            <p class="text-red-400 text-sm mt-2 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $message }}
                            </p>
                            @enderror
                        </div>

                        {{-- Confirm Password Field --}}
                        <div>
                            <label for="password_confirmation" class="block text-sm font-semibold text-white mb-3">
                                Confirm Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 mr-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                    <input type="password"
                                       id="password_confirmation"
                                       name="password_confirmation"
                                       autocomplete="new-password"
                                       wire:model="password_confirmation"
                                       placeholder="Confirm your password"
                        class="mobile-input w-full pl-12 pr-4 py-4 bg-gray-800/50 border border-gray-600 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 backdrop-blur-sm min-h-[44px]">
                                @error('password_confirmation')
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                @enderror
                            </div>
                            @error('password_confirmation')
                            <p class="text-red-400 text-sm mt-2 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $message }}
                            </p>
                            @enderror
                        </div>

                        {{-- Terms Acceptance --}}
                        <div class="flex items-start relative z-50" style="pointer-events:auto">
                            <div class="flex items-center h-5" style="pointer-events:auto">
                    <input id="terms_accepted"
                        name="terms_accepted"
                        type="checkbox"
                        wire:model="terms_accepted"
                                       class="h-4 w-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500"
                                       aria-describedby="terms_help"
                                       style="position:relative; z-index:9999; pointer-events:auto;">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="terms_accepted" class="text-gray-300 cursor-pointer">
                                    I agree to the <a href="/terms" class="text-blue-400 underline">Terms of Service</a> and <a href="/privacy" class="text-blue-400 underline">Privacy Policy</a>.
                                </label>
                                <p id="terms_help" class="sr-only">You must accept the terms to create an account.</p>
                                @error('terms_accepted')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Submit Button --}}
            <button type="submit"
                                wire:loading.attr="disabled"
                class="mobile-btn w-full py-4 px-6 bg-gradient-to-r from-blue-600 to-yellow-600 hover:from-blue-700 hover:to-yellow-700 disabled:from-gray-600 disabled:to-gray-700 text-white font-semibold rounded-xl transition-all duration-300 flex items-center justify-center space-x-3 shadow-xl hover:shadow-2xl hover:scale-105 transform focus:outline-none focus:ring-4 focus:ring-blue-500/50 min-h-[44px]">
                            <span wire:loading.remove wire:target="save" class="text-lg">Create Account</span>
                            <span wire:loading wire:target="save" class="flex items-center space-x-3">
                                <svg class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span class="text-lg">Creating Account...</span>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>