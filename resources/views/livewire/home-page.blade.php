@extends('layouts.app')

@section('content')
<div>
    {{-- Hero Section Start  --}}
    <div class="w-full max-h-auto pt-3 py-auto font-mono bg-gradient-to-r from-green-900 to-green-600 px-4 sm:px-6 lg:px-8 mx-auto relative">
        <div class="max-w-[85rem] mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Grid -->
            <div class="grid md:grid-cols-2 gap-4 md:gap-8 xl:gap-20 md:items-center">
                <div>
                <h1 class="text-2xl sm:text-4xl lg:text-6xl leading-snug font-bold text-white">
                    Discover the Future of <span class="text-green-400">VPN & Proxy</span> Solutions
                </h1>

                <p class="mt-3 text-base sm:text-lg text-white">
                    Optimize your internet experience with cutting-edge VPN and Proxy configurations designed for complete anonymity and security.
                </p>

                {{-- Dynamic Platform Stats Display --}}
                @if($this->showStats && $this->platformStats)
                <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-400">{{ number_format($this->platformStats['total_users']) }}+</div>
                        <div class="text-sm text-white">Happy Customers</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-400">{{ number_format($this->platformStats['active_servers']) }}+</div>
                        <div class="text-sm text-white">Active Servers</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-400">{{ $this->platformStats['countries_count'] }}+</div>
                        <div class="text-sm text-white">Countries</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-400">{{ $this->platformStats['avg_rating'] }}</div>
                        <div class="text-sm text-white">Average Rating</div>
                    </div>
                </div>
                @endif

                    <!-- Buttons -->
                    <div class="mt-7 w-full flex flex-col sm:flex-row sm:gap-3 gap-2">
                        <a wire:navigate class="py-3 px-4 font-mono inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-green-500 text-white hover:bg-yellow-600 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="/register">
                            Get Started
                            <x-custom-icon name="arrow-right" class="flex-shrink-0 w-4 h-4" />
                        </a>
                        <a wire:navigate class="py-3 px-4 inline-flex font-mono justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-white bg-green-800 text-green-600 shadow-sm hover:bg-yellow-600 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-green-600 dark:text-white dark:hover:bg-yellow-600 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="/contact">
                            Contact Sales Team
                        </a>
                    </div>
                    <!-- End Buttons -->

                    <!-- Review -->
                    <div class="mt-6 lg:mt-10 grid grid-cols-2 gap-x-5">
                        <!-- Review -->
                        <div class="py-5">
                            <div class="flex space-x-1">
                                <svg class="h-4 w-4 text-yellow-600 dark:text-green-400" width="51" height="51"
                                    viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                        fill="currentColor" />
                                </svg>
                                <svg class="h-4 w-4 text-yellow-600 dark:text-green-400" width="51" height="51"
                                    viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                        fill="currentColor" />
                                </svg>
                                <svg class="h-4 w-4 text-yellow-600 dark:text-green-400" width="51" height="51"
                                    viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                        fill="currentColor" />
                                </svg>
                                <svg class="h-4 w-4 text-yellow-600 dark:text-green-400" width="51" height="51"
                                    viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                        fill="currentColor" />
                                </svg>
                                <svg class="h-4 w-4 text-yellow-600 dark:text-green-400" width="51" height="51"
                                    viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                        fill="currentColor" />
                                </svg>
                            </div>

                            <p class="mt-3 text-sm text-white dark:text-green-400">
                                <span class="font-bold font-mono">4.6</span> /5 - from 12k reviews
                            </p>

                            <div class="mt-5">
                                <!-- Star -->
                                <svg class="h-auto w-16 text-white dark:text-white" width="80" height="27"
                                    viewBox="0 0 80 27" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M20.558 9.74046H11.576V12.3752H17.9632C17.6438 16.0878 14.5301 17.7245 11.6159 17.7245C7.86341 17.7245 4.58995 14.7704 4.58995 10.6586C4.58995 6.62669 7.70373 3.51291 11.6159 3.51291C14.6498 3.51291 16.4063 5.42908 16.4063 5.42908L18.2426 3.51291C18.2426 3.51291 15.8474 0.878184 11.4961 0.878184C5.94724 0.838264 1.67578 5.50892 1.67578 10.5788C1.67578 15.5289 5.70772 20.3592 11.6558 20.3592C16.8854 20.3592 20.7177 16.8063 20.7177 11.4969C20.7177 10.3792 20.558 9.74046 20.558 9.74046Z"
                                        fill="currentColor" />
                                    <path
                                        d="M27.8621 7.78442C24.1894 7.78442 21.5547 10.6587 21.5547 14.012C21.5547 17.4451 24.1096 20.3593 27.9419 20.3593C31.415 20.3593 34.2094 17.7645 34.2094 14.0918C34.1695 9.94011 30.896 7.78442 27.8621 7.78442ZM27.902 10.2994C29.6984 10.2994 31.415 11.7764 31.415 14.0918C31.415 16.4072 29.7383 17.8842 27.902 17.8842C25.906 17.8842 24.3491 16.2874 24.3491 14.0519C24.3092 11.8962 25.8661 10.2994 27.902 10.2994Z"
                                        fill="currentColor" />
                                    <path
                                        d="M41.5964 7.78442C37.9238 7.78442 35.2891 10.6587 35.2891 14.012C35.2891 17.4451 37.844 20.3593 41.6763 20.3593C45.1493 20.3593 47.9438 17.7645 47.9438 14.0918C47.9038 9.94011 44.6304 7.78442 41.5964 7.78442ZM41.6364 10.2994C43.4328 10.2994 45.1493 11.7764 45.1493 14.0918C45.1493 16.4072 43.4727 17.8842 41.6364 17.8842C39.6404 17.8842 38.0835 16.2874 38.0835 14.0519C38.0436 11.8962 39.6004 10.2994 41.6364 10.2994Z"
                                        fill="currentColor" />
                                    <path
                                        d="M55.0475 7.82434C51.6543 7.82434 49.0195 10.7784 49.0195 14.0918C49.0195 17.8443 52.0934 20.3992 54.9676 20.3992C56.764 20.3992 57.6822 19.7205 58.4407 18.8822V20.1198C58.4407 22.2754 57.1233 23.5928 55.1273 23.5928C53.2111 23.5928 52.2531 22.1557 51.8938 21.3573L49.4587 22.3553C50.297 24.1517 52.0135 26.0279 55.0874 26.0279C58.4407 26.0279 60.9956 23.9122 60.9956 19.481V8.18362H58.3608V9.26147C57.6423 8.38322 56.5245 7.82434 55.0475 7.82434ZM55.287 10.2994C56.9237 10.2994 58.6403 11.7365 58.6403 14.1317C58.6403 16.6068 56.9636 17.9241 55.2471 17.9241C53.4507 17.9241 51.774 16.4471 51.774 14.1716C51.8139 11.6966 53.5305 10.2994 55.287 10.2994Z"
                                        fill="currentColor" />
                                    <path
                                        d="M72.8136 7.78442C69.62 7.78442 66.9453 10.2994 66.9453 14.0519C66.9453 18.004 69.9393 20.3593 73.093 20.3593C75.7278 20.3593 77.4044 18.8822 78.3625 17.6048L76.1669 16.1277C75.608 17.006 74.6499 17.8443 73.093 17.8443C71.3365 17.8443 70.5381 16.8862 70.0192 15.9281L78.4423 12.4152L78.0032 11.3772C77.1649 9.46107 75.2886 7.78442 72.8136 7.78442ZM72.8934 10.2196C74.0511 10.2196 74.8495 10.8184 75.2487 11.5768L69.6599 13.9321C69.3405 12.0958 71.097 10.2196 72.8934 10.2196Z"
                                        fill="currentColor" />
                                    <path d="M62.9531 19.9999H65.7076V1.47693H62.9531V19.9999Z" fill="currentColor" />
                                </svg>
                                <!-- End Star -->
                            </div>
                        </div>
                        <!-- End Review -->

                        <!-- Review -->
                        <div class="py-5">
                            <div class="flex space-x-1">
                                <svg class="h-4 w-4 text-yellow-600 dark:text-green-400" width="51" height="51"
                                    viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                        fill="currentColor" />
                                </svg>
                                <svg class="h-4 w-4 text-yellow-600 dark:text-green-400" width="51" height="51"
                                    viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                        fill="currentColor" />
                                </svg>
                                <svg class="h-4 w-4 text-yellow-600 dark:text-green-400" width="51" height="51"
                                    viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                        fill="currentColor" />
                                </svg>
                                <svg class="h-4 w-4 text-yellow-600 dark:text-green-400" width="51" height="51"
                                    viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                        fill="currentColor" />
                                </svg>
                                <svg class="h-4 w-4 text-yellow-600 dark:text-green-400" width="51" height="51"
                                    viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M49.6867 20.0305C50.2889 19.3946 49.9878 18.1228 49.0846 18.1228L33.7306 15.8972C33.4296 15.8972 33.1285 15.8972 32.8275 15.2613L25.9032 0.317944C25.6021 0 25.3011 0 25 0V42.6046C25 42.6046 25.3011 42.6046 25.6021 42.6046L39.7518 49.9173C40.3539 50.2352 41.5581 49.5994 41.2571 48.6455L38.5476 32.4303C38.5476 32.1124 38.5476 31.7944 38.8486 31.4765L49.6867 20.0305Z"
                                        fill="transparent" />
                                    <path
                                        d="M0.313299 20.0305C-0.288914 19.3946 0.0122427 18.1228 0.915411 18.1228L16.2694 15.8972C16.5704 15.8972 16.8715 15.8972 17.1725 15.2613L24.0968 0.317944C24.3979 0 24.6989 0 25 0V42.6046C25 42.6046 24.6989 42.6046 24.3979 42.6046L10.2482 49.9173C9.64609 50.2352 8.44187 49.5994 8.74292 48.6455L11.4524 32.4303C11.4524 32.1124 11.4524 31.7944 11.1514 31.4765L0.313299 20.0305Z"
                                        fill="currentColor" />
                                </svg>
                            </div>

                            <p class="mt-3 text-sm text-white dark:text-green-400">
                                <span class="font-bold font-mono">4.8</span> /5 - from 5k reviews
                            </p>

                            <div class="mt-5">
                                <!-- Star -->
                                <svg class="h-auto w-16 text-white dark:text-white" width="110" height="28"
                                    viewBox="0 0 110 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M66.6601 8.35107C64.8995 8.35107 63.5167 8.72875 62.1331 9.48265C62.1331 5.4582 62.1331 1.81143 62.2594 0.554199L53.8321 2.06273V2.81736L54.7124 2.94301C55.8433 3.19431 56.2224 3.82257 56.4715 5.33255C56.725 8.35107 56.5979 24.4496 56.4715 27.0912C58.7354 27.5945 61.1257 27.9722 63.5159 27.9722C70.1819 27.9722 74.2064 23.8213 74.2064 17.281C74.2064 12.1249 70.9366 8.35107 66.6601 8.35107ZM63.7672 26.5878C63.2639 26.5878 62.6342 26.5878 62.258 26.4629C62.1316 24.7023 62.0067 17.281 62.1316 10.7413C62.8862 10.4893 63.3888 10.3637 64.0185 10.3637C66.7872 10.3637 68.2965 13.6335 68.2965 17.6572C68.2957 22.6898 66.4088 26.5878 63.7672 26.5878ZM22.1363 1.0568H0V2.18838L1.25796 2.31403C2.89214 2.56533 3.52184 3.57127 3.77242 5.9608C4.15082 10.4886 4.02445 18.6646 3.77242 22.5619C3.52112 24.9522 2.89287 26.0845 1.25796 26.2087L0 26.4615V27.4674H14.2123V26.4615L12.703 26.2087C11.0681 26.0838 10.4392 24.9522 10.1879 22.5619C10.0615 20.9263 9.93583 18.2847 9.93583 15.0156L12.9543 15.1413C14.8413 15.1413 15.7208 16.6505 16.0985 18.7881H17.2308V9.86106H16.0985C15.7201 11.9993 14.8413 13.5078 12.9543 13.5078L9.93655 13.6342C9.93655 9.35773 10.0622 5.33328 10.1886 2.94374H14.59C17.9869 2.94374 19.7475 5.08125 21.0047 8.85513L22.2626 8.47745L22.1363 1.0568Z"
                                        fill="currentColor" />
                                    <path
                                        d="M29.3053 8.09998C35.5944 8.09998 38.7385 12.3764 38.7385 18.0358C38.7385 23.4439 35.2167 27.9731 28.9276 27.9731C22.6393 27.9731 19.4951 23.6959 19.4951 18.0358C19.4951 12.6277 23.0162 8.09998 29.3053 8.09998ZM28.9276 9.35793C26.1604 9.35793 25.4058 13.1311 25.4058 18.0358C25.4058 22.8149 26.6637 26.7137 29.1796 26.7137C32.0703 26.7137 32.8264 22.9405 32.8264 18.0358C32.8264 13.2567 31.5699 9.35793 28.9276 9.35793ZM75.8403 18.1622C75.8403 13.0054 79.1101 8.09998 85.5248 8.09998C90.8057 8.09998 93.3224 11.9995 93.3224 17.1555H81.6253C81.4989 21.8089 83.7628 25.2051 88.2913 25.2051C90.3038 25.2051 91.3098 24.7033 92.5685 23.8223L93.0703 24.4505C91.8124 26.2111 89.0459 27.9731 85.5248 27.9731C79.8647 27.9724 75.8403 23.9479 75.8403 18.1622ZM81.6253 15.7726L87.5366 15.6463C87.5366 13.1311 87.159 9.35793 85.0214 9.35793C82.8839 9.35793 81.7502 12.8791 81.6253 15.7726ZM108.291 9.10663C106.782 8.47693 104.77 8.09998 102.506 8.09998C97.8538 8.09998 94.9594 10.8665 94.9594 14.137C94.9594 17.4075 97.0955 18.7904 100.118 19.7971C103.261 20.9279 104.142 21.8089 104.142 23.3182C104.142 24.8275 103.01 26.2103 100.997 26.2103C98.6084 26.2103 96.8464 24.8275 95.4635 21.0536L94.5825 21.3063L94.7089 26.84C96.2181 27.4683 98.9846 27.9724 101.375 27.9724C106.28 27.9724 109.425 25.4557 109.425 21.5576C109.425 18.9161 108.041 17.4075 104.771 16.1489C101.249 14.766 99.992 13.8857 99.992 12.2501C99.992 10.6152 101.126 9.48286 102.635 9.48286C104.897 9.48286 106.407 10.8665 107.54 14.2627L108.42 14.0114L108.291 9.10663ZM55.0883 8.6033C52.9508 7.3468 49.1769 7.97433 47.1651 12.5028L47.29 8.1007L38.8642 9.73561V10.4902L39.7444 10.6159C40.8775 10.7423 41.3794 11.3705 41.5057 13.0062C41.757 16.0247 41.6314 21.3078 41.5057 23.9486C41.3794 25.4564 40.8775 26.2111 39.7444 26.3374L38.8642 26.4638V27.4697H50.5606V26.4638L49.0513 26.3374C47.7941 26.2111 47.4164 25.4564 47.29 23.9486C47.0387 21.5584 47.0387 16.7793 47.1651 13.7608C47.7933 12.8798 50.5606 12.1259 53.0757 13.7608L55.0883 8.6033Z"
                                        fill="currentColor" />
                                </svg>
                                <!-- End Star -->
                            </div>
                        </div>
                        <!-- End Review -->
                    </div>
                    <!-- End Review -->
                </div>
                <!-- End Col -->

                <div>
                    <img class="relative py-auto size-auto justify-center items-center mx-auto w-full max-w-full sm:max-w-full md:max-w-full"
                        src="{{ asset('storage/uploads/hero.png') }}" alt="#">
                </div>
                <!-- End Col -->
            </div>
            <!-- End Grid -->
        </div>
    </div>
    {{-- Hero Section End  --}}

    {{-- Enhanced Search & Filtering Section Start --}}
    <div class="w-full bg-gradient-to-r from-green-800 to-green-700 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                <h2 class="text-2xl font-bold text-white text-center mb-6">Find Your Perfect Proxy Solution</h2>

                {{-- Search Bar --}}
                <div class="mb-6">
                    <div class="relative">
                        <input type="text"
                               wire:model.live.debounce.300ms="searchTerm"
                               placeholder="Search servers, locations, brands..."
                               class="w-full px-4 py-3 pl-12 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <svg class="absolute left-4 top-3.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        @if($searchTerm)
                            <button wire:click="$set('searchTerm', '')" class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Quick Filters --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Brand Filter --}}
                    <div>
                        <label class="block text-sm font-medium text-white mb-2">Select Brand</label>
                        <select wire:model.live="selectedBrand" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">All Brands</option>
                            @foreach($this->brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Category Filter --}}
                    <div>
                        <label class="block text-sm font-medium text-white mb-2">Select Category</label>
                        <select wire:model.live="selectedCategory" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">All Categories</option>
                            @foreach($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Quick Action Buttons --}}
                <div class="flex flex-wrap justify-center gap-3 mt-6">
                    <a href="/servers" wire:navigate class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200">
                        Browse All Servers
                    </a>
                    <a href="/servers?featured=1" wire:navigate class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition duration-200">
                        Featured Plans
                    </a>
                    @auth
                        <a href="/customer" wire:navigate class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                            My Dashboard
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
    {{-- Enhanced Search & Filtering Section End --}}

    {{-- Featured Plans Section Start --}}
    @if($this->showFeaturedPlans && $this->featuredPlans->count() > 0)
    <div class="w-full bg-gradient-to-r from-green-900 to-green-600 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-white mb-4">Featured Plans</h2>
                <p class="text-lg text-white/80">Handpicked premium proxy solutions for every need</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($this->featuredPlans as $plan)
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 hover:bg-white/20 transition duration-300">
                    <div class="flex items-center mb-4">
                        @if($plan->brand)
                            <img src="{{ url('storage/'.$plan->brand->image) }}"
                                 alt="{{ $plan->brand->name }}"
                                 class="w-12 h-12 rounded-lg mr-3">
                        @endif
                        <div>
                            <h3 class="text-xl font-bold text-white">{{ $plan->name }}</h3>
                            @if($plan->category)
                                <p class="text-green-300 text-sm">{{ $plan->category->name }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="text-3xl font-bold text-green-400">${{ number_format($plan->price, 2) }}</div>
                        <div class="text-white/60 text-sm">per month</div>
                    </div>

                    @if($plan->server)
                    <div class="text-white/80 text-sm mb-4">
                        <div class="flex items-center mb-1">
                            <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
                            Location: {{ $plan->server->name }}
                        </div>
                        <div class="flex items-center">
                            <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
                            Country: {{ $plan->server->location }}
                        </div>
                    </div>
                    @endif

                    <a href="/servers/{{ $plan->slug }}" wire:navigate
                       class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200 text-center block">
                        View Details
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
    {{-- Featured Plans Section End --}}

    {{-- Categories Section Start  --}}
    <div class="w-full h-auto py-auto font-mono bg-gradient-to-r from-green-900 to-green-600 py-6">
        <div class="max-w-7xl mx-auto px-10 py-6 lg:py-8 md:px-10">
            <div class="text-center ">
                <div class="relative flex flex-col items-center">
                    <h1 class="text-5xl font-bold font-mono text-white py-3">Fast and Secure <span class="text-yellow-600">Proxies</span>
                    </h1>
                    <div class="flex w-40 mt-3 mb-6 overflow-hidden rounded">
                        <div class="flex-1 h-2 bg-gradient-to-r from-green-400 to-yellow-600">
                        </div>
                    </div>
                </div>
                <p class="mb-6 text-lg justify-left font-mono text-white py-3">
                    Experience blazing fast speeds and top-notch security with our cutting-edge proxy services. Whether you need to browse
                    anonymously, access restricted content, or safeguard your data, our proxies have got you covered.
                </p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-10 py-6 lg:py-8 md:px-10">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($this->categories as $serverCategory)
                <a class="group flex flex-col justify-center items-center border-transparent bg-dark-green hover:bg-green-400 hover:text-green-900 disabled:opacity-50 disabled:pointer-events-none dark:focus:ring-green-600 border shadow-sm rounded-xl hover:shadow-md transition dark:bg-dark-green dark:border-gray-800 dark:focus:outline-none dark:focus:ring-1"
                    href="/servers?selected_categories[0]={{ $serverCategory->id }}" wire:key="{{$serverCategory->id}}" wire:navigate>
                    <div class="p-4 md:p-5">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <img class="h-6 w-6 rounded-full"
                                    src="{{ url('storage/'.$serverCategory->image)}}" alt="{{ $serverCategory->name}}">
                                <div class="ms-3">
                                    <h3 class="group-hover:text-green-900 text-base sm:text-lg md:text-xl font-bold text-white">
                                        {{ $serverCategory->name}}
                                    </h3>
                                    <p class="text-sm text-white/70 group-hover:text-green-800">
                                        {{ $serverCategory->server_plans_count }} plans available
                                    </p>
                                </div>
                            </div>
                            <div class="ps-3">
                                <svg class="flex-shrink-0 w-5 h-5 text-accent-yellow group-hover:text-accent-yellow"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m9 18 6-6-6-6" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>

    </div>
    {{-- Categories Section End  --}}

    {{-- Server Start  --}}
    <section class="w-full h-auto pt-8 m-auto -mt-0 font-mono bg-gradient-to-r from-green-900 to-green-600 py-6">
        <div class="max-w-7xl mx-auto px-10 py-6 lg:py-8 md:px-10">
            <div class="text-center ">
                <div class="relative flex flex-col items-center">
                    <h1 class="text-5xl font-bold font-mono text-white py-3">Global Network <span class="text-green-400">&</span>
                        <spa
                            class="text-yellow-600">Easy Integration</span></h1>
                    <div class="flex w-40 mt-3 mb-6 overflow-hidden rounded">
                        <div class="flex-1 h-2 bg-gradient-to-r from-green-400 to-yellow-600"></div>
                    </div>
                </div>
                <p class="mb-6 text-lg justify-left font-mono text-white py-3">
                    Unlock the internet's full potential with our global proxy network. Connect from anywhere, bypass geo-restrictions, and
                    access content seamlessly. Designed for simplicity, our proxies offer quick setup and easy integration for developers,
                    businesses, and individual users.
                </p>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-10 py-6 lg:py-8 md:px-10">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-4 md:grid-cols-2">
                @foreach ($this->brands as $serverBrand)
                <div class="py-6 px-4 justify-center items-center rounded-lg border border-transparent bg-dark-green hover:bg-green-400 hover:text-green-900 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-green-600"
                    wire:key="{{ $serverBrand->id }}">
                    <a href="/servers?selected_brands[0]={{ $serverBrand->id }}" wire:navigate class="block">
                        <img src="{{ url('storage/'.$serverBrand->image) }}" alt="{{ $serverBrand->name }}"
                            class="object-contain w-full h-40 mx-auto rounded-lg">
                    </a>
                    <div class="p-5 text-center">
                        <a href="/servers?selected_brands[0]={{ $serverBrand->id }}" wire:navigate
                            class="text-xl sm:text-2xl font-bold tracking-tight text-white hover:text-green-900">{{ $serverBrand->name }}</a>
                        <p class="text-sm text-white/70 mt-1">{{ $serverBrand->server_plans_count }} plans available</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    {{-- Server End  --}}

    {{-- Customer Reviews Start  --}}
    <section class="w-full h-auto pt-8 m-auto -mt-0 font-mono bg-gradient-to-r from-green-900 to-green-600">
        <div class="max-w-7xl mx-auto px-10 py-6 lg:py-8 md:px-10">
            <div class="max-w-7xl mx-auto">
                <div class="text-center ">
                    <div class="relative flex flex-col items-center">
                        <h1 class="text-5xl font-bold font-mono text-white py-3">Need help? <span class="text-green-400">or read</span>
                            <spa class="text-yellow-600"> Reviews</span>
                        </h1>
                        <div class="flex w-40 mt-3 mb-6 overflow-hidden rounded">
                            <div class="flex-1 h-2 bg-gradient-to-r from-green-400 to-yellow-600"></div>
                        </div>
                    </div>
                    <p class="mb-6 text-lg justify-left font-mono text-white py-3">
                        Discover what customers say about our VPN and proxy services. Read their testimonials on our secure and reliable network
                        solutions. Our 24/7 support team is here to assist with any questions, ensuring the best experience with our
                        proxy services.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-dark-green rounded-lg shadow-md dark:bg-gray-700 py-6">
                    <div
                        class="flex flex-wrap items-center justify-between pb-4 mb-6 space-x-2 border-b border-green-600 dark:border-gray-700">
                        <div class="flex items-center px-6 mb-2 md:mb-0">
                            <div class="flex mr-2 rounded-full">
                                <img src="{{ asset('storage/uploads/sarah.jpg') }}" alt=""
                                    class="object-cover w-12 h-12 rounded-full">
                            </div>
                            <div>
                                <h2 class="text-base sm:text-lg font-bold text-green-500 dark:text-green-400">
                                    Emma Martinez</h2>
                                <p class="text-sm sm:text-base text-white leading-relaxed font-mono dark:text-green-400">Research Analyst</p>
                            </div>
                        </div>
                        {{-- <p class="px-6 text-base font-medium text-green-700 dark:text-green-400"> Joined 12, SEP , 2022
                                        </p> --}}
                    </div>
                    <p class="px-6 mb-6 text-base text-white font-mono dark:text-green-400">
                        I rely on these proxies for my daily research tasks. The security and performance are top-notch, allowing me to gather
                        data efficiently without any geo-restrictions.
                    </p>
                    <div class="flex flex-wrap justify-between pt-4 border-t border-green-600 dark:border-gray-700">
                        <div class="flex px-6 mb-2 md:mb-0">
                            <ul class="flex items-center justify-start mr-4">
                                <li>
                                    <a href="#">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 mr-1 text-yellow-600 dark:text-green-400 bi bi-star-fill" viewBox="0 0 16 16">
                                            <path
                                                d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z">
                                            </path>
                                        </svg>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 mr-1 text-yellow-600 dark:text-green-400 bi bi-star-fill" viewBox="0 0 16 16">
                                            <path
                                                d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z">
                                            </path>
                                        </svg>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 mr-1 text-yellow-600 dark:text-green-400 bi bi-star-fill" viewBox="0 0 16 16">
                                            <path
                                                d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z">
                                            </path>
                                        </svg>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 mr-1 text-yellow-600 dark:text-green-400 bi bi-star-fill" viewBox="0 0 16 16">
                                            <path
                                                d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z">
                                            </path>
                                        </svg>
                                    </a>
                                </li>
                            </ul>
                            <h2 class="text-sm text-white font- font-bold dark:text-green-400">Rating:<span
                                    class="font-bold font-mono text-yellow-600 dark:text-green-400">
                                    5.0</span>
                            </h2>
                        </div>
                        <div class="flex items-center px-6 space-x-1 text-sm font-medium text-gray-500 dark:text-gray-400">
                            <div class="flex items-center">
                                <div class="flex mr-3 text-sm font-mono text-yellow-600 dark:text-green-400">
                                    <a href="#">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 h-4 mr-1 text-green-400 bi bi-hand-thumbs-up-fill" viewBox="0 0 16 16">
                                            <path
                                                d="M6.956 1.745C7.021.81 7.908.087 8.864.325l.261.066c.463.116.874.456 1.012.965.22.816.533 2.511.062 4.51a9.84 9.84 0 0 1 .443-.051c.713-.065 1.669-.072 2.516.21.518.173.994.681 1.2 1.273.184.532.16 1.162-.234 1.733.058.119.103.242.138.363.077.27.113.567.113.856 0 .289-.036.586-.113.856-.039.135-.09.273-.16.404.169.387.107.819-.003 1.148a3.163 3.163 0 0 1-.488.901c.054.152.076.312.076.465 0 .305-.089.625-.253.912C13.1 15.522 12.437 16 11.5 16H8c-.605 0-1.07-.081-1.466-.218a4.82 4.82 0 0 1-.97-.484l-.048-.03c-.504-.307-.999-.609-2.068-.722C2.682 14.464 2 13.846 2 13V9c0-.85.685-1.432 1.357-1.615.849-.232 1.574-.787 2.132-1.41.56-.627.914-1.28 1.039-1.639.199-.575.356-1.539.428-2.59z">
                                            </path>
                                        </svg>
                                    </a>
                                    <span class="font-bold font-mono text-yellow-600 dark:text-green-300">12</span>
                                </div>
                                <div class="flex text-sm font-bold text-white font-mono dark:text-green-400">
                                    <a href="#" class="inline-flex hover:underline ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 h-4 mr-1 text-green-400 bi bi-chat" viewBox="0 0 16 16">
                                            <path
                                                d="M2.678 11.894a1 1 0 0 1 .287.801 10.97 10.97 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8.06 8.06 0 0 0 8 14c3.996 0 7-2.807 7-6 0-3.192-3.004-6-7-6S1 4.808 1 8c0 1.468.617 2.83 1.678 3.894zm-.493 3.905a21.682 21.682 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a9.68 9.68 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9.06 9.06 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105z">
                                            </path>
                                        </svg>Reply</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-dark-green rounded-lg shadow-md dark:bg-gray-700 py-6">
                    <div
                        class="flex flex-wrap items-center justify-between pb-4 mb-6 space-x-2 border-b border-green-600 dark:border-gray-700">
                        <div class="flex items-center px-6 mb-2 md:mb-0">
                            <div class="flex mr-2 rounded-full">
                                <img src="{{ asset('storage/uploads/david.jpg') }}" alt=""
                                    class="object-cover w-12 h-12 rounded-full">
                            </div>
                            <div>
                            <h2 class="text-base sm:text-lg font-bold text-green-500 dark:text-green-400">
                                David Lee</h2>
                                <p class="text-sm sm:text-base text-white leading-relaxed font-mono dark:text-green-400">Research Analyst</p>
                            </div>
                        </div>
                        {{-- <p class="px-6 text-base font-medium text-green-700 dark:text-green-400"> Joined 12, SEP , 2022
                                                        </p> --}}
                    </div>
                    <p class="px-6 mb-6 text-base text-white font-mono dark:text-green-400">
                        The integration was quick and, less than 5 minutes. The proxies are stable and fast, making my development and testing process much
                        smoother. Great service!
                    </p>
                    <div class="flex flex-wrap justify-between pt-4 border-t border-green-600 dark:border-gray-700">
                        <div class="flex px-6 mb-2 md:mb-0">
                            <ul class="flex items-center justify-start mr-4">
                                <li>
                                    <a href="#">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 mr-1 text-yellow-600 dark:text-green-400 bi bi-star-fill" viewBox="0 0 16 16">
                                            <path
                                                d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z">
                                            </path>
                                        </svg>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 mr-1 text-yellow-600 dark:text-green-400 bi bi-star-fill" viewBox="0 0 16 16">
                                            <path
                                                d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z">
                                            </path>
                                        </svg>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 mr-1 text-yellow-600 dark:text-green-400 bi bi-star-fill" viewBox="0 0 16 16">
                                            <path
                                                d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z">
                                            </path>
                                        </svg>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 mr-1 text-yellow-600 dark:text-green-400 bi bi-star-fill" viewBox="0 0 16 16">
                                            <path
                                                d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z">
                                            </path>
                                        </svg>
                                    </a>
                                </li>
                            </ul>
                            <h2 class="text-sm text-white font- font-bold dark:text-green-400">Rating:<span
                                    class="font-bold font-mono text-yellow-600 dark:text-green-400">
                                    5.0</span>
                            </h2>
                        </div>
                        <div class="flex items-center px-6 space-x-1 text-sm font-medium text-gray-500 dark:text-gray-400">
                            <div class="flex items-center">
                                <div class="flex mr-3 text-sm font-mono text-yellow-600 dark:text-green-400">
                                    <a href="#">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 h-4 mr-1 text-green-400 bi bi-hand-thumbs-up-fill" viewBox="0 0 16 16">
                                            <path
                                                d="M6.956 1.745C7.021.81 7.908.087 8.864.325l.261.066c.463.116.874.456 1.012.965.22.816.533 2.511.062 4.51a9.84 9.84 0 0 1 .443-.051c.713-.065 1.669-.072 2.516.21.518.173.994.681 1.2 1.273.184.532.16 1.162-.234 1.733.058.119.103.242.138.363.077.27.113.567.113.856 0 .289-.036.586-.113.856-.039.135-.09.273-.16.404.169.387.107.819-.003 1.148a3.163 3.163 0 0 1-.488.901c.054.152.076.312.076.465 0 .305-.089.625-.253.912C13.1 15.522 12.437 16 11.5 16H8c-.605 0-1.07-.081-1.466-.218a4.82 4.82 0 0 1-.97-.484l-.048-.03c-.504-.307-.999-.609-2.068-.722C2.682 14.464 2 13.846 2 13V9c0-.85.685-1.432 1.357-1.615.849-.232 1.574-.787 2.132-1.41.56-.627.914-1.28 1.039-1.639.199-.575.356-1.539.428-2.59z">
                                            </path>
                                        </svg>
                                    </a>
                                    <span class="font-bold font-mono text-yellow-600 dark:text-green-300">12</span>
                                </div>
                                <div class="flex text-sm font-bold text-white font-mono dark:text-green-400">
                                    <a href="#" class="inline-flex hover:underline ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 h-4 mr-1 text-green-400 bi bi-chat" viewBox="0 0 16 16">
                                            <path
                                                d="M2.678 11.894a1 1 0 0 1 .287.801 10.97 10.97 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8.06 8.06 0 0 0 8 14c3.996 0 7-2.807 7-6 0-3.192-3.004-6-7-6S1 4.808 1 8c0 1.468.617 2.83 1.678 3.894zm-.493 3.905a21.682 21.682 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a9.68 9.68 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9.06 9.06 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105z">
                                            </path>
                                        </svg>Reply</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-dark-green rounded-lg shadow-md dark:bg-gray-700 py-6">
                    <div
                        class="flex flex-wrap items-center justify-between pb-4 mb-6 space-x-2 border-b border-green-600 dark:border-gray-700">
                        <div class="flex items-center px-6 mb-2 md:mb-0">
                            <div class="flex mr-2 rounded-full">
                                <img src="{{ asset('storage/uploads/emma.jpg') }}" alt=""
                                    class="object-cover w-12 h-12 rounded-full">
                            </div>
                            <div>
                            <h2 class="text-base sm:text-lg font-bold text-green-500 dark:text-green-400">
                                Sarah Johnson</h2>
                                <p class="text-sm sm:text-base text-white leading-relaxed font-mono dark:text-green-400">Research Analyst</p>
                            </div>
                        </div>
                        {{-- <p class="px-6 text-base font-medium text-green-700 dark:text-green-400"> Joined 12, SEP , 2022
                                                        </p> --}}
                    </div>
                    <p class="px-6 mb-6 text-base text-white font-mono dark:text-green-400">
                        Using these proxies has transformed my workflow. The reliability and speed are unmatched, and I can access region-locked
                        content seamlessly. Highly recommend!
                    </p>
                    <div class="flex flex-wrap justify-between pt-4 border-t border-green-600 dark:border-gray-700">
                        <div class="flex px-6 mb-2 md:mb-0">
                            <ul class="flex items-center justify-start mr-4">
                                <li>
                                    <a href="#">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 mr-1 text-yellow-600 dark:text-green-400 bi bi-star-fill" viewBox="0 0 16 16">
                                            <path
                                                d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z">
                                            </path>
                                        </svg>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 mr-1 text-yellow-600 dark:text-green-400 bi bi-star-fill" viewBox="0 0 16 16">
                                            <path
                                                d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z">
                                            </path>
                                        </svg>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 mr-1 text-yellow-600 dark:text-green-400 bi bi-star-fill" viewBox="0 0 16 16">
                                            <path
                                                d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z">
                                            </path>
                                        </svg>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 mr-1 text-yellow-600 dark:text-green-400 bi bi-star-fill" viewBox="0 0 16 16">
                                            <path
                                                d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z">
                                            </path>
                                        </svg>
                                    </a>
                                </li>
                            </ul>
                            <h2 class="text-sm text-white font- font-bold dark:text-green-400">Rating:<span
                                    class="font-bold font-mono text-yellow-600 dark:text-green-400">
                                    5.0</span>
                            </h2>
                        </div>
                        <div class="flex items-center px-6 space-x-1 text-sm font-medium text-gray-500 dark:text-gray-400">
                            <div class="flex items-center">
                                <div class="flex mr-3 text-sm font-mono text-yellow-600 dark:text-green-400">
                                    <a href="#">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 h-4 mr-1 text-green-400 bi bi-hand-thumbs-up-fill" viewBox="0 0 16 16">
                                            <path
                                                d="M6.956 1.745C7.021.81 7.908.087 8.864.325l.261.066c.463.116.874.456 1.012.965.22.816.533 2.511.062 4.51a9.84 9.84 0 0 1 .443-.051c.713-.065 1.669-.072 2.516.21.518.173.994.681 1.2 1.273.184.532.16 1.162-.234 1.733.058.119.103.242.138.363.077.27.113.567.113.856 0 .289-.036.586-.113.856-.039.135-.09.273-.16.404.169.387.107.819-.003 1.148a3.163 3.163 0 0 1-.488.901c.054.152.076.312.076.465 0 .305-.089.625-.253.912C13.1 15.522 12.437 16 11.5 16H8c-.605 0-1.07-.081-1.466-.218a4.82 4.82 0 0 1-.97-.484l-.048-.03c-.504-.307-.999-.609-2.068-.722C2.682 14.464 2 13.846 2 13V9c0-.85.685-1.432 1.357-1.615.849-.232 1.574-.787 2.132-1.41.56-.627.914-1.28 1.039-1.639.199-.575.356-1.539.428-2.59z">
                                            </path>
                                        </svg>
                                    </a>
                                    <span class="font-bold font-mono text-yellow-600 dark:text-green-300">12</span>
                                </div>
                                <div class="flex text-sm font-bold text-white font-mono dark:text-green-400">
                                    <a href="#" class="inline-flex hover:underline ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 h-4 mr-1 text-green-400 bi bi-chat" viewBox="0 0 16 16">
                                            <path
                                                d="M2.678 11.894a1 1 0 0 1 .287.801 10.97 10.97 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8.06 8.06 0 0 0 8 14c3.996 0 7-2.807 7-6 0-3.192-3.004-6-7-6S1 4.808 1 8c0 1.468.617 2.83 1.678 3.894zm-.493 3.905a21.682 21.682 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a9.68 9.68 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9.06 9.06 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105z">
                                            </path>
                                        </svg>Reply</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>




            </div>
    </section>
    {{-- Customer Reviews End  --}}

</div>
@endsection
