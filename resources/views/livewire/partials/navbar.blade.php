<header
    class="flex z-50 sticky font-mono top-0 flex-wrap md:justify-start md:flex-nowrap w-full bg-gradient-to-r from-green-900 to-green-600 text-sm py-3 md:py-0 dark:bg-green-800 shadow-md">
    <nav class="max-w-[85rem] w-full mx-auto px-4 md:px-6 lg:px-8" aria-label="Global">
        <div class="relative md:flex md:items-center md:justify-between">
            <div class="flex items-center justify-between">
                <a wire:navigate
                    class="flex-none text-xl font-bold text-white hover:text-yellow-600 dark:text-white dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-green-600"
                    href="/" aria-label="Brand">1000 PROXIES</a>
                <div class="md:hidden">
                    <button type="button"
                        class="hs-collapse-toggle flex justify-center items-center w-9 h-9 text-sm font-bold rounded-lg border border-gray-200 text-green-800 hover:bg-green-700 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                        data-hs-collapse="#navbar-collapse-with-animation"
                        aria-controls="navbar-collapse-with-animation" aria-label="Toggle navigation">
                        <svg class="hs-collapse-open:hidden flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg"
                            width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" x2="21" y1="6" y2="6" />
                            <line x1="3" x2="21" y1="12" y2="12" />
                            <line x1="3" x2="21" y1="18" y2="18" />
                        </svg>
                        <svg class="hs-collapse-open:block hidden flex-shrink-0 w-4 h-4"
                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div id="navbar-collapse-with-animation"
                class="hs-collapse hidden overflow-hidden transition-all duration-300 basis-full grow md:block">
                <div
                    class="overflow-hidden overflow-y-auto max-h-[75vh] [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-green-400 [&::-webkit-scrollbar-thumb]:bg-green-600 dark:[&::-webkit-scrollbar-track]:bg-slate-700 dark:[&::-webkit-scrollbar-thumb]:bg-slate-500">
                    <div
                        class="flex flex-col gap-x-0 mt-5 divide-y divide-dashed divide-green-400 md:flex-row md:items-center md:justify-end md:gap-x-7 md:mt-0 md:ps-7 md:divide-y-0 md:divide-solid dark:divide-green-700">

                        <a wire:navigate
                            class="font-mono {{request()->is('/') ? 'text-accent-yellow' : 'text-white'}} hover:text-yellow-600 py-3 md:py-6 dark:text-green-400 dark:hover:text-yellow-600 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-green-600"
                            href="/" aria-current="page">Home</a>

                        <a wire:navigate
                            class="font-mono {{request()->is('categories') ? 'text-accent-yellow' : 'text-white'}} hover:text-yellow-600 py-3 md:py-6 dark:text-green-400 dark:hover:text-yellow-600 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-green-600"
                            href="/categories">Categories</a>

                        <a wire:navigate
                            class="font-mono {{request()->is('products') ? 'text-accent-yellow' : 'text-white'}} hover:text-yellow-600 py-3 md:py-6 dark:text-green-400 dark:hover:text-yellow-600 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-green-600"
                            href="/servers">Products</a>

                        <a wire:navigate
                            class="font-mono flex items-center {{request()->is('cart') ? 'text-accent-yellow' : 'text-white'}} hover:text-yellow-600 py-3 md:py-6 dark:text-green-400 dark:hover:text-white dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-green-600"
                            href="/cart">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="flex-shrink-0 w-5 h-5 mr-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                            <span class="mr-1">Cart</span> <span
                                class="py-0.5 px-1.5 rounded-full text-xs font-mono bg-green-400 border border-white text-green-900">{{$total_count}}</span>
                        </a>

                        @if (!auth('web')->check() && !auth('customer')->check())
                        <div class="pt-3 md:pt-0">
                            <a wire:navigate
                                class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-bold rounded-lg border border-transparent bg-green-600 text-white hover:bg-yellow-600 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-green-600"
                                href="/login">
                                <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24"
                                    height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                Log in
                            </a>
                        </div>
                        @endif

                        @auth('web')
                        <div
                            class="hs-dropdown [--strategy:static] md:[--strategy:fixed] [--adaptive:none] md:[--trigger:hover] md:py-4">
                            <button type="button"
                                class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-bold rounded-lg border border-transparent bg-green-600 text-white hover:bg-yellow-600 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-green-600">
                                {{ auth('web')->user()->name ?? '' }}
                                <svg class="ms-2 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </button>

                            <div
                                class="hs-dropdown-menu bg-green-400 transition-[opacity,margin] duration-[0.1ms] md:duration-[150ms] hs-dropdown-open:opacity-100 opacity-0 md:w-48 hidden z-10 md:shadow-md rounded-lg p-2 dark:bg-green-400 md:dark:border dark:border-yellow-600 dark:divide-green-900 before:absolute top-full md:border before:-top-5 before:start-0 before:w-full before:h-5">
                                <a class="flex items-center gap-x-3.5 hover:text-green-900 py-2 px-3 rounded-lg text-sm text-white hover:bg-green-400 focus:ring-2 focus:ring-yellow-600 dark:text-green-400 dark:hover:bg-green-400 dark:hover:text-green-900 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-yellow-600"
                                    href="#">
                                    My Orders
                                </a>

                                <a class="flex items-center gap-x-3.5 hover:text-green-900 py-2 px-3 rounded-lg text-sm text-white hover:bg-green-400 focus:ring-2 focus:ring-yellow-600 dark:text-green-400 dark:hover:bg-green-400 dark:hover:text-green-900 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-yellow-600"
                                    href="#">
                                    My Account
                                </a>
                                <a class="flex items-center gap-x-3.5 hover:text-green-900 py-2 px-3 rounded-lg text-sm text-white hover:bg-green-400 focus:ring-2 focus:ring-yellow-600 dark:text-green-400 dark:hover:bg-green-400 dark:hover:text-green-900 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-yellow-600"
                                    href="/logout">
                                    Logout
                                </a>
                            </div>
                        </div>
                        @elseauth('customer')
                        <div
                            class="hs-dropdown [--strategy:static] md:[--strategy:fixed] [--adaptive:none] md:[--trigger:hover] md:py-4">
                            <button type="button"
                                class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-bold rounded-lg border border-transparent bg-green-600 text-white hover:bg-yellow-600 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-green-600">
                                {{ auth('customer')->user()->name ?? '' }}
                                <svg class="ms-2 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </button>

                            <div
                                class="hs-dropdown-menu bg-green-400 transition-[opacity,margin] duration-[0.1ms] md:duration-[150ms] hs-dropdown-open:opacity-100 opacity-0 md:w-48 hidden z-10 md:shadow-md rounded-lg p-2 dark:bg-green-400 md:dark:border dark:border-yellow-600 dark:divide-green-900 before:absolute top-full md:border before:-top-5 before:start-0 before:w-full before:h-5">
                                <a class="flex items-center gap-x-3.5 hover:text-green-900 py-2 px-3 rounded-lg text-sm text-white hover:bg-green-400 focus:ring-2 focus:ring-yellow-600 dark:text-green-400 dark:hover:bg-green-400 dark:hover:text-green-900 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-yellow-600"
                                    wire:navigate href="/my-orders">
                                    My Orders
                                </a>

                                <a class="flex items-center gap-x-3.5 hover:text-green-900 py-2 px-3 rounded-lg text-sm text-white hover:bg-green-400 focus:ring-2 focus:ring-yellow-600 dark:text-green-400 dark:hover:bg-green-400 dark:hover:text-green-900 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-yellow-600"
                                    href="#">
                                    My Account
                                </a>
                                <a class="flex items-center gap-x-3.5 hover:text-green-900 py-2 px-3 rounded-lg text-sm text-white hover:bg-green-400 focus:ring-2 focus:ring-yellow-600 dark:text-green-400 dark:hover:bg-green-400 dark:hover:text-green-900 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-yellow-600"
                                    href="/logout">
                                    Logout
                                </a>
                            </div>
                        </div>
                        @endauth

                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
