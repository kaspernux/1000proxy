
<div class="max-w-2xl mx-auto">
    <div class="bg-gradient-to-r from-blue-100 to-purple-100 dark:from-blue-900 dark:to-purple-900 rounded-2xl shadow-xl p-8">
        <div class="flex items-center gap-4 mb-6">
            <div class="p-3 bg-blue-500/10 rounded-full">
                <x-heroicon-o-users class="w-8 h-8 text-blue-600 dark:text-blue-400" />
            </div>
            <h2 class="text-2xl font-extrabold text-blue-900 dark:text-white tracking-tight">Referral Details</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Name</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $record->name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Email</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $record->email ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Joined</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $record->joined_at ? $record->joined_at->format('M j, Y') : 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Status</p>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold {{
                    $record->status === 'Active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                    ($record->status === 'Inactive' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' :
                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200')
                }}">
                    @if($record->status === 'Active')
                        <x-heroicon-o-check-circle class="w-4 h-4 text-green-500" />
                    @elseif($record->status === 'Inactive')
                        <x-heroicon-o-x-circle class="w-4 h-4 text-red-500" />
                    @else
                        <x-heroicon-o-clock class="w-4 h-4 text-yellow-500" />
                    @endif
                    {{ $record->status ?? 'N/A' }}
                </span>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Orders</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $record->orders_count ?? 0 }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Total Spent</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($record->total_spent ?? 0, 2) }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Commission Earned</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($record->commission_earned ?? 0, 2) }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Commission Status</p>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold {{
                    $record->commission_status === 'Paid' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                    ($record->commission_status === 'Pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                    'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200')
                }}">
                    @if($record->commission_status === 'Paid')
                        <x-heroicon-o-currency-dollar class="w-4 h-4 text-green-500" />
                    @elseif($record->commission_status === 'Pending')
                        <x-heroicon-o-clock class="w-4 h-4 text-yellow-500" />
                    @else
                        <x-heroicon-o-minus-circle class="w-4 h-4 text-gray-500" />
                    @endif
                    {{ $record->commission_status ?? 'N/A' }}
                </span>
            </div>
        </div>
    </div>
</div>
