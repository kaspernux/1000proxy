@props([
    'name' => null,
    'set' => 'o', // o = outline, s = solid, m = mini
    'class' => 'w-5 h-5'
])
{{--
  Generic icon proxy component.
  Usage: <x-custom-icon name="bolt" class="w-4 h-4 text-yellow-400" />
  Delegates to blade-heroicons. Falls back to placeholder if not found.
--}}
@php
    $normalized = is_string($name) ? strtolower(str_replace('_', '-', $name)) : '';
    $component = 'heroicon-' . $set . '-' . $normalized; // e.g. heroicon-o-bolt
    $componentExists = \Illuminate\Support\Facades\Blade::getClassComponentAliases()[$component] ?? false;
@endphp
@if($componentExists)
    <x-dynamic-component :component="$component" {{ $attributes->merge(['class' => $class]) }} />
@else
    <svg {{ $attributes->merge(['class' => $class . ' text-gray-400 dark:text-gray-500']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="10" class="opacity-25" />
        <text x="12" y="16" text-anchor="middle" font-size="10" class="font-medium select-none">{{ substr($normalized,0,2) ?: '?' }}</text>
    </svg>
@endif
