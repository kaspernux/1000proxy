{{-- Advanced Progress Bar Component Template --}}
@php
    $id = $id ?? 'progress-' . uniqid();
    $value = $value ?? 0;
    $max = $max ?? 100;
    $min = $min ?? 0;
    $size = $size ?? 'md';
    $variant = $variant ?? 'default';
    $showPercentage = $showPercentage ?? true;
    $showValue = $showValue ?? false;
    $animated = $animated ?? true;
    $striped = $striped ?? false;
    $label = $label ?? '';
    $description = $description ?? '';
    $segments = $segments ?? [];
@endphp

<div
    x-data="progress()"
    x-init="
        value = {{ $value }};
        max = {{ $max }};
        min = {{ $min }};
        size = '{{ $size }}';
        variant = '{{ $variant }}';
        showPercentage = {{ $showPercentage ? 'true' : 'false' }};
        showValue = {{ $showValue ? 'true' : 'false' }};
        animated = {{ $animated ? 'true' : 'false' }};
        striped = {{ $striped ? 'true' : 'false' }};
        label = '{{ $label }}';
        description = '{{ $description }}';
        segments = {{ json_encode($segments) }};
    "
    class="w-full {{ $attributes->get('class', '') }}"
    {{ $attributes->except(['class']) }}
>
    {{-- Progress Label --}}
    <div x-show="label || showPercentage || showValue" class="flex justify-between items-center mb-1">
        <span x-show="label" class="text-sm font-medium text-gray-900" x-text="label"></span>
        <span x-show="showPercentage || showValue" class="text-sm text-gray-600" x-text="getDisplayText()"></span>
    </div>

    {{-- Progress Description --}}
    <div x-show="description" class="mb-2">
        <span class="text-xs text-gray-500" x-text="description"></span>
    </div>

    {{-- Progress Bar Container --}}
    <div :class="getContainerClasses()">
        {{-- Single Progress Bar --}}
        <template x-if="segments.length === 0">
            <div
                :class="getBarClasses()"
                :style="`width: ${getWidth()}`"
                role="progressbar"
                :aria-valuenow="value"
                :aria-valuemin="min"
                :aria-valuemax="max"
                :aria-valuetext="getAriaValueText()"
                :aria-label="getAriaLabel()"
            >
                {{-- Progress Text (for larger sizes) --}}
                <span :class="getTextClasses()" x-text="getDisplayText()"></span>
            </div>
        </template>

        {{-- Stacked Progress Bars --}}
        <template x-if="segments.length > 0">
            <template x-for="(segment, index) in segments" :key="segment.id || index">
                <div
                    :class="getSegmentClasses(segment, index)"
                    :style="`width: ${calculateSegmentWidth(segment)}`"
                    :title="`${segment.label}: ${segment.value}`"
                >
                    <span x-show="segment.showText !== false" class="text-xs font-medium" x-text="segment.label"></span>
                </div>
            </template>
        </template>
    </div>

    {{-- Progress Status Text --}}
    <div x-show="isComplete()" class="mt-1 flex items-center text-sm text-green-600">
        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
        <span>Complete</span>
    </div>

    {{-- Indeterminate Progress (for loading states) --}}
    <template x-if="value < 0">
        <div :class="getContainerClasses()">
            <div class="h-full bg-blue-600 rounded-full animate-pulse"></div>
        </div>
    </template>

    {{-- Additional Content Slot --}}
    {{ $slot }}
</div>

{{-- Utility Methods for Alpine Component --}}
<script>
// Add utility functions to window for progress calculations
window.progressUtils = {
    clamp: (value, min, max) => Math.min(Math.max(value, min), max),
    percentage: (value, min, max) => {
        if (max === min) return 0;
        return ((value - min) / (max - min)) * 100;
    },
    formatBytes: (bytes, decimals = 2) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
};
</script>

{{-- Example Usage in Comments:
<!--
<!-- Basic Progress Bar -->
<x-ui.progress
    :value="75"
    :max="100"
    label="Upload Progress"
    size="md"
    variant="success"
    :show-percentage="true"
    :animated="true"
/>

<!-- Stacked Progress Bar -->
<x-ui.progress
    label="Server Resources"
    :segments="[
        ['id' => 'cpu', 'label' => 'CPU', 'value' => 60, 'color' => 'bg-blue-600'],
        ['id' => 'memory', 'label' => 'RAM', 'value' => 30, 'color' => 'bg-green-600'],
        ['id' => 'disk', 'label' => 'Disk', 'value' => 10, 'color' => 'bg-yellow-600']
    ]"
/>

<!-- File Upload Progress -->
<x-ui.progress
    x-data="{ uploadProgress: 0 }"
    :value="uploadProgress"
    label="Uploading files..."
    variant="info"
    size="lg"
    :striped="true"
    :animated="true"
/>
-->
--}}
