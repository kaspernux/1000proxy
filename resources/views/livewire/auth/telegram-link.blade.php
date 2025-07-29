@extends('layouts.app')

@section('content')
<div class="bg-white shadow rounded-lg p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Telegram Integration</h3>
        <button 
            wire:click="refreshStatus" 
            class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Refresh
        </button>
    </div>

    @if($isLinked)
        <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M22.05 1.577c-.393-.016-.78.08-1.127.283L.85 8.343c-.348.204-.673.48-.953.816-.28.335-.483.719-.606 1.139-.123.42-.166.86-.12 1.296.046.436.162.86.34 1.252.178.393.407.748.677 1.053.27.305.578.556.912.742.334.186.69.305 1.056.352.367.047.737.024 1.095-.068l5.227-1.336 5.227 1.336c.358.092.728.115 1.095.068.366-.047.722-.166 1.056-.352.334-.186.642-.437.912-.742.27-.305.5-.66.677-1.053.178-.392.294-.816.34-1.252.046-.436.003-.876-.12-1.296-.123-.42-.326-.804-.606-1.139-.28-.336-.605-.612-.953-.816L22.05 1.577z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">
                        Telegram Account Linked
                    </p>
                    <p class="text-sm text-green-600">
                        Connected as: {{ $telegramInfo['display_name'] }}
                        @if($telegramInfo['username'])
                            ({{ '@' . $telegramInfo['username'] }})
                        @endif
                    </p>
                </div>
            </div>
            <button 
                wire:click="unlinkTelegram" 
                wire:confirm="Are you sure you want to unlink your Telegram account?"
                class="inline-flex items-center px-3 py-1.5 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
            >
                Unlink
            </button>
        </div>
    @else
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M22.05 1.577c-.393-.016-.78.08-1.127.283L.85 8.343c-.348.204-.673.48-.953.816-.28.335-.483.719-.606 1.139-.123.42-.166.86-.12 1.296.046.436.162.86.34 1.252.178.393.407.748.677 1.053.27.305.578.556.912.742.334.186.69.305 1.056.352.367.047.737.024 1.095-.068l5.227-1.336 5.227 1.336c.358.092.728.115 1.095.068.366-.047.722-.166 1.056-.352.334-.186.642-.437.912-.742.27-.305.5-.66.677-1.053.178-.392.294-.816.34-1.252.046-.436.003-.876-.12-1.296-.123-.42-.326-.804-.606-1.139-.28-.336-.605-.612-.953-.816L22.05 1.577z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Link Your Telegram Account</h3>
            <p class="text-sm text-gray-600 mb-4">
                Connect your Telegram account to receive notifications and manage your proxies directly from Telegram.
            </p>
            
            @if(!$showLinkingCode)
                <button 
                    wire:click="generateLinkingCode" 
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M22.05 1.577c-.393-.016-.78.08-1.127.283L.85 8.343c-.348.204-.673.48-.953.816-.28.335-.483.719-.606 1.139-.123.42-.166.86-.12 1.296.046.436.162.86.34 1.252.178.393.407.748.677 1.053.27.305.578.556.912.742.334.186.69.305 1.056.352.367.047.737.024 1.095-.068l5.227-1.336 5.227 1.336c.358.092.728.115 1.095.068.366-.047.722-.166 1.056-.352.334-.186.642-.437.912-.742.27-.305.5-.66.677-1.053.178-.392.294-.816.34-1.252.046-.436.003-.876-.12-1.296-.123-.42-.326-.804-.606-1.139-.28-.336-.605-.612-.953-.816L22.05 1.577z"/>
                    </svg>
                    Generate Linking Code
                </button>
            @else
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-blue-900 mb-2">Linking Code Generated</h4>
                    <div class="bg-white border border-blue-300 rounded-md p-3 mb-3">
                        <p class="text-2xl font-mono font-bold text-blue-800 text-center">{{ $linkingCode }}</p>
                    </div>
                    <div class="text-sm text-blue-700 space-y-2">
                        <p><strong>Instructions:</strong></p>
                        <ol class="list-decimal list-inside space-y-1">
                            <li>Open Telegram and search for our bot: <strong>@{{ config('app.name') }}Bot</strong></li>
                            <li>Start a conversation with the bot by clicking "Start"</li>
                            <li>Send the linking code above to the bot</li>
                            <li>Your account will be linked automatically</li>
                        </ol>
                    </div>
                    <div class="mt-4 flex space-x-2">
                        <button 
                            wire:click="cancelLinking"
                            class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            Cancel
                        </button>
                        <button 
                            wire:click="refreshStatus"
                            class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Check Status
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div class="mt-4 border-t border-gray-200 pt-4">
        <h4 class="text-sm font-medium text-gray-900 mb-2">Telegram Features</h4>
        <ul class="text-sm text-gray-600 space-y-1">
            <li>• Check wallet balance</li>
            <li>• Browse available servers</li>
            <li>• Place orders</li>
            <li>• View order history</li>
            <li>• Get support</li>
            <li>• Receive notifications</li>
        </ul>
    </div>
</div>

<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('telegram-linking-code-generated', (event) => {
        console.log('Linking code generated:', event.code);
        // You can add additional UI feedback here
    });
    
    Livewire.on('telegram-unlinked', (event) => {
        console.log('Telegram unlinked:', event.message);
        // You can add additional UI feedback here
    });
});
</script>
@endsection
