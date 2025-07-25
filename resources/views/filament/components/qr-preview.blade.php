@props([
    'image' => null,
    'label' => 'QR Code',
    'record' => null,
])

@php
    $imagePath = $record?->{$image} ?? null;
@endphp

@if ($imagePath)
    <div class="qr-preview">
        <div class="text-sm font-semibold text-gray-600">{{ $label }}</div>
        <a href="{{ asset('storage/' . $imagePath) }}" target="_blank">
            <img src="{{ asset('storage/' . $imagePath) }}"
                 alt="{{ $label }}"
                 class="h-32 w-32 object-contain border border-gray-300 rounded hover:scale-105 transition-transform" />
        </a>
        <a href="{{ asset('storage/' . $imagePath) }}"
           download
           class="text-sm text-blue-500 hover:underline mt-1">
            Download
        </a>
    </div>
@else
    <div class="text-xs text-gray-400 italic">QR code not available</div>
@endif


<!-- @once
    @push('styles')
        <style>
            .qr-preview {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                background-color: white;
                border: 1px solid #e5e7eb;
                padding: 1rem;
                border-radius: 0.375rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }
        </style>
    @endpush
@endonce
@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const qrPreview = document.querySelector('.qr-preview');
                if (qrPreview) {
                    qrPreview.addEventListener('click', function () {
                        this.classList.toggle('active');
                    });
                }
            });
        </script>
    @endpush
@endonce

 -->
