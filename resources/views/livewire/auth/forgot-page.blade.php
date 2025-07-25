<div
    class="w-full bg-gradient-to-r font-mono from-green-900 to-green-600 py-12 px-6 sm:px-8 lg:px-10 mx-auto max-w-[auto] flex justify-center">
    <div class="container mx-auto px-4 max-w-7xl">
        <main class="w-full max-w-md mx-auto p-6">
            <div
                class="mt-7 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="p-4 sm:p-7">
                    <div class="text-center">
                        <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">Forgot password?</h1>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Remember your password?
                            <a wire:navigate
                                class="text-accent-yellow decoration-2 hover:underline font-medium dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                                href="/login">
                                Sign in here
                            </a>
                        </p>
                    </div>

                    <div class="mt-5">
                        <!-- Flash Messages -->

                        @if (session()->has('success'))
                        <div class="bg-green-400 mt-2 border mb-4 border-green-600 text-sm text-green-900 rounded-lg p-4 dark:bg-green-400 dark:border-green-600 dark:white">
                            {{ session('success') }}
                        </div>
                        @endif
                        @if (session()->has('error'))
                        <div class="mt-2 bg-red-400 border mb-4 border-red-600 text-sm text-red-800 rounded-lg p-4 dark:bg-red-800/10 dark:border-red-900 dark:text-red-500">
                            {{ session('error') }}
                        </div>
                        @endif

                        <!-- Form -->
                        <form wire:submit.prevent='save'>
                            @csrf
                            <div class="grid gap-y-4">
                                <!-- Form Group -->
                                <div>
                                    <label for="email" class="block text-sm mb-2 dark:text-white">Email address</label>
                                    <div class="relative">
                                        <input type="email" id="email" wire:model="email"
                                            class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-accent-yellow focus:ring-accent-yellow disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600"
                                            aria-describedby="email-error">
                                        @error('email')
                                        <div
                                            class="py-3 absolute inset-y-0 end-0 items-center pointer-events-none pe-3">
                                            <svg class="h-5 w-5 text-red-500" width="16" height="16" fill="currentColor"
                                                viewBox="0 0 16 16" aria-hidden="true">
                                                <path
                                                    d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" />
                                            </svg>
                                        </div>
                                        @enderror
                                    </div>
                                    @error('email')
                                    <p class="text-xs text-red-600 mt-2" id="email-error">{{$message}}</p>
                                    @enderror
                                </div>
                                <!-- End Form Group -->
                                <button type="submit"
                                    class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-green-400 text-white hover:bg-yellow-600 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
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
