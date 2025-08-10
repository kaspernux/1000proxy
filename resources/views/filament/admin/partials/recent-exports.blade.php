@php($exports = $exports ?? [])
<div class="space-y-3">
    @if(empty($exports))
        <div class="text-sm text-gray-500 dark:text-gray-400">No exports found yet.</div>
    @else
        <ul class="divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            @foreach($exports as $export)
                <li class="py-2 flex items-center justify-between">
                    <div class="min-w-0">
                        <div class="font-medium text-gray-800 dark:text-gray-100 truncate">{{ $export['name'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::createFromTimestamp($export['time'])->diffForHumans() }}</div>
                    </div>
                    <a href="{{ $export['url'] }}" class="text-primary-600 hover:underline font-medium">Download</a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
