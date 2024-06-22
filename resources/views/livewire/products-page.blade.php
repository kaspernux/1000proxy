<div class="w-full font-mono bg-gradient-to-r from-green-900 to-green-600 max-w-auto py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    <section class="py-10 bg-gradient-to-r from-green-900 to-green-600 font-mono rounded-lg">
        <div class="px-4 py-4 mx-auto max-w-7xl lg:py-6 md:px-6">
            <div class="flex flex-wrap mb-24 -mx-3">
                <div class="w-full pr-2 lg:w-1/4 lg:block">

                    <div class="p-4 mb-5 border-2 bg-green-900 border-double rounded-lg border-yellow-600 dark:border-yellow-600 dark:bg-green-900">
                        <h2 class="text-2xl text-white font-bold dark:text-green-400"> Category</h2>
                        <div class="w-16 pb-2 mb-6 border-b border-yellow-600 dark:border-green-400"></div>
                        <ul>
                            @foreach($categories as $serverCategory)
                            <li class="mb-4" wire:key={{$serverCategory->id}}>
                                <label for="{{$serverCategory->slug}}" class="flex items-center dark:text-green-400 ">
                                    <input type="checkbox" id="{{$serverCategory->slug}}" value="{{$serverCategory->id}}" class="w-4 h-4 mr-2 border-2 border-double rounded-lg border-yellow-600 dark:border-yellow-600">
                                    <span class="text-lg text-white uppercase">{{$serverCategory->name}}</span>
                                </label>
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    <div
                        class="p-4 mb-5 border-2 bg-green-900 border-double rounded-lg border-yellow-600 dark:border-yellow-600 dark:bg-green-900">
                        <h2 class="text-2xl font-bold text-white dark:text-green-400">Brand</h2>
                        <div class="w-16 pb-2 mb-6 border-b border-yellow-600 dark:border-green-400"></div>
                        <ul>
                            @foreach($brands as $serverBrand)
                            <li class="mb-4" wire:key={{$serverBrand->id}}>
                                <label for="{{$serverBrand->slug}}" class="flex items-center dark:text-green-400">
                                    <input type="checkbox" id="{{$serverBrand->slug}}" value="{{$serverBrand->id}}"
                                        class="w-4 h-4 mr-2 border-2 border-double rounded-lg border-yellow-600 dark:border-yellow-600">
                                    <span class="text-lg text-white uppercase">{{$serverBrand->name}}</span>
                                </label>
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    <div
                        class="p-4 mb-5 border-2 bg-green-900 border-double rounded-lg border-yellow-600 dark:border-yellow-600 dark:bg-green-900">
                        <h2 class="text-2xl font-bold text-white dark:text-green-400">Location</h2>
                        <div class="w-16 pb-2 mb-6 border-b border-yellow-600 dark:border-green-400"></div>
                        <ul>
                            @foreach($servers as $server)
                            <li class="mb-4" wire:key={{$server->id}}>
                                <label for="{{$server->country}}" class="flex items-center dark:text-green-400">
                                    <input type="checkbox" id="{{$server->country}}" value="{{$server->id}}"
                                        class="w-4 h-4 mr-2 border-2 border-double rounded-lg border-yellow-600 dark:border-yellow-600">
                                    <span class="text-lg text-white uppercase">{{$server->country}}</span>
                                </label>
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    <div
                        class="p-4 mb-5 border-2 bg-green-900 border-double rounded-lg border-yellow-600 dark:border-yellow-600 dark:bg-green-900">
                        <h2 class="text-2xl font-bold text-white dark:text-green-400">Product Status</h2>
                        <div class="w-16 pb-2 mb-6 border-b border-yellow-600 dark:border-green-400"></div>
                        <ul>
                            <li class="mb-4">
                                <label for="" class="flex items-center dark:text-gray-300">
                                    <input type="checkbox"
                                        class="w-4 h-4 mr-2 border-2 border-double rounded-lg border-yellow-600 dark:border-yellow-600">
                                    <span class="text-lg text-white dark:text-green-400">In Stock</span>
                                </label>
                            </li>
                            <li class="mb-4">
                                <label for="" class="flex items-center dark:text-gray-300">
                                    <input type="checkbox"
                                        class="w-4 h-4 mr-2 border-2 border-double rounded-lg border-yellow-600 dark:border-yellow-600">
                                    <span class="text-lg text-white dark:text-green-400">On Sale</span>
                                </label>
                            </li>
                        </ul>
                    </div>

                    <div
                        class="p-4 mb-5 bg-green-900 border-2 border-double rounded-lg border-yellow-600 dark:border-yellow-600 dark:bg-green-900">
                        <h2 class="text-2xl font-bold text-white dark:text-green-400">Price</h2>
                        <div class="w-16 pb-2 mb-6 border-b border-yellow-600 dark:border-green-400"></div>
                        <div>
                            <input type="range" class="w-full h-1 mb-4 bg-yellow-600 rounded appearance-none cursor-pointer" max="200"
                                value="1" step="1">
                            <div class="flex justify-between ">
                                <span class="inline-block text-lg font-bold text-white ">$ 1</span>
                                <span class="inline-block text-lg font-bold text-white ">$ 200</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-full px-3 lg:w-3/4">
                    <div class="px-3 mb-4">
                        <div
                            class="items-center justify-between hidden px-4 py-4 md:flex border-2 border-double rounded-lg border-yellow-600 dark:border-yellow-600 ">
                            <div class="flex items-center justify-between ">
                                <select name="" id=""
                                    class="w-auto block text-white bg-green-900 border-double rounded-lg hover:bg-accent-yellow cursor-pointer dark:text-green-400 dark:bg-green-900">
                                    <option value="">Sort by latest</option>
                                    <option value="">Sort by Price</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center ">
                        @foreach($serverPlans as $serverPlan)
                        <div class="w-full px-3 mb-6 sm:w-1/2 md:w-1/3" wire:key={{$serverPlan->id}}>
                            <div class="group flex flex-col border-double rounded-lg border-2 border-yellow-600">
                                <div class="bg-green-900 flex justify-center items-center  overflow-hidden">
                                    <a wire:navigate href="/servers/{{$serverPlan->slug}}" class="py-10 h-[16rem] w-auto">
                                        <img src="{{ url('storage/'.$serverPlan->product_image) }}" alt="{{$serverPlan->name}}"
                                            class="h-full w-full object-contain">
                                    </a>
                                </div>
                                <div class="p-3 hover:text-green-900">
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <h3 class="text-xl font-mono font-bold text-white dark:text-white">
                                            {{$serverPlan->name}}
                                        </h3>
                                    </div>
                                    <p class="text-lg hover:after:text-white">
                                        <span
                                            class="text-yellow-600 dark:text-yellow-600 text-xl">{{Number::currency($serverPlan->price)}}</span>
                                    </p>
                                </div>
                                <div
                                    class="flex justify-center p-4 border-t border-yellow-600 hover:bg-yellow-600 dark:bg-dark-green dark:focus:ring-green-600 dark:border-yellow-600 shadow-sm hover:shadow-md transition dark:focus:outline-none dark:focus:ring-1 disabled:opacity-50 disabled:pointer-events-none">
                                    <a href="#" class="text-white flex items-center space-x-2 dark:text-white hover:text-green-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="w-4 h-4 bi bi-cart3" viewBox="0 0 16 16">
                                            <path
                                                d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l.84 4.479 9.144-.459L13.89 4H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z">
                                            </path>
                                        </svg>
                                        <span>Add to Cart</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- pagination start -->
                    <div class="flex justify-end mt-6">
                        {{ $serverPlans->links()}}
                    </div>
                    <!-- pagination end -->
                </div>
            </div>
        </div>
    </section>

</div>
