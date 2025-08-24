<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Minimal TDLib gateway client that mimics a subset of Bot API calls used by TelegramBotService.
 * It forwards requests to an internal HTTP gateway that talks to TDLib (to be provided separately).
 */
class TdlibGatewayClient
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected int $timeout;

    public function __construct()
    {
        $cfg = config('telegram.tdlib');
        $this->baseUrl = rtrim((string)($cfg['gateway_url'] ?? 'http://tdlib-gateway:8080'), '/');
        $this->apiKey = $cfg['api_key'] ?? null;
        $this->timeout = (int)($cfg['timeout'] ?? 10);
    }

    protected function headers(): array
    {
        $headers = ['Accept' => 'application/json'];
        if ($this->apiKey) {
            $headers['X-Api-Key'] = $this->apiKey;
        }
        return $headers;
    }

    protected function post(string $path, array $payload): array
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        $resp = Http::withHeaders($this->headers())
            ->timeout($this->timeout)
            ->post($url, $payload);
        if (!$resp->ok()) {
            $resp->throw();
        }
        $data = $resp->json();
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid TDLib gateway response');
        }
        return $data;
    }

    // Bot-like methods used by our service
    public function sendMessage(array $params): array
    {
        return $this->post('/bot/sendMessage', $params);
    }

    public function editMessageText(array $params): array
    {
        return $this->post('/bot/editMessageText', $params);
    }

    public function sendPhoto(array $params): array
    {
        // Gateway should support multipart upload or local file path reference.
        return $this->post('/bot/sendPhoto', $params);
    }

    public function deleteMessage(array $params): array
    {
        return $this->post('/bot/deleteMessage', $params);
    }

    public function answerCallbackQuery(array $params): array
    {
        return $this->post('/bot/answerCallbackQuery', $params);
    }

    public function setWebhook(array $params): array
    {
        return $this->post('/bot/setWebhook', $params);
    }

    public function getWebhookInfo(): array
    {
        return $this->post('/bot/getWebhookInfo', []);
    }

    public function removeWebhook(): array
    {
        return $this->post('/bot/deleteWebhook', []);
    }

    public function rawApi(string $method, array $params = []): array
    {
        return $this->post('/bot/raw/' . $method, $params);
    }
}
