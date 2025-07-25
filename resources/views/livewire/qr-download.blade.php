@php
    $qrPath = $getState();
    $publicPath = $qrPath ? Storage::disk('public')->url($qrPath) : null;
    $filename = basename($qrPath ?? 'qr-code.png');
@endphp

@if ($publicPath)
    <div class="flex flex-col items-center justify-center text-center space-y-2">
        <img src="{{ $publicPath }}"
             alt="QR Code"
             class="w-16 h-16 sm:w-20 sm:h-20 object-contain rounded-md shadow-sm">
        <a href="{{ $publicPath }}"
           download="{{ $filename }}"
           class="text-sm sm:text-base text-blue-600 hover:underline font-medium">
            Download
        </a>
    </div>
@else
    <span class="text-gray-400 italic">No QR</span>
@endif
