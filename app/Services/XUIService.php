<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Cookie\CookieJar;
use App\Models\Server;
 use App\Models\ServerPlan;
use App\Models\ServerClient;
use App\Models\ServerConfig;
use App\Models\ServerInbound;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class XUIService
{
    protected $baseUrl;
    protected $token;
    protected $username;
    protected $password;
    protected $httpClient;
    protected $client;
    protected $cookieJar;
    protected $config;

    public function __construct($server_id)
    {
        $server = Server::findOrFail($server_id);

        if (empty($server->panel_url) || empty($server->username) || empty($server->password)) {
            Log::error("Server configuration missing for server ID: " . $server_id);
            throw new Exception("Server configuration missing for server ID: " . $server_id);
        }

        $this->baseUrl = rtrim($server->panel_url, '/') . '/';
        $this->username = $server->username;
        $this->password = $server->password;

        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'cookies'  => true,
        ]);

        $this->token = $this->login();
    }

    protected function login()
    {
        $response = $this->httpClient->post('login', [
            'form_params' => [
                'username' => $this->username,
                'password' => $this->password,
            ],
        ]);
        return implode('; ', $response->getHeader('Set-Cookie'));
    }

    public function getInbounds()
    {
        $response = $this->httpClient->get('panel/api/inbounds/list', [
            'headers' => [
                'Cookie' => $this->token,
            ],
        ]);
        $responseBody = json_decode($response->getBody()->getContents());
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to parse JSON response: " . json_last_error_msg());
        }
        if (!isset($responseBody->obj)) {
            throw new Exception("Invalid response structure: 'obj' property not found.");
        }
        return $responseBody->obj;
    }

    protected function executeCurlRequest($url, $session, $dataArr = [])
    {
        $response = $this->httpClient->post($url, [
            'form_params' => $dataArr,
            'headers' => [
                'Cookie' => $session,
            ],
        ]);
        return $response->getBody()->getContents();
    }

    public function generateUID()
    {
        return (string) Str::uuid();
    }

    // Generate a random string based on type
    public function RandomString($count = 9, $type = "all")
    {
        $characters = $type === "all" ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789' :
            ($type === "small" ? 'abcdef123456789' : 'abcdefghijklmnopqrstuvwxyz');
        $randstring = '';
        for ($i = 0; $i < $count; $i++) {
            $randstring .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randstring;
    }

    // Get a step value from a given table using Laravel's DB façade
    public function checkStep($table)
    {
        $result = DB::table($table)->where('active', 0)->first();
        return $result ? $result->step : null;
    }

    // Format byte amounts as GB or MB
    public function summarize($amount)
    {
        $gb = $amount / (1024 * 1024 * 1024);
        if ($gb >= 1) {
            return round($gb, 2) . " GB";
        } else {
            $mb = $amount / (1024 * 1024);
            return round($mb, 2) . " MB";
        }
    }

    public function summarize2($amount)
    {
        $gb = $amount / (1024 * 1024 * 1024);
        return round($gb, 2);
    }

    // Generate a random string based on protocol
    public function generateRandomString($length, $protocol)
    {
        return ($protocol == 'trojan') ? substr(md5(time()), 5, 15) : $this->generateUID();
    }

    // Generate connection link(s) based on protocol
    public function generateConnectionLink($protocol, $uniqid, $remark, $port, $netType, $server_ip, $bypass, $customPath,
        $customPort, $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid,
        $serviceName, $grpcSecurity, $alpn, $kcpType, $kcpSeed)
    {
        $protocol = strtolower($protocol);
        $serverIp = explode("\n", $server_ip);
        $outputLinks = [];
        foreach ($serverIp as $ip) {
            $ip = trim(str_replace("\r", "", $ip));
            if ($protocol == 'vless') {
                $outputLinks[] = $this->generateVlessLink($uniqid, $remark, $port, $netType, $ip, $bypass, $customPath, $customPort, $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid, $serviceName, $grpcSecurity, $alpn, $kcpType, $kcpSeed);
            } elseif ($protocol == 'trojan') {
                $outputLinks[] = $this->generateTrojanLink($uniqid, $remark, $port, $netType, $ip, $bypass, $customPath, $customPort, $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid, $serviceName, $grpcSecurity, $alpn, $kcpType, $kcpSeed);
            } elseif ($protocol == 'vmess') {
                $outputLinks[] = $this->generateVmessLink($uniqid, $remark, $port, $netType, $ip, $bypass, $customPath, $customPort, $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid, $serviceName, $grpcSecurity, $alpn, $kcpType, $kcpSeed);
            }
        }
        return implode("\n", $outputLinks);
    }

    // Generate a VLESS link string
    public function generateVlessLink($uniqid, $remark, $port, $netType, $server_ip, $bypass, $customPath, $customPort,
        $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid, $serviceName,
        $grpcSecurity, $alpn, $kcpType, $kcpSeed)
    {
        return "vless://$uniqid@$server_ip:$port?remarks=$remark&path=$path&security=$tlsStatus&encryption=none&alpn=$alpn&fp=$fp&sni=$sni&pbk=$pbk&sid=$sid&spiderX=$spiderX&serviceName=$serviceName&grpcSecurity=$grpcSecurity&host=$host";
    }

    // Generate a Trojan link string
    public function generateTrojanLink($uniqid, $remark, $port, $netType, $server_ip, $bypass, $customPath, $customPort,
        $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid, $serviceName,
        $grpcSecurity, $alpn, $kcpType, $kcpSeed)
    {
        return "trojan://$uniqid@$server_ip:$port?remarks=$remark&path=$path&security=$tlsStatus&alpn=$alpn&fp=$fp&sni=$sni&pbk=$pbk&sid=$sid&spiderX=$spiderX&serviceName=$serviceName&grpcSecurity=$grpcSecurity&host=$host";
    }

    // Generate a VMess link string (base64-encoded JSON)
    public function generateVmessLink($uniqid, $remark, $port, $netType, $server_ip, $bypass, $customPath, $customPort, $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid, $serviceName, $grpcSecurity, $alpn, $kcpType, $kcpSeed)
    {
        $link = [
            'v'      => '2',
            'ps'     => $remark,
            'add'    => $server_ip,
            'port'   => $port,
            'id'     => $uniqid,
            'aid'    => '0',
            'scy'    => 'auto',
            'net'    => $netType,
            'type'   => $header_type,
            'host'   => $host,
            'path'   => $path,
            'tls'    => $tlsStatus,
            'fp'     => $fp,
            'alpn'   => $alpn,
            'pbk'    => $pbk,
            'sid'    => $sid,
            'spiderX'=> $spiderX,
            'serviceName' => $serviceName,
            'grpcSecurity' => $grpcSecurity,
            'kcpType' => $kcpType,
            'kcpSeed' => $kcpSeed
        ];
        return 'vmess://' . base64_encode(json_encode($link, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    public function addInboundAccount($server_id, $client_id, $inbound_id, $expiryTime, $remark, $volume, $limitip = 1, $planId = null)
    {
        $server = Server::findOrFail($server_id);
        $volume = ($volume == 0) ? 0 : floor($volume * 1073741824);
        $subId   = $this->generateUID();

        // Build the new-client payload
        $newClient = [
            'id'         => $client_id,
            'enable'     => true,
            'email'      => $remark,
            'limitIp'    => $limitip,
            'totalGB'    => $volume,
            'expiryTime' => $expiryTime,
            'subId'      => $subId,
        ];

        // (optional) your existing flow logic here...

        $settings = json_encode(['clients' => [$newClient]]);
        $dataArr   = ['id' => $inbound_id, 'settings' => $settings];

        // Attempt to create
        $url      = 'panel/api/inbounds/addClient';
        $response = $this->executeCurlRequest($url, $this->token, $dataArr);
        $parsed   = json_decode($response, true);

        // Handle duplicate‐email as “fetch existing” instead of failing
        if (empty($parsed['success'])) {
            $msg = $parsed['msg'] ?? '';
            if (str_contains($msg, 'Duplicate email')) {
                // load the inbound and find the client record by email
                $inbound = collect($this->getInbounds())->firstWhere('id', $inbound_id);
                $clients = json_decode($inbound->settings, true)['clients'] ?? [];
                $remoteClient = collect($clients)->firstWhere('email', $remark);

                if (! $remoteClient) {
                    throw new Exception("XUI reported duplicate email, but no matching client found remotely.");
                }
            } else {
                throw new Exception("XUI API error: " . json_encode($parsed));
            }
        } else {
            // on success, re-fetch and grab the newly-created client by its id
            sleep(1);
            $inbound       = collect($this->getInbounds())->firstWhere('id', $inbound_id);
            $clients       = json_decode($inbound->settings, true)['clients'] ?? [];
            $remoteClient  = collect($clients)->firstWhere('id', $client_id);

            if (! $remoteClient) {
                throw new Exception("XUI: Created client ID {$client_id} not found in inbound settings.");
            }
        }

        // Ensure we have a local inbound record
        $localInbound = ServerInbound::updateOrCreate(
            ['server_id' => $server_id, 'port' => $inbound->port],
            [
                'protocol'       => $inbound->protocol,
                'settings'       => json_decode($inbound->settings, true),
                'streamSettings' => json_decode($inbound->streamSettings, true),
                'sniffing'       => json_decode($inbound->sniffing, true),
                'enable'         => $inbound->enable,
            ]
        );

        // Build the subscription links
        $link     = ServerClient::buildXuiClientLink($remoteClient, $localInbound, $server);
        $subLink  = "{$server->getPanelBase()}/sub_proxy/{$remoteClient['subId']}";
        $jsonLink = "{$server->getPanelBase()}/json_proxy/{$remoteClient['subId']}";

        // Return a unified array for both “new” and “duplicate” cases
        return array_merge($remoteClient, [
            'subId'       => $remoteClient['subId'],
            'link'        => $link,
            'sub_link'    => $subLink,
            'json_link'   => $jsonLink,
        ]);
    }

    public function getDefaultInboundId()
    {
        $response = $this->httpClient->get('panel/api/inbounds/list', [
            'headers' => [
                'Accept' => 'application/json',
                'Cookie' => $this->token,
            ],
        ]);

        $responseBody = json_decode($response->getBody()->getContents(), true);

        if ($responseBody['success']) {
            foreach ($responseBody['obj'] as $inbound) {
                if ($inbound['remark'] == 'Default Inbound') {
                    return $inbound['id'];
                }
            }
        }

        throw new \Exception("Default inbound not found.");
    }

    // XUI API Methods

    public function listInbounds()
    {
        return $this->request('GET', '/panel/api/inbounds/list');
    }

    public function getInbound($inboundId)
    {
        return $this->request('GET', "/panel/api/inbounds/get/{$inboundId}");
    }

    public function addInbound(array $payload)
    {
        return $this->request('POST', '/panel/api/inbounds/add', $payload);
    }

    public function updateInbound($inboundId, array $payload)
    {
        return $this->request('POST', "/panel/api/inbounds/update/{$inboundId}", $payload);
    }

    public function deleteInbound($inboundId)
    {
        return $this->request('POST', "/panel/api/inbounds/del/{$inboundId}");
    }

    public function addClient($inboundId, array $settings)
    {
        return $this->request('POST', '/panel/api/inbounds/addClient', [
            'id' => $inboundId,
            'settings' => json_encode($settings),
        ]);
    }

    public function updateClient($uuid, array $settings)
    {
        return $this->request('POST', "/panel/api/inbounds/updateClient/{$uuid}", [
            'settings' => json_encode($settings),
        ]);
    }

    public function deleteClient($inboundId, $uuid)
    {
        return $this->request('POST', "/panel/api/inbounds/{$inboundId}/delClient/{$uuid}");
    }

    public function getClientTrafficByEmail($email)
    {
        return $this->request('GET', "/panel/api/inbounds/getClientTraffics/{$email}");
    }

    public function getClientTrafficById($uuid)
    {
        return $this->request('GET', "/panel/api/inbounds/getClientTrafficsById/{$uuid}");
    }

    public function clearClientIps($email)
    {
        return $this->request('POST', "/panel/api/inbounds/clearClientIps/{$email}");
    }

    public function resetAllTraffics()
    {
        return $this->request('POST', '/panel/api/inbounds/resetAllTraffics');
    }

    public function resetAllClientTraffics($inboundId)
    {
        return $this->request('POST', "/panel/api/inbounds/resetAllClientTraffics/{$inboundId}");
    }

    public function resetClientTraffic($inboundId, $email)
    {
        return $this->request('POST', "/panel/api/inbounds/{$inboundId}/resetClientTraffic/{$email}");
    }

    public function createBackup()
    {
        return $this->request('GET', '/panel/api/inbounds/createbackup');
    }

    public function deleteDepletedClients($inboundId)
    {
        return $this->request('POST', "/panel/api/inbounds/delDepletedClients/{$inboundId}");
    }

    public function onlineClients()
    {
        return $this->request('POST', '/panel/api/inbounds/onlines');
    }

    protected function request($method, $uri, array $body = [])
    {
        $options = [
            'cookies' => $this->cookieJar,
        ];

        if (!empty($body)) {
            if ($method === 'POST') {
                $options['form_params'] = $body;
            }
        }

        $response = $this->client->request($method, $uri, $options);

        $data = json_decode((string) $response->getBody(), true);

        if (!($data['success'] ?? false)) {
            throw new Exception('XUI Error: ' . ($data['msg'] ?? 'Unknown error'));
        }

        return $data['obj'] ?? $data;
    }

    // Update configuration for an inbound
    public function updateConfig($server, $inboundId, $protocol, $netType = 'tcp', $security = 'none', $bypass = false)
    {
        $serverConfig = $server->serverConfig;
        $sni = $serverConfig->sni;
        $tlsSettings = $serverConfig->tlsSettings;
        $requestHeader = $serverConfig->request_header;
        $responseHeader = $serverConfig->response_header;
        $headerType = $serverConfig->header_type;
        $serverType = $serverConfig->type;

        if (!empty($sni) && in_array($serverType, ['sanaei', 'alireza'])) {
            $tlsArray = json_decode($tlsSettings, true);
            $tlsArray['serverName'] = $sni;
            $tlsSettings = json_encode($tlsArray, JSON_UNESCAPED_UNICODE);
        }

        $inbound = $this->getInbound($inboundId);

        $headers = $this->getNewHeaders($netType, $requestHeader, $responseHeader, $headerType);
        $streamSettings = $this->generateStreamSettings($protocol, $netType, $security, $tlsSettings, $headers, $server->panel_url, $sni, $serverType);

        $payload = [
            'up' => $inbound['up'],
            'down' => $inbound['down'],
            'total' => $inbound['total'],
            'remark' => $inbound['remark'],
            'enable' => true,
            'expiryTime' => $inbound['expiryTime'] ?? null,
            'listen' => '',
            'port' => $inbound['port'],
            'protocol' => $protocol,
            'settings' => $inbound['settings'],
            'streamSettings' => json_decode($streamSettings, true),
            'sniffing' => $inbound['sniffing'] ?? [],
        ];

        return $this->updateInbound($inboundId, $payload);
    }

    protected function generateStreamSettings($protocol, $netType, $security, $tlsSettings, $headers, $panelUrl, $sni, $serverType)
    {
        if ($protocol === 'trojan') {
            if ($netType === 'grpc') {
                return $this->generateGrpcSettings($security, $tlsSettings, $sni, $serverType, $panelUrl);
            }
            $tcpSettings = $this->generateTcpWsSettings('tcp', $security, $tlsSettings, $headers, $serverType);
            $wsSettings = $this->generateTcpWsSettings('ws', $security, $tlsSettings, $headers, $serverType, true);
            return ($netType === 'tcp') ? $tcpSettings : $wsSettings;
        }
        return json_encode([]);
    }

    protected function generateTcpWsSettings($network, $security, $tlsSettings, $headers, $serverType, $bypass = false)
    {
        $path = $bypass ? '/wss' : '/';
        $base = [
            'network' => $network,
            'security' => $security,
        ];
        if ($security === 'tls') {
            $base['tlsSettings'] = json_decode($tlsSettings, true);
        }
        if ($security === 'xtls' && !in_array($serverType, ['sanaei', 'alireza'])) {
            $base['xtlsSettings'] = json_decode($tlsSettings, true);
        }
        $base["{$network}Settings"] = [
            'path' => $path,
            'header' => json_decode($headers, true),
        ];

        return json_encode($base, JSON_UNESCAPED_UNICODE);
    }

    protected function generateGrpcSettings($security, $tlsSettings, $sni, $serverType, $panelUrl)
    {
        $tls = json_decode($tlsSettings, true);
        $certFile = $tls['certificates'][0]['certificateFile'] ?? '/root/cert.crt';
        $keyFile = $tls['certificates'][0]['keyFile'] ?? '/root/private.key';

        if ($security === 'tls') {
            return json_encode([
                'network' => 'grpc',
                'security' => 'tls',
                'tlsSettings' => [
                    'serverName' => $sni ?: parse_url($panelUrl, PHP_URL_HOST),
                    'certificates' => [[
                        'certificateFile' => $certFile,
                        'keyFile' => $keyFile,
                    ]],
                    'alpn' => [],
                ],
                'grpcSettings' => [
                    'serviceName' => '',
                ],
            ], JSON_UNESCAPED_UNICODE);
        }

        return json_encode([
            'network' => 'grpc',
            'security' => 'none',
            'grpcSettings' => [
                'serviceName' => parse_url($panelUrl, PHP_URL_HOST),
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    protected function authenticateAndUpdateInbound($inboundId, array $dataArr)
    {
        return $this->updateInbound($inboundId, $dataArr);
    }

    public function changeInboundState($inboundId)
    {
        $inbound = $this->getInbound($inboundId);

        $payload = [
            'up' => $inbound['up'],
            'down' => $inbound['down'],
            'total' => $inbound['total'],
            'remark' => $inbound['remark'],
            'enable' => !$inbound['enable'], // toggle
            'expiryTime' => $inbound['expiryTime'] ?? null,
            'listen' => '',
            'port' => $inbound['port'],
            'protocol' => $inbound['protocol'],
            'settings' => $inbound['settings'],
            'streamSettings' => $inbound['streamSettings'],
            'sniffing' => $inbound['sniffing'] ?? [],
        ];

        return $this->updateInbound($inboundId, $payload);
    }

    public function renewInboundUuid($inboundId, $oldUuid)
    {
        $inbound = $this->getInbound($inboundId);
        $settings = json_decode($inbound['settings'], true);

        $newUuid = (string) Str::uuid();

        foreach ($settings['clients'] as &$client) {
            if ($client['id'] === $oldUuid || $client['password'] === $oldUuid) {
                if ($inbound['protocol'] === 'trojan') {
                    $client['password'] = $newUuid;
                } else {
                    $client['id'] = $newUuid;
                }
                break;
            }
        }

        $payload = [
            'settings' => json_encode(['clients' => $settings['clients']]),
        ];

        $this->updateClient($oldUuid, $payload);

        return ['newUuid' => $newUuid];
    }

    public function editClientRemark($inboundId, $uuid, $newRemark)
    {
        $inbound = $this->getInbound($inboundId);
        $settings = json_decode($inbound['settings'], true);

        foreach ($settings['clients'] as &$client) {
            if ($client['id'] === $uuid || $client['password'] === $uuid) {
                $client['email'] = $newRemark;
                break;
            }
        }

        $payload = [
            'settings' => json_encode(['clients' => $settings['clients']]),
        ];

        return $this->updateClient($uuid, $payload);
    }

    public function editClientTraffic($inboundId, $uuid, $extraVolumeGb, $extraDays)
    {
        $inbound = $this->getInbound($inboundId);
        $settings = json_decode($inbound['settings'], true);

        foreach ($settings['clients'] as &$client) {
            if ($client['id'] === $uuid || $client['password'] === $uuid) {
                if ($extraVolumeGb > 0) {
                    $client['totalGB'] = ($client['totalGB'] ?? 0) + ($extraVolumeGb * 1073741824);
                }
                if ($extraDays > 0) {
                    $currentExpiry = $client['expiryTime'] ?? (time() * 1000);
                    $newExpiry = $currentExpiry + ($extraDays * 86400 * 1000);
                    $client['expiryTime'] = $newExpiry;
                }
                break;
            }
        }

        $payload = [
            'settings' => json_encode(['clients' => $settings['clients']]),
        ];

        return $this->updateClient($uuid, $payload);
    }

    public function resetIpLog($email)
    {
        return $this->clearClientIps($email);
    }

    public function getNewHeaders($netType, $requestHeader, $responseHeader, $headerType)
    {
        $inputRequest = explode(':', $requestHeader);
        $inputResponse = explode(':', $responseHeader);

        $key = trim($inputRequest[0] ?? '');
        $value = trim($inputRequest[1] ?? '');
        $reskey = trim($inputResponse[0] ?? '');
        $resvalue = trim($inputResponse[1] ?? '');

        if ($netType === 'tcp') {
            if ($headerType === 'none') {
                return json_encode(['type' => 'none']);
            }

            return json_encode([
                'type' => 'http',
                'request' => [
                    'method' => 'GET',
                    'path' => ['/'],
                    'headers' => [
                        $key => [$value],
                    ],
                ],
                'response' => [
                    'version' => '1.1',
                    'status' => '200',
                    'reason' => 'OK',
                    'headers' => [
                        $reskey => [$resvalue],
                    ],
                ],
            ]);
        }

        if ($netType === 'ws') {
            if ($headerType === 'none') {
                return '{}';
            }

            return json_encode([
                $key => $value,
            ]);
        }

        return '{}';
    }

    public function getConnectionLink($server, $uuid, $protocol, $remark, $port, $netType, $inboundId = null)
    {
        $panelHost = parse_url($server->panel_url, PHP_URL_HOST) ?? $server->ip ?? '';
        $serverIp = $server->ip ?: $panelHost;
        $sni = $server->sni;
        $headerType = $server->header_type;
        $requestHeader = $server->request_header;
        $responseHeader = $server->response_header;

        $inbounds = $this->listInbounds();
        foreach ($inbounds as $row) {
            if ($inboundId === null) {
                $clients = json_decode($row['settings'], true)['clients'] ?? [];
                if (isset($clients[0]) && ($clients[0]['id'] === $uuid || $clients[0]['password'] === $uuid)) {
                    return $this->extractServerDetailsAndGenerateLink($row, $serverIp, $uuid, $protocol, $remark, $port, $netType, $sni, $headerType, $requestHeader);
                }
            } elseif ($row['id'] == $inboundId) {
                return $this->extractServerDetailsAndGenerateLink($row, $serverIp, $uuid, $protocol, $remark, $port, $netType, $sni, $headerType, $requestHeader);
            }
        }

        return null;
    }

    protected function extractServerDetailsAndGenerateLink($row, $serverIp, $uuid, $protocol, $remark, $port, $netType, $sni, $headerType, $requestHeader)
    {
        $streamSettings = json_decode($row['streamSettings'], true);
        $tlsStatus = $streamSettings['security'] ?? 'none';
        $path = '/';
        $host = $serverIp;

        if ($netType === 'tcp' && isset($streamSettings['tcpSettings'])) {
            $path = $streamSettings['tcpSettings']['header']['request']['path'][0] ?? '/';
            $host = $streamSettings['tcpSettings']['header']['request']['headers']['Host'][0] ?? $serverIp;
        }
        if ($netType === 'ws' && isset($streamSettings['wsSettings'])) {
            $path = $streamSettings['wsSettings']['path'] ?? '/';
            $host = $streamSettings['wsSettings']['headers']['Host'] ?? $serverIp;
        }

        return $this->generateConnectionLinkWrapper(
            $protocol, $uuid, $remark, $port, $netType, $serverIp,
            false, false, 0, null, $sni, $headerType, $host,
            $tlsStatus, $path, null, null, null, null, null, null, null, null, null, null
        );
    }

    public function generateConnectionLinkWrapper($protocol, $uuid, $remark, $port, $netType, $serverIp, $bypass, $customPath, $customPort, $customSni, $sni, $headerType, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid, $serviceName, $grpcSecurity, $alpn, $kcpType, $kcpSeed) 
    { 
        $protocol = strtolower($protocol);

        if ($protocol === 'vless') {
            return "vless://$uuid@$serverIp:$port?encryption=none&security=$tlsStatus&path=$path&host=$host&sni=$sni#$remark";
        }

        if ($protocol === 'trojan') {
            return "trojan://$uuid@$serverIp:$port?security=$tlsStatus&host=$host&sni=$sni#$remark";
        }

        if ($protocol === 'vmess') {
            $vmess = [
                'v' => '2',
                'ps' => $remark,
                'add' => $serverIp,
                'port' => (string) $port,
                'id' => $uuid,
                'aid' => '0',
                'scy' => 'auto',
                'net' => $netType,
                'type' => $headerType,
                'host' => $host,
                'path' => $path,
                'tls' => $tlsStatus,
            ];
            return 'vmess://' . base64_encode(json_encode($vmess, JSON_UNESCAPED_SLASHES));
        }

        return null;
    }

    public function getServerIp($server)
    {
        return $server->ip ?: parse_url($server->panel_url, PHP_URL_HOST);
    }

    public function getServerPort($server)
    {
        return $server->port ?: 443;
    }

    public function getServerSni($server)
    {
        return $server->sni ?: parse_url($server->panel_url, PHP_URL_HOST);
    }

    public function getServerProtocol($server)
    {
        return $server->protocol ?: 'vmess';
    }

    public function getServerRemark($server)
    {
        return $server->remark ?: 'Default Inbound';
    }

    public function getServerNetType($server)
    {
        return $server->net_type ?: 'tcp';
    }

    public function getServerHeaderType($server)
    {
        return $server->header_type ?: 'none';
    }

    public function getServerPath($server)
    {
        return $server->path ?: '/';
    }
    public function getServerHost($server)
    {
        return $server->host ?: $this->getServerIp($server);
    }

    public function getServerTlsStatus($server)
    {
        return $server->tls_status ?: 'none';
    }

    public function getServerFlow($server)
    {
        return $server->flow ?: '';
    }

    public function getServerFp($server)
    {
        return $server->fp ?: '';
    } 

    public function getServerSpiderX($server)
    {
        return $server->spiderX ?: '';
    }

    public function getServerPbk($server)
    {
        return $server->pbk ?: '';
    }

    public function getServerSid($server)
    {
        return $server->sid ?: '';
    }

    public function getServerServiceName($server)
    {
        return $server->service_name ?: '';
    }

    public function getServerGrpcSecurity($server)
    {
        return $server->grpc_security ?: '';
    }

    public function getServerAlpn($server)
    {
        return $server->alpn ?: '';
    }

    public function getServerKcpType($server)
    {
        return $server->kcp_type ?: '';
    }

    public function getServerKcpSeed($server)
    {
        return $server->kcp_seed ?: '';
    }

    public function getServerCustomPath($server)
    {
        return $server->custom_path ?: '';
    }

    public function getServerCustomPort($server)
    {
        return $server->custom_port ?: '';
    }

    public function getServerCustomSni($server)
    {
        return $server->custom_sni ?: '';
    }
    
}
