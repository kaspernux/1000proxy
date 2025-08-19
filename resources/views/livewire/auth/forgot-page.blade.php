{{-- Modern Forgot Password Page matching homepage design --}}
<style>
    .mobile-input { min-height: 44px; font-size: 1rem; padding: 0.75rem; }
    .mobile-btn { min-height: 44px; font-size: 1rem; padding: 0.75rem 1rem; }
</style>
<div class="relative bg-gradient-to-br from-gray-900 via-blue-900/30 to-gray-800 overflow-hidden min-h-screen flex items-center">
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
        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500">
                <div class="p-8">
            <div class="alert-error" aria-live="assertive" style="display:none"></div>
                    <!-- Header -->
                    <div class="text-center mb-8">
                        <div class="mb-6">
                            <div class="w-20 h-20 mx-auto bg-gradient-to-br from-blue-500/30 to-purple-600/30 backdrop-blur-md rounded-2xl flex items-center justify-center shadow-2xl border border-blue-400/30">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                        </div>
                        <h1 class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400 mb-3">
                            Forgot Password?
                        </h1>
                        <p class="text-gray-300 text-lg mb-4">
                            No worries! Enter your email and we'll send you reset instructions.
                        </p>
                        <p class="text-sm text-gray-400">
                            Remember your password?
                            <a wire:navigate href="/login" class="text-blue-400 hover:text-blue-300 font-medium transition duration-300 hover:underline">
                                Sign in here
                            </a>
                        </p>
                    </div>
                    <!-- Flash Messages -->
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

                    <!-- Form -->
                    <form wire:submit="save" class="space-y-6">
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-semibold text-white mb-3">Email Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 mr-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                    </svg>
                                </div>
                                <input type="email" id="email" wire:model="email"
                                    class="mobile-input w-full pl-12 pr-4 py-4 bg-gray-800/50 border border-gray-600 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 backdrop-blur-sm"
                                    placeholder="Enter your email address"
                                    aria-describedby="email-error">
                                @error('email')
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                @enderror
                            </div>
                            @error('email')
                            <p class="text-red-400 text-sm mt-2 flex items-center" id="email-error">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{$message}}
                            </p>
                            @enderror
                        </div>

                        <!-- Submit -->
            <button type="submit"
                class="mobile-btn w-full py-4 px-6 bg-gradient-to-r from-blue-600 to-yellow-600 hover:from-blue-700 hover:to-yellow-700 text-white font-semibold rounded-xl transition-all duration-300 flex items-center justify-center space-x-3 shadow-xl hover:shadow-2xl hover:scale-105 transform focus:outline-none focus:ring-4 focus:ring-blue-500/50">
                            <span wire:loading.remove wire:target="save" class="text-lg">Send Reset Link</span>
                            <span wire:loading wire:target="save" class="flex items-center space-x-3">
                                <svg class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span class="text-lg">Sending...</span>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
