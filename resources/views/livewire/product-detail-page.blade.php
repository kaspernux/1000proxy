<div
    class="w-full font-mono bg-gradient-to-r from-green-900 to-green-600 max-w-auto py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    <section
        class="overflow-hidden container mx-auto px-4 max-w-7xl border-2 rounded-lg border-double border-yellow-600 py-11 font-mono dark:bg-green-400">
        <div class="max-w-6xl px-4 py-4 mx-auto lg:py-8 md:px-6">
            <div class="flex flex-wrap -mx-4">
                <div class="mb-8 md:w-1/2 md:mb-0 "
                    x-data="{ mainImage: '{{url('storage/'.$serverPlan->product_image)}}' }">
                    <div class="top-0 z-50 py-6 items-center">
                        <div class="relative mb-6 lg:mb-10 lg:h-2/4">
                            <img x-bind:src="mainImage" alt="" class="object-cover w-full lg:h-full">
                        </div>
                        {{-- <div class="flex-wrap hidden md:flex ">
                            <div class="w-1/2 p-2 sm:w-1/4" x-on:click="mainImage='{{ url('storage/'.$serverPlan->product_image)}}'">
                                <img src="{{ url('storage/'.$serverPlan->product_image) }}" alt="{{ $serverPlan->name}}"
                                    class="object-cover w-full lg:h-20 cursor-pointer hover:border hover:border-blue-500">
                        </div>
                        </div> --}}
                        <div class="px-6 pb-6 mt-6 border-t border-accent-yellow dark:border-green-400">
                            <div class="flex flex-wrap items-center mt-6">
                                <span class="mr-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" class="w-4 h-4 text-white dark:text-white"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM7 14h1v1H7v-1zM14 14h1v1h-1v-1zM14 20h1v1h-1v-1zM14 17h1v1h-1v-1zM17 14h1v1h-1v-1zM17 17h1v1h-1v-1zM17 20h1v1h-1v-1zM4 14h1v1H4v-1zM4 17h1v1H4v-1zM4 20h1v1H4v-1zM7 17h1v1H7v-1zM7 20h1v1H7v-1zM10 14h1v1h-1v-1zM10 17h1v1h-1v-1zM10 20h1v1h-1v-1zM14 10h1v1h-1v-1zM14 7h1v1h-1V7zM14 4h1v1h-1V4zM17 10h1v1h-1v-1zM17 7h1v1h-1V7zM17 4h1v1h-1V4zM10 10h1v1h-1v-1zM10 7h1v1h-1V7zM10 4h1v1h-1V4zM3 10h1v1H3v-1zM3 7h1v1H3V7zM3 17h1v1H3v-1zM7 10h1v1H7v-1zM7 7h1v1H7V7zM7 4h1v1H7V4zM20 10h1v1h-1v-1zM20 7h1v1h-1V7zM20 4h1v1h-1V4zM20 14h1v1h-1v-1zM20 17h1v1h-1v-1zM20 20h1v1h-1v-1z" />
                                    </svg>
                                </span>
                                <h2 class="text-sm font-medium text-white dark:text-white">Get the config files straight to your account</h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="w-full py-6 pt-6 md:w-1/2 bg-green-900 border-2 items-center rounded-lg border-double border-yellow-600">
                    <div class="lg:pl-20">
                        <div class="mb-8 text-white dark:text-white [&>*]:mx-3 [&>ul]:list-disc [&>ul]:mx-8 [&>*]:text-white [&>*]:dark:text-white">
                            <h2 class="max-w-xl mb-6 text-2xl text-white font-bold dark:text-white md:text-4xl">{{ $serverPlan->name}}</h2>
                            @php
                            $price = $serverPlan->price;
                            $increasedPrice = $price * 1.3;

                            // Round the increased price to the nearest 0 or 5
                            $roundedIncreasedPrice = round($increasedPrice / 5) * 5;
                            @endphp

                            <p class="inline-block mb-6 text-4xl font-bold text-white dark:text-white">
                                <span>{{ Number::currency($price) }}</span>
                                <span
                                    class="text-base font-mono text-white line-through dark:text-white">{{ Number::currency($roundedIncreasedPrice) }}</span>
                            </p>
                            <p class="max-w-md text-white dark:text-white">
                                {!! Str::markdown($serverPlan->description) !!}
                            </p>
                        </div>
                        <div class="w-32 mb-8">
                            <label for=""
                                class="w-full pb-1 text-xl font-semibold text-white border-b border-yellow-600 dark:border-yellow-600 dark:text-white">Quantity</label>
                            <div class="relative flex flex-row w-full h-10 mt-6 bg-transparent rounded-lg">
                                <button wire:click='decreaseQty'
                                    class="w-20 h-full text-white bg-green-700 rounded-l outline-none cursor-pointer dark:hover:bg-yellow-600 dark:text-white hover:text-white dark:bg-green-700 hover:bg-green-400">
                                    <span class="m-auto text-2xl font-mono text-white">-</span>
                                </button>
                                <input type="number" wire:model='quantity' readonly
                                    class="flex justify-center items-center w-full font-bold text-center text-white placeholder-green-700 bg-green-400 outline-none border-green-700 dark:border-green-700 dark:text-white dark:placeholder-green-700 dark:bg-green-900 focus:outline-none text-md hover:yellow-600"
                                    placeholder="1">
                                <button wire:click='increaseQty'
                                    class="w-20 h-full text-white bg-green-700 rounded-r outline-none cursor-pointer dark:hover:bg-yellow-600 dark:text-white hover:text-white dark:bg-green-700 hover:bg-green-400">
                                    <span class="m-auto text-2xl font-mono text-white">+</span>
                                </button>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-4">
                            <button wire:click='addToCart({{$serverPlan->id}})'
                                class="w-full p-4 bg-yellow-600 rounded-md lg:w-2/5 dark:text-white text-white hover:bg-green-400 dark:bg-green-400 dark:hover:bg-green-400">
                                <span wire:loading.remove wire:target='addToCart({{$serverPlan->id}})'>Add to Cart</span>
                                <span wire:loading wire:target='addToCart({{$serverPlan->id}})'>Adding...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
