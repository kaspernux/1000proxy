<footer class="relative bg-gradient-to-br from-gray-900 via-blue-900/20 to-gray-900 border-t border-blue-500/30 backdrop-blur-xl mt-auto overflow-hidden">
    <!-- Enhanced background effects -->
    <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 to-yellow-500/5"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-400/10 to-transparent rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-96 h-96 bg-gradient-to-tr from-yellow-400/10 to-transparent rounded-full blur-3xl"></div>
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.1) 1px, transparent 0); background-size: 30px 30px;"></div>
    </div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-16">
            <!-- Enhanced Company Info -->
            <div class="col-span-1 md:col-span-2 space-y-8">
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-yellow-500 rounded-xl flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold text-xl">1K</span>
                        </div>
                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full animate-pulse"></div>
                    </div>
                    <span class="text-white font-bold text-2xl bg-gradient-to-r from-blue-400 to-yellow-400 bg-clip-text text-transparent">
                        1000 Proxy
                    </span>
                </div>
                
                <p class="text-gray-300 leading-relaxed text-lg max-w-md">
                    Professional-grade proxy solutions for developers, businesses, and individuals
                    who demand the best performance and reliability. Fast, secure, and always available.
                </p>
                
                <!-- Enhanced Newsletter Signup -->
                <div class="space-y-4">
                    <h4 class="text-white font-semibold text-lg flex items-center">
                        <svg class="w-5 h-5 mr-2 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Stay Updated
                    </h4>
                    <form class="flex flex-col sm:flex-row gap-3">
                        @csrf
                        <div class="flex-1">
                            <input type="email" id="newsletter-email" name="newsletter-email"
                                class="w-full px-4 py-3 bg-gradient-to-r from-gray-800/80 to-gray-900/80 backdrop-blur-sm border border-blue-500/30 rounded-xl text-white placeholder-gray-400 focus:border-blue-400/50 focus:ring-2 focus:ring-blue-400/20 focus:outline-none transition-all duration-300 hover:border-blue-400/40"
                                placeholder="Enter your email address">
                        </div>
                        <button type="submit" 
                            class="group relative bg-gradient-to-r from-blue-600 to-yellow-600 hover:from-blue-700 hover:to-yellow-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <span class="relative z-10">Subscribe</span>
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-yellow-600 rounded-xl blur opacity-30 group-hover:opacity-50 transition-opacity duration-300"></div>
                        </button>
                    </form>
                </div>

                <!-- Enhanced Social Links -->
                <div class="space-y-4">
                    <h4 class="text-white font-semibold text-lg flex items-center">
                        <svg class="w-5 h-5 mr-2 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Follow Us
                    </h4>
                    <div class="flex space-x-4">
                        <a href="#" class="group relative bg-gradient-to-r from-gray-800/80 to-gray-900/80 backdrop-blur-sm border border-blue-500/30 hover:border-blue-400/50 rounded-xl p-3 transition-all duration-300 hover:scale-110 transform">
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-400 transition-colors duration-300" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                            </svg>
                        </a>
                        <a href="#" class="group relative bg-gradient-to-r from-gray-800/80 to-gray-900/80 backdrop-blur-sm border border-blue-500/30 hover:border-yellow-400/50 rounded-xl p-3 transition-all duration-300 hover:scale-110 transform">
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-yellow-400 transition-colors duration-300" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                        <a href="#" class="group relative bg-gradient-to-r from-gray-800/80 to-gray-900/80 backdrop-blur-sm border border-blue-500/30 hover:border-green-400/50 rounded-xl p-3 transition-all duration-300 hover:scale-110 transform">
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-green-400 transition-colors duration-300" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.404-5.958 1.404-5.958s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.758-1.378l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.624 0 11.99-5.367 11.99-11.989C24.007 5.367 18.641.001 12.017.001z"/>
                            </svg>
                        </a>
                        <a href="#" class="group relative bg-gradient-to-r from-gray-800/80 to-gray-900/80 backdrop-blur-sm border border-blue-500/30 hover:border-purple-400/50 rounded-xl p-3 transition-all duration-300 hover:scale-110 transform">
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-purple-400 transition-colors duration-300" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.404-5.958 1.404-5.958s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.758-1.378l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.624 0 11.99-5.367 11.99-11.989C24.007 5.367 18.641.001 12.017.001z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Enhanced Services Section -->
            <div class="space-y-6">
                <h3 class="text-white font-semibold text-xl flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500/20 to-blue-600/20 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                        </svg>
                    </div>
                    Services
                </h3>
                <ul class="space-y-4">
                    <li>
                        <a href="/servers" class="group flex items-center text-gray-300 hover:text-blue-400 transition-all duration-300 py-2">
                            <svg class="w-4 h-4 mr-3 text-blue-400 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Proxy Services
                        </a>
                    </li>
                    <li>
                        <a href="/categories" class="group flex items-center text-gray-300 hover:text-yellow-400 transition-all duration-300 py-2">
                            <svg class="w-4 h-4 mr-3 text-yellow-400 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            Categories
                        </a>
                    </li>
                    <li>
                        <a href="/brands" class="group flex items-center text-gray-300 hover:text-green-400 transition-all duration-300 py-2">
                            <svg class="w-4 h-4 mr-3 text-green-400 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                            Brands
                        </a>
                    </li>
                    <li>
                        <a href="/cart" class="group flex items-center text-gray-300 hover:text-purple-400 transition-all duration-300 py-2">
                            <svg class="w-4 h-4 mr-3 text-purple-400 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5H21M7 13v6a2 2 0 002 2h6a2 2 0 002-2v-6m-8 0V9a2 2 0 012-2h4a2 2 0 012 2v4.01"/>
                            </svg>
                            Shopping Cart
                        </a>
                    </li>
                    @auth
                        <li>
                            <a href="/my-orders" class="group flex items-center text-gray-300 hover:text-cyan-400 transition-all duration-300 py-2">
                                <svg class="w-4 h-4 mr-3 text-cyan-400 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                My Orders
                            </a>
                        </li>
                    @endauth
                </ul>
            </div>

            <!-- Enhanced Support Section -->
            <div class="space-y-6">
                <h3 class="text-white font-semibold text-xl flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-br from-yellow-500/20 to-yellow-600/20 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    Support
                </h3>
                <ul class="space-y-4">
                    <li>
                        <a href="/docs" class="group flex items-center text-gray-300 hover:text-blue-400 transition-all duration-300 py-2">
                            <svg class="w-4 h-4 mr-3 text-blue-400 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Documentation
                        </a>
                    </li>
                    <li>
                        <a href="mailto:support@1000proxy.com" class="group flex items-center text-gray-300 hover:text-green-400 transition-all duration-300 py-2">
                            <svg class="w-4 h-4 mr-3 text-green-400 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Contact Support
                        </a>
                    </li>
                    <li>
                        <a href="/status" class="group flex items-center text-gray-300 hover:text-yellow-400 transition-all duration-300 py-2">
                            <svg class="w-4 h-4 mr-3 text-yellow-400 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            System Status
                        </a>
                    </li>
                    <li>
                        <a href="/help" class="group flex items-center text-gray-300 hover:text-purple-400 transition-all duration-300 py-2">
                            <svg class="w-4 h-4 mr-3 text-purple-400 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Help Center
                        </a>
                    </li>
                    <li>
                        <a href="/api" class="group flex items-center text-gray-300 hover:text-cyan-400 transition-all duration-300 py-2">
                            <svg class="w-4 h-4 mr-3 text-cyan-400 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                            API Documentation
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Enhanced Bottom Section -->
        <div class="border-t border-blue-500/20 pt-12 mt-16">
            <div class="flex flex-col lg:flex-row justify-between items-center space-y-6 lg:space-y-0">
                <!-- Enhanced Copyright -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-500/20 to-yellow-500/20 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <p class="text-gray-300 text-sm">
                            &copy; {{ date('Y') }} <span class="font-semibold bg-gradient-to-r from-blue-400 to-yellow-400 bg-clip-text text-transparent">1000Proxy</span>. All rights reserved.
                        </p>
                    </div>
                    <div class="hidden sm:block w-px h-6 bg-gray-600/50"></div>
                    <p class="hidden sm:block text-gray-400 text-sm">
                        Delivering premium proxy solutions worldwide.
                    </p>
                </div>
                
                <!-- Enhanced Legal Links -->
                <div class="flex flex-wrap gap-6 lg:gap-8">
                    <a href="/privacy" class="group flex items-center text-gray-400 hover:text-blue-400 text-sm transition-all duration-300">
                        <svg class="w-3 h-3 mr-2 text-blue-400 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Privacy Policy
                    </a>
                    <a href="/terms" class="group flex items-center text-gray-400 hover:text-yellow-400 text-sm transition-all duration-300">
                        <svg class="w-3 h-3 mr-2 text-yellow-400 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Terms of Service
                    </a>
                    <a href="/security" class="group flex items-center text-gray-400 hover:text-green-400 text-sm transition-all duration-300">
                        <svg class="w-3 h-3 mr-2 text-green-400 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Security
                    </a>
                    <a href="/refund" class="group flex items-center text-gray-400 hover:text-purple-400 text-sm transition-all duration-300">
                        <svg class="w-3 h-3 mr-2 text-purple-400 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refund Policy
                    </a>
                </div>
            </div>
            
            <!-- Enhanced Trust Indicators -->
            <div class="mt-8 pt-8 border-t border-gray-700/30">
                <div class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-8">
                    <div class="flex items-center space-x-2 text-gray-400 text-sm">
                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                        <span>99.9% Uptime Guaranteed</span>
                    </div>
                    <div class="flex items-center space-x-2 text-gray-400 text-sm">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span>SSL Secured</span>
                    </div>
                    <div class="flex items-center space-x-2 text-gray-400 text-sm">
                        <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span>Lightning Fast</span>
                    </div>
                    <div class="flex items-center space-x-2 text-gray-400 text-sm">
                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                        </svg>
                        <span>24/7 Support</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
