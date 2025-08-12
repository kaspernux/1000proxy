<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramBotService;

class TelegramSetBranding extends Command
{
    protected $signature = 'telegram:set-branding {--name=} {--short=} {--desc=} {--menu=commands} {--menu-text=} {--menu-url=} {--localized}';
    protected $description = 'Set Telegram bot branding (name/short/desc) and menu button';

    public function handle(TelegramBotService $service): int
    {
        $name = $this->option('name');
        $short = $this->option('short');
        $desc = $this->option('desc');

        $ok1 = true;
        if ($this->option('localized')) {
            $this->info('Setting localized branding…');
            $ok1 = $service->setBrandingLocalized();
        } else {
            $ok1 = $service->setBranding($name, $short, $desc);
        }

        $menu = $this->option('menu') ?? 'commands';
        $menuText = $this->option('menu-text');
        $menuUrl = $this->option('menu-url');
        if ($menu === 'web_app') {
            if (!$menuText) {
                $menuText = (string) \Lang::get('telegram.bot.menu_text', [], app()->getLocale());
            }
            if (!$menuUrl) {
                $menuUrl = (string) config('app.url');
            }
        }
    $ok2 = $service->setMenuButton($menu, $menuText, $menuUrl);

        if ($ok1 && $ok2) {
            $this->info('✅ Branding and menu updated.');
            return self::SUCCESS;
        }

        $this->warn('Branding or menu update had issues. Check logs.');
        return self::FAILURE;
    }
}
