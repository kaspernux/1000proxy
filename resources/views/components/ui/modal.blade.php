{{-- Advanced Modal Component Template --}}
@php
    $id = $id ?? 'modal-' . uniqid();
    $size = $size ?? 'md';
    $position = $position ?? 'center';
    $animation = $animation ?? 'fade';
    $backdrop = $backdrop ?? 'blur';
    $title = $title ?? '';
    $description = $description ?? '';
    $showCloseButton = $showCloseButton ?? true;
    $allowBackdropClose = $allowBackdropClose ?? true;
    $allowEscapeClose = $allowEscapeClose ?? true;
    $preventScroll = $preventScroll ?? true;
@endphp

<div
    x-data="modal()"
    x-init="
        size = '{{ $size }}';
        position = '{{ $position }}';
        animation = '{{ $animation }}';
        backdrop = '{{ $backdrop }}';
        title = '{{ $title }}';
        description = '{{ $description }}';
        showCloseButton = {{ $showCloseButton ? 'true' : 'false' }};
        allowBackdropClose = {{ $allowBackdropClose ? 'true' : 'false' }};
        allowEscapeClose = {{ $allowEscapeClose ? 'true' : 'false' }};
        preventScroll = {{ $preventScroll ? 'true' : 'false' }};
    "
    {{ $attributes }}
>
    {{-- Trigger Button (if provided) --}}
    {{ $trigger ?? '' }}

    {{-- Modal Overlay --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        :class="getOverlayClasses()"
        @click="handleBackdropClick($event)"
    >
        {{-- Modal Container --}}
        <div
            x-ref="modal"
            :class="getModalClasses()"
            @click.stop
            role="dialog"
            :aria-labelledby="title ? 'modal-title' : null"
            :aria-describedby="description ? 'modal-description' : null"
            aria-modal="true"
        >
            {{-- Modal Header --}}
            <div x-show="hasHeader()" x-ref="header" class="flex items-center justify-between p-6 border-b border-gray-200">
                <div class="flex-1">
                    <h3 x-show="title" id="modal-title" class="text-lg font-semibold text-gray-900" x-text="title"></h3>
                    <p x-show="description" id="modal-description" class="mt-1 text-sm text-gray-600" x-text="description"></p>
                </div>

                {{-- Close Button --}}
                <button
                    x-show="showCloseButton"
                    @click="close()"
                    type="button"
                    class="ml-4 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-lg p-1"
                    aria-label="Close modal"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                {{-- Custom Header Content --}}
                {{ $header ?? '' }}
            </div>

            {{-- Modal Body --}}
            <div class="flex-1 overflow-y-auto">
                <div class="p-6">
                    {{ $slot }}
                </div>
            </div>

            {{-- Modal Footer --}}
            <div x-show="hasFooter()" x-ref="footer" class="flex items-center justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50">
                {{ $footer ?? '' }}
            </div>
        </div>
    </div>
</div>

{{-- Example Usage in Comments:
<!--
<x-ui.modal
    size="lg"
    position="center"
    animation="scale"
    backdrop="blur"
    title="Confirm Action"
    description="Are you sure you want to proceed?"
    :show-close-button="true"
>
    <x-slot name="trigger">
        <button @click="open()" class="btn btn-primary">Open Modal</button>
    </x-slot>

    <div class="space-y-4">
        <p>Modal content goes here...</p>
    </div>

    <x-slot name="footer">
        <button @click="close()" class="btn btn-secondary">Cancel</button>
        <button @click="close()" class="btn btn-primary">Confirm</button>
    </x-slot>
</x-ui.modal>
-->
--}}
