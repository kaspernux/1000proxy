@once
    {{-- SweetAlert2 (CDN) for Filament panels --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Global SweetAlert2 listener for Livewire Alert fallback (Filament panels)
        window.addEventListener('swal', (event) => {
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
                Swal.fire(opts);
            }
        });
    </script>
@endonce
