<?php

namespace App\Services;

use Exception;
use App\Models\Server;
use App\Models\ServerPlan;
use App\Models\ServerConfig;
use App\Models\ServerInbound;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class XUIService
{
    protected $baseUrl;
    protected $token;
    protected $username;
    protected $password;
    protected $httpClient;

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

    // Login via HTTP client (Guzzle)
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

    // Get list of inbounds from remote panel
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

    // Helper method mimicking original getJson()
    protected function getJson($server_id)
    {
        return (object)[
            'obj' => $this->getInbounds($server_id)
        ];
    }

    // Execute an HTTP POST request using Guzzle (used to mimic cURL requests)
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

    // Generate a UUID-like string
    public function generateUID()
    {
        $randomString = openssl_random_pseudo_bytes(16);
        $time_low = bin2hex(substr($randomString, 0, 4));
        $time_mid = bin2hex(substr($randomString, 4, 2));
        $time_hi_and_version = bin2hex(substr($randomString, 6, 2));
        $clock_seq_hi_and_reserved = bin2hex(substr($randomString, 8, 2));
        $node = bin2hex(substr($randomString, 10, 6));
        $time_hi_and_version = hexdec($time_hi_and_version) >> 4 | 0x4000;
        $clock_seq_hi_and_reserved = hexdec($clock_seq_hi_and_reserved) >> 2 | 0x8000;
        return sprintf(
            '%08s-%04s-%04x-%04x-%012s',
            $time_low,
            $time_mid,
            $time_hi_and_version,
            $clock_seq_hi_and_reserved,
            $node
        );
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
    public function generateVmessLink($uniqid, $remark, $port, $netType, $server_ip, $bypass, $customPath, $customPort,
        $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid, $serviceName,
        $grpcSecurity, $alpn, $kcpType, $kcpSeed)
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

    // Add an inbound account by updating the inbound's clients settings
    public function addInboundAccount($server_id, $client_id, $inbound_id, $expiryTime, $remark, $volume, $limitip = 1, $planId = null)
    {
        $server = Server::findOrFail($server_id);
        $volume = ($volume == 0) ? 0 : floor($volume * 1073741824);

        $newClient = [
            "id"         => $client_id,
            "enable"     => true,
            "email"      => $remark,
            "limitIp"    => $limitip,
            "totalGB"    => $volume,
            "expiryTime" => $expiryTime,
            "subId"      => $this->generateUID(),
        ];

        if (($server->type == "sanaei" || $server->type == "alireza") && $server->reality == "true") {
            $plan = ServerPlan::find($planId);
            $flow = isset($plan->flow) && $plan->flow != "None" ? $plan->flow : "";
            $newClient['flow'] = $flow;
        }

        $settings = json_encode(["clients" => [$newClient]]);

        $dataArr = [
            'id' => $inbound_id,
            'settings' => $settings
        ];

        $url = rtrim($this->baseUrl, '/') . '/panel/api/inbounds/addClient';

        $response = $this->executeCurlRequest($url, $this->token, $dataArr);

        return json_decode($response, true);
    }

    // Edit an inbound by matching a client's UUID/password
    public function editInbound($server_id, $uniqid, $uuid, $protocol, $netType = 'tcp', $security = 'none', $bypass = false)
    {
        $server_info = DB::table('server_config')->where('id', $server_id)->first();
        if (!$server_info) {
            return null;
        }
        $panel_url       = $server_info->panel_url;
        $security        = $server_info->security;
        $tlsSettings     = $server_info->tlsSettings;
        $header_type     = $server_info->header_type;
        $request_header  = $server_info->request_header;
        $response_header = $server_info->response_header;
        $serverType      = $server_info->type;
        $xtlsTitle       = ($serverType == "sanaei" || $serverType == "alireza") ? "XTLSSettings" : "xtlsSettings";
        $sni             = $server_info->sni;

        if (!empty($sni) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $tlsArr = json_decode($tlsSettings, true);
            $tlsArr['serverName'] = $sni;
            $tlsSettings = json_encode($tlsArr, JSON_UNESCAPED_UNICODE);
        }

        $responseObj = $this->getJson($server_id);
        if (!$responseObj) return null;
        $inbounds = $responseObj->obj;
        $found = false;
        foreach ($inbounds as $row) {
            $clients = json_decode($row->settings)->clients;
            if ($clients[0]->id == $uuid || $clients[0]->password == $uuid) {
                $iid = $row->id;
                $remark = $row->remark;
                $streamSettings = $row->streamSettings;
                $settings = $row->settings;
                $rowData = $row;
                $found = true;
                break;
            }
        }
        if (!$found || !intval($iid)) return null;

        $headers = $this->getNewHeaders($netType, $request_header, $response_header, $header_type);
        $headers = empty($headers) ? "{}" : $headers;

        if ($protocol == 'trojan') {
            $tcpSettings = json_encode([
                "network"   => "tcp",
                "security"  => $security,
                "tlsSettings" => json_decode($tlsSettings, true),
                "tcpSettings" => [
                    "header" => json_decode($headers, true)
                ]
            ]);
            $wsSettings = json_encode([
                "network"   => "ws",
                "security"  => $security,
                "tlsSettings" => json_decode($tlsSettings, true),
                "wsSettings" => [
                    "path"    => "/",
                    "headers" => json_decode($headers, true)
                ]
            ]);
        } else {
            $tcpSettings = $wsSettings = '{}';
        }

        $dataArr = [
            'up'            => $rowData->up,
            'down'          => $rowData->down,
            'total'         => $rowData->total,
            'remark'        => $remark,
            'enable'        => 'true',
            'expiryTime'    => $rowData->expiryTime,
            'listen'        => '',
            'port'          => $rowData->port,
            'protocol'      => $protocol,
            'settings'      => $settings,
            'streamSettings'=> ($netType == 'tcp') ? $tcpSettings : $wsSettings,
            'sniffing'      => $rowData->sniffing
        ];

        $session = $this->authenticateAndGetSession($server_info);
        if (!$session) {
            return ['success' => false, 'message' => 'Failed to authenticate'];
        }

        $url = ($serverType == "sanaei") ? "$panel_url/panel/api/inbound/update/$iid" : "$panel_url/xui/inbound/update/$iid";
        $response = $this->executeCurlRequest($url, $session, $dataArr);

        return $response;
    }

    // Delete an inbound or return its current stats if not deleting
    public function deleteInbound($server_id, $uuid, $delete = 0)
    {
        $server = Server::findOrFail($server_id);
        $inbounds = $this->getInbounds($server_id);
        $inbound = null;
        foreach ($inbounds as $row) {
            $clients = json_decode($row->settings);
            if ($clients->clients[0]->id == $uuid || $clients->clients[0]->password == $uuid) {
                $inbound = $row;
                break;
            }
        }
        if (!$inbound) {
            return null;
        }
        if ($delete == 1) {
            $url = $this->baseUrl . 'xui/inbound/del/' . $inbound->id;
            $response = $this->executeCurlRequest($url, $this->token, []);
            return json_decode($response);
        }
        return [
            'total'     => $inbound->total,
            'up'        => $inbound->up,
            'down'      => $inbound->down,
            'volume'    => ((int)$inbound->total - (int)$inbound->up - (int)$inbound->down),
            'port'      => $inbound->port,
            'protocol'  => $inbound->protocol,
            'expiryTime'=> $inbound->expiryTime,
            'uniqid'    => $uuid,
            'netType'   => json_decode($inbound->streamSettings)->network,
            'security'  => json_decode($inbound->streamSettings)->security,
        ];
    }

    // Add a client to an inbound
    public function addClientInbound($data)
    {
        $expiryTime = $data['expiryTime'] ? strtotime($data['expiryTime']) : null;
        $clientData = [
            "id" => $data['server_inbound_id'],
            "settings" => json_encode([
                "clients" => [
                    [
                        "id" => $data['client_id'],
                        "enable" => true,
                        "email" => $data['email'],
                        "limitIp" => $data['limitIp'],
                        "totalGB" => $data['totalGB'] * 1073741824,
                        "expiryTime" => $expiryTime,
                        "subId" => $this->generateUID()
                    ]
                ]
            ])
        ];

        try {
            $response = $this->httpClient->post('panel/api/inbounds/addClient', [
                'form_params' => $clientData,
                'headers' => [
                    'Accept' => 'application/json',
                    'Cookie' => $this->token,
                ],
            ]);
            $responseBody = $response->getBody()->getContents();
            $clientAddResponse = json_decode($responseBody);
            if (isset($clientAddResponse->success) && $clientAddResponse->success) {
                return $clientAddResponse->obj->clients[0];
            } else {
                Log::error('Error adding client: ' . json_encode($clientAddResponse));
                throw new Exception('Failed to add client');
            }
        } catch (Exception $e) {
            Log::error('Error adding client: ' . $e->getMessage());
            throw new Exception('Failed to add client');
        }
    }

    // Add an inbound and then add a client to it
    public function addInbound(Request $request)
    {
        $dataArr = [
            "enable"        => $request->input('enable', true),
            "remark"        => $request->input('remark', 'New inbound'),
            "listen"        => $request->input('listen', ''),
            "port"          => $request->input('port', 48965),
            "protocol"      => $request->input('protocol', 'vmess'),
            "expiryTime"    => $request->input('expiryTime', 0),
            "settings"      => json_encode($request->input('settings', [
                "clients" => [],
                "decryption" => "none",
                "fallbacks" => []
            ])),
            "streamSettings"=> json_encode($request->input('streamSettings', [
                "network" => "ws",
                "security" => "none",
                "wsSettings" => [
                    "acceptProxyProtocol" => false,
                    "path" => "/",
                    "headers" => new \stdClass()
                ]
            ])),
            "sniffing"      => json_encode($request->input('sniffing', [
                "enabled" => true,
                "destOverride" => ["http", "tls"]
            ]))
        ];
        Log::info('Data to be sent to XUI:', $dataArr);
        try {
            $response = $this->httpClient->post('panel/api/inbounds/add', [
                'form_params' => $dataArr,
                'headers' => [
                    'Accept' => 'application/json',
                    'Cookie' => $this->token,
                ],
            ]);
            $responseBody = $response->getBody()->getContents();
            $inboundResponse = json_decode($responseBody);
            if (isset($inboundResponse->success) && $inboundResponse->success) {
                $inboundId = $inboundResponse->obj->id;
                $client_id = $request->input('client_id', $this->generateUID());
                $volume = $request->input('volume', 1) == 0 ? 0 : floor($request->input('volume', 1) * 1073741824);
                $newClient = [
                    "id" => $client_id,
                    "enable" => true,
                    "email" => $request->input('remark', 'New Client'),
                    "limitIp" => $request->input('limitIp', 1),
                    "totalGB" => $volume,
                    "expiryTime" => strtotime($request->input('expiryTime', 0)),
                    "subId" => $this->generateUID()
                ];
                $server = Server::findOrFail($request->server_id);
                $serverType = $server->type;
                $settings = json_decode($inboundResponse->obj->settings, true);
                $settings['clients'][] = $newClient;
                $clientData = [
                    "id" => $inboundId,
                    "settings" => json_encode($settings)
                ];
                $url = ($serverType == "sanaei") ? "{$this->baseUrl}panel/inbound/addClient/" : "{$this->baseUrl}xui/inbound/addClient/";
                $clientResponse = $this->httpClient->post($url, [
                    'form_params' => $clientData,
                    'headers' => [
                        'Accept' => 'application/json',
                        'Cookie' => $this->token,
                    ],
                ]);
                $clientResponseBody = $clientResponse->getBody()->getContents();
                $clientAddResponse = json_decode($clientResponseBody);
                if (isset($clientAddResponse->success) && $clientAddResponse->success) {
                    return [
                        'obj'    => $inboundResponse->obj,
                        'client' => $clientAddResponse->obj->clients[0]
                    ];
                } else {
                    Log::error('Error adding client: ' . json_encode($clientAddResponse));
                    throw new Exception('Failed to add client');
                }
            } else {
                Log::error('Error adding inbound: ' . json_encode($inboundResponse));
                throw new Exception('Failed to add inbound');
            }
        } catch (Exception $e) {
            Log::error('Error adding inbound: ' . $e->getMessage());
            throw new Exception('Failed to add inbound');
        }
    }

    // Update client data for an inbound
    public function updateClient($data)
    {
        $clientData = [
            "id" => $data['server_inbound_id'],
            "settings" => json_encode([
                "clients" => [
                    [
                        "id" => $data['client_id'],
                        "enable" => $data['enable'],
                        "email" => $data['email'],
                        "limitIp" => $data['limitIp'],
                        "totalGB" => $data['totalGb'] * 1073741824,
                        "expiryTime" => $data['expiryTime'],
                        "subId" => $data['subId']
                    ]
                ]
            ])
        ];

        try {
            $response = $this->httpClient->post('panel/api/inbounds/updateClient', [
                'form_params' => $clientData,
                'headers' => [
                    'Accept' => 'application/json',
                    'Cookie' => $this->token,
                ],
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            Log::error('Error updating client: ' . $e->getMessage());
            throw new Exception('Failed to update client');
        }
    }

    // Delete a client by ID
    public function deleteClient($clientId)
    {
        try {
            $response = $this->httpClient->post("panel/api/inbounds/delClient/{$clientId}", [
                'headers' => [
                    'Accept' => 'application/json',
                    'Cookie' => $this->token,
                ],
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            Log::error('Error deleting client: ' . $e->getMessage());
            throw new Exception('Failed to delete client');
        }
    }

    // Update configuration for an inbound
    public function updateConfig($server_id, $inboundId, $protocol, $netType = 'tcp', $security = 'none', $bypass = false)
    {
        $server_info = DB::table('server_config')->where('id', $server_id)->first();
        if (!$server_info) return null;
        $panel_url = $server_info->panel_url;
        $security = $server_info->security;
        $tlsSettings = $server_info->tlsSettings;
        $header_type = $server_info->header_type;
        $request_header = $server_info->request_header;
        $response_header = $server_info->response_header;
        $cookie = 'Cookie: session=' . $server_info->cookie;
        $serverType = $server_info->type;
        $sni = $server_info->sni;
        $xtlsTitle = ($serverType == "sanaei" || $serverType == "alireza") ? "XTLSSettings" : "xtlsSettings";

        if (!empty($sni) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $tlsArr = json_decode($tlsSettings, true);
            $tlsArr['serverName'] = $sni;
            $tlsSettings = json_encode($tlsArr, JSON_UNESCAPED_UNICODE);
        }

        $jsonResponse = $this->getJson($server_id);
        if (!$jsonResponse) return null;
        $inbounds = $jsonResponse->obj;
        foreach ($inbounds as $row) {
            if ($row->id == $inboundId) {
                $iid = $row->id;
                $remark = $row->remark;
                $streamSettings = $row->streamSettings;
                $settings = $row->settings;
                break;
            }
        }
        if (!intval($iid)) return null;

        $headers = $this->getNewHeaders($netType, $request_header, $response_header, $header_type);
        $headers = empty($headers) ? "{}" : $headers;

        $streamSettings = $this->generateStreamSettings($protocol, $netType, $security, $tlsSettings, $headers, $panel_url, $sni, $serverType, $xtlsTitle, $bypass);

        $dataArr = [
            'up' => $row->up,
            'down' => $row->down,
            'total' => $row->total,
            'remark' => $remark,
            'enable' => 'true',
            'expiryTime' => $row->expiryTime,
            'listen' => '',
            'port' => $row->port,
            'protocol' => $protocol,
            'settings' => $settings,
            'streamSettings' => $streamSettings,
            'sniffing' => $row->sniffing
        ];

        return $this->authenticateAndUpdateInbound($panel_url, $server_info, $dataArr, $iid, $serverType);
    }

    // Helper to generate stream settings based on protocol and network
    protected function generateStreamSettings($protocol, $netType, $security, $tlsSettings, $headers, $panel_url, $sni, $serverType, $xtlsTitle, $bypass)
    {
        if ($protocol == 'trojan') {
            if ($netType == 'grpc') {
                return $this->generateGrpcSettings($security, $tlsSettings, $sni, $serverType, $panel_url);
            }
            $tcpSettings = $this->generateTcpWsSettings('tcp', $security, $tlsSettings, $headers, $xtlsTitle, $serverType);
            $wsSettings = $this->generateTcpWsSettings('ws', $security, $tlsSettings, $headers, $xtlsTitle, $serverType, $bypass);
            return ($netType == 'tcp') ? $tcpSettings : $wsSettings;
        }
        return null;
    }

    // Generate TCP/WS settings as JSON
    protected function generateTcpWsSettings($network, $security, $tlsSettings, $headers, $xtlsTitle, $serverType, $bypass = false)
    {
        $path = $bypass ? "/wss" : "/";
        return json_encode([
            'network' => $network,
            'security' => $security,
            $xtlsTitle => ($security == 'xtls' && ($serverType != "sanaei" && $serverType != "alireza")) ? json_decode($tlsSettings, true) : null,
            'tlsSettings' => ($security == 'tls') ? json_decode($tlsSettings, true) : null,
            "{$network}Settings" => [
                'path' => $path,
                'header' => json_decode($headers, true)
            ]
        ], JSON_UNESCAPED_UNICODE);
    }

    // Generate gRPC settings as JSON
    protected function generateGrpcSettings($security, $tlsSettings, $sni, $serverType, $panel_url)
    {
        $keyFileInfo = json_decode($tlsSettings, true);
        $certificateFile = "/root/cert.crt";
        $keyFile = '/root/private.key';
        if (isset($keyFileInfo['certificates'])) {
            $certificateFile = $keyFileInfo['certificates'][0]['certificateFile'];
            $keyFile = $keyFileInfo['certificates'][0]['keyFile'];
        }
        if ($security == 'tls') {
            return json_encode([
                'network' => 'grpc',
                'security' => 'tls',
                'tlsSettings' => [
                    'serverName' => !empty($sni) && ($serverType == "sanaei" || $serverType == "alireza") ? $sni : parse_url($panel_url, PHP_URL_HOST),
                    'certificates' => [
                        [
                            'certificateFile' => $certificateFile,
                            'keyFile' => $keyFile
                        ]
                    ],
                    'alpn' => []
                ],
                'grpcSettings' => [
                    'serviceName' => ''
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
        return json_encode([
            'network' => 'grpc',
            'security' => 'none',
            'grpcSettings' => [
                'serviceName' => parse_url($panel_url, PHP_URL_HOST)
            ]
        ], JSON_UNESCAPED_UNICODE);
    }

    // Authenticate and update an inbound using cURL via Guzzle
    protected function authenticateAndUpdateInbound($panel_url, $server_info, $dataArr, $iid, $serverType)
    {
        $loginUrl = $panel_url . '/login';
        $postFields = [
            "username" => $server_info->username,
            "password" => $server_info->password
        ];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $loginUrl);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($curl, CURLOPT_HEADER, 1);
        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        preg_match('/^Set-Cookie:\s*([^;]*)/mi', $header, $match);
        $session = $match[1];
        $loginResponse = json_decode($body);
        if (!isset($loginResponse->success) || !$loginResponse->success) {
            curl_close($curl);
            return $loginResponse;
        }
        $url = ($serverType == "sanaei") ? "$panel_url/panel/api/inbound/update/$iid" : "$panel_url/xui/inbound/update/$iid";
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $dataArr,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                'Accept: application/json, text/plain, */*',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate',
                'X-Requested-With: XMLHttpRequest',
                'Cookie: ' . $session
            ]
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }

    // Get a new certificate from the server
    protected function getNewCert($server_id)
    {
        $server_info = $this->getServerConfig($server_id);
        if (!$server_info) {
            return null;
        }
        $panel_url = $server_info->panel_url;
        $session = $this->authenticateAndGetSession($server_info);
        if (!$session) {
            return null;
        }
        $url = "$panel_url/server/getNewX25519Cert";
        return $this->executeCurlRequest($url, $session);
    }

    // Retrieve server configuration using DB façade
    protected function getServerConfig($server_id)
    {
        return DB::table('server_config')->where('id', $server_id)->first();
    }

    // Authenticate and return session token using cURL
    protected function authenticateAndGetSession($server_info)
    {
        $panel_url = $server_info->panel_url;
        $loginUrl = $panel_url . '/login';
        $postFields = [
            "username" => $server_info->username,
            "password" => $server_info->password
        ];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $loginUrl);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($curl, CURLOPT_HEADER, 1);
        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            curl_close($curl);
            return null;
        }
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        preg_match('/^Set-Cookie:\s*([^;]*)/mi', $header, $match);
        $session = $match[1];
        $body = substr($response, $header_size);
        $loginResponse = json_decode($body);
        if (!isset($loginResponse->success) || !$loginResponse->success) {
            curl_close($curl);
            return null;
        }
        curl_close($curl);
        return $session;
    }

    // Change the state of an inbound (toggle enable)
    public function changeInboundState($server_id, $uuid)
    {
        $server_info = DB::table('server_config')->where('id', $server_id)->first();
        if (!$server_info) return null;
        $panel_url = $server_info->panel_url;
        $serverType = $server_info->type;

        $jsonResponse = $this->getJson($server_id);
        if (!$jsonResponse) return null;
        $inbounds = $jsonResponse->obj;
        foreach ($inbounds as $row) {
            $settings = json_decode($row->settings, true);
            $clients = $settings['clients'];
            if ($clients[0]['id'] == $uuid || $clients[0]['password'] == $uuid) {
                $inbound_id = $row->id;
                $enable = $row->enable;
                break;
            }
        }
        if (!isset($inbound_id)) return null;
        if (!isset($settings['clients'][0]['subId']) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $settings['clients'][0]['subId'] = $this->RandomString(16);
        }
        if (!isset($settings['clients'][0]['enable']) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $settings['clients'][0]['enable'] = true;
        }
        $newEnable = !$enable;
        $dataArr = [
            'up' => $row->up,
            'down' => $row->down,
            'total' => $row->total,
            'remark' => $row->remark,
            'enable' => $newEnable,
            'expiryTime' => $row->expiryTime,
            'listen' => '',
            'port' => $row->port,
            'protocol' => $row->protocol,
            'settings' => json_encode($settings, 488),
            'streamSettings' => $row->streamSettings,
            'sniffing' => $row->sniffing
        ];

        // Authenticate
        $serverName = $server_info->username;
        $serverPass = $server_info->password;
        $loginUrl = $panel_url . '/login';
        $postFields = ["username" => $serverName, "password" => $serverPass];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $loginUrl);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($curl, CURLOPT_HEADER, 1);
        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        preg_match('/^Set-Cookie:\s*([^;]*)/mi', $header, $match);
        $session = $match[1];
        $url = ($serverType == "sanaei") ? "$panel_url/panel/api/inbound/update/$inbound_id" : "$panel_url/xui/inbound/update/$inbound_id";
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $dataArr,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0',
                'Accept: application/json, text/plain, */*',
                'Cookie: ' . $session
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }

    // Renew the UUID for an inbound client
    public function renewInboundUuid($server_id, $uuid)
    {
        $server_info = DB::table('server_config')->where('id', $server_id)->first();
        if (!$server_info) return null;
        $panel_url = $server_info->panel_url;
        $serverType = $server_info->type;
        $jsonResponse = $this->getJson($server_id);
        if (!$jsonResponse) return null;
        $inbounds = $jsonResponse->obj;
        foreach ($inbounds as $row) {
            $settings = json_decode($row->settings, true);
            $clients = $settings['clients'];
            if ($clients[0]['id'] == $uuid || $clients[0]['password'] == $uuid) {
                $inbound_id = $row->id;
                $total = $row->total;
                $up = $row->up;
                $down = $row->down;
                $expiryTime = $row->expiryTime;
                $port = $row->port;
                $protocol = $row->protocol;
                $netType = json_decode($row->streamSettings)->network;
                break;
            }
        }
        $newUuid = $this->generateRandomString(42, $protocol);
        if ($protocol == "trojan") {
            $settings['clients'][0]['password'] = $newUuid;
        } else {
            $settings['clients'][0]['id'] = $newUuid;
        }
        if (!isset($settings['clients'][0]['subId']) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $settings['clients'][0]['subId'] = $this->RandomString(16);
        }
        if (!isset($settings['clients'][0]['enable']) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $settings['clients'][0]['enable'] = true;
        }
        $settings = json_encode($settings, 488);
        $dataArr = [
            'up' => $row->up,
            'down' => $row->down,
            'total' => $row->total,
            'remark' => $row->remark,
            'enable' => 'true',
            'expiryTime' => $row->expiryTime,
            'listen' => '',
            'port' => $row->port,
            'protocol' => $row->protocol,
            'settings' => $settings,
            'streamSettings' => $row->streamSettings,
            'sniffing' => $row->sniffing
        ];
        $serverName = $server_info->username;
        $serverPass = $server_info->password;
        $loginUrl = $panel_url . '/login';
        $postFields = ["username" => $serverName, "password" => $serverPass];
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $loginUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postFields),
            CURLOPT_HEADER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 3,
        ]);
        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        preg_match('/^Set-Cookie:\s*([^;]*)/mi', substr($response, 0, $header_size), $match);
        $session = $match[1];
        $url = ($serverType == "sanaei") ? "$panel_url/panel/api/inbound/update/$inbound_id" : "$panel_url/xui/inbound/update/$inbound_id";
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $dataArr,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0',
                'Accept: application/json, text/plain, */*',
                'Cookie: ' . $session
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response);
        $response->newUuid = $newUuid;
        return $response;
    }

    // Edit a client's remark for an inbound
    public function editClientRemark($server_id, $inbound_id, $uuid, $newRemark)
    {
        $server_info = DB::table('server_config')->where('id', $server_id)->first();
        if (!$server_info) return null;
        $panel_url = $server_info->panel_url;
        $serverType = $server_info->type;
        $jsonResponse = $this->getJson($server_id);
        if (!$jsonResponse) return null;
        $inbounds = $jsonResponse->obj;
        $client_key = 0;
        foreach ($inbounds as $row) {
            if ($row->id == $inbound_id) {
                $settings = json_decode($row->settings, true);
                $clients = $settings['clients'];
                $clientsStates = $row->clientStats;
                foreach ($clients as $key => $client) {
                    if ($client['id'] == $uuid || $client['password'] == $uuid) {
                        $client_key = $key;
                        $email = $client['email'];
                        $emails = array_column($clientsStates, 'email');
                        $emailKey = array_search($email, $emails);
                        break;
                    }
                }
                break;
            }
        }
        $settings['clients'][$client_key]['email'] = $newRemark;
        if (!isset($settings['clients'][$client_key]['subId']) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $settings['clients'][$client_key]['subId'] = $this->RandomString(16);
        }
        if (!isset($settings['clients'][$client_key]['enable']) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $settings['clients'][$client_key]['enable'] = true;
        }
        $editedClient = $settings['clients'][$client_key];
        $settings['clients'] = array_values($settings['clients']);
        $settings = json_encode($settings);
        $dataArr = [
            'up' => $row->up,
            'down' => $row->down,
            'total' => $row->total,
            'remark' => $row->remark,
            'enable' => 'true',
            'expiryTime' => $row->expiryTime,
            'listen' => '',
            'port' => $row->port,
            'protocol' => $row->protocol,
            'settings' => $settings,
            'streamSettings' => $row->streamSettings,
            'sniffing' => $row->sniffing
        ];
        $serverName = $server_info->username;
        $serverPass = $server_info->password;
        $loginUrl = $panel_url . '/login';
        $postFields = ["username" => $serverName, "password" => $serverPass];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $loginUrl);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($curl, CURLOPT_HEADER, 1);
        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        preg_match('/^Set-Cookie:\s*([^;]*)/mi', substr($response, 0, $header_size), $match);
        $session = $match[1];
        if ($serverType == "sanaei" || $serverType == "alireza") {
            $newSetting = ['clients' => [$editedClient]];
            $dataArr = [
                "id" => $inbound_id,
                "settings" => json_encode($newSetting)
            ];
            $url = ($serverType == "sanaei") ? "$panel_url/panel/api/inbound/updateClient/" . rawurlencode($uuid) : "$panel_url/xui/inbound/updateClient/" . rawurlencode($uuid);
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $dataArr,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Mozilla/5.0',
                    'Accept: application/json, text/plain, */*',
                    'Cookie: ' . $session
                ]
            ]);
        } else {
            curl_setopt_array($curl, [
                CURLOPT_URL => "$panel_url/xui/inbound/update/$inbound_id",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $dataArr,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Mozilla/5.0',
                    'Accept: application/json, text/plain, */*',
                    'Cookie: ' . $session
                ]
            ]);
        }
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }

    // Edit a client's traffic (volume and expiry)
    public function editClientTraffic($server_id, $inbound_id, $uuid, $volume, $days, $editType = null)
    {
        $server_info = DB::table('server_config')->where('id', $server_id)->first();
        if (!$server_info) return null;
        $panel_url = $server_info->panel_url;
        $serverType = $server_info->type;
        $jsonResponse = $this->getJson($server_id);
        if (!$jsonResponse) return null;
        $inbounds = $jsonResponse->obj;
        $client_key = 0;
        foreach ($inbounds as $row) {
            if ($row->id == $inbound_id) {
                $settings = json_decode($row->settings, true);
                $clients = $settings['clients'];
                $clientsStates = $row->clientStats;
                foreach ($clients as $key => $client) {
                    if ($client['id'] == $uuid || $client['password'] == $uuid) {
                        $client_key = $key;
                        break;
                    }
                }
                break;
            }
        }
        if ($volume != 0) {
            $client_total = $settings['clients'][$client_key]['totalGB'];
            $extend_volume = floor($volume * 1073741824);
            $volume = ($client_total > 0) ? $client_total + $extend_volume : $extend_volume;
            $settings['clients'][$client_key]['totalGB'] = $volume;
        }
        if ($days != 0) {
            $currentExpiry = $settings['clients'][$client_key]['expiryTime'] ?? 0;
            $newExpiry = max($currentExpiry, time()) + $days * 86400;
            $settings['clients'][$client_key]['expiryTime'] = $newExpiry;
        }
        if (!isset($settings['clients'][$client_key]['subId']) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $settings['clients'][$client_key]['subId'] = $this->RandomString(16);
        }
        if (!isset($settings['clients'][$client_key]['enable']) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $settings['clients'][$client_key]['enable'] = true;
        }
        $editedClient = $settings['clients'][$client_key];
        $settings['clients'] = array_values($settings['clients']);
        $settings = json_encode($settings);
        $dataArr = [
            'up' => $row->up,
            'down' => $row->down,
            'total' => $row->total,
            'remark' => $row->remark,
            'enable' => 'true',
            'expiryTime' => $row->expiryTime,
            'listen' => '',
            'port' => $row->port,
            'protocol' => $row->protocol,
            'settings' => $settings,
            'streamSettings' => $row->streamSettings,
            'sniffing' => $row->sniffing
        ];
        $serverName = $server_info->username;
        $serverPass = $server_info->password;
        $loginUrl = $panel_url . '/login';
        $postFields = ["username" => $serverName, "password" => $serverPass];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $loginUrl);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($curl, CURLOPT_HEADER, 1);
        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        preg_match('/^Set-Cookie:\s*([^;]*)/mi', substr($response, 0, $header_size), $match);
        $session = $match[1];
        if ($serverType == "sanaei" || $serverType == "alireza") {
            $newSetting = ['clients' => [$editedClient]];
            $dataArr = [
                "id" => $inbound_id,
                "settings" => json_encode($newSetting)
            ];
            $url = ($serverType == "sanaei") ? "$panel_url/panel/api/inbound/updateClient/" . rawurlencode($uuid) : "$panel_url/xui/inbound/updateClient/" . rawurlencode($uuid);
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $dataArr,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Mozilla/5.0',
                    'Accept: application/json, text/plain, */*',
                    'Cookie: ' . $session
                ]
            ]);
        } else {
            curl_setopt_array($curl, [
                CURLOPT_URL => "$panel_url/xui/inbound/update/$inbound_id",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $dataArr,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Mozilla/5.0',
                    'Accept: application/json, text/plain, */*',
                    'Cookie: ' . $session
                ]
            ]);
        }
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }

    // Reset IP log for an inbound (clear client IPs)
    public function resetIpLog($server_id, $remark)
    {
        $server_info = DB::table('server_config')->where('id', $server_id)->first();
        if (!$server_info) return null;
        $panel_url = $server_info->panel_url;
        $serverType = $server_info->type;
        $serverName = $server_info->username;
        $serverPass = $server_info->password;
        $loginUrl = $panel_url . '/login';
        $postFields = ["username" => $serverName, "password" => $serverPass];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $loginUrl);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($curl, CURLOPT_HEADER, 1);
        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        preg_match('/^Set-Cookie:\s*([^;]*)/mi', substr($response, 0, $header_size), $match);
        $session = $match[1];
        $url = $serverType == "sanaei" ? "$panel_url/panel/api/inbound/clearClientIps/" . urlencode($remark) : "$panel_url/xui/inbound/clearClientIps/" . urlencode($remark);
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0',
                'Accept: application/json, text/plain, */*',
                'Cookie: ' . $session
            ]
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }

    // Reset client traffic (optionally for a specific inbound)
    public function resetClientTraffic($server_id, $remark, $inboundId = null)
    {
        $server_info = DB::table('server_config')->where('id', $server_id)->first();
        if (!$server_info) return null;
        $panel_url = $server_info->panel_url;
        $serverType = $server_info->type;
        $serverName = $server_info->username;
        $serverPass = $server_info->password;
        $loginUrl = $panel_url . '/login';
        $postFields = ["username" => $serverName, "password" => $serverPass];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $loginUrl);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($curl, CURLOPT_HEADER, 1);
        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        preg_match('/^Set-Cookie:\s*([^;]*)/mi', substr($response, 0, $header_size), $match);
        $session = $match[1];
        if ($serverType == "sanaei") {
            $url = "$panel_url/panel/api/inbound/$inboundId/resetClientTraffic/" . rawurlencode($remark);
        } else {
            $url = $inboundId === null ? "$panel_url/xui/inbound/resetClientTraffic/" . rawurlencode($remark) : "$panel_url/xui/inbound/$inboundId/resetClientTraffic/" . rawurlencode($remark);
        }
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0',
                'Accept: application/json, text/plain, */*',
                'Cookie: ' . $session
            ]
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }

    // Get new headers for a connection based on network type and request/response headers
    public function getNewHeaders($netType, $request_header, $response_header, $type)
    {
        $inputRequest = explode(':', $request_header);
        $key = trim($inputRequest[0]);
        $value = trim($inputRequest[1]);
        $inputResponse = explode(':', $response_header);
        $reskey = trim($inputResponse[0]);
        $resvalue = trim($inputResponse[1]);
        if ($netType == 'tcp') {
            if ($type == 'none') {
                $headers = '{"type": "none"}';
            } else {
                $headers = json_encode([
                    'type' => 'http',
                    'request' => [
                        'method' => 'GET',
                        'path' => ['/'],
                        'headers' => [$key => [$value]]
                    ],
                    'response' => [
                        'version' => '1.1',
                        'status' => '200',
                        'reason' => 'OK',
                        'headers' => [$reskey => [$resvalue]]
                    ]
                ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            }
        } elseif ($netType == 'ws') {
            if ($type == 'none') {
                $headers = '{}';
            } else {
                $headers = json_encode([$key => $value], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            }
        } else {
            $headers = '{}';
        }
        return $headers;
    }

    // Get a connection link using the original extractServerDetails() and generateConnectionLink() functions
    public function getConnectionLink($server_id, $uniqid, $protocol, $remark, $port, $netType, $inbound_id = 0, $bypass = false, $customPath = false, $customPort = 0, $customSni = null)
    {
        $server_info = DB::table('server_config')->where('id', $server_id)->first();
        if (!$server_info) return null;
        $panel_url = parse_url($server_info->panel_url, PHP_URL_HOST);
        $server_ip = empty($server_info->ip) ? $panel_url : $server_info->ip;
        $sni = $server_info->sni;
        $header_type = $server_info->header_type;
        $request_header = $server_info->request_header;
        $response_header = $server_info->response_header;
        $serverType = $server_info->type;
        preg_match("/^Host:(.*)/i", $request_header, $hostMatch);
        $jsonResponse = $this->getJson($server_id);
        if (!$jsonResponse) return null;
        $inbounds = $jsonResponse->obj;
        foreach ($inbounds as $row) {
            if ($inbound_id == 0) {
                $clients = json_decode($row->settings)->clients;
                if ($clients[0]->id == $uniqid || $clients[0]->password == $uniqid) {
                    $this->extractServerDetails($row, $serverType, $netType, $remark, $sni, $tlsStatus, $tlsSetting, $xtlsSetting, $settings, $fp, $spiderX, $pbk, $sid, $flow, $path, $host, $serviceName, $grpcSecurity, $alpn, $kcpType, $kcpSeed);
                    break;
                }
            } else {
                if ($row->id == $inbound_id) {
                    $this->extractServerDetails($row, $serverType, $netType, $remark, $sni, $tlsStatus, $tlsSetting, $xtlsSetting, $settings, $fp, $spiderX, $pbk, $sid, $flow, $path, $host, $serviceName, $grpcSecurity, $alpn, $kcpType, $kcpSeed);
                    break;
                }
            }
        }
        return $this->generateConnectionLink($protocol, $uniqid, $remark, $port, $netType, $server_ip, $bypass, $customPath, $customPort, $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid, $serviceName, $grpcSecurity, $alpn, $kcpType, $kcpSeed);
    }

    // Extract server details from an inbound row
    public function extractServerDetails($row, $serverType, &$netType, &$remark, &$sni, &$tlsStatus, &$tlsSetting,
        &$xtlsSetting, &$settings, &$fp, &$spiderX, &$pbk, &$sid, &$flow, &$path, &$host, &$serviceName, &$grpcSecurity,
        &$alpn, &$kcpType, &$kcpSeed)
    {
        if ($serverType == "sanaei" || $serverType == "alireza") {
            $settings = json_decode($row->settings, true);
            $remark = $row->remark;
        }
        $tlsStatus = json_decode($row->streamSettings)->security;
        $tlsSetting = json_decode($row->streamSettings)->tlsSettings;
        $xtlsSetting = json_decode($row->streamSettings)->xtlsSettings;
        $netType = json_decode($row->streamSettings)->network;
        if ($netType == 'tcp') {
            $header_type = json_decode($row->streamSettings)->tcpSettings->header->type;
            $path = json_decode($row->streamSettings)->tcpSettings->header->request->path[0];
            $host = json_decode($row->streamSettings)->tcpSettings->header->request->headers->Host[0];
            if ($tlsStatus == "reality") {
                $realitySettings = json_decode($row->streamSettings)->realitySettings;
                $fp = $realitySettings->settings->fingerprint;
                $spiderX = $realitySettings->settings->spiderX;
                $pbk = $realitySettings->settings->publicKey;
                $sni = $realitySettings->serverNames[0];
                $flow = $settings['clients'][0]['flow'];
                $sid = $realitySettings->shortIds[0];
            }
        }
        if ($netType == 'ws') {
            $header_type = json_decode($row->streamSettings)->wsSettings->header->type;
            $path = json_decode($row->streamSettings)->wsSettings->path;
            $host = json_decode($row->streamSettings)->wsSettings->headers->Host;
        }
        if ($header_type == 'http' && empty($host)) {
            $request_header = explode(':', $request_header);
            $host = trim($request_header[1]);
        }
        if ($netType == 'grpc') {
            if ($tlsStatus == 'tls') {
                $alpn = $tlsSetting->certificates->alpn;
                $sni = $tlsSetting->serverName ?? $tlsSetting->settings->serverName;
            } elseif ($tlsStatus == "reality") {
                $realitySettings = json_decode($row->streamSettings)->realitySettings;
                $fp = $realitySettings->settings->fingerprint;
                $spiderX = $realitySettings->settings->spiderX;
                $pbk = $realitySettings->settings->publicKey;
                $sni = $realitySettings->serverNames[0];
                $flow = $settings['clients'][0]['flow'];
                $sid = $realitySettings->shortIds[0];
            }
            $serviceName = json_decode($row->streamSettings)->grpcSettings->serviceName;
            $grpcSecurity = json_decode($row->streamSettings)->security;
        }
        if ($tlsStatus == 'tls') {
            $serverName = $tlsSetting->serverName ?? $tlsSetting->settings->serverName;
            $sni = $tlsSetting->serverName ?? $tlsSetting->settings->serverName;
        }
        if ($tlsStatus == "xtls") {
            $serverName = $xtlsSetting->serverName ?? $xtlsSetting->settings->serverName;
            $alpn = $xtlsSetting->alpn;
            $sni = $xtlsSetting->serverName ?? $xtlsSetting->settings->serverName;
        }
        if ($netType == 'kcp') {
            $kcpSettings = json_decode($row->streamSettings)->kcpSettings;
            $kcpType = $kcpSettings->header->type;
            $kcpSeed = $kcpSettings->seed;
        }
    }

    // Generate VLESS, Trojan, or VMess connection link (wrapper calling respective methods)
    public function generateConnectionLinkWrapper($protocol, $uniqid, $remark, $port, $netType, $server_ip, $bypass, $customPath,
        $customPort, $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid,
        $serviceName, $grpcSecurity, $alpn, $kcpType, $kcpSeed)
    {
        // This is identical to generateConnectionLink() defined above.
        return $this->generateConnectionLink($protocol, $uniqid, $remark, $port, $netType, $server_ip, $bypass, $customPath,
            $customPort, $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid,
            $serviceName, $grpcSecurity, $alpn, $kcpType, $kcpSeed);
    }
}
