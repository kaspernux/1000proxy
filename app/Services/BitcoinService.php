<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BitcoinService
{
    protected string $rpcUser;
    protected string $rpcPassword;
    protected string $rpcHost;
    protected int $rpcPort;

    public function __construct()
    {
        $this->rpcUser = config('bitcoin.rpc_user');
        $this->rpcPassword = config('bitcoin.rpc_password');
        $this->rpcHost = config('bitcoin.rpc_host');
        $this->rpcPort = config('bitcoin.rpc_port');
    }

    protected function request(string $method, array $params = [])
    {
        $response = Http::withBasicAuth($this->rpcUser, $this->rpcPassword)
            ->post("http://{$this->rpcHost}:{$this->rpcPort}/", [
                'jsonrpc' => '1.0',
                'id' => 'laravel',
                'method' => $method,
                'params' => $params,
            ]);

        if ($response->failed()) {
            throw new \Exception("Bitcoin RPC failed: " . $response->body());
        }

        return $response->json()['result'] ?? null;
    }

    public function getNewAddress(string $label = '')
    {
        return $this->request('getnewaddress', [$label]);
    }

    public function getBalance()
    {
        return $this->request('getbalance');
    }

    public function getTransaction(string $txid)
    {
        return $this->request('gettransaction', [$txid]);
    }

    public function listTransactions(string $label = '*', int $count = 10)
    {
        return $this->request('listtransactions', [$label, $count]);
    }

    public function getBlockchainInfo()
    {
        return $this->request('getblockchaininfo');
    }
}
