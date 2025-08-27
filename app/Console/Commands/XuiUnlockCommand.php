<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Server;

class XuiUnlockCommand extends Command
{
    protected $signature = 'xui:unlock {serverId?} {--clear-session : Also clear stored session cookie}';
    protected $description = 'Clear XUI login lock (login_attempts) for one or all servers';

    public function handle(): int
    {
        $serverId = $this->argument('serverId');
        $clear = (bool) $this->option('clear-session');

        $query = Server::query();
        if ($serverId) {
            $query->where('id', (int) $serverId);
        }
        $servers = $query->get();
        if ($servers->isEmpty()) {
            $this->warn('No servers matched.');
            return self::SUCCESS;
        }

        foreach ($servers as $server) {
            $server->update([
                'login_attempts' => 0,
                'last_login_attempt_at' => null,
                ...($clear ? ['session_cookie' => null, 'session_expires_at' => null] : []),
            ]);
            $this->info("Unlocked server #{$server->id} ({$server->name})" . ($clear ? ' and cleared session' : ''));
        }

        return self::SUCCESS;
    }
}
