@extends('layouts.app')

@section('content')
{{-- Standard Laravel Login Form --}}
<div class="relative bg-gradient-to-br from-gray-900 via-gray-800 to-blue-900 overflow-hidden min-h-screen flex items-center">
    <div class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/15 to-yellow-500/15 animate-pulse"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-transparent to-gray-900/60"></div>
    </div>
    
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-32 w-80 h-80 bg-gradient-to-br from-yellow-400/25 to-blue-400/25 rounded-full blur-3xl animate-bounce duration-[6000ms]"></div>
        <div class="absolute -bottom-40 -left-32 w-80 h-80 bg-gradient-to-br from-blue-400/20 to-yellow-400/15 rounded-full blur-3xl animate-pulse duration-[8000ms]"></div>
    </div>

    <div class="relative z-10 container mx-auto px-4 max-w-7xl">
        <main class="w-full max-w-md mx-auto">
            <div class="bg-gradient-to-br from-gray-800/80 to-gray-900/80 backdrop-blur-xl border border-blue-500/30 rounded-2xl shadow-2xl">
                <div class="p-8">
                    {{-- Header --}}
                    <div class="text-center mb-8">
                        <div class="mb-6">
                            <div class="w-20 h-20 mx-auto bg-gradient-to-br from-blue-500 to-yellow-500 rounded-2xl flex items-center justify-center shadow-2xl">
                                <span class="text-white font-bold text-2xl">1K</span>
                            </div>
                        </div>
                        <h1 class="text-4xl font-bold text-white mb-3 bg-gradient-to-r from-blue-400 to-yellow-400 bg-clip-text text-transparent">
                            Welcome Back
                        </h1>
                        <p class="text-gray-300 text-lg">
                            Sign in to access your proxy services
                        </p>
                    </div>

                    {{-- Standard Login Form --}}
                    <form method="POST" action="{{ route('login.submit') }}" class="space-y-6">
                        @csrf

                        {{-- Email Field --}}
                        <div>
                            <label for="email" class="block text-sm font-semibold text-white mb-3">
                                Email Address
                            </label>
                            <div class="relative">
                                <input type="email"
                                       id="email"
                                       name="email"
                                       value="{{ old('email') }}"
                                       autocomplete="email"
                                       placeholder="Enter your email address"
                                       class="w-full pl-4 pr-4 py-4 bg-gray-800/50 border @error('email') border-red-500 @else border-gray-600 @enderror rounded-xl text-white placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 backdrop-blur-sm">
                            </div>
                            @error('email')
                            <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Password Field --}}
                        <div>
                            <label for="password" class="block text-sm font-semibold text-white mb-3">
                                Password
                            </label>
                            <div class="relative">
                                <input type="password"
                                       id="password"
                                       name="password"
                                       autocomplete="current-password"
                                       placeholder="Enter your password"
                                       class="w-full pl-4 pr-4 py-4 bg-gray-800/50 border @error('password') border-red-500 @else border-gray-600 @enderror rounded-xl text-white placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 backdrop-blur-sm">
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
                                       name="remember"
                                       class="w-4 h-4 bg-gray-800 border border-gray-600 rounded focus:ring-blue-500 focus:ring-2 text-blue-600">
                                <label for="remember" class="ml-3 text-sm text-gray-300">
                                    Remember me for 30 days
                                </label>
                            </div>
                        </div>

                        {{-- Submit Button --}}
                        <button type="submit"
                                class="w-full py-4 px-6 bg-gradient-to-r from-blue-600 to-yellow-600 hover:from-blue-700 hover:to-yellow-700 text-white font-semibold rounded-xl transition-all duration-300 flex items-center justify-center space-x-3 shadow-xl hover:shadow-2xl hover:scale-105 transform focus:outline-none focus:ring-4 focus:ring-blue-500/50">
                            <span class="text-lg">Sign In (Standard)</span>
                        </button>
                    </form>

                    {{-- Test Link to Livewire Version --}}
                    <div class="mt-6 text-center">
                        <a href="/login" class="text-blue-400 hover:text-blue-300 text-sm">
                            Try Livewire Version
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
