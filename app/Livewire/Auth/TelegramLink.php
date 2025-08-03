<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
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
        $this->checkTelegramStatus();
    }

    public function checkTelegramStatus()
    {
        $user = Auth::guard('customer')->user();
        $this->isLinked = $user->hasTelegramLinked();

        if ($this->isLinked) {
            $this->telegramInfo = [
                'username' => $user->telegram_username,
                'first_name' => $user->telegram_first_name,
                'last_name' => $user->telegram_last_name,
                'display_name' => $user->getTelegramDisplayName(),
            ];
        }
    }

    public function generateLinkingCode()
    {
        $user = Auth::guard('customer')->user();

        // Generate a unique linking code
        $this->linkingCode = Str::random(8);

        // Store the linking code in cache for 10 minutes
        cache()->put("telegram_linking_{$this->linkingCode}", $user->id, 600);

        $this->showLinkingCode = true;

        $this->dispatch('telegram-linking-code-generated', [
            'code' => $this->linkingCode,
            'instructions' => 'Send this code to the bot in Telegram to link your account.'
        ]);
    }

    public function cancelLinking()
    {
        if ($this->linkingCode) {
            cache()->forget("telegram_linking_{$this->linkingCode}");
        }

        $this->linkingCode = null;
        $this->showLinkingCode = false;
    }

    public function unlinkTelegram()
    {
        $user = Auth::guard('customer')->user();
        $user->unlinkTelegram();

        $this->checkTelegramStatus();

        $this->dispatch('telegram-unlinked', [
            'message' => 'Telegram account has been unlinked successfully.'
        ]);
    }

    public function refreshStatus()
    {
        $this->checkTelegramStatus();
    }

    public function render()
    {
        return view('livewire.auth.telegram-link');
    }
}
