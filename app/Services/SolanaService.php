<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SolanaService
{
    protected string $rpcUrl;

    public function __construct()
    {
        $this->rpcUrl = config('solana.rpc_url', 'http://127.0.0.1:8899');
    }

    protected function request(string $method, array $params = [])
    {
        $response = Http::post($this->rpcUrl, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params,
        ]);

        if ($response->failed()) {
            Log::error("❌ Solana RPC Error [$method]", ['error' => $response->body()]);
            throw new \Exception("Solana RPC failed: " . $response->body());
        }

        return $response->json('result');
    }

    public function getBalance(string $walletAddress): float
    {
        $lamports = $this->request('getBalance', [$walletAddress])['value'] ?? 0;
        return $lamports / 1e9; // Convert lamports to SOL
    }

    public function getTransaction(string $signature): array
    {
        return $this->request('getConfirmedTransaction', [$signature]);
    }

    public function getRecentBlockhash(): string
    {
        return $this->request('getRecentBlockhash')['value']['blockhash'];
    }
}
