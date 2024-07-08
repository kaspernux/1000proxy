<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Facades\Http;

class XUIService
{
    protected $baseUrl;
    protected $token;
    protected $username;
    protected $password;

    public function __construct(Server $server)
    {
        $this->baseUrl = $server->config->panel_url;
        $this->username = $server->config->username;
        $this->password = $server->config->password;
        $this->token = $this->login();
    }

    protected function login()
    {
        $response = Http::post("{$this->baseUrl}/login", [
            'username' => $this->username,
            'password' => $this->password,
        ]);

        return $response->json()['token'] ?? null;
    }

    // Inbound Operations

    public function createInbound(array $data)
    {
        $response = Http::withToken($this->token)->post("{$this->baseUrl}/panel/api/inbounds", $data);
        return $response->json();
    }

    public function getInbound($inboundId)
    {
        $response = Http::withToken($this->token)->get("{$this->baseUrl}/panel/api/inbounds/{$inboundId}");
        return $response->json();
    }

    public function updateInbound($inboundId, array $data)
    {
        $response = Http::withToken($this->token)->put("{$this->baseUrl}/panel/api/inbounds/{$inboundId}", $data);
        return $response->json();
    }

    public function deleteInbound($inboundId)
    {
        $response = Http::withToken($this->token)->delete("{$this->baseUrl}/panel/api/inbounds/{$inboundId}");
        return $response->json();
    }

    public function listInbounds()
    {
        $response = Http::withToken($this->token)->get("{$this->baseUrl}/panel/api/inbounds");
        return $response->json();
    }

    public function deleteDepletedClients($inboundId = null)
    {
        $url = "{$this->baseUrl}/panel/api/inbounds/delDepletedClients";
        if ($inboundId) {
            $url .= "/{$inboundId}";
        }

        $response = Http::withToken($this->token)->post($url);
        return $response->json();
    }

    public function getOnlineClients()
    {
        $response = Http::withToken($this->token)->post("{$this->baseUrl}/panel/api/inbounds/onlines");
        return $response->json();
    }

    public function exportDatabase()
    {
        $response = Http::withToken($this->token)->get("{$this->baseUrl}/panel/api/inbounds/createbackup");
        return $response->json();
    }

    // Client Operations

    public function createClient(array $data)
    {
        $response = Http::withToken($this->token)->post("{$this->baseUrl}/panel/api/clients", $data);
        return $response->json();
    }

    public function getClient($clientId)
    {
        $response = Http::withToken($this->token)->get("{$this->baseUrl}/panel/api/clients/{$clientId}");
        return $response->json();
    }

    public function updateClient($clientId, array $data)
    {
        $response = Http::withToken($this->token)->put("{$this->baseUrl}/panel/api/clients/{$clientId}", $data);
        return $response->json();
    }

    public function deleteClient($clientId)
    {
        $response = Http::withToken($this->token)->delete("{$this->baseUrl}/panel/api/clients/{$clientId}");
        return $response->json();
    }

    public function resetClientTraffic($clientId)
    {
        $response = Http::withToken($this->token)->post("{$this->baseUrl}/panel/api/clients/resetTraffic/{$clientId}");
        return $response->json();
    }

    // Other operations...

}