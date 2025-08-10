{{-- Override to inject runtime debug banner & heartbeat script --}}
<x-filament-panels::page>
    @php $since = now(); @endphp
    <div x-data="{alive: true, ts: Date.now(), errors: [], refresh(){window.dispatchEvent(new Event('filament-refresh-widgets'))}}" class="space-y-4">
        <div class="hidden" x-bind:class="{'!block': !alive}">
            <div class="rounded bg-danger-600 text-white p-3 text-sm font-medium">
                Dashboard stalled. Attempting soft refresh… <button x-on:click="refresh()" class="underline ml-2">Retry</button>
            </div>
        </div>
        {{ $slot }}
    </div>
    <script>
        (function(){
            const HEARTBEAT_INTERVAL = 25000; // 25s
            const STALL_THRESHOLD = 60000; // 60s without Livewire message considered stalled
            let lastMessage = Date.now();
            document.addEventListener('livewire:init', () => {
                window.Livewire.hook('message.processed', () => { lastMessage = Date.now(); });
            });
            setInterval(() => {
                const idle = Date.now() - lastMessage;
                if(idle > STALL_THRESHOLD){
                    console.warn('[Dashboard Heartbeat] Stall detected ('+idle+'ms). Forcing widget refresh.');
                    window.dispatchEvent(new Event('filament-refresh-widgets'));
                }
            }, HEARTBEAT_INTERVAL);
        })();
    </script>
</x-filament-panels::page>
