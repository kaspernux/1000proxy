@once
    {{-- SweetAlert2 (CDN) for Filament panels --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Bridge Filament-rendered 'swal' events into the unified DOM event
        // pipeline so the main layout owns presentation. This prevents
        // the Livewire Alert package (or Filament pages) from rendering a
        // second Swal instance while still supporting listeners that expect
        // a DOM 'swal' event.
        window.addEventListener('swal', (event) => {
            // Re-dispatch the same payload as a DOM CustomEvent so the main
            // layout listener receives it and renders via Swal + stacked toast.
            try {
                const detail = event.detail || {};
                const custom = new CustomEvent('swal', { detail });
                window.dispatchEvent(custom);
            } catch (e) {
                // Fallback: if dispatching fails, still try to call Swal
                if (window.Swal) {
                    const detail = event.detail || {};
                    const opts = Object.assign({
                        icon: detail.icon || 'info',
                        title: detail.title || '',
                        toast: detail.toast ?? true,
                        position: detail.position || 'top-end',
                        timer: typeof detail.timer !== 'undefined' ? detail.timer : 2500,
                        showConfirmButton: detail.showConfirmButton ?? false,
                    }, detail);
                    try { window.Swal.fire(opts); } catch(_){}
                }
            }
        });
    </script>
@endonce
