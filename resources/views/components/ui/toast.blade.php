{{-- Advanced Toast Notification Component Template --}}
@php
    $position = $position ?? 'top-right';
    $maxNotifications = $maxNotifications ?? 5;
    $defaultDuration = $defaultDuration ?? 5000;
    $allowDismiss = $allowDismiss ?? true;
    $showProgress = $showProgress ?? true;
@endphp

<div
    x-data="toast()"
    x-init="
        position = '{{ $position }}';
        maxNotifications = {{ $maxNotifications }};
        defaultDuration = {{ $defaultDuration }};
        allowDismiss = {{ $allowDismiss ? 'true' : 'false' }};
        showProgress = {{ $showProgress ? 'true' : 'false' }};
    "
    {{ $attributes }}
>
    {{-- Toast Container --}}
    <div :class="getContainerClasses()">
        <template x-for="notification in notifications" :key="notification.id">
            <div
                :class="getNotificationClasses(notification)"
                @mouseenter="pauseTimer(notification)"
                @mouseleave="resumeTimer(notification)"
                :aria-live="getAriaLive(notification)"
                :aria-label="getAriaLabel(notification)"
                role="alert"
            >
                {{-- Notification Content --}}
                <div class="flex items-start p-4">
                    {{-- Icon --}}
                    <div class="flex-shrink-0">
                        <div :class="getIconClasses(notification)">
                            <span x-text="getIcon(notification)" class="text-lg"></span>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="ml-3 flex-1">
                        {{-- Title --}}
                        <h3 x-show="notification.title" class="text-sm font-medium" x-text="notification.title"></h3>

                        {{-- Message --}}
                        <div class="text-sm" :class="notification.title ? 'mt-1 text-gray-600' : 'text-gray-900'">
                            <span x-show="!notification.html" x-text="notification.message"></span>
                            <div x-show="notification.html" x-html="notification.message"></div>
                        </div>

                        {{-- Actions --}}
                        <div x-show="notification.actions && notification.actions.length > 0" class="mt-3 flex gap-2">
                            <template x-for="action in notification.actions" :key="action.label">
                                <button
                                    @click="handleAction(notification, action)"
                                    class="text-sm font-medium text-blue-600 hover:text-blue-500 focus:outline-none focus:underline"
                                    x-text="action.label"
                                ></button>
                            </template>
                        </div>

                        {{-- Timestamp --}}
                        <div x-show="notification.timestamp" class="mt-2 text-xs text-gray-400">
                            <span x-text="formatTimestamp(notification.timestamp)"></span>
                        </div>
                    </div>

                    {{-- Dismiss Button --}}
                    <div x-show="allowDismiss" class="ml-4 flex-shrink-0">
                        <button
                            @click="dismiss(notification.id)"
                            class="inline-flex text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-lg p-1"
                            aria-label="Dismiss notification"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div x-show="showProgress && notification.duration > 0 && !notification.persistent">
                    <div :class="getProgressClasses(notification)" :style="`width: ${notification.progress}%`"></div>
                </div>
            </div>
        </template>
    </div>

    {{-- Global Dismiss All Button (when multiple notifications) --}}
    <div x-show="notifications.length > 1" :class="getContainerClasses()">
        <div class="pointer-events-auto mb-2">
            <button
                @click="dismissAll()"
                class="w-full bg-gray-900 text-white text-sm font-medium py-2 px-4 rounded-lg shadow-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
            >
                Dismiss All (<span x-text="notifications.length"></span>)
            </button>
        </div>
    </div>
</div>

{{-- Toast Notification Styles --}}
<style>
/* Additional styles for toast animations */
.toast-enter {
    transform: translateX(100%);
    opacity: 0;
}

.toast-enter-active {
    transition: all 0.3s ease-out;
}

.toast-enter-to {
    transform: translateX(0);
    opacity: 1;
}

.toast-leave {
    transform: translateX(0);
    opacity: 1;
}

.toast-leave-active {
    transition: all 0.3s ease-in;
}

.toast-leave-to {
    transform: translateX(100%);
    opacity: 0;
}
</style>

{{-- Example Usage in Comments:
<!--
<!-- Basic Toast Container (place once in layout) -->
<x-ui.toast
    position="top-right"
    :max-notifications="5"
    :default-duration="5000"
    :allow-dismiss="true"
    :show-progress="true"
/>

<!-- JavaScript Usage Examples -->
<script>
// Show different types of notifications
showSuccess('Operation completed successfully!');
showError('Something went wrong. Please try again.');
showWarning('This action cannot be undone.');
showInfo('New update available.');

// Advanced notification with actions
showNotification('info', 'Update available', {
    title: 'System Update',
    duration: 0, // Persistent
    actions: [
        { label: 'Update Now', handler: () => window.location.reload() },
        { label: 'Later', dismiss: true }
    ]
});

// Custom notification
showNotification('success', 'File uploaded successfully', {
    title: 'Upload Complete',
    icon: 'check-circle',
    duration: 3000,
    html: true // Allow HTML in message
});
</script>
-->
--}}
