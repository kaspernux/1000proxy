<footer class="bg-gradient-to-r from-green-900 to-green-600 w-full">
    <div class="w-full max-w-[85rem] py-12 px-4 sm:px-6 lg:px-8 lg:pt-20 mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Company Info -->
            <div class="col-span-1 md:col-span-2">
                <div class="flex items-center mb-4">
                    <x-custom-icon name="server" class="h-8 w-8 text-emerald-500 mr-3" />
                    <span class="text-2xl font-bold bg-gradient-to-r from-emerald-400 to-blue-500 bg-clip-text text-transparent">
                        1000Proxy
                    </span>
                </div>
                <p class="text-green-200 mb-4">
                    Professional-grade proxy solutions for developers, businesses, and individuals
                    who demand the best performance and reliability.
                </p>
                <div class="flex space-x-4">
                    <a href="#" class="text-green-200 hover:text-yellow-600 transition-colors duration-200">
                        <x-custom-icon name="globe-alt" class="h-5 w-5" />
                    </a>
                    <a href="#" class="text-green-200 hover:text-yellow-600 transition-colors duration-200">
                        <x-custom-icon name="heart" class="h-5 w-5" />
                    </a>
                </div>
                <div class="col-span-2">
                    <form>
                        @csrf
                        <div
                            class="mt-4 flex flex-col font-mono items-center gap-2 sm:flex-row sm:gap-3 bg-white rounded-lg p-2 dark:bg-green-800">
                            <div class="w-full">
                                <input type="text" id="hero-input" name="hero-input"
                                    class="py-3 px-4 block w-full border-transparent rounded-lg text-sm focus:border-green-500 focus:ring-green-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-transparent dark:text-green-400 dark:focus:ring-green-600"
                                    placeholder="Enter your email">
                            </div>
                            <a wire:navigate class="w-full sm:w-auto whitespace-nowrap p-3 inline-flex justify-center items-center gap-x-2 text-sm font-bold font-mono rounded-lg border border-transparent bg-green-400 border-white hover:bg-yellow-600 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-green-600"
                                href="#">
                                Subscribe
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Shop Links -->
            <div>
                <h3 class="text-white font-semibold mb-4">Shop</h3>
                <ul class="space-y-2">
                    <li><a href="/servers" class="text-green-200 hover:text-yellow-600 transition-colors duration-200">Proxy Services</a></li>
                    <li><a href="/categories" class="text-green-200 hover:text-yellow-600 transition-colors duration-200">Categories</a></li>
                    <li><a href="/cart" class="text-green-200 hover:text-yellow-600 transition-colors duration-200">Cart</a></li>
                    <li><a href="/account-settings" class="text-green-200 hover:text-yellow-600 transition-colors duration-200">Account</a></li>
                </ul>
            </div>
            <!-- Support Links -->
            <div>
                <h3 class="text-white font-semibold mb-4">Support</h3>
                <ul class="space-y-2">
                    <li><a href="/docs" class="text-green-200 hover:text-yellow-600 transition-colors duration-200">Documentation</a></li>
                    <li><a href="mailto:support@1000proxy.com" class="text-green-200 hover:text-yellow-600 transition-colors duration-200">Contact Us</a></li>
                    <li><a href="/status" class="text-green-200 hover:text-yellow-600 transition-colors duration-200">System Status</a></li>
                    <li><a href="/help" class="text-green-200 hover:text-yellow-600 transition-colors duration-200">Help Center</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-green-700 pt-8 mt-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-green-200 text-sm">
                    &copy; {{ date('Y') }} 1000Proxy. All rights reserved.
                </p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="/privacy" class="text-green-200 hover:text-yellow-600 text-sm transition-colors duration-200">Privacy Policy</a>
                    <a href="/terms" class="text-green-200 hover:text-yellow-600 text-sm transition-colors duration-200">Terms of Service</a>
                    <a href="/security" class="text-green-200 hover:text-yellow-600 text-sm transition-colors duration-200">Security</a>
                    <a class="w-10 h-10 inline-flex justify-center items-center gap-x-2 text-sm font-bold font-mono rounded-lg border border-transparent text-white hover:bg-yellow-600 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-1 focus:ring-green-600"
                        href="#">
                        <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                            fill="currentColor" viewBox="0 0 16 16">
                            <path
                                d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z" />
                        </svg>
                    </a>
                    <a class="w-10 h-10 inline-flex justify-center items-center gap-x-2 text-sm font-bold font-mono rounded-lg border border-transparent text-white hover:bg-yellow-600 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-1 focus:ring-green-600"
                        href="#">
                        <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                            fill="currentColor" viewBox="0 0 16 16">
                            <path
                                d="M15.545 6.558a9.42 9.42 0 0 1 .139 1.626c0 2.434-.87 4.492-2.384 5.885h.002C11.978 15.292 10.158 16 8 16A8 8 0 1 1 8 0a7.689 7.689 0 0 1 5.352 2.082l-2.284 2.284A4.347 4.347 0 0 0 8 3.166c-2.087 0-3.86 1.408-4.492 3.304a4.792 4.792 0 0 0 0 3.063h.003c.635 1.893 2.405 3.301 4.492 3.301 1.078 0 2.004-.276 2.722-.764h-.003a3.702 3.702 0 0 0 1.599-2.431H8v-3.08h7.545z" />
                        </svg>
                    </a>
                    <a  class="w-10 h-10 inline-flex justify-center items-center gap-x-2 text-sm font-bold font-mono rounded-lg border border-transparent text-white hover:bg-yellow-600 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-1 focus:ring-green-600"
                        href="#">
                        <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                            fill="currentColor" viewBox="0 0 16 16">
                            <path
                                d="M5.026 15c6.038 0 9.341-5.003 9.341-9.334 0-.14 0-.282-.006-.422A6.685 6.685 0 0 0 16 3.542a6.658 6.658 0 0 1-1.889.518 3.301 3.301 0 0 0 1.447-1.817 6.533 6.533 0 0 1-2.087.793A3.286 3.286 0 0 0 7.875 6.03a9.325 9.325 0 0 1-6.767-3.429 3.289 3.289 0 0 0 1.018 4.382A3.323 3.323 0 0 1 .64 6.575v.045a3.288 3.288 0 0 0 2.632 3.218 3.203 3.203 0 0 1-.865.115 3.23 3.23 0 0 1-.614-.057 3.283 3.283 0 0 0 3.067 2.277A6.588 6.588 0 0 1 .78 13.58a6.32 6.32 0 0 1-.78-.045A9.344 9.344 0 0 0 5.026 15z" />
                        </svg>
                    </a>
                    <a class="w-10 h-10 inline-flex justify-center items-center gap-x-2 text-sm font-bold font-mono rounded-lg border border-transparent text-white hover:bg-yellow-600 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-1 focus:ring-green-600"
                        href="#">
                        <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                            fill="currentColor" viewBox="0 0 16 16">
                            <path
                                d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.012 8.012 0 0 0 16 8c0-4.42-3.58-8-8-8z" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>
