<div class="w-full bg-gradient-to-r font-mono from-green-900 to-green-600 py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        <div class="grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4 sm:gap-6">
            @foreach($categories as $serverCategory)
            <a class="group flex flex-col justify-center items-left border-transparent bg-dark-green hover:bg-green-400 hover:text-green-900 disabled:opacity-50 disabled:pointer-events-none dark:focus:ring-green-600 border shadow-sm rounded-xl hover:shadow-md transition dark:bg-dark-green dark:border-gray-800 dark:focus:outline-none dark:focus:ring-1"
                href="/servers?selected_categories[0]={{ $serverCategory->id }}" wire:key="{{ $serverCategory->id }}">
                <div class="p-4 md:p-5">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <img class="h-[5rem] w-[5rem]" src="{{ url('storage/'.$serverCategory->image) }}"
                                alt="{{ $serverCategory->name }}">
                            <div class="ms-3">
                                <h3
                                    class="group-hover:text-green-700 text-2xl px-6 font-semibold text-white dark:group-hover:text-green-700 dark:text-green-400">
                                    {{ $serverCategory->name }}
                                </h3>
                            </div>
                        </div>
                        <div class="ps-3">
                            <svg class="flex-shrink-0 w-5 h-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
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
