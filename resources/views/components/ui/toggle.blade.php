{{-- Advanced Toggle Switch Component Template --}}
@php
    $id = $id ?? 'toggle-' . uniqid();
    $size = $size ?? 'md';
    $variant = $variant ?? 'default';
    $labelOn = $labelOn ?? 'On';
    $labelOff = $labelOff ?? 'Off';
    $showLabels = $showLabels ?? true;
    $checked = $checked ?? false;
    $disabled = $disabled ?? false;
    $loading = $loading ?? false;
    $description = $description ?? '';
    $name = $name ?? '';
@endphp

<div
    x-data="toggle()"
    x-init="
        checked = {{ $checked ? 'true' : 'false' }};
        disabled = {{ $disabled ? 'true' : 'false' }};
        loading = {{ $loading ? 'true' : 'false' }};
        size = '{{ $size }}';
        variant = '{{ $variant }}';
        showLabels = {{ $showLabels ? 'true' : 'false' }};
        labelOn = '{{ $labelOn }}';
        labelOff = '{{ $labelOff }}';
        description = '{{ $description }}';
    "
    class="flex items-center {{ $attributes->get('class', '') }}"
    {{ $attributes->except(['class']) }}
>
    {{-- Hidden Input for Form Submission --}}
    @if($name)
        <input type="hidden" :name="'{{ $name }}'" :value="checked ? '1' : '0'">
    @endif

    {{-- Toggle Switch --}}
    <button
        type="button"
        @click="toggle()"
        @keydown.space.prevent="toggle()"
        :class="getSwitchClasses()"
        :aria-checked="checked"
        :aria-label="getAriaLabel()"
        :disabled="disabled || loading"
        role="switch"
    >
        {{-- Toggle Thumb --}}
        <span :class="getThumbClasses()">
            {{-- Loading Spinner --}}
            <template x-if="loading">
                <svg :class="getIconSize()" class="animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </template>

            {{-- Success Icon (when checked and not loading) --}}
            <template x-if="checked && !loading && variant === 'success'">
                <svg :class="getIconSize()" class="text-green-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </template>

            {{-- Error Icon (when checked and variant is danger) --}}
            <template x-if="checked && !loading && variant === 'danger'">
                <svg :class="getIconSize()" class="text-red-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </template>

            {{-- Warning Icon (when checked and variant is warning) --}}
            <template x-if="checked && !loading && variant === 'warning'">
                <svg :class="getIconSize()" class="text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </template>

            {{-- Info Icon (when checked and variant is info) --}}
            <template x-if="checked && !loading && variant === 'info'">
                <svg :class="getIconSize()" class="text-cyan-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </template>
        </span>
    </button>

    {{-- Labels --}}
    <div x-show="showLabels" class="ml-3 flex flex-col">
        <span class="text-sm font-medium text-gray-900" x-text="checked ? labelOn : labelOff"></span>
        <span x-show="description" class="text-xs text-gray-500" x-text="description"></span>
    </div>
</div>
