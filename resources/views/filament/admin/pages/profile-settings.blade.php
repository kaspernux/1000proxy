<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <x-heroicon-o-cog-6-tooth class="w-6 h-6 text-primary-600" />
                <h2 class="text-xl font-semibold">Profile & Settings</h2>
            </div>
            <div class="flex gap-2">
                <x-filament::button icon="heroicon-o-arrow-path" wire:click="mount">Reload</x-filament::button>
                <x-filament::button icon="heroicon-o-check-circle" color="success" wire:click="save">Save</x-filament::button>
            </div>
        </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                {{ $this->form }}
            </div>

            <div class="space-y-4">
                <div class="bg-white dark:bg-gray-900 shadow-sm rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <x-heroicon-o-chat-bubble-left-right class="w-5 h-5 text-primary-600" />
                        <h3 class="font-medium">Telegram</h3>
                    </div>
                    @if(auth()->user()->hasTelegramLinked())
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Linked as {{ auth()->user()->getTelegramDisplayName() }}.</p>
                        <x-filament::button color="danger" icon="heroicon-o-link" wire:click="unlinkTelegram">Unlink</x-filament::button>
                    @else
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Link your Telegram account to receive notifications.</p>
                        <x-filament::button icon="heroicon-o-key" wire:click="generateTelegramCode">Generate Linking Code</x-filament::button>
                        <div class="text-xs text-gray-500 mt-2">Open the Telegram bot and send the code. The link is valid for 10 minutes.</div>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-900 shadow-sm rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <x-heroicon-o-adjustments-vertical class="w-5 h-5 text-primary-600" />
                        <h3 class="font-medium">Quick Tips</h3>
                    </div>
                    <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li>Theme is applied immediately for new tabs.</li>
                        <li>Locale affects in-app translations.</li>
                        <li>Keep email up to date to receive alerts.</li>
                    </ul>
                </div>

                <div class="bg-white dark:bg-gray-900 shadow-sm rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <x-heroicon-o-lock-closed class="w-5 h-5 text-primary-600" />
                        <h3 class="font-medium">Security</h3>
                    </div>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li>Change your password regularly.</li>
                        <li>Enable 2FA to secure your account.</li>
                    </ul>
                    @if(data_get($this->data,'two_factor_enabled'))
                        @php $svg = $this->getQrSvg(); $secret = $this->getCurrentTwoFactorSecret(); @endphp
                        @if($svg && $secret)
                            <div class="mt-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Scan this QR in your authenticator app, then enter the 6-digit code and press Save.</div>
                                <div class="bg-white dark:bg-gray-800 p-3 rounded-md inline-block" aria-hidden="true">{!! $svg !!}</div>
                                <div class="mt-2 text-xs text-gray-500">Or use secret: <span class="font-mono">{{ $secret }}</span></div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
