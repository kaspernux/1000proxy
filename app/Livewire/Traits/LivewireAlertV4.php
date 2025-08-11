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
        // Resolve the alert service bound to the current Livewire component context
        /** @var LivewireAlertService $alert */
        $alert = app(LivewireAlertService::class);

        // Base
        $alert->title($title);

        // Icon/type
        switch (strtolower($type)) {
            case 'success':
                $alert->success();
                break;
            case 'error':
                $alert->error();
                break;
            case 'warning':
                $alert->warning();
                break;
            case 'info':
                $alert->info();
                break;
            case 'question':
                $alert->question();
                break;
        }

        // Common mappings
        if (array_key_exists('text', $options) && is_string($options['text'])) {
            $alert->text($options['text']);
        }

        if (array_key_exists('toast', $options)) {
            $alert->toast((bool) $options['toast']);
        }

        if (array_key_exists('position', $options) && is_string($options['position'])) {
            $alert->position($options['position']);
        }

        if (array_key_exists('timer', $options)) {
            $alert->timer(is_null($options['timer']) ? null : (int) $options['timer']);
        }

        if (!empty($options['html']) && is_string($options['html'])) {
            $alert->html($options['html']);
        }

        if (array_key_exists('allowOutsideClick', $options)) {
            $alert->allowOutsideClick((bool) $options['allowOutsideClick']);
        }

        if (array_key_exists('allowEscapeKey', $options)) {
            $alert->allowEscapeKey((bool) $options['allowEscapeKey']);
        }

        // Buttons
        if (!empty($options['showConfirmButton'])) {
            $alert->withConfirmButton(
                $options['confirmButtonText'] ?? null
            );
        } elseif (!empty($options['confirmButtonText'])) {
            // If only text provided, still enable confirm button
            $alert->withConfirmButton($options['confirmButtonText']);
        }

        if (!empty($options['showCancelButton'])) {
            $alert->withCancelButton(
                $options['cancelButtonText'] ?? null
            );
        } elseif (!empty($options['cancelButtonText'])) {
            $alert->withCancelButton($options['cancelButtonText']);
        }

        if (!empty($options['showDenyButton'])) {
            $alert->withDenyButton(
                $options['denyButtonText'] ?? null
            );
        } elseif (!empty($options['denyButtonText'])) {
            $alert->withDenyButton($options['denyButtonText']);
        }

        if (!empty($options['confirmButtonColor']) && is_string($options['confirmButtonColor'])) {
            $alert->confirmButtonColor($options['confirmButtonColor']);
        }
        if (!empty($options['cancelButtonColor']) && is_string($options['cancelButtonColor'])) {
            $alert->cancelButtonColor($options['cancelButtonColor']);
        }
        if (!empty($options['denyButtonColor']) && is_string($options['denyButtonColor'])) {
            $alert->denyButtonColor($options['denyButtonColor']);
        }

        // Pass-through for any remaining SweetAlert2 options
        $known = [
            'text','toast','position','timer','timerProgressBar','html',
            'allowOutsideClick','allowEscapeKey',
            'showConfirmButton','showCancelButton','showDenyButton',
            'confirmButtonText','cancelButtonText','denyButtonText',
            'confirmButtonColor','cancelButtonColor','denyButtonColor',
        ];

        $additional = array_diff_key($options, array_flip($known));
        if (!empty($additional)) {
            $alert->withOptions($additional);
        }

        // Timer progress bar isn't a dedicated method, pass via options
        if (array_key_exists('timerProgressBar', $options)) {
            $alert->withOptions(['timerProgressBar' => (bool) $options['timerProgressBar']]);
        }

        $alert->show();

        // Fire a browser event as a fallback so the frontend can render the alert
        // even if the package's JS listener isn't present. This ensures reliability
        // across public pages and Filament panels.
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

            // Dispatch as a browser event; the layout listens for this and calls Swal.fire()
            if (method_exists($this, 'dispatchBrowserEvent')) {
                $this->dispatchBrowserEvent('swal', $payload);
            }
        } catch (\Throwable $e) {
            // Silently ignore to avoid breaking the request cycle due to client-side helpers.
        }
    }
}
