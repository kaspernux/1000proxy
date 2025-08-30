<?php

namespace App\Livewire\Traits;

use Jantinnerezo\LivewireAlert\LivewireAlert as LivewireAlertService;

/**
 * Adapter trait to keep $this->alert() API compatible with legacy usage,
 * while delegating to the Livewire Alert v4 service (fluent builder).
 */
trait LivewireAlertV4
{
    /**
     * Show a SweetAlert2 alert via Livewire Alert v4.
     *
     * @param string $type   success|error|warning|info|question
     * @param string $title  Main message/title
     * @param array  $options Key-value SweetAlert2 options (subset mapped; others passed via withOptions)
     */
    public function alert(string $type, string $title, array $options = []): void
    {
        // Build a normalized payload and dispatch a single browser 'swal' event.
        // We intentionally avoid calling the LivewireAlert service methods here
        // to prevent that package from rendering its own UI; the global layout
        // now owns presentation (Swal + stacked toast) and we must send only
        // one event to it.
        try {
            $payload = array_merge([
                'icon' => strtolower($type),
                'title' => $title,
            ], $options);

            // Normalize some known option names to SweetAlert2 expectations
            if (isset($options['text']) && is_string($options['text'])) {
                $payload['text'] = $options['text'];
            }
            if (isset($options['html']) && is_string($options['html'])) {
                $payload['html'] = $options['html'];
            }
            if (array_key_exists('timer', $options)) {
                $payload['timer'] = is_null($options['timer']) ? null : (int) $options['timer'];
            }

            // Ensure `text` is always present so client toasts can display a message.
            if (!array_key_exists('text', $payload) || is_null($payload['text'])) {
                // Prefer any 'message' key if present, otherwise empty string
                $payload['text'] = $payload['message'] ?? '';
            }

            // Also ensure `message` exists (some clients look for `message`)
            if (!array_key_exists('message', $payload) || is_null($payload['message'])) {
                // If no explicit message/text provided, fall back to the title so
                // toast UIs that expect a `message` still show readable content.
                $payload['message'] = $payload['text'] ?? $payload['title'] ?? '';
            }

            // Explicitly include toast flag when provided; default to true for
            // $this->alert() invocations that are commonly used for toasts.
            if (!array_key_exists('toast', $payload)) {
                $payload['toast'] = $options['toast'] ?? true;
            }

            // Normalize icon to a simple string or null so clients can display it.
            if (isset($payload['icon']) && $payload['icon'] !== null) {
                $payload['icon'] = (string) $payload['icon'];
            } else {
                $payload['icon'] = $payload['icon'] ?? null;
            }

            // Dispatch as a vendor-shaped 'alert' browser event so the
            // included Livewire Alert client-side adapter (or our layout
            // interception) can normalize and render it. This preserves
            // compatibility with existing LivewireAlert flows while still
            // routing presentation through our unified UI.
            $vendorDetail = [
                // `message` is the top-level string livewire-alert expects as the
                // title; `options` must avoid a `message` key because
                // SweetAlert2 does not recognize it (causes console warnings).
                'message' => $payload['title'] ?? '',
                'type' => $payload['icon'] ?? null,
                'options' => array_filter(array_merge($payload, []), function($k) { return $k !== 'message'; }, ARRAY_FILTER_USE_KEY) ,
                // Vendor script also reads `events` and `data` properties
                'events' => $payload['events'] ?? [],
                'data' => $payload['data'] ?? [],
            ];

            // Use Livewire's dispatchBrowserEvent to emit a DOM CustomEvent
            // that the vendor `livewire-alert` script listens for.
            if (method_exists($this, 'dispatchBrowserEvent')) {
                $this->dispatchBrowserEvent('alert', $vendorDetail);
            } elseif (method_exists($this, 'dispatch')) {
                // Fallback to older Livewire dispatch signature if available
                $this->dispatch('alert', $vendorDetail);
            }
        } catch (\Throwable $e) {
            // Silently ignore to avoid breaking the request cycle due to client-side helpers.
        }
    }
}
