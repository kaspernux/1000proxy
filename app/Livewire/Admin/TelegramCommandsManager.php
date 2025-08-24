<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Arr;

class TelegramCommandsManager extends Component
{
    public $commands = [];
    public $command = '';
    public $description = '';
    public $isSaving = false;
    public $successMessage = null;
    public $errorMessage = null;

    public function mount()
    {
        $this->loadCommands();
    }

    public function loadCommands()
    {
        $service = app(\App\Services\TelegramBotService::class);
        $this->commands = $service->getCurrentCommands() ?? [];
    }

    public function addCommand()
    {
        $this->validate([
            'command' => 'required|string|alpha_dash|max:32',
            'description' => 'required|string|max:256',
        ]);
        $this->commands[] = [
            'command' => $this->command,
            'description' => $this->description,
        ];
        $this->command = '';
        $this->description = '';
    }

    public function removeCommand($index)
    {
        Arr::forget($this->commands, $index);
        $this->commands = array_values($this->commands);
    }

    public function saveCommands()
    {
        $this->isSaving = true;
        $this->successMessage = null;
        $this->errorMessage = null;
        try {
            $service = app(\App\Services\TelegramBotService::class);
            $ok = $service->setCommands($this->commands);
            if ($ok) {
                $this->successMessage = 'Commands updated and synced to Telegram.';
            } else {
                $this->errorMessage = 'Failed to update commands. Check bot token and connectivity.';
            }
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
        $this->isSaving = false;
    }

    public function render()
    {
        return view('livewire.admin.telegram-commands-manager');
    }
}
