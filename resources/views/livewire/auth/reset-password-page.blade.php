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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h1 class="block text-3xl font-bold text-white mb-2">Reset Password</h1>
                        <p class="text-white/80">Set a new password for your account</p>
                    </div>
                    <!-- Flash Messages -->
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
                    <!-- Form -->
                    <form wire:submit.prevent='save' class="space-y-6">
                        @csrf
                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-white mb-2">Password</label>
                            <div class="relative">
                                <input type="password" id="password" wire:model="password"
                                    class="py-3 px-4 block w-full bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:border-green-400 focus:bg-white/20 transition duration-200"
                                    placeholder="Enter your new password"
                                    aria-describedby="password-error">
                                <button type="button"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5"
                                    onclick="togglePassword('password')">
                                    <x-heroicon-o-eye id="password-eye-open" class="h-6 text-white hidden" />
                                    <x-heroicon-o-eye-slash id="password-eye-closed" class="h-6 text-white" />
                                </button>
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
                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-white mb-2">Confirm Password</label>
                            <div class="relative">
                                <input type="password" id="password_confirmation" wire:model="password_confirmation"
                                    class="py-3 px-4 block w-full bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:border-green-400 focus:bg-white/20 transition duration-200"
                                    placeholder="Confirm your new password"
                                    aria-describedby="password_confirmation-error">
                                <button type="button"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5"
                                    onclick="togglePassword('password_confirmation')">
                                    <x-heroicon-o-eye id="password_confirmation-eye-open" class="h-6 text-white hidden" />
                                    <x-heroicon-o-eye-slash id="password_confirmation-eye-closed" class="h-6 text-white" />
                                </button>
                                @error('password_confirmation')
                                <div class="absolute inset-y-0 end-0 flex items-center pr-3 pointer-events-none">
                                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                @enderror
                            </div>
                            @error('password_confirmation')
                            <p class="text-red-400 text-sm mt-2" id="password_confirmation-error">{{$message}}</p>
                            @enderror
                        </div>
                        <!-- Submit -->
                        <button type="submit"
                                class="w-full py-3 px-4 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-200 flex items-center justify-center space-x-2">
                            <span wire:loading.remove wire:target="save">Reset Password</span>
                            <span wire:loading wire:target="save" class="flex items-center space-x-2">
                                <svg class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span>Resetting...</span>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</section>

<script>
    function togglePassword(field) {
        const passwordField = document.getElementById(field);
        const eyeOpen = document.getElementById(`${field}-eye-open`);
        const eyeClosed = document.getElementById(`${field}-eye-closed`);

        if (passwordField.type === "password") {
            passwordField.type = "text";
            eyeOpen.classList.remove("hidden");
            eyeClosed.classList.add("hidden");
        } else {
            passwordField.type = "password";
            eyeOpen.classList.add("hidden");
            eyeClosed.classList.remove("hidden");
        }
    }
</script>
@endsection

