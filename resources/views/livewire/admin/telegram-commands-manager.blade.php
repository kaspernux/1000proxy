<div>
    <div class="mb-4">
        <form wire:submit.prevent="addCommand" class="flex flex-col md:flex-row gap-2 items-end">
            <div class="flex-1">
                <x-filament::input label="Command" wire:model.defer="command" placeholder="e.g. mycommand" required maxlength="32" class="w-full" />
            </div>
            <div class="flex-1">
                <x-filament::input label="Description" wire:model.defer="description" placeholder="Command description" required maxlength="256" class="w-full" />
            </div>
            <x-filament::button type="submit" color="primary" class="shrink-0">Add</x-filament::button>
        </form>
        @error('command') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
        @error('description') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Command</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Description</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($commands as $i => $cmd)
                    <tr>
                        <td class="px-4 py-2 font-mono text-blue-700 dark:text-blue-300">/{{ $cmd['command'] }}</td>
                        <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $cmd['description'] }}</td>
                        <td class="px-4 py-2 text-right">
                            <x-filament::button color="danger" size="sm" wire:click="removeCommand({{ $i }})">Remove</x-filament::button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-4 text-center text-gray-400">No commands defined.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex items-center gap-2">
        <x-filament::button color="success" wire:click="saveCommands" :disabled="$isSaving || count($commands) === 0">
            <x-heroicon-o-arrow-up-tray class="w-4 h-4 mr-1" /> Save & Sync to Telegram
        </x-filament::button>
        @if($isSaving)
            <span class="text-xs text-gray-500">Saving...</span>
        @endif
        @if($successMessage)
            <span class="text-xs text-green-600">{{ $successMessage }}</span>
        @endif
        @if($errorMessage)
            <span class="text-xs text-red-600">{{ $errorMessage }}</span>
        @endif
    </div>
</div>
