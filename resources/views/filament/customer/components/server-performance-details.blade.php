<div class="p-4">
    <h2 class="text-lg font-bold mb-2">Server Performance Details</h2>
    <p class="text-gray-600 mb-4">Detailed metrics and performance information for the selected server will appear here.</p>
    <ul class="space-y-2">
        <li><span class="font-semibold">Server Name:</span> {{ $record->server->name ?? 'N/A' }}</li>
        <li><span class="font-semibold">Location:</span> {{ $record->server->location ?? 'N/A' }}</li>
        <li><span class="font-semibold">Status:</span> {{ $record->status ?? 'N/A' }}</li>
        <li><span class="font-semibold">Uptime:</span> {{ $record->uptime ?? 'N/A' }}</li>
        <li><span class="font-semibold">Latency:</span> {{ $record->latency ?? 'N/A' }}</li>
    <li><span class="font-semibold">Bandwidth Used:</span> {{ isset($record->bandwidth_used_mb) ? number_format($record->bandwidth_used_mb, 2) . ' MB' : 'N/A' }}</li>
        <li><span class="font-semibold">Last Check:</span> {{ $record->last_check ?? 'N/A' }}</li>
    </ul>
</div>
