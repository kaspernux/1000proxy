@php
    $qrPath = $getState();
    $publicPath = $qrPath ? Storage::disk('public')->url($qrPath) : null;
    $filename = basename($qrPath ?? 'qr-code.png');
@endphp

@if ($publicPath)
    <div class="text-center">
        <img src="{{ $publicPath }}" style="height:60px; width:60px; margin: 0 auto; border-radius: 6px;">
        <br>
        <a href="{{ $publicPath }}" download="{{ $filename }}" class="text-sm text-primary hover:underline">
            Download
        </a>
    </div>
@else
    <span class="text-gray-400 italic">No QR</span>
@endif
