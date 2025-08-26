<?php

namespace App\Services\Chat;

class AutoResponder
{
    // Very lightweight keyword matcher; replace with vector search later if needed
    private array $faq;

    public function __construct()
    {
        $this->faq = [
            'refund' => "Refund policy: You can request a refund within 24 hours if no traffic has been consumed. For help, type 'support'.",
            'replace|rotation' => "IP rotation/replacement: You can rotate IPs from your dashboard > Proxies > Actions. Need help? Type 'support'.",
            'api|docs' => "API docs: See /api in your dashboard or contact us for your token.",
            'pricing|price|cost' => "Pricing: Plans are listed on the Pricing page. For custom plans, chat with support.",
            'telegram' => "Telegram: Link your account at /telegram-link to receive notifications.
            ",
        ];
    }

    public function answer(string $text): ?string
    {
        $q = mb_strtolower(trim($text));
        if ($q === '') return null;
        foreach ($this->faq as $pattern => $reply) {
            $regex = '/\b(' . $pattern . ')\b/i';
            if (preg_match($regex, $q)) return $reply;
        }
        return null;
    }
}
