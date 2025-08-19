<x-filament-panels::page>
    <div class="fi-section-content-ctn">
		<div class="my-6">
			<h1 class="text-xl font-semibold text-gray-900 dark:text-white">Staff Users</h1>
			<p class="text-sm text-gray-600 dark:text-gray-400">Internal staff accounts directory</p>
		</div>

		<div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
			<div class="px-4 py-5 sm:p-6">
				{{ $this->table }}
			</div>
		</div>
	</div>
</x-filament-panels::page>
