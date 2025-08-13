<?php

namespace Tests\Unit;

use App\Services\TelegramBotService;
use Tests\TestCase;

class TelegramHtmlSanitizerTest extends TestCase
{
    /** @test */
    public function it_strips_disallowed_tags_and_normalizes_line_breaks()
    {
        $service = new class extends TelegramBotService {
            public function publicSanitize(string $html): string { return $this->sanitizeTelegramHtml($html); }
        };

        $input = "<div><p>Hello <span>World</span></p><br><strong>Bold</strong></div>";
        $out = $service->publicSanitize($input);

        $this->assertStringContainsString('Hello World', $out);
        $this->assertStringContainsString('Bold', $out);
        $this->assertStringNotContainsString('<div>', $out);
        $this->assertStringNotContainsString('<span>', $out);
        $this->assertStringContainsString("\n", $out);
    }
}
