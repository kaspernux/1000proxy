@php
    $path = $getState();
@endphp

@if ($path && file_exists(public_path('storage/' . $path)))
    <div class="text-center">
        <img src="{{ asset('storage/' . $path) }}"
             alt="QR Code"
             width="50"
             height="50"
             title="Click to download"
             style="cursor: pointer;"
             onclick="window.open('{{ asset('storage/' . $path) }}', '_blank')"
             onmouseover="this.style.boxShadow='0 0 10px rgba(0,0,0,0.5)'"
             onmouseout="this.style.boxShadow='none'" />
        <div style="margin-top: 5px;">
            <a href="{{ asset('storage/' . $path) }}"
               download
               class="text-xs text-blue-600 underline hover:text-blue-800">
                Download
            </a>
        </div>
    </div>
@else
    <span class="text-gray-400 text-xs italic">Not generated</span>
@endif
