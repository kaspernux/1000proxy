<div class="p-4">
    <h2 class="text-lg font-bold mb-2">Server Performance Details</h2>
    <p class="text-gray-600 mb-4">Detailed metrics and performance information for the selected server will appear here.</p>
    <ul class="space-y-2">
        <li><span class="font-semibold">Server Name:</span> {{ $record->server->name ?? 'N/A' }}</li>
        <li><span class="font-semibold">Location:</span> {{ $record->server->location ?? 'N/A' }}</li>
        <li><span class="font-semibold">Status:</span> {{ $record->status ?? 'N/A' }}</li>
        <li><span class="font-semibold">Uptime:</span> {{ $record->uptime ?? 'N/A' }}</li>
        <li><span class="font-semibold">Latency:</span> {{ $record->latency ?? 'N/A' }}</li>
        <li><span class="font-semibold">Bandwidth Used:</span> {{ $record->bandwidth_used ?? 'N/A' }}</li>
        <li><span class="font-semibold">Last Check:</span> {{ $record->last_check ?? 'N/A' }}</li>
    </ul>
</div>
