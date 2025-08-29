{{-- User Activity Log Modal --}}
<div>
    <h3 class="font-bold mb-2">Recent Activity for {{ $user->name }}</h3>
    @if($user->userActivities->isEmpty())
        <div class="text-gray-500">No activity recorded.</div>
    @else
        <ul class="divide-y divide-gray-200">
            @foreach($user->userActivities->sortByDesc('created_at')->take(20) as $activity)
                <li class="py-2">
                    <span class="font-semibold">{{ ucfirst($activity->action) }}</span>
                    <span class="text-gray-600">({{ $activity->created_at }})</span>
                    <div class="text-sm text-gray-700">{{ $activity->description }}</div>
                    <div class="text-xs text-gray-400">IP: {{ $activity->ip_address }}</div>
                    @if($activity->properties)
                        <div class="text-xs text-gray-500">{{ json_encode($activity->properties) }}</div>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="mt-4 text-right">
            <a href="{{ route('filament.admin.resources.activity-logs.view', ['record' => $user->id]) }}" target="_blank" class="text-primary-600 hover:underline text-sm">View full activity log</a>
        </div>
    @endif
</div>
