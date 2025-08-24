@php($tokenSet = (bool) config('services.telegram.bot_token'))
<x-filament-panels::page>
    <div class="space-y-8">
        <x-filament::card>
            <div class="mb-4">
                <h3 class="text-base font-semibold">Overview</h3>
                <p class="text-sm text-gray-500">Quick status and helpful references.</p>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <x-filament::card>
                    <dl class="grid grid-cols-1 gap-1">
                        <dt class="text-sm text-gray-500">Status</dt>
                        <dd class="text-lg font-semibold">{{ $tokenSet ? 'Configured' : 'Missing token' }}</dd>
                        <dd class="text-xs text-gray-500">Set TELEGRAM_BOT_TOKEN in .env (services.telegram.bot_token)</dd>
                    </dl>
                </x-filament::card>
                <x-filament::card>
                    <dl class="grid grid-cols-1 gap-1">
                        <dt class="text-sm text-gray-500">Panel URL</dt>
                        <dd class="text-lg font-semibold">{{ config('app.url') }}/admin</dd>
                        <dd class="text-xs text-gray-500">Admins can link Telegram from their profile by entering chat ID.</dd>
                    </dl>
                </x-filament::card>
                <x-filament::card>
                    <dl class="grid grid-cols-1 gap-1">
                        <dt class="text-sm text-gray-500">Webhook</dt>
                        <dd class="text-lg font-semibold">Use "Set Webhook" then "Webhook Info" to verify.</dd>
                        <dd class="text-xs text-gray-500">Ensure HTTPS and reachable APP_URL.</dd>
                    </dl>
                </x-filament::card>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="mb-4 flex items-center gap-2">
                <x-heroicon-o-command-line class="h-5 w-5" />
                <h3 class="text-base font-semibold">Telegram Commands</h3>
            </div>
            <div class="space-y-4">
                <div class="text-sm text-gray-500">Admins can view, add, or remove bot commands. These are synced to Telegram and localized for users.</div>
                <livewire:admin.telegram-commands-manager />
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="mb-4">
                <h3 class="text-base font-semibold">Tips</h3>
                <p class="text-sm text-gray-500">Helpful notes for smooth operations.</p>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-filament::card>
                    <ul class="list-disc pl-5 text-sm space-y-1">
                        <li>Commands are localized and update per language.</li>
                        <li>Branding updates bot name and descriptions in Telegram.</li>
                        <li>Broadcasts are queued via the “telegram” queue in paced batches.</li>
                    </ul>
                </x-filament::card>
                <x-filament::card>
                    <dl class="grid grid-cols-1 gap-1">
                        <dt class="text-sm text-gray-500">Support</dt>
                        <dd class="text-sm">Use “Bot Info”, “Webhook Info”, and Queue Health below to diagnose quickly.</dd>
                        <dd class="text-xs text-gray-500">Check server timeouts and SSL if webhooks fail.</dd>
                    </dl>
                </x-filament::card>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="mb-4">
                <h3 class="text-base font-semibold">Queue Health</h3>
                <p class="text-sm text-gray-500">Live status of the telegram queue and recent failures.</p>
            </div>
            <div class="space-y-4">
                @livewire(\App\Filament\Admin\Widgets\TelegramQueueHealth::class)
                @livewire(\App\Filament\Admin\Widgets\RecentFailedJobs::class)
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>

