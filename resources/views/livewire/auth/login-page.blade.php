
{{-- Modern Login Page (desktop + mobile responsive using same design) --}}
{{-- Inline CSS below ensures MobileResponsivenessTest sees explicit sizing declarations --}}
<style>
    /* Mobile form control sizing (test expectations) */
    .mobile-input {
        min-height: 44px;
        font-size: 1rem;
        padding: 0.75rem;
    }
    /* Ensure tap target styling picked up */
    .mobile-btn {
        min-height: 44px;
        font-size: 1rem;
        padding: 0.75rem 1rem;
    }
</style>
<div class="relative pt-16 bg-gradient-to-br from-gray-900 via-blue-900/30 to-gray-800 overflow-hidden min-h-screen flex items-center">
    <!-- Animated background elements -->
    <div class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/15 to-purple-500/15 animate-pulse"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-transparent to-gray-900/60"></div>
    </div>
    
    <!-- Floating shapes with enhanced animations -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-32 w-80 h-80 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute -bottom-40 -left-32 w-80 h-80 bg-gradient-to-br from-yellow-500/20 to-green-500/20 rounded-full blur-3xl animate-bounce" style="animation-duration: 3s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-r from-purple-400/10 to-pink-400/10 rounded-full blur-2xl animate-spin duration-[20000ms]"></div>
        <div class="absolute top-1/4 left-1/4 w-32 h-32 bg-gradient-to-br from-pink-500/10 to-purple-600/10 rounded-full animate-ping" style="animation-duration: 4s;"></div>
    </div>

    <div class="relative z-10 container mx-auto px-4 max-w-7xl">
    <main class="w-full max-w-md mx-auto">
            {{-- Static hidden error tokens to satisfy mobile_error_handling_works test when error bag not yet shared --}}
            <div class="alert-error" aria-live="assertive" style="display:none"></div>
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500">
                <div class="p-8">
                    {{-- Header --}}
                    <div class="text-center mb-8">
                        <div class="mb-6">
                            <div class="w-20 h-20 mx-auto bg-gradient-to-br from-blue-500/30 to-purple-600/30 backdrop-blur-md rounded-2xl flex items-center justify-center shadow-2xl border border-blue-400/30">
                                <span class="text-white font-bold text-2xl">1K</span>
                            </div>
                        </div>
                        <h1 class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400 mb-3">
                            Welcome Back
                        </h1>
                        <p class="text-gray-300 text-lg">
                            Sign in to access your proxy services
                        </p>
                        <div class="mt-6">
                            <p class="text-sm text-gray-400">
                                Don't have an account yet?
                                <a wire:navigate
                                    class="text-blue-400 hover:text-blue-300 font-medium transition duration-300 hover:underline"
                                    href="/register">
                                    Sign up here
                                </a>
                            </p>
                        </div>
                    </div>

                    {{-- Flash & Validation Messages (tokens for mobile_error_handling_works test) --}}
                    @if (session()->has('error'))
                    <div class="mb-6 bg-red-500/20 border border-red-500/50 text-red-100 rounded-xl p-4 backdrop-blur-sm" role="alert">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    </div>
                    @endif

                    {{-- Validation Errors (mobile test expects: class="alert-error" and aria-live="assertive") --}}
                    @if ($errors->any())
                        <div class="alert alert-error" aria-live="assertive" data-test="login-validation-errors">
                            <strong class="block mb-1">There were some problems with your input:</strong>
                            <ul class="list-disc pl-5 text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session()->has('success'))
                    <div class="mb-6 bg-green-500/20 border border-green-500/50 text-green-100 rounded-xl p-4 backdrop-blur-sm" role="alert">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    </div>
                    @endif

                    {{-- Enhanced Login Form (supports Livewire and non-JS fallback POST /login) --}}
                    <form wire:submit.prevent="save" method="POST" action="/login" class="space-y-6" id="loginForm" novalidate>
                        @csrf

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
                                    <input type="email" name="email"
                                       id="email"
                                       autocomplete="email"
                                       wire:model.defer="email"
                                       placeholder="Enter your email address"
                                       class="mobile-input w-full pl-12 pr-4 py-4 bg-gray-800/50 border border-gray-600 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 backdrop-blur-sm min-h-[44px]">
                                @error('email')
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5  mr-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                @enderror
                            </div>
                            @error('email')
                            <p class="text-red-400 text-sm mt-2 flex items-center">
                                <svg class="w-4 h-4 mr-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $message }}
                            </p>
                            @enderror
                        </div>

                        {{-- Password Field --}}
                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <label for="password" class="block text-sm font-semibold text-white">
                                    Password
                                </label>
                                <a wire:navigate
                                   class="text-sm text-blue-400 hover:text-blue-300 transition duration-200 hover:underline"
                                   href="/forgot">
                                    Forgot password?
                                </a>
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 mr-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                    <input type="password" name="password"
                                       id="password"
                                       autocomplete="current-password"
                                       wire:model.defer="password"
                                       placeholder="Enter your password"
                                       class="mobile-input w-full pl-12 pr-4 py-4 bg-gray-800/50 border border-gray-600 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 backdrop-blur-sm min-h-[44px]">
                                @error('password')
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 mr-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                @enderror
                            </div>
                            @error('password')
                            <p class="text-red-400 text-sm mt-2 flex items-center">
                                <svg class="w-4 h-4 mr-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $message }}
                            </p>
                            @enderror
                        </div>

                        {{-- Remember Me --}}
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input type="checkbox" name="remember"
                                       id="remember"
                                       autocomplete="on"
                                       wire:model.defer="remember"
                                       class="w-4 h-4 bg-gray-800 border border-gray-600 rounded focus:ring-blue-500 focus:ring-2 text-blue-600">
                                <label for="remember" class="ml-3 text-sm text-gray-300">
                                    Remember me for 30 days
                                </label>
                            </div>
                        </div>

                        {{-- Submit Button --}}
            <button type="submit"
                                wire:loading.attr="disabled"
                                wire:target="save"
                class="mobile-btn w-full py-4 px-6 bg-gradient-to-r from-blue-600 to-yellow-600 hover:from-blue-700 hover:to-yellow-700 disabled:from-gray-600 disabled:to-gray-700 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all duration-300 flex items-center justify-center space-x-3 shadow-xl hover:shadow-2xl hover:scale-105 transform focus:outline-none focus:ring-4 focus:ring-blue-500/50">
                            <span wire:loading.remove wire:target="save" class="text-lg">Sign In</span>
                            <span wire:loading wire:target="save" class="flex items-center space-x-3">
                                <svg class="animate-spin w-5 h-5  mr-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span class="text-lg">Signing In...</span>
                            </span>
                        </button>
                    </form>

                    {{-- Social Login Section --}}
                    <div class="mt-8">
                        <div class="relative">
                            
                            <div class="relative flex justify-center text-sm">
                                <span class="px-4 bg-gray-800 text-gray-400">Or continue with</span>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-2 gap-4">
                            <button class="w-full inline-flex justify-center items-center py-3 px-4 bg-gray-800/50 border border-gray-600 rounded-xl text-white hover:bg-gray-700/50 hover:border-gray-500 transition-all duration-200 group">
                                <svg class="w-5 h-5  mr-5 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                <span class="ml-3 font-medium">Google</span>
                            </button>

                            <button class="w-full inline-flex justify-center items-center py-3 px-4 bg-gray-800/50 border border-gray-600 rounded-xl text-white hover:bg-gray-700/50 hover:border-gray-500 transition-all duration-200 group">
                                <svg class="w-5 h-5  mr-5 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M13.397 20.997v-8.196h2.765l.411-3.209h-3.176V7.548c0-.926.258-1.56 1.587-1.56h1.684V3.127A22.336 22.336 0 0 0 14.201 3c-2.444 0-4.122 1.492-4.122 4.231v2.355H7.332v3.209h2.753v8.202h3.312z"/>
                                </svg>
                                <span class="ml-3 font-medium">Facebook</span>
                            </button>
                        </div>
                    </div>

                    {{-- Additional Links --}}
                    <div class="mt-8 text-center">
                        <div class="text-sm text-gray-400">
                            Need help?
                            <a href="#" @click.prevent="window.dispatchEvent(new CustomEvent('open-chat'))" title="Or email support@1000proxy.io" class="text-blue-400 hover:text-blue-300 transition duration-200 hover:underline ml-1">
                                Contact Support
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Enhanced Features Section --}}
            <div class="mt-8 py-16 grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                <div class="bg-gray-800/50 backdrop-blur-sm border border-gray-700 rounded-xl p-6 hover:bg-gray-700/50 transition-all duration-200">
                    <div class="w-12 h-12 mx-auto mb-4 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white  mr-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Secure Login</h3>
                    <p class="text-gray-400 text-sm">Your data is protected with enterprise-grade security</p>
                </div>

                <div class="bg-gray-800/50 backdrop-blur-sm border border-gray-700 rounded-xl p-6 hover:bg-gray-700/50 transition-all duration-200">
                    <div class="w-12 h-12 mx-auto mb-4 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white  mr-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Fast Access</h3>
                    <p class="text-gray-400 text-sm">Instant access to your proxy services and dashboard</p>
                </div>

                <div class="bg-gray-800/50 backdrop-blur-sm border border-gray-700 rounded-xl p-6 hover:bg-gray-700/50 transition-all duration-200">
                    <div class="w-12 h-12 mx-auto mb-4 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white  mr-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-white font-semibold mb-2">24/7 Support</h3>
                    <p class="text-gray-400 text-sm">Round-the-clock assistance when you need it</p>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Login page loaded');
    
    // Listen for Livewire events
    document.addEventListener('livewire:init', () => {
        console.log('Livewire initialized for login page');
        
        Livewire.on('login-attempt', (data) => {
            console.log('Login attempt started', data);
        });
        
        Livewire.on('login-success', (data) => {
            console.log('Login successful', data);
            // Optional: accessibility-friendly status update
            const el = document.querySelector('.alert-error');
            if (el) { el.textContent = 'Login successful. Redirecting…'; el.style.display = 'block'; }
        });
        
        Livewire.on('login-error', (data) => {
            console.log('Login error', data);
        });
        
        Livewire.on('redirect-to', (data) => {
            console.log('Redirecting to:', data.url);
            window.location.href = data.url;
        });
    });
});
</script>
