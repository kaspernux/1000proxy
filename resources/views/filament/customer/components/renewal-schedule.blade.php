<div class="p-6">
    <h2 class="text-xl font-bold mb-2 text-primary-700 dark:text-primary-200">Renewal Schedule</h2>
    <p class="text-gray-600 dark:text-gray-400 mb-4">This modal displays the renewal schedule for your active services. You can review upcoming renewal dates and buffer periods here.</p>
    <ul class="space-y-2">
        @foreach($getUpcomingRenewals() as $renewal)
            <li class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div>
                    <span class="font-semibold text-primary-700 dark:text-primary-200">{{ $renewal->items->first()?->serverClient?->server?->name ?? 'Unknown Service' }}</span>
                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">Expires: {{ $renewal->items->first()?->expires_at?->format('M j, Y') ?? 'Unknown' }}</span>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                    {{ $renewal->items->first()?->expires_at?->diffInDays() ?? 0 }} days left
                </span>
            </li>
        @endforeach
    </ul>
</div>
