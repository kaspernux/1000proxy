<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data
    x-init="
        const theme = localStorage.getItem('theme') ?? '{{ session('theme_mode', 'system') }}';
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (theme === 'dark' || (theme === 'system' && prefersDark)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    "
    class="antialiased"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <title>{{ $title ?? '1000 PROXIES' }}</title>
</head>
<body class="bg-slate-200 text-gray-900 dark:bg-gray-800 dark:text-gray-200">
    @livewire('partials.navbar')

    <main>
        {{ $slot }}
    </main>

    @livewire('partials.footer')

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <x-livewire-alert::scripts />
    @livewireScripts
</body>
</html>