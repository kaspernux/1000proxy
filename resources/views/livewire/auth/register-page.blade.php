@extends('layouts.app')

@section('content')
<section class="w-full bg-gradient-to-r from-green-900 to-green-600 min-h-screen py-12 px-6 sm:px-8 lg:px-10 flex items-center justify-center">
    <div class="container mx-auto px-4 max-w-7xl">
        <main class="w-full max-w-md mx-auto">
            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl shadow-2xl">
                <div class="p-4 sm:p-7">
                    <!-- Header -->
                    <div class="text-center mb-8">
                        <div class="mb-4">
                            <svg class="w-16 h-16 mx-auto text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <h1 class="block text-3xl font-bold text-white mb-2">Create Account</h1>
                        <p class="text-white/80">
                            Sign up to access your proxy services
                        </p>
                        <div class="mt-4">
                            <p class="text-sm text-white/70">
                                Already have an account?
                                <a wire:navigate href="/login" class="text-yellow-400 hover:text-yellow-300 font-medium transition duration-200">Sign in here</a>
                            </p>
                        </div>
                    </div>
                    <!-- Flash Messages (optional) -->
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
                    <!-- Register Form -->
                    <form wire:submit.prevent='save' class="space-y-6">
                        @csrf
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-white mb-2">Name</label>
                            <div class="relative">
                                <input type="text" id="name" wire:model="name"
                                    class="py-3 px-4 block w-full bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:border-green-400 focus:bg-white/20 transition duration-200"
                                    placeholder="Enter your name"
                                    aria-describedby="name-error">
                                @error('name')
                                <div class="absolute inset-y-0 end-0 flex items-center pr-3 pointer-events-none">
                                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                @enderror
                            </div>
                            @error('name')
                            <p class="text-red-400 text-sm mt-2" id="name-error">{{$message}}</p>
                            @enderror
                        </div>
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-white mb-2">Email Address</label>
                            <div class="relative">
                                <input type="email" id="email" wire:model="email"
                                    class="py-3 px-4 block w-full bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:border-green-400 focus:bg-white/20 transition duration-200"
                                    placeholder="Enter your email address"
                                    aria-describedby="email-error">
                                @error('email')
                                <div class="absolute inset-y-0 end-0 flex items-center pr-3 pointer-events-none">
                                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                @enderror
                            </div>
                            @error('email')
                            <p class="text-red-400 text-sm mt-2" id="email-error">{{$message}}</p>
                            @enderror
                        </div>
                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-white mb-2">Password</label>
                            <div class="relative">
                                <input type="password" id="password" wire:model="password"
                                    class="py-3 px-4 block w-full bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:border-green-400 focus:bg-white/20 transition duration-200"
                                    placeholder="Enter your password"
                                    aria-describedby="password-error">
                                @error('password')
                                <div class="absolute inset-y-0 end-0 flex items-center pr-3 pointer-events-none">
                                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                @enderror
                            </div>
                            @error('password')
                            <p class="text-red-400 text-sm mt-2" id="password-error">{{$message}}</p>
                            @enderror
                        </div>
                        <!-- Submit -->
                        <button type="submit"
                                class="w-full py-3 px-4 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-200 flex items-center justify-center space-x-2">
                            <span wire:loading.remove wire:target="save">Sign Up</span>
                            <span wire:loading wire:target="save" class="flex items-center space-x-2">
                                <svg class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span>Signing Up...</span>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</section>
@endsection
