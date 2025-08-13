<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;

class TemplateRenderer
{
    /**
     * Render a template by key/channel/locale with safe placeholder substitution.
     * Placeholders use :name style. Falls back to translation key telegram.messages.$key if not found.
     */
    public function render(string $key, string $channel = 'telegram', array $data = [], ?string $locale = null): string
    {
        $locale = $locale ?: App::getLocale();
        $tpl = NotificationTemplate::byKey($key, $locale, $channel)
            ?? NotificationTemplate::byKey($key, 'en', $channel);

        if ($tpl && $tpl->enabled) {
            return $this->interpolate($tpl->body, $data, $locale);
        }

        // Fallback to translations: telegram.messages.$key
        $line = Lang::get("telegram.messages.$key", $data, $locale);
        if (is_string($line) && $line !== "telegram.messages.$key") {
            return $line;
        }
        // Last resort: key
        return $key;
    }

    protected function interpolate(string $body, array $data, string $locale): string
    {
        $replaced = $body;
        foreach ($data as $k => $v) {
            $replaced = str_replace(':' . $k, (string) $v, $replaced);
        }
        return $replaced;
    }
}
