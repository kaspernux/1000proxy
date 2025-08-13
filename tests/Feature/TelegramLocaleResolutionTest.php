<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Telegram\Bot\Objects\Update;
use ReflectionMethod;

class TelegramLocaleResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function invokeApplyLocale(TelegramBotService $svc, array $updateArray): void
    {
        $update = new Update($updateArray);
        $ref = new ReflectionMethod($svc, 'applyLocale');
        $ref->setAccessible(true);
        $ref->invoke($svc, $update);
    }

    public function test_persisted_user_locale_takes_priority_over_telegram_language_code(): void
    {
        $user = User::factory()->create([
            'telegram_chat_id' => 12345,
            'locale' => 'fr',
        ]);

        $svc = app(TelegramBotService::class);

        $this->invokeApplyLocale($svc, [
            'update_id' => 999,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 111,
                    'is_bot' => false,
                    'first_name' => 'Test',
                    'language_code' => 'es',
                ],
                'chat' => [
                    'id' => 12345,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/menu'
            ]
        ]);

        $this->assertEquals('fr', app()->getLocale(), 'Persisted user locale should override Telegram language_code');
    }

    public function test_telegram_language_code_used_when_no_persisted_locale(): void
    {
        $user = User::factory()->create([
            'telegram_chat_id' => 54321,
            'locale' => null,
        ]);

        $svc = app(TelegramBotService::class);

        $this->invokeApplyLocale($svc, [
            'update_id' => 1000,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 222,
                    'is_bot' => false,
                    'first_name' => 'Test',
                    'language_code' => 'ru',
                ],
                'chat' => [
                    'id' => 54321,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/menu'
            ]
        ]);

        $this->assertEquals('ru', app()->getLocale(), 'Telegram language_code should be applied when no persisted locale');
        $user->refresh();
        $this->assertEquals('ru', $user->locale, 'Detected locale should be persisted to user record');
    }
}
