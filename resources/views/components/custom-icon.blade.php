@props([
    'name',
    'size' => 'w-6 h-6',
    'class' => '',
    'stroke' => 'currentColor',
    'fill' => 'none'
])

@php
$iconClass = "inline-block {$size} {$class}";
@endphp

@switch($name)
    @case('chevron-down')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
        @break

    @case('chevron-up')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
        </svg>
        @break

    @case('globe-alt')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3s-4.5 4.03-4.5 9 2.015 9 4.5 9Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m0 0 4.5-9-4.5-9-4.5 9 4.5 9Z" />
        </svg>
        @break

    @case('folder')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25H11.69Z" />
        </svg>
        @break

    @case('building-office')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15l-.75 18H5.25L4.5 3ZM9 9h1.5M9 12h1.5M9 15h1.5M13.5 9H15M13.5 12H15M13.5 15H15" />
        </svg>
        @break

    @case('funnel')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
        </svg>
        @break

    @case('magnifying-glass')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        @break

    @case('server')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 17.25v-.228a4.5 4.5 0 0 0-.12-1.03l-2.268-9.64a3.375 3.375 0 0 0-3.285-2.602H7.923a3.375 3.375 0 0 0-3.285 2.602l-2.268 9.64a4.5 4.5 0 0 0-.12 1.03v.228m19.5 0a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3m19.5 0a3 3 0 0 0-3-3H5.25a3 3 0 0 0-3 3m16.5 0h.008v.008h-.008V17.25Zm-3 0h.008v.008h-.008V17.25Z" />
        </svg>
        @break

    @case('shield-check')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.623A11.99 11.99 0 0 0 20.402 6 11.959 11.959 0 0 1 12 2.75Z" />
        </svg>
        @break

    @case('bolt')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
        </svg>
        @break

    @case('shopping-cart')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
        </svg>
        @break

    @case('user')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
        </svg>
        @break

    @case('heart')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
        </svg>
        @break

    @case('star')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
        </svg>
        @break

    @case('check-circle')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        @break

    @case('x-circle')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        @break

    @case('arrow-right')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
        </svg>
        @break

    @case('flag')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71L21 15.3M3 14.25l2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71L21 13.8M3 8.688l2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71L21 8.25M3 5.625l2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71L21 5.1" />
        </svg>
        @break

    @case('cog-6-tooth')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a6.759 6.759 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
        @break

    @case('clock')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        @break

    @case('chart-bar')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
        </svg>
        @break

    @case('credit-card')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
        </svg>
        @break

    @case('document-text')
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
        </svg>
        @break

    @default
        <svg {{ $attributes->merge(['class' => $iconClass]) }} fill="{{ $fill }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $stroke }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
        </svg>
@endswitch
