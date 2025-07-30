@extends('layouts.app')

@section('content')

@php
    $qrPath = $getState();
    $publicPath = $qrPath ? Storage::disk('public')->url($qrPath) : null;
    $filename = basename($qrPath ?? 'qr-code.png');
@endphp


<section class="min-h-[40vh] flex items-center justify-center py-10 px-4">
    <div class="w-full max-w-xs sm:max-w-sm md:max-w-md bg-white/90 dark:bg-green-900 rounded-2xl shadow-lg p-6 flex flex-col items-center">
        @if ($publicPath)
            <figure class="flex flex-col items-center w-full">
                <img src="{{ $publicPath }}"
                     alt="QR Code"
                     class="w-24 h-24 sm:w-32 sm:h-32 object-contain rounded-lg shadow mb-4 border border-green-100 dark:border-green-800">
                <figcaption class="mb-2 text-green-700 dark:text-green-300 font-semibold text-base">Scan or Download QR Code</figcaption>
            </figure>
            <a href="{{ $publicPath }}"
               download="{{ $filename }}"
               class="inline-block mt-2 px-6 py-2 bg-gradient-to-r from-green-600 to-yellow-500 text-white font-bold rounded-lg shadow hover:from-yellow-600 hover:to-green-600 transition-all duration-200">
                Download
            </a>
        @else
            <span class="text-gray-400 italic">No QR</span>
        @endif
    </div>
</section>
@endsection
