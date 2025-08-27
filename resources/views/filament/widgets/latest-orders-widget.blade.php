<x-filament-widgets::widget>
	<x-filament::section>
		<div class="p-4">
			<div class="mb-3 flex items-center justify-between">
				<h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Latest Orders') }}</h3>
				<x-filament::badge color="gray" icon="heroicon-o-queue-list">{{ __('Recent') }}</x-filament::badge>
			</div>
			{{ $this->table }}
		</div>
	</x-filament::section>
</x-filament-widgets::widget>
