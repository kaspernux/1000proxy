<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Illuminate\Support\Str;

class TelegramLink extends Component
{
    public $linkingCode;
    public $isLinked = false;
    public $telegramInfo = null;
    public $showLinkingCode = false;

    public function mount()
    {
        // Ensure customer is authenticated
        if (!Auth::guard('customer')->check()) {
            Log::warning('Unauthenticated access to Telegram link page');
            $this->redirect('/login', navigate: true);
            return;
        }

        $this->checkTelegramStatus();
    }

    public function checkTelegramStatus()
    {
        try {
            $customer = Auth::guard('customer')->user();
            if (!$customer) {
                Log::warning('No customer found in Telegram status check');
                return;
            }

            $this->isLinked = $customer->hasTelegramLinked();

            if ($this->isLinked) {
                $this->telegramInfo = [
                    'username' => $customer->telegram_username,
                    'first_name' => $customer->telegram_first_name,
                    'last_name' => $customer->telegram_last_name,
                    'display_name' => $customer->getTelegramDisplayName(),
                ];

                Log::info('Telegram status checked', [
                    'customer_id' => $customer->id,
                    'is_linked' => true
                ]);
            } else {
                Log::info('Telegram status checked', [
                    'customer_id' => $customer->id,
                    'is_linked' => false
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to check Telegram status', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function generateLinkingCode()
    {
        try {
            $customer = Auth::guard('customer')->user();
            if (!$customer) {
                Log::warning('No customer found for Telegram linking code generation');
                session()->flash('error', 'Authentication error. Please login again.');
                return;
            }

            // Generate a unique linking code
            $this->linkingCode = Str::random(8);

            // Store the linking code in cache for 10 minutes
            cache()->put("telegram_linking_{$this->linkingCode}", $customer->id, 600);

            $this->showLinkingCode = true;

            $this->dispatch('telegram-linking-code-generated', [
                'code' => $this->linkingCode,
                'instructions' => 'Send this code to the bot in Telegram to link your account.'
            ]);

            Log::info('Telegram linking code generated', [
                'customer_id' => $customer->id,
                'code' => $this->linkingCode,
                'expires_at' => now()->addMinutes(10)
            ]);

            session()->flash('success', 'Linking code generated successfully! Code expires in 10 minutes.');
        } catch (\Exception $e) {
            Log::error('Failed to generate Telegram linking code', [
                'customer_id' => Auth::guard('customer')->id(),
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'Failed to generate linking code. Please try again.');
        }
    }

    public function cancelLinking()
    {
        try {
            if ($this->linkingCode) {
                cache()->forget("telegram_linking_{$this->linkingCode}");
                Log::info('Telegram linking code cancelled', [
                    'customer_id' => Auth::guard('customer')->id(),
                    'code' => $this->linkingCode
                ]);
            }

            $this->linkingCode = null;
            $this->showLinkingCode = false;
            
            session()->flash('info', 'Linking code cancelled.');
        } catch (\Exception $e) {
            Log::error('Failed to cancel Telegram linking code', [
                'customer_id' => Auth::guard('customer')->id(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function unlinkTelegram()
    {
        try {
            $customer = Auth::guard('customer')->user();
            if (!$customer) {
                Log::warning('No customer found for Telegram unlinking');
                session()->flash('error', 'Authentication error. Please login again.');
                return;
            }

            // Store telegram info for logging before unlinking
            $telegramUsername = $customer->telegram_username;
            $telegramChatId = $customer->telegram_chat_id;

            $customer->unlinkTelegram();
            $this->checkTelegramStatus();

            $this->dispatch('telegram-unlinked', [
                'message' => 'Telegram account has been unlinked successfully.'
            ]);

            Log::info('Telegram account unlinked', [
                'customer_id' => $customer->id,
                'previous_telegram_username' => $telegramUsername,
                'previous_chat_id' => $telegramChatId
            ]);

            session()->flash('success', 'Telegram account unlinked successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to unlink Telegram account', [
                'customer_id' => Auth::guard('customer')->id(),
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'Failed to unlink Telegram account. Please try again.');
        }
    }

    public function refreshStatus()
    {
        try {
            $this->checkTelegramStatus();
            
            Log::info('Telegram status refreshed', [
                'customer_id' => Auth::guard('customer')->id(),
                'is_linked' => $this->isLinked
            ]);
            
            session()->flash('info', 'Status refreshed.');
        } catch (\Exception $e) {
            Log::error('Failed to refresh Telegram status', [
                'customer_id' => Auth::guard('customer')->id(),
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'Failed to refresh status.');
        }
    }

    public function render()
    {
        return view('livewire.auth.telegram-link');
    }
}
