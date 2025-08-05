@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between">
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-6 py-3 text-sm font-medium text-gray-500 bg-gray-800/50 border border-gray-600 cursor-default rounded-xl">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" 
                   class="relative inline-flex items-center px-6 py-3 text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 border border-blue-400/50 rounded-xl transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-blue-500/25">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" 
                   class="relative inline-flex items-center px-6 py-3 ml-3 text-sm font-bold text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-green-400 border border-green-400/50 rounded-xl transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-green-500/25">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="relative inline-flex items-center px-6 py-3 ml-3 text-sm font-medium text-gray-500 bg-gray-800/50 border border-gray-600 cursor-default rounded-xl">
                    {!! __('pagination.next') !!}
                </span>
            @endif
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-center">
            <div class="flex items-center space-x-2">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span class="relative inline-flex items-center px-4 py-3 text-sm font-medium text-gray-500 bg-gray-800/50 border border-gray-600/50 cursor-default rounded-xl backdrop-blur-sm">
                        <x-heroicon-o-chevron-left class="w-5 h-5" />
                        <span class="ml-2">Previous</span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" 
                       class="relative inline-flex items-center px-4 py-3 text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 border border-blue-400/50 rounded-xl transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-blue-500/25 backdrop-blur-sm">
                        <x-heroicon-o-chevron-left class="w-5 h-5" />
                        <span class="ml-2">Previous</span>
                    </a>
                @endif

                {{-- Pagination Elements --}}
                <div class="flex items-center space-x-2">
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span class="relative inline-flex items-center justify-center w-12 h-12 text-sm font-medium text-gray-400 bg-gray-800/50 border border-gray-600/30 rounded-xl backdrop-blur-sm">{{ $element }}</span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="relative inline-flex items-center justify-center w-12 h-12 text-sm font-bold text-white bg-gradient-to-br from-yellow-500 to-yellow-400 border border-yellow-300/50 rounded-xl shadow-lg transform scale-110 backdrop-blur-sm">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $url }}" 
                                       class="relative inline-flex items-center justify-center w-12 h-12 text-sm font-bold text-white bg-gray-800/80 hover:bg-gradient-to-br hover:from-gray-700 hover:to-gray-600 border border-gray-600/50 hover:border-gray-500 rounded-xl transition-all duration-300 hover:scale-105 hover:shadow-lg backdrop-blur-sm">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </div>

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" 
                       class="relative inline-flex items-center px-4 py-3 text-sm font-bold text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-green-400 border border-green-400/50 rounded-xl transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-green-500/25 backdrop-blur-sm">
                        <span class="mr-2">Next</span>
                        <x-heroicon-o-chevron-right class="w-5 h-5" />
                    </a>
                @else
                    <span class="relative inline-flex items-center px-4 py-3 text-sm font-medium text-gray-500 bg-gray-800/50 border border-gray-600/50 cursor-default rounded-xl backdrop-blur-sm">
                        <span class="mr-2">Next</span>
                        <x-heroicon-o-chevron-right class="w-5 h-5" />
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
