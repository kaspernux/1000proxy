@php
    use App\Filament\Customer\Clusters\MyWallet\Resources\WalletResource;
@endphp

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
                            class="font-mono flex items-center gap-1 {{request()->is('/') ? 'text-accent-yellow' : 'text-white'}} hover:text-yellow-600 py-3 md:py-6 dark:text-green-400 dark:hover:text-yellow-600 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-green-600"
                            href="/" aria-current="page">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-9 2v8m4-8l2 2m-2-2v8m0 0H7m6 0h2" /></svg>
                            Home
                        </a>

                        <a wire:navigate
                            class="font-mono flex items-center gap-1 {{request()->is('categories') ? 'text-accent-yellow' : 'text-white'}} hover:text-yellow-600 py-3 md:py-6 dark:text-green-400 dark:hover:text-yellow-600 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-green-600"
                            href="/categories">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6m16 0a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2m16 0V7m0 6v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-6" /></svg>
                            Categories
                        </a>

                        <a wire:navigate
                            class="font-mono flex items-center gap-1 {{request()->is('products') ? 'text-accent-yellow' : 'text-white'}} hover:text-yellow-600 py-3 md:py-6 dark:text-green-400 dark:hover:text-yellow-600 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-green-600"
                            href="/servers">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                            Proxies
                        </a>

                        <a wire:navigate
                            class="font-mono flex items-center gap-1 {{request()->is('cart') ? 'text-accent-yellow' : 'text-white'}} hover:text-yellow-600 py-3 md:py-6 dark:text-green-400 dark:hover:text-white dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-green-600"
                            href="/cart">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m13-9l2 9m-5-9V6a2 2 0 1 0-4 0v3" /></svg>
                            <span class="mr-1">Cart</span> <span
                                class="py-0.5 px-1.5 rounded-full text-xs font-mono bg-green-400 border border-white text-green-900">{{$total_count ?? (session('cart') ? count(session('cart')) : 0)}}</span>
                        </a>

                        @if (!auth('web')->check() && !auth('customer')->check())
                        <div class="pt-3 md:pt-0">
                            <a wire:navigate
                                class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-bold rounded-lg border border-transparent bg-green-600 text-white hover:bg-yellow-600 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-green-600"
                                href="/login">
                                <x-custom-icon name="user" class="h-4 w-4 inline mr-1" /> Log in
                            </a>
                        </div>
                        @endif

                        {{-- admin/web user menu --}}
                        @auth('web')
                            <div class="hs-dropdown [--strategy:static] md:[--strategy:fixed] [--adaptive:none] md:[--trigger:hover] md:py-4">
                                <button type="button"
                                        class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-bold rounded-lg border border-transparent bg-green-600 text-white hover:bg-yellow-600 focus:outline-none focus:ring-1 focus:ring-green-600">
                                    <x-custom-icon name="user" class="h-4 w-4 inline mr-1" /> {{ auth('web')->user()->name }}
                                    <svg class="ms-2 w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path d="m6 9 6 6 6-6" />
                                    </svg>
                                </button>
                                <div
                                    class="hs-dropdown-menu bg-green-400 transition-[opacity,margin] duration-[150ms] opacity-0 hs-dropdown-open:opacity-100 md:w-48 hidden z-10 rounded-lg p-2 shadow-md dark:bg-green-400">
                                    <a href="/orders" class="block py-2 px-3 text-sm text-white hover:bg-green-400 focus:ring-2 focus:ring-yellow-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2m-6 0h6m-6 0a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2m-6 0h6" /></svg> My Orders
                                    </a>
                                    <a href="/account-settings" class="block py-2 px-3 text-sm text-white hover:bg-green-400 focus:ring-2 focus:ring-yellow-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0 1 12 15c2.485 0 4.847.657 6.879 1.804M15 11a3 3 0 1 0-6 0 3 3 0 0 0 6 0z" /></svg> My Account
                                    </a>
                                    <a href="/logout" class="block py-2 px-3 text-sm text-white hover:bg-green-400 focus:ring-2 focus:ring-yellow-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v1" /></svg> Logout
                                    </a>
                                </div>
                            </div>
                        @endauth

                        {{-- customer dropdown with Balance after My Account --}}
                        @auth('customer')
                            @php
                                $wallet = auth('customer')->user()->getWallet();
                            @endphp
                            <div class="hs-dropdown [--strategy:static] md:[--strategy:fixed] [--adaptive:none] md:[--trigger:hover] md:py-4">
                                <button type="button"
                                        class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-bold rounded-lg border border-transparent bg-green-600 text-white hover:bg-yellow-600 focus:outline-none focus:ring-1 focus:ring-green-600">
                                    <x-custom-icon name="user" class="h-4 w-4 inline mr-1" /> {{ auth('customer')->user()->name }}
                                    <svg class="ms-2 w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path d="m6 9 6 6 6-6" />
                                    </svg>
                                </button>
                                <div
                                class="hs-dropdown-menu bg-green-400 transition-[opacity,margin] duration-[0.1ms] md:duration-[150ms] hs-dropdown-open:opacity-100 opacity-0 md:w-48 hidden z-10 md:shadow-md rounded-lg p-2 dark:bg-green-400 md:dark:border dark:border-yellow-600 dark:divide-green-900 before:absolute top-full md:border before:-top-5 before:start-0 before:w-full before:h-5">

                                <!-- Wallet balance -->

                                <a href="{{ route('filament.customer.my-wallet.resources.wallets.view', ['record' => $wallet->id]) }}"
                                    class="flex items-center gap-x-3.5 hover:text-green-900 py-2 px-3 rounded-lg text-sm text-white hover:bg-green-400 dark:text-green-400 dark:hover:bg-green-400"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 0 0-10 0v2a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2z" /></svg> Balance: ${{ number_format($wallet->balance, 2) }}
                                </a>

                                <a class="flex items-center gap-x-3.5 hover:text-green-900 py-2 px-3 rounded-lg text-sm text-white hover:bg-green-400 focus:ring-2 focus:ring-yellow-600 dark:text-green-400 dark:hover:bg-green-400 dark:hover:text-green-900 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-yellow-600"
                                href="/account-settings">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0 1 12 15c2.485 0 4.847.657 6.879 1.804M15 11a3 3 0 1 0-6 0 3 3 0 0 0 6 0z" /></svg> My Account
                                </a>
                                
                                <a class="flex items-center gap-x-3.5 hover:text-green-900 py-2 px-3 rounded-lg text-sm text-white hover:bg-green-400 focus:ring-2 focus:ring-yellow-600 dark:text-green-400 dark:hover:bg-green-400 dark:hover:text-green-900 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-yellow-600"
                                    wire:navigate href="/my-orders">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2m-6 0h6m-6 0a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2m-6 0h6" /></svg> My Orders
                                    </a>

                                    <a class="flex items-center gap-x-3.5 hover:text-green-900 py-2 px-3 rounded-lg text-sm text-white hover:bg-green-400 focus:ring-2 focus:ring-yellow-600 dark:text-green-400 dark:hover:bg-green-400 dark:hover:text-green-900 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-yellow-600"
                                    href="/logout">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v1" /></svg> Logout
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
