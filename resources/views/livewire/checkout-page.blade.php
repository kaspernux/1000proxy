<div
    class="w-full bg-gradient-to-r from-green-900 to-green-600 container max-w-auto py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    <div class="container mx-auto px-4 max-w-7xl">
        <h1 class="text-4xl my-10 font-bold text-white text-left">
            Checkout
        </h1>
        <form wire:submit.prevent="placeOrder">
            <div class="grid grid-cols-12 gap-4">
                <div class="md:col-span-12 lg:col-span-8 col-span-12">
                    <div class="bg-white rounded-xl shadow p-4 sm:p-7 dark:bg-slate-900">
                        @if (session()->has('error'))
                        <div class="bg-red-500 text-white p-3 rounded mb-4">
                            {{ session('error') }}
                        </div>
                        @endif

                        <!-- Shipping Address -->
                        <div class="mb-6">
                            <h2 class="text-xl font-bold underline text-gray-700 dark:text-white mb-2">
                                Customer Detail
                            </h2>
                            <div class="mt-4">
                                <label class="block text-gray-700 dark:text-white mb-1" for="name">
                                    Username or Name
                                </label>
                                <input
                                    class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none @error('name') border-red-600 @enderror"
                                    id="name" type="text" wire:model="name" disabled>
                                </input>
                                @error('name')
                                    <div class="text-red-600 text-sm">{{$message}}</div>
                                @enderror
                            </div>
                            <div class="mt-4">
                                <label class="block text-gray-700 dark:text-white mb-1" for="email">
                                    Email
                                </label>
                                <input
                                    class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none @error('name') border-red-600 @enderror"
                                    id="email" type="text" wire:model="email" disabled>
                                </input>
                                @error('email')
                                    <div class="text-red-600 text-sm">{{$message}}</div>
                                @enderror
                            </div>
                            <div class="mt-4">
                                <label class="block text-gray-700 dark:text-white mb-1" for="phone">
                                    Phone
                                </label>
                                <input
                                    class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none @error('name') border-red-600 @enderror"
                                    id="phone" type="text" wire:model="phone">
                                </input>
                                @error('phone')
                                    <div class="text-red-600 text-sm">{{$message}}</div>
                                @enderror
                            </div>
                            <div class="mt-4">
                                <label class="block text-gray-700 dark:text-white mb-1" for="telegram_id">
                                    Telegram ID
                                </label>
                                <input
                                    class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none @error('name') border-red-600 @enderror"
                                    id="telegram_id" type="text" wire:model="telegram_id">
                                </input>
                                @error('telegram_id')
                                    <div class="text-red-600 text-sm">{{$message}}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="text-lg font-semibold mb-4">
                            Select Payment Method
                        </div>
                        <ul class="grid w-full gap-6 md:grid-cols-2">
                            @foreach($paymentMethods as $method)
                            <li>
                                <input wire:model='payment_method' class="hidden peer"
                                    id="payment-method-{{ $method->id }}" type="radio" value="{{ $method->id }}" />
                                <label
                                    class="inline-flex items-center justify-between w-full p-5 text-gray-500 bg-white border border-gray-200 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-700 dark:peer-checked:text-blue-500 peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700"
                                    for="payment-method-{{ $method->id }}">
                                    <div class="block">
                                        <div class="w-full text-lg font-semibold">
                                            {{ $method->name }}
                                        </div>
                                    </div>
                                    <svg aria-hidden="true" class="w-5 h-5 ms-3 rtl:rotate-180" fill="none"
                                        viewBox="0 0 14 10" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M1 5h12m0 0L9 1m4 4L9 9" stroke="currentColor" stroke-linecap="round"
                                            stroke-linejoin="round" stroke-width="2"></path>
                                    </svg>
                                </label>
                            </li>
                            @endforeach
                            @foreach($staticPaymentMethods as $key => $method)
                            <li>
                                <input wire:model='payment_method' class="hidden peer" id="payment-method-{{ $key }}"
                                    type="radio" value="{{ $key }}">
                                <label
                                    class="inline-flex items-center justify-between w-full p-5 text-gray-500 bg-white border border-gray-200 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-700 dark:peer-checked:text-blue-500 peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700"
                                    for="payment-method-{{ $key }}">
                                    <div class="block">
                                        <div class="w-full text-lg font-semibold">
                                            {{ $method }}
                                        </div>
                                    </div>
                                    <svg aria-hidden="true" class="w-5 h-5 ms-3 rtl:rotate-180" fill="none"
                                        viewbox="0 0 14 10" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M1 5h12m0 0L9 1m4 4L9 9" stroke="currentColor" stroke-linecap="round"
                                            stroke-linejoin="round" stroke-width="2">
                                        </path>
                                    </svg>
                                </label>
                                </input>
                            </li>
                            @endforeach
                        </ul>
                        @error('payment_method')
                            <div class="text-red-600 text-sm">{{$message}}</div>
                        @enderror
                    </div>
                </div>
                <div class="md:col-span-12 lg:col-span-4 col-span-12">
                    <div class="bg-white rounded-xl shadow p-4 sm:p-7 dark:bg-slate-900">
                        <div class="text-xl font-bold underline text-gray-700 dark:text-white mb-2">
                            ORDER SUMMARY
                        </div>
                        <div class="flex justify-between mb-2 font-bold">
                            <span>
                                Subtotal
                            </span>
                            <span>
                                {{ Number::currency($grand_amount) }}
                            </span>
                        </div>
                        <div class="flex justify-between mb-2 font-bold">
                            <span>
                                Taxes
                            </span>
                            <span>
                                {{ Number::currency(0) }}
                            </span>
                        </div>
                        <div class="flex justify-between mb-2 font-bold">
                            <span>
                                Shipping Cost
                            </span>
                            <span>
                                {{ Number::currency(0) }}
                            </span>
                        </div>
                        <hr class="bg-slate-400 my-4 h-1 rounded">
                        <div class="flex justify-between mb-2 font-bold">
                            <span>
                                Grand Total
                            </span>
                            <span>
                                {{ Number::currency($grand_amount) }}
                            </span>
                        </div>
                        </hr>
                    </div>

                    <button class="bg-yellow-600 mt-4 w-full p-3 rounded-lg text-lg text-white hover:bg-green-400" type="submit">
                        Place Order
                    </button>

                    <div class="bg-white mt-4 rounded-xl shadow p-4 sm:p-7 dark:bg-slate-900">
                        <div class="text-xl font-bold underline text-gray-700 dark:text-white mb-2">
                            BASKET SUMMARY
                        </div>
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700" role="list">
                            @foreach ($order_items as $ci)
                            <li class="py-3 sm:py-4" wire:key="{{$ci['server_plan_id']}}">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <img alt="{{$ci['name']}}" class="w-12 h-12 rounded-full"
                                            src="{{ url('storage/'.$ci['product_image']) }}">
                                        </img>
                                    </div>
                                    <div class="flex-1 min-w-0 ms-4">
                                        <p class="text-sm font-medium text-gray-900 truncate dark:text-white">
                                            {{$ci['name']}}
                                        </p>
                                        <p class="text-sm text-gray-500 truncate dark:text-gray-400">
                                            Quantity: {{$ci['quantity']}}
                                        </p>
                                    </div>
                                    <div
                                        class="inline-flex items-center text-base font-semibold text-gray-900 dark:text-white">
                                        {{ Number::currency($ci['total_amount']) }}
                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
