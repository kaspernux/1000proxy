@php
    $qrPath = $getState();
    $publicPath = $qrPath ? Storage::disk('public')->url($qrPath) : null;
    $filename = basename($qrPath ?? 'qr-code.png');
@endphp

<section class="min-h-[40vh] flex items-center justify-center py-10 px-4 relative overflow-hidden">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-20 -right-16 w-40 h-40 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-full animate-pulse"></div>
        <div class="absolute -bottom-16 -left-16 w-32 h-32 bg-gradient-to-tr from-yellow-500/20 to-green-500/20 rounded-full animate-bounce" style="animation-duration: 3s;"></div>
    </div>

    <div class="w-full max-w-xs sm:max-w-sm md:max-w-md bg-white/10 backdrop-blur-md rounded-2xl shadow-2xl p-6 flex flex-col items-center border border-white/20 hover:shadow-3xl transition-all duration-500 relative z-10">
        @if ($publicPath)
            <figure class="flex flex-col items-center w-full group">
                <div class="relative">
                    <img src="{{ $publicPath }}"
                         alt="QR Code"
                         class="w-24 h-24 sm:w-32 sm:h-32 object-contain rounded-lg shadow-lg mb-4 border border-white/30 group-hover:scale-110 transition-transform duration-300">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </div>
                <figcaption class="mb-2 text-white font-semibold text-base">Scan or Download QR Code</figcaption>
            </figure>
            <a href="{{ $publicPath }}"
               download="{{ $filename }}"
               class="inline-block mt-2 px-6 py-3 bg-gradient-to-r from-green-600 to-blue-600 hover:from-blue-600 hover:to-green-600 text-white font-bold rounded-lg shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Download
            </a>
        @else
            <div class="flex flex-col items-center">
                <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-lg bg-gradient-to-br from-gray-500/20 to-gray-600/20 flex items-center justify-center border border-white/30 mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <span class="text-gray-400 italic">No QR Code Available</span>
            </div>
        @endif
    </div>
</section>
