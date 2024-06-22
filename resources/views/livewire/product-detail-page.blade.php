<div
    class="w-full bg-gradient-to-r from-green-900 to-green-600 container max-w-auto py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    <section
        class="overflow-hidden container mx-auto px-4 max-w-7xl border-2 rounded-lg border-double border-yellow-600 py-11 font-mono dark:bg-green-400">
        <div class="max-w-6xl px-4 py-4 mx-auto lg:py-8 md:px-6">
            <div class="flex flex-wrap -mx-4">
                <div class=" w-full mb-8 md:w-1/2 md:mb-0 relative"
                    x-data="{ mainImage: '{{url('storage/'.$serverPlan->product_image)}}' }">
                    <div class="top-0 z-50 py-6 items-center">
                        <div class=" mb-6 lg:mb-10 lg:h-2/4">
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
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white"
                                        class="w-4 h-4 text-white dark:text-white bi bi-truck"
                                        viewBox="0 0 16 16">
                                        <path
                                            d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5v-7zm1.294 7.456A1.999 1.999 0 0 1 4.732 11h5.536a2.01 2.01 0 0 1 .732-.732V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .294.456zM12 10a2 2 0 0 1 1.732 1h.768a.5.5 0 0 0 .5-.5V8.35a.5.5 0 0 0-.11-.312l-1.48-1.85A.5.5 0 0 0 13.02 6H12v4zm-9 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm9 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2z">
                                        </path>
                                    </svg>
                                </span>
                                <h2 class="text-lg font-bold text-white dark:text-white">Free Shipping</h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="w-full py-6 pt-6 md:w-1/2 bg-green-900 border-2 items-center rounded-lg border-double border-yellow-600">
                    <div class="lg:pl-20">
                        <div class="mb-8">
                            <h2 class="max-w-xl mb-6 text-2xl text-white font-bold dark:text-white md:text-4xl">{{ $serverPlan->name}}</h2>
                            <p class="inline-block mb-6 text-4xl font-bold text-white dark:text-white">
                                <span>{{Number::currency($serverPlan->price)}}</span>
                                <span
                                    class="text-base font-mono text-white line-through dark:text-white">{{Number::currency($serverPlan->price)}}</span>
                            </p>
                            <p class="max-w-md text-white dark:text-white">
                                {{$serverPlan->description}}
                            </p>
                        </div>
                        <div class="w-32 mb-8">
                            <label for=""
                                class="w-full pb-1 text-xl font-semibold text-white border-b border-yellow-600 dark:border-yellow-600 dark:text-white">Quantity</label>
                            <div class="relative flex flex-row w-full h-10 mt-6 bg-transparent rounded-lg">
                                <button
                                    class="w-20 h-full text-white bg-green-700 rounded-l outline-none cursor-pointer dark:hover:bg-yellow-600 dark:text-white hover:text-white dark:bg-green-700 hover:bg-green-400">
                                    <span class="m-auto text-2xl font-mono text-white">-</span>
                                </button>
                                <input type="number" readonly
                                    class="flex justify-center items-center w-full font-bold text-center text-white placeholder-green-700 bg-green-400 outline-none dark:text-white dark:placeholder-green-700 dark:bg-green-900 focus:outline-none text-md hover:yellow-600"
                                    placeholder="1">
                                <button
                                    class="w-20 h-full text-white bg-green-700 rounded-r outline-none cursor-pointer dark:hover:bg-yellow-600 dark:text-white hover:text-white dark:bg-green-700 hover:bg-green-400">
                                    <span class="m-auto text-2xl font-mono text-white">+</span>
                                </button>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-4">
                            <button
                                class="w-full p-4 bg-yellow-600 rounded-md lg:w-2/5 dark:text-white text-white hover:bg-green-400 dark:bg-green-400 dark:hover:bg-green-400">Add
                                to cart</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
