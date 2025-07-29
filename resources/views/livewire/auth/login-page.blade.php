@extends('layouts.app')

@section('content')
<div class="w-full bg-gradient-to-r from-green-900 to-green-600 min-h-screen py-12 px-6 sm:px-8 lg:px-10 flex items-center justify-center">
    <div class="container mx-auto px-4 max-w-7xl">
        <main class="w-full max-w-md mx-auto">
            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl shadow-2xl">
                <div class="p-4 sm:p-7">
                    {{-- Header --}}
                    <div class="text-center mb-8">
                        <div class="mb-4">
                            <svg class="w-16 h-16 mx-auto text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <h1 class="block text-3xl font-bold text-white mb-2">Welcome Back</h1>
                        <p class="text-white/80">
                            Sign in to access your proxy services
                        </p>
                        <div class="mt-4">
                            <p class="text-sm text-white/70">
                                Don't have an account yet?
                                <a wire:navigate
                                    class="text-yellow-400 hover:text-yellow-300 font-medium transition duration-200"
                                    href="/register">
                                    Sign up here
                                </a>
                            </p>
                        </div>
                    </div>

                    {{-- Flash Messages --}}
                    @if (session()->has('error'))
                    <div class="mb-6 bg-red-500/20 border border-red-500 text-red-100 rounded-lg p-4" role="alert">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ session('error') }}
                        </div>
                    </div>
                    @endif

                    @if (session()->has('success'))
                    <div class="mb-6 bg-green-500/20 border border-green-500 text-green-100 rounded-lg p-4" role="alert">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ session('success') }}
                        </div>
                    </div>
                    @endif

                    {{-- Enhanced Login Form --}}
                    <form wire:submit.prevent='save' class="space-y-6">
                        @csrf

                        {{-- Email Field --}}
                        <div>
                            <label for="email" class="block text-sm font-medium text-white mb-2">
                                Email Address
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                    </svg>
                                </div>
                                <input type="email"
                                       id="email"
                                       wire:model="email"
                                       placeholder="Enter your email address"
                                       class="w-full pl-10 pr-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:border-green-400 focus:bg-white/20 transition duration-200">
                                @error('email')
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                @enderror
                            </div>
                            @error('email')
                            <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Password Field --}}
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label for="password" class="block text-sm font-medium text-white">
                                    Password
                                </label>
                                <a wire:navigate
                                   class="text-sm text-yellow-400 hover:text-yellow-300 transition duration-200"
                                   href="/forgot">
                                    Forgot password?
                                </a>
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <input type="password"
                                       id="password"
                                       wire:model="password"
                                       placeholder="Enter your password"
                                       class="w-full pl-10 pr-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:border-green-400 focus:bg-white/20 transition duration-200">
                                @error('password')
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                @enderror
                            </div>
                            @error('password')
                            <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Remember Me --}}
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input type="checkbox"
                                       id="remember"
                                       wire:model="remember"
                                       class="w-4 h-4 bg-white/10 border border-white/20 rounded focus:ring-green-400 focus:ring-2">
                                <label for="remember" class="ml-2 text-sm text-white/80">
                                    Remember me
                                </label>
                            </div>
                        </div>

                        {{-- Submit Button --}}
                        <button type="submit"
                                class="w-full py-3 px-4 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-200 flex items-center justify-center space-x-2">
                            <span wire:loading.remove wire:target="save">Sign In</span>
                            <span wire:loading wire:target="save" class="flex items-center space-x-2">
                                <svg class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span>Signing In...</span>
                            </span>
                        </button>
                    </form>

                    {{-- Social Login Section --}}
                    <div class="mt-8">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-white/20"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-transparent text-white/70">Or continue with</span>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-2 gap-3">
                            <button class="w-full inline-flex justify-center py-3 px-4 bg-white/10 border border-white/20 rounded-lg text-white hover:bg-white/20 transition duration-200">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                <span class="ml-2">Google</span>
                            </button>

                            <button class="w-full inline-flex justify-center py-3 px-4 bg-white/10 border border-white/20 rounded-lg text-white hover:bg-white/20 transition duration-200">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M13.397 20.997v-8.196h2.765l.411-3.209h-3.176V7.548c0-.926.258-1.56 1.587-1.56h1.684V3.127A22.336 22.336 0 0 0 14.201 3c-2.444 0-4.122 1.492-4.122 4.231v2.355H7.332v3.209h2.753v8.202h3.312z"/>
                                </svg>
                                <span class="ml-2">Facebook</span>
                            </button>
                        </div>
                    </div>

                    {{-- Additional Links --}}
                    <div class="mt-8 text-center">
                        <div class="text-sm text-white/70">
                            Need help?
                            <a href="/support" wire:navigate class="text-yellow-400 hover:text-yellow-300 transition duration-200">
                                Contact Support
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Additional Features --}}
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                    <svg class="w-8 h-8 mx-auto text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <h3 class="text-white font-semibold text-sm">Secure Login</h3>
                    <p class="text-white/70 text-xs mt-1">Your data is protected with enterprise-grade security</p>
                </div>

                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                    <svg class="w-8 h-8 mx-auto text-yellow-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <h3 class="text-white font-semibold text-sm">Fast Access</h3>
                    <p class="text-white/70 text-xs mt-1">Instant access to your proxy services and dashboard</p>
                </div>

                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                    <svg class="w-8 h-8 mx-auto text-blue-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <h3 class="text-white font-semibold text-sm">24/7 Support</h3>
                    <p class="text-white/70 text-xs mt-1">Round-the-clock assistance when you need it</p>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
