@php($tokenSet = (bool) config('services.telegram.bot_token'))
<x-filament-panels::page>
<div class="fi-section-content-ctn">
    <x-filament::section>
        <x-slot name="heading">Telegram Bot</x-slot>
        <x-slot name="description">Configure bot branding, commands, and webhook. Use the actions above.</x-slot>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 rounded-xl bg-white dark:bg-gray-900 shadow ring-1 ring-gray-200/60 dark:ring-white/5">
                <div class="text-sm text-gray-500">Status</div>
                <div class="mt-1 font-medium">
                    {{ $tokenSet ? 'Configured' : 'Missing token' }}
                </div>
                <div class="mt-2 text-xs text-gray-500">Set TELEGRAM_BOT_TOKEN in .env (services.telegram.bot_token)</div>
            </div>
            <div class="p-4 rounded-xl bg-white dark:bg-gray-900 shadow ring-1 ring-gray-200/60 dark:ring-white/5">
                <div class="text-sm text-gray-500">Panel URL</div>
                <div class="mt-1 font-medium">{{ config('app.url') }}/admin</div>
                <div class="mt-2 text-xs text-gray-500">Admins can link Telegram from their profile by entering chat ID.</div>
            </div>
            <div class="p-4 rounded-xl bg-white dark:bg-gray-900 shadow ring-1 ring-gray-200/60 dark:ring-white/5">
                <div class="text-sm text-gray-500">Webhook</div>
                <div class="mt-1 font-medium">Use "Set Webhook" then "Webhook Info" to verify.</div>
                <div class="mt-2 text-xs text-gray-500">Ensure HTTPS and reachable APP_URL.</div>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Tips</x-slot>
        <ul class="list-disc pl-5 text-sm text-gray-600 dark:text-gray-300 space-y-1">
            <li>Commands are localized and will update per language.</li>
            <li>Branding updates bot name and descriptions visible in Telegram.</li>
            <li>Broadcast queues messages in chunks via the "telegram" queue.</li>
        </ul>
    </x-filament::section>
</div>
</x-filament-panels::page>

