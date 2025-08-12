<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TelegramSmokeProfile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:smoke-profile {--locales=en,ru,ar,zh : Comma-separated language codes to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runtime smoke test for Telegram /profile and inline edit buttons across locales';

    public function handle(TelegramBotService $service): int
    {
        $locales = array_filter(array_map('trim', explode(',', (string) $this->option('locales'))));
        if (empty($locales)) {
            $locales = ['en','ru','ar','zh'];
        }

        $baseChat = 9900000; // synthetic chat id base
        $i = 0;

        foreach ($locales as $locale) {
            $chatId = $baseChat + (++$i);
            $userId = $chatId; // keep consistent

            // Ensure a test customer exists and is linked to this chat
            $email = "smoke_{$locale}_" . Str::random(5) . '@example.test';
            $customer = Customer::firstOrCreate(
                ['telegram_chat_id' => $chatId],
                [
                    'name' => strtoupper($locale) . ' Tester',
                    'email' => $email,
                    'is_active' => true,
                    'password' => bcrypt(Str::random(16)),
                ]
            );

            $this->info("[{$locale}] Using chat_id={$chatId}, customer_id={$customer->id}");

            // 1) Send /profile command
            $update1 = [
                'update_id' => random_int(1, 999999999),
                'message' => [
                    'message_id' => random_int(1, 1000),
                    'from' => [
                        'id' => $userId,
                        'is_bot' => false,
                        'first_name' => 'Smoke',
                        'username' => 'smoke_' . $locale,
                        'language_code' => $locale,
                    ],
                    'chat' => [
                        'id' => $chatId,
                        'type' => 'private',
                    ],
                    'date' => time(),
                    'text' => '/profile',
                    'entities' => [ [ 'offset' => 0, 'length' => 8, 'type' => 'bot_command' ] ],
                ],
            ];
            $service->processUpdate($update1);
            $this->line("   → /profile dispatched");

            // 2) Tap inline button: edit name
            $update2 = [
                'update_id' => random_int(1, 999999999),
                'callback_query' => [
                    'id' => (string) random_int(1, 999999999),
                    'from' => [
                        'id' => $userId,
                        'is_bot' => false,
                        'first_name' => 'Smoke',
                        'username' => 'smoke_' . $locale,
                        'language_code' => $locale,
                    ],
                    'message' => [
                        'message_id' => random_int(1, 1000),
                        'from' => [ 'id' => 123456789, 'is_bot' => true, 'first_name' => 'Bot' ],
                        'chat' => [ 'id' => $chatId, 'type' => 'private' ],
                        'date' => time(),
                        'text' => 'Profile',
                    ],
                    'chat_instance' => (string) random_int(1, 999999999),
                    'data' => 'profile_update_name',
                ],
            ];
            $service->processUpdate($update2);
            $this->line("   → callback profile_update_name dispatched");

            // 3) Send new name text to complete flow
            $update3 = [
                'update_id' => random_int(1, 999999999),
                'message' => [
                    'message_id' => random_int(1, 1000),
                    'from' => [
                        'id' => $userId,
                        'is_bot' => false,
                        'first_name' => 'Smoke',
                        'username' => 'smoke_' . $locale,
                        'language_code' => $locale,
                    ],
                    'chat' => [ 'id' => $chatId, 'type' => 'private' ],
                    'date' => time(),
                    'text' => strtoupper($locale) . ' Updated',
                ],
            ];
            $service->processUpdate($update3);
            $this->line("   → name update text dispatched");

            // 4) Tap inline button: edit email
            $update4 = $update2;
            $update4['callback_query']['data'] = 'profile_update_email';
            $service->processUpdate($update4);
            $this->line("   → callback profile_update_email dispatched");

            // 5) Send new email text to complete flow
            $newEmail = 'smoke_' . $locale . '_' . Str::random(4) . '@example.test';
            $update5 = $update3;
            $update5['message']['text'] = $newEmail;
            $service->processUpdate($update5);
            $this->line("   → email update text dispatched ({$newEmail})");
        }

        $this->info('Done. Check logs for any Telegram/keyboard/profile errors.');
        return self::SUCCESS;
    }
}
