<div class="space-y-5">
    @php
        $inbound = $client->serverInbound ?? null;
        $server = $inbound?->server;
        $ms = $client->expiry_time ?? null;
        $expiresAt = ($ms && (int)$ms > 0) ? \Carbon\Carbon::createFromTimestampMs((int)$ms) : null;
        // Links: support both legacy and new property names
        $clientLink = $client->client_link ?? null;
        $subLink = $client->subscription_link ?? ($client->remote_sub_link ?? null);
        $jsonLink = $client->json_subscription_link ?? ($client->remote_json_link ?? null);
        $clientQr = $qrCode ?: ($client->qr_code ?? null);
    @endphp

    <!-- Summary row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-xs text-gray-500 dark:text-gray-400">Server</div>
            <div class="mt-1 font-semibold text-gray-900 dark:text-gray-100">
                {{ $server->name ?? '—' }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $server->country ?? '' }}</div>
        </div>

        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-xs text-gray-500 dark:text-gray-400">Protocol / Endpoint</div>
            <div class="mt-1 flex items-center gap-2 text-gray-900 dark:text-gray-100">
                <span class="font-semibold">{{ $inbound->protocol ?? '—' }}</span>
                <span class="text-xs text-gray-500">|</span>
                <span class="text-sm">{{ $server->ip ?? '0.0.0.0' }}:{{ $inbound->port ?? '—' }}</span>
            </div>
        </div>

        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-xs text-gray-500 dark:text-gray-400">Status / Expiry</div>
            <div class="mt-1 flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                    {{ $client->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300' }}">
                    {{ ucfirst($client->status ?? 'unknown') }}
                </span>
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    {{ $expiresAt ? $expiresAt->format('M j, Y H:i') : 'Never' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Links -->
    <div class="bg-white dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Configuration Links</h4>

        <div class="space-y-2">
            <div class="flex items-center gap-2">
                <span class="w-24 text-[11px] font-medium text-gray-600 dark:text-gray-400">Client</span>
                @if($clientLink)
                    <a href="{{ $clientLink }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline text-xs">Open</a>
                    <code data-config="client-{{ $client->id }}" class="flex-1 text-xs bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded px-2 py-1 break-all">{{ $clientLink }}</code>
                    <button type="button" onclick="modalCopyToClipboard('client-{{ $client->id }}')" class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" title="Copy">
                        <x-heroicon-o-clipboard class="w-4 h-4" />
                    </button>
                @else
                    <span class="text-xs text-gray-500">Not available</span>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <span class="w-24 text-[11px] font-medium text-gray-600 dark:text-gray-400">Subscription</span>
                @if($subLink)
                    <a href="{{ $subLink }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline text-xs">Open</a>
                    <code data-config="sub-{{ $client->id }}" class="flex-1 text-xs bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded px-2 py-1 break-all">{{ $subLink }}</code>
                    <button type="button" onclick="modalCopyToClipboard('sub-{{ $client->id }}')" class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" title="Copy">
                        <x-heroicon-o-clipboard class="w-4 h-4" />
                    </button>
                @else
                    <span class="text-xs text-gray-500">Not available</span>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <span class="w-24 text-[11px] font-medium text-gray-600 dark:text-gray-400">JSON</span>
                @if($jsonLink)
                    <a href="{{ $jsonLink }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline text-xs">Open</a>
                    <code data-config="json-{{ $client->id }}" class="flex-1 text-xs bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded px-2 py-1 break-all">{{ $jsonLink }}</code>
                    <button type="button" onclick="modalCopyToClipboard('json-{{ $client->id }}')" class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" title="Copy">
                        <x-heroicon-o-clipboard class="w-4 h-4" />
                    </button>
                @else
                    <span class="text-xs text-gray-500">Not available</span>
                @endif
            </div>
        </div>
    </div>

    <!-- QR Codes -->
    <div class="bg-white dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">QR Codes</h4>

        @php
            // Try to generate missing QRs from links if the service is available
            try {
                $qrSvc = app(\App\Services\QrCodeService::class);
            } catch (\Throwable $e) { $qrSvc = null; }

            if (!$clientQr && $clientLink && $qrSvc) {
                try { $clientQr = $qrSvc->generateClientQrCode($clientLink); } catch (\Throwable $e) {}
            }

            $subQr = null; $jsonQr = null;
            if (isset($client->qr_code_sub) && $client->qr_code_sub) {
                $subQr = $client->qr_code_sub;
            } elseif ($subLink && $qrSvc) {
                try { $subQr = $qrSvc->generateClientQrCode($subLink); } catch (\Throwable $e) {}
            }
            if (isset($client->qr_code_sub_json) && $client->qr_code_sub_json) {
                $jsonQr = $client->qr_code_sub_json;
            } elseif ($jsonLink && $qrSvc) {
                try { $jsonQr = $qrSvc->generateClientQrCode($jsonLink); } catch (\Throwable $e) {}
            }

            // Normalize QR values: if they are storage paths (e.g., qr_codes/xxx.png or public/qr_codes/xxx.png),
            // convert to a public URL via asset('storage/...'). Keep data URIs and http(s) URLs as-is.
            $normalizeQr = function ($qr) {
                if (empty($qr)) return null;
                $isData = str_starts_with($qr, 'data:');
                $isHttp = preg_match('#^https?://#i', $qr);
                if ($isData || $isHttp) {
                    return $qr;
                }
                // Treat it as a storage path
                $path = ltrim($qr, '/');
                if (str_starts_with($path, 'public/')) {
                    $path = substr($path, 7);
                }
                return asset('storage/' . $path);
            };

            $clientQr = $normalizeQr($clientQr);
            $subQr = $normalizeQr($subQr);
            $jsonQr = $normalizeQr($jsonQr);
        @endphp

        <!-- Instructions -->
        <div class="mb-4 p-3 rounded-md bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 text-sm text-primary-900 dark:text-primary-200">
            <span class="inline-flex items-center px-2 py-0.5 rounded bg-primary-100 dark:bg-primary-900/40 text-[11px] font-semibold mr-2">Recommended</span>
            Scan the <strong>Client QR</strong> with your VPN/proxy app (e.g., V2RayNG, v2rayN, Nekoray, Shadowrocket) to import the connection instantly.
            <ul class="list-disc pl-5 mt-2 space-y-1 text-[13px]">
                <li>Open your client app and tap “Scan QR”.</li>
                <li>Point your camera at the Client QR below.</li>
                <li>Save/apply and connect.</li>
            </ul>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Client QR -->
            <div class="text-center">
                <div class="mb-2 flex items-center justify-center gap-2">
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">Client QR</span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Primary</span>
                </div>
                @if($clientQr)
                    <img src="{{ $clientQr }}" alt="Client QR" class="mx-auto w-80 h-80 object-contain bg-white p-2 border rounded shadow-sm" />
                    <p class="text-xs text-gray-500 mt-2">Imports a single client configuration.</p>
                @else
                    <p class="text-sm text-gray-500">Not available.</p>
                @endif
            </div>

            <!-- Subscription QR -->
            <div class="text-center">
                <div class="mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100">Subscription QR</div>
                @if($subQr)
                    <img src="{{ $subQr }}" alt="Subscription QR" class="mx-auto w-80 h-80 object-contain bg-white p-2 border rounded shadow-sm" />
                    <p class="text-xs text-gray-500 mt-2">Imports your subscription URL (auto-updates supported apps).</p>
                @else
                    <p class="text-sm text-gray-500">Not available.</p>
                @endif
            </div>

            <!-- JSON QR -->
            <div class="text-center">
                <div class="mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100">JSON QR</div>
                @if($jsonQr)
                    <img src="{{ $jsonQr }}" alt="JSON Subscription QR" class="mx-auto w-80 h-80 object-contain bg-white p-2 border rounded shadow-sm" />
                    <p class="text-xs text-gray-500 mt-2">JSON subscription endpoint for compatible clients.</p>
                @else
                    <p class="text-sm text-gray-500">Not available.</p>
                @endif
            </div>
        </div>
    </div>

    <script>
    function modalCopyToClipboard(key) {
        const el = document.querySelector(`[data-config="${key}"]`);
        if (el) {
            navigator.clipboard.writeText(el.textContent.trim());
        }
    }
    </script>
</div>
