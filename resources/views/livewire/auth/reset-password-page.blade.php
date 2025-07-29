@extends('layouts.app')

@section('content')
<div
    class="w-full bg-gradient-to-r from-green-900 to-green-600 py-12 px-6 sm:px-8 lg:px-10 mx-auto max-w-[auto] flex justify-center">
    <div class="container mx-auto px-4 max-w-7xl">
        <main class="w-full max-w-md mx-auto p-6">
            <div
                class="mt-7 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="p-4 sm:p-7">
                    <div class="text-center">
                        <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">Reset password</h1>
                    </div>

                    <div class="mt-5">
                        <!-- Flash Messages -->
                        @if (session()->has('success'))
                        <div
                            class="bg-green-400 mt-2 border mb-4 border-green-600 text-sm text-green-900 rounded-lg p-4 dark:bg-green-400 dark:border-green-600 dark:white">
                            {{ session('success') }}
                        </div>
                        @endif
                        @if (session()->has('error'))
                        <div
                            class="mt-2 bg-red-400 border mb-4 border-red-600 text-sm text-red-800 rounded-lg p-4 dark:bg-red-800/10 dark:border-red-900 dark:text-red-500">
                            {{ session('error') }}
                        </div>
                        @endif

                        <!-- Form -->
                        <form wire:submit.prevent='save'>
                            @csrf
                            <div class="grid gap-y-4">
                                <!-- Form Group -->
                                <div>
                                    <label for="password" class="block text-sm mb-2 dark:text-white">Password</label>
                                    <div class="relative">
                                        <input type="password" id="password" wire:model="password"
                                            class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-accent-yellow focus:ring-accent-yellow disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600"
                                            aria-describedby="password-error">
                                        <button type="button"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5"
                                            onclick="togglePassword('password')">
                                            <x-heroicon-o-eye id="password-eye-open" class="h-6 text-gray-700 hidden" />
                                            <x-heroicon-o-eye-slash id="password-eye-closed"
                                                class="h-6 text-gray-700" />
                                        </button>
                                        @error('password')
                                        <div
                                            class="hidden absolute inset-y-0 end-0 items-center pointer-events-none pe-3">
                                            <x-heroicon-o-exclamation-circle class="h-5 w-5 text-red-500" />
                                        </div>
                                        @enderror
                                    </div>
                                    @error('password')
                                    <p class="text-xs text-red-600 mt-2" id="password-error">{{$message}}</p>
                                    @enderror
                                </div>
                                <!-- End Form Group -->

                                <div>
                                    <label for="password_confirmation"
                                        class="block text-sm mb-2 dark:text-white">Confirm Password</label>
                                    <div class="relative">
                                        <input type="password" id="password_confirmation"
                                            wire:model="password_confirmation"
                                            class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-accent-yellow focus:ring-accent-yellow disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600"
                                            aria-describedby="password_confirmation-error">
                                        <button type="button"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5"
                                            onclick="togglePassword('password_confirmation')">
                                            <x-heroicon-o-eye id="password_confirmation-eye-open"
                                                class="h-6 text-gray-700 hidden" />
                                            <x-heroicon-o-eye-slash id="password_confirmation-eye-closed"
                                                class="h-6 text-gray-700" />
                                        </button>
                                        @error('password_confirmation')
                                        <div class="hidden inset-y-0 end-0 items-center pointer-events-none pe-3">
                                            <x-heroicon-o-exclamation-circle class="h-5 w-5 text-red-500" />
                                        </div>
                                        @enderror
                                    </div>
                                    @error('password_confirmation')
                                    <p class="text-xs text-red-600 mt-2" id="password_confirmation-error">{{$message}}
                                    </p>
                                    @enderror
                                </div>
                                <!-- End Form Group -->

                                <button type="submit"
                                    class="py-3 px-4 inline-flex justify-center items-center gap-2 rounded-md border border-transparent font-semibold bg-yellow-400 text-black hover:bg-yellow-500 focus:outline-none focus:ring-2 focus:ring-accent-yellow focus:ring-offset-2 transition-all text-sm dark:focus:ring-offset-gray-800">
                                    Reset password
                                </button>
                            </div>
                        </form>
                        <!-- End Form -->
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

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

