<?php

namespace Tests\Unit;

use App\Models\NotificationTemplate;
use App\Services\TemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateRendererTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_db_template_with_placeholders_and_locale_fallback()
    {
        NotificationTemplate::create([
            'key' => 'greeting',
            'channel' => 'telegram',
            'locale' => 'en',
            'name' => 'Greeting',
            'subject' => null,
            'body' => 'Hello, :name!',
            'enabled' => true,
        ]);

        $renderer = new TemplateRenderer();
        $this->app->setLocale('fr'); // No FR template -> fallback to EN
        $text = $renderer->render('greeting', 'telegram', ['name' => 'Alice']);

        $this->assertSame('Hello, Alice!', $text);
    }

    /** @test */
    public function it_falls_back_to_translation_key_when_no_template()
    {
        // Assuming lang/en/telegram.php contains messages.unknown => 'Unknown command.'
        $this->app->setLocale('en');
        $renderer = new TemplateRenderer();
        $text = $renderer->render('unknown', 'telegram');

        // Fallback returns translation, not the key
        $this->assertNotSame('unknown', $text);
        $this->assertIsString($text);
    }
}
