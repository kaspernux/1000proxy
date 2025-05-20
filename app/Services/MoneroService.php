<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoneroService
{
    protected string $host;
    protected int $port;
    protected string $user;
    protected string $pass;
    protected int $accountIndex;

    public function __construct()
    {
        $this->host = config('monero.rpc_host', '127.0.0.1');
        $this->port = (int) config('monero.rpc_port', 18082);
        $this->user = config('monero.rpc_user', '');
        $this->pass = config('monero.rpc_password', '');
        $this->accountIndex = (int) config('monero.account_index', 0);
    }

    protected function request(string $method, array $params = [])
    {
        $url = "http://{$this->host}:{$this->port}/json_rpc";

        $http = Http::withHeaders([
            'Content-Type' => 'application/json',
        ]);

        if ($this->user && $this->pass) {
            $http = $http->withBasicAuth($this->user, $this->pass);
        }

        $response = $http->post($url, [
            'jsonrpc' => '2.0',
            'id' => 'laravel',
            'method' => $method,
            'params' => $params,
        ]);

        if ($response->failed()) {
            Log::error("❌ Monero RPC Error [$method]", [
                'error' => $response->body(),
            ]);
            throw new \Exception("Monero RPC failed: " . $response->body());
        }

        return $response->json('result');
    }

    public function makeIntegratedAddress(string $paymentId = null): array
    {
        return $this->request('make_integrated_address', [
            'payment_id' => $paymentId ?? bin2hex(random_bytes(8)),
        ]);
    }

    public function getAddress(array $subaddressIndices = []): array
    {
        return $this->request('get_address', [
            'account_index' => $this->accountIndex,
            'address_index' => $subaddressIndices,
        ]);
    }

    public function createAddress(string $label = ''): ?string
    {
        $result = $this->request('create_address', [
            'account_index' => $this->accountIndex,
            'label' => $label,
        ]);

        return $result['address'] ?? null;
    }

    public function getBalance(): float
    {
        $result = $this->request('get_balance', [
            'account_index' => $this->accountIndex,
        ]);

        return ($result['balance'] ?? 0) / 1e12;
    }

    public function getTransfers(int $minConfirmations = 10): array
    {
        $transfers = $this->request('get_transfers', [
            'in' => true,
            'account_index' => $this->accountIndex,
        ])['in'] ?? [];

        return array_filter($transfers, function ($tx) use ($minConfirmations) {
            return isset($tx['confirmations']) && $tx['confirmations'] >= $minConfirmations;
        });
    }

    public function getConfirmedTransferToAddress(string $address, int $minConfirmations = 10, ?string $fallbackPaymentId = null): ?array
    {
        $transfers = $this->getTransfers($minConfirmations);

        foreach ($transfers as $tx) {
            // Primary: Match by unique subaddress
            if ($tx['address'] === $address) {
                return $tx;
            }

            // Fallback: Match by payment_id (less secure)
            if ($fallbackPaymentId && isset($tx['payment_id']) && $tx['payment_id'] === $fallbackPaymentId) {
                return $tx;
            }
        }

        return null;
    }
}
