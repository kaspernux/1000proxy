<?php

namespace App\Services;

use stdClass;
use Exception;
use App\Models\Server;
use GuzzleHttp\Client;
use App\Models\ServerInfo;
use App\Models\ServerPlan;
use App\Models\ServerConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

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
            'cookies' => true,
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

        return $response->getHeader('Set-Cookie');
    }

    protected function getJson($server_id)
    {
        $server = Server::findOrFail($server_id);
        $response = $this->httpClient->get('xui.inbounds.get', [
            'headers' => [
                'Cookie' => $this->token,
            ],
        ]);

        return json_decode($response->getBody()->getContents());
    }

    protected function executeCurlRequest($url, $session, $dataArr)
    {
        $response = $this->httpClient->post($url, [
            'form_params' => $dataArr,
            'headers' => [
                'Cookie' => $session,
            ],
        ]);

        return $response->getBody()->getContents();
    }

    function RandomString($count = 9, $type = "all")
    {
        $characters = $type === "all" ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789' : ($type === "small" ?
        'abcdef123456789' : 'abcdefghijklmnopqrstuvwxyz');
        $randstring = '';

        for ($i = 0; $i < $count; $i++) { $randstring .=$characters[rand(0, strlen($characters) - 1)]; } return $randstring;
    }

    function checkStep($table)
    {
        global $connection;

        if ($table == "server_plans") {
            $stmt = $connection->prepare("SELECT * FROM `server_plans` WHERE `active` = 0");
        } elseif ($table == "server_categories") {
            $stmt = $connection->prepare("SELECT * FROM `server_categories` WHERE `active` = 0");
        }

        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $res['step'];
    }

    function summarize($amount)
    {
        $gb = $amount / (1024 * 1024 * 1024);
        if ($gb >= 1) {
            return round($gb, 2) . " GB";
        } else {
            $mb = $amount / (1024 * 1024);
            return round($mb, 2) . " MB";
        }
    }

    function summarize2($amount)
    {
        $gb = $amount / (1024 * 1024 * 1024);
        return round($gb, 2);
    }

    function generateRandomString($length, $protocol)
    {
        return ($protocol == 'trojan') ? substr(md5(time()), 5, 15) : $this->generateUID();
    }

    public function addInbound(Request $request)
    {
        // Use default values from an existing inbound
        $defaultInbound = [
            "enable" => true,
            "remark" => "New inbound",
            "listen" => "",
            "port" => 44330,
            "protocol" => "vless",
            "expiryTime" => 0,
            "settings" => json_encode([
                "clients" => [
                    [
                        "email" => "default@example.com",
                        "enable" => true,
                        "expiryTime" => 0,
                        "flow" => "",
                        "id" => $this->generateUID(),
                        "limitIp" => 0,
                        "reset" => 0,
                        "subId" => $this->generateUID(),
                        "tgId" => "",
                        "totalGB" => 0
                    ]
                ],
                "decryption" => "none",
                "fallbacks" => []
            ]),
            "streamSettings" => json_encode([
                "network" => "ws",
                "security" => "none",
                "wsSettings" => [
                    "acceptProxyProtocol" => false,
                    "path" => "/",
                    "headers" => new \stdClass()
                ]
            ]),
            "sniffing" => json_encode([
                "enabled" => true,
                "destOverride" => ["http", "tls"]
            ])
        ];

        $dataArr = [
            "enable" => $request->input('enable', $defaultInbound['enable']),
            "remark" => $request->input('remark', $defaultInbound['remark']),
            "listen" => $request->input('listen', $defaultInbound['listen']),
            "port" => $request->input('port', $defaultInbound['port']),
            "protocol" => $request->input('protocol', $defaultInbound['protocol']),
            "expiryTime" => $request->input('expiryTime', $defaultInbound['expiryTime']),
            "settings" => json_encode($request->input('settings', json_decode($defaultInbound['settings'], true))),
            "streamSettings" => json_encode($request->input('streamSettings', json_decode($defaultInbound['streamSettings'], true))),
            "sniffing" => json_encode($request->input('sniffing', json_decode($defaultInbound['sniffing'], true)))
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
            $inboundResponse = json_decode($responseBody, true);

            if (isset($inboundResponse['success']) && $inboundResponse['success']) {
                $inboundId = $inboundResponse['obj']['id'];

                $client_id = $request->input('client_id', $this->generateUID());
                $volume = ($request->input('volume', 1) == 0) ? 0 : floor($request->input('volume', 1) * 1073741824);

                $newClient = [
                    "id" => $client_id,
                    "enable" => true,
                    "email" => $request->input('remark', 'New Client'),
                    "limitIp" => $request->input('limitIp', 1),
                    "totalGB" => $volume,
                    "expiryTime" => strtotime($request->input('expiryTime', 0)),
                    "subId" => $this->generateUID()
                ];

                $settings = json_decode($inboundResponse['obj']['settings'], true);
                $settings['clients'][] = $newClient;

                $clientData = [
                    "id" => $inboundId,
                    "settings" => json_encode($settings)
                ];

                $url = $this->baseUrl . 'panel/api/inbounds/addClient';

                $clientResponse = $this->httpClient->post($url, [
                    'form_params' => $clientData,
                    'headers' => [
                        'Accept' => 'application/json',
                        'Cookie' => $this->token,
                    ],
                ]);

                $clientResponseBody = $clientResponse->getBody()->getContents();
                $clientAddResponse = json_decode($clientResponseBody, true);

                if (isset($clientAddResponse['success']) && $clientAddResponse['success']) {
                    return [
                        'inbound' => $inboundResponse,
                        'client' => $clientAddResponse['obj']['clients'][0]
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

    public function addClientInbound($data)
    {
        $expiryTime = $data['expiryTime'];
        if ($expiryTime) {
            $expiryTime = strtotime($expiryTime);
        }

        $clientData = [
            "id" => $data['server_inbound_id'],
            "settings" => json_encode([
                "clients" => [
                    [
                        "id" => $data['client_id'],
                        "enable" => true,
                        "email" => $data['email'],
                        "limitIp" => $data['limitIp'],
                        "totalGB" => $data['totalGB'] * 1073741824, // Convert GB to Bytes
                        "expiryTime" => $expiryTime,
                        "subId" => $data['subId']
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
            $clientAddResponse = json_decode($responseBody, true);

            if (isset($clientAddResponse['success']) && $clientAddResponse['success']) {
                return $clientAddResponse['obj']['clients'][0];
            } else {
                Log::error('Error adding client: ' . json_encode($clientAddResponse));
                throw new Exception('Failed to add client');
            }
        } catch (Exception $e) {
            Log::error('Error adding client: ' . $e->getMessage());
            throw new Exception('Failed to add client');
        }
    }

    function generateUID()
    {
        $randomString = openssl_random_pseudo_bytes(16);
        $time_low = bin2hex(substr($randomString, 0, 4));
        $time_mid = bin2hex(substr($randomString, 4, 2));
        $time_hi_and_version = bin2hex(substr($randomString, 6, 2));
        $clock_seq_hi_and_reserved = bin2hex(substr($randomString, 8, 2));
        $node = bin2hex(substr($randomString, 10, 6));
        $time_hi_and_version = hexdec($time_hi_and_version);
        $time_hi_and_version = $time_hi_and_version >> 4;
        $time_hi_and_version = $time_hi_and_version | 0x4000;

        $clock_seq_hi_and_reserved = hexdec($clock_seq_hi_and_reserved);
        $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved >> 2;
        $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved | 0x8000;

        return sprintf(
            '%08s-%04s-%04x-%04x-%012s',
            $time_low,
            $time_mid,
            $time_hi_and_version,
            $clock_seq_hi_and_reserved,
            $node
        );
    }

    /* public function updateClient(array $data)
    {
        // Implementation for updating a client on the remote XUI server.
        // This will depend on the XUI API documentation for updating clients.
    }

    public function deleteClient($clientId)
    {
        // Implementation for deleting a client from the remote XUI server.
        // This will depend on the XUI API documentation for deleting clients.
    }

    public function getInboundById($serverId, $inboundId)
    {
        // Implementation for fetching an inbound by ID from the remote XUI server.
        // This will depend on the XUI API documentation for fetching inbounds.
    }

    public function updateInbound($serverId, $inboundId, array $data)
    {
        // Implementation for updating an inbound on the remote XUI server.
        // This will depend on the XUI API documentation for updating inbounds.
    }

    public function deleteInbound($serverId, $inboundId)
    {
        // Implementation for deleting an inbound from the remote XUI server.
        // This will depend on the XUI API documentation for deleting inbounds.
    } */

    public function getInbounds()
    {
        $response = $this->httpClient->get('panel/inbounds/list', [
            'headers' => [
                'Cookie' => $this->token,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function getInbound($id)
    {
        $response = $this->httpClient->get("panel/inbounds/get/$id", [
            'headers' => [
                'Cookie' => $this->token,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
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

            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            Log::error('Error updating client: ' . $e->getMessage());
            throw new Exception('Failed to update client');
        }
    }

    public function deleteClient($clientId)
    {
        try {
            $response = $this->httpClient->post("panel/api/inbounds/delClient/$clientId", [
                'headers' => [
                    'Accept' => 'application/json',
                    'Cookie' => $this->token,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            Log::error('Error deleting client: ' . $e->getMessage());
            throw new Exception('Failed to delete client');
        }
    }


    function editInbound($server_id, $uniqid, $uuid, $protocol, $netType = 'tcp', $security = 'none', $bypass = false)
    {
        global $connection;

        // Fetch server configuration
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Extract necessary server info
        $panel_url = $server_info['panel_url'];
        $security = $server_info['security'];
        $tlsSettings = $server_info['tlsSettings'];
        $header_type = $server_info['header_type'];
        $request_header = $server_info['request_header'];
        $response_header = $server_info['response_header'];
        $serverType = $server_info['type'];
        $xtlsTitle = ($serverType == "sanaei" || $serverType == "alireza") ? "XTLSSettings" : "xtlsSettings";
        $sni = $server_info['sni'];

        // Update TLS settings if applicable
        if (!empty($sni) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $tlsSettings = json_decode($tlsSettings, true);
            $tlsSettings['serverName'] = $sni;
            $tlsSettings = json_encode($tlsSettings);
        }

        // Fetch inbound details
        $response = $this->getJson($server_id);
        if (!$response) return null;
        $response = $response->obj;
        $found = false;
        foreach ($response as $row) {
            $clients = json_decode($row->settings)->clients;
            if ($clients[0]->id == $uuid || $clients[0]->password == $uuid) {
                $iid = $row->id;
                $remark = $row->remark;
                $streamSettings = $row->streamSettings;
                $settings = $row->settings;
                $found = true;
                break;
            }
        }
        if (!$found || !intval($iid)) return;

        // Prepare headers
        $headers = $this->getNewHeaders($netType, $request_header, $response_header, $header_type);
        $headers = empty($headers) ? "{}" : $headers;

        // Prepare stream settings based on network type and security
        if ($netType == 'grpc') {
            // Handle gRPC settings
            $keyFileInfo = json_decode($tlsSettings, true);
            $certificateFile = "/root/cert.crt";
            $keyFile = '/root/private.key';

            if (isset($keyFileInfo['certificates'])) {
                $certificateFile = $keyFileInfo['certificates'][0]['certificateFile'];
                $keyFile = $keyFileInfo['certificates'][0]['keyFile'];
            }

            if ($security == 'tls') {
                $streamSettings = '{
                    "network": "grpc",
                    "security": "tls",
                    "tlsSettings": {
                        "serverName": "' . (!empty($sni) && ($serverType == "sanaei" || $serverType == "alireza") ?  $sni : parse_url($panel_url, PHP_URL_HOST)) . '",
                        "certificates": [{
                            "certificateFile": "' . $certificateFile . '",
                            "keyFile": "' . $keyFile . '"
                        }],
                        "alpn": []
                    },
                    "grpcSettings": {
                        "serviceName": ""
                    }
                }';
            } else {
                $streamSettings = '{
                    "network": "grpc",
                    "security": "none",
                    "grpcSettings": {
                        "serviceName": "' . parse_url($panel_url, PHP_URL_HOST) . '"
                    }
                }';
            }
        } else {
            // Handle TCP and WebSocket settings
            $xtlsField = ($serverType == "sanaei" || $serverType == "alireza") ? '"' . $xtlsTitle . '": ' . $tlsSettings . ',' : '';

            if ($security == 'none') {
                $tcpSettings = '{
                    "network": "tcp",
                    "security": "' . $security . '",
                    "tlsSettings": ' . $tlsSettings . ',
                    "tcpSettings": {
                        "header": ' . $headers . '
                    }
                }';
                $wsSettings = '{
                    "network": "ws",
                    "security": "' . $security . '",
                    "tlsSettings": ' . $tlsSettings . ',
                    "wsSettings": {
                        "path": "/",
                        "headers": ' . $headers . '
                    }
                }';
            } elseif ($security == 'xtls' && $serverType != "sanaei" && $serverType != "alireza") {
                $tcpSettings = '{
                    "network": "tcp",
                    "security": "' . $security . '",
                    ' . $xtlsField . '
                    "tcpSettings": {
                        "header": ' . $headers . '
                    }
                }';
                $wsSettings = '{
                    "network": "ws",
                    "security": "' . $security . '",
                    ' . $xtlsField . '
                    "wsSettings": {
                        "path": "/",
                        "headers": ' . $headers . '
                    }
                }';
            } else {
                $tcpSettings = '{
                    "network": "tcp",
                    "security": "' . $security . '",
                    "tlsSettings": ' . $tlsSettings . ',
                    "tcpSettings": {
                        "header": ' . $headers . '
                    }
                }';
                $wsSettings = '{
                    "network": "ws",
                    "security": "' . $security . '",
                    "tlsSettings": ' . $tlsSettings . ',
                    "wsSettings": {
                        "path": "/",
                        "headers": ' . $headers . '
                    }
                }';
            }

            // Determine client settings based on server type and security
            if ($serverType == "sanaei" || $serverType == "alireza") {
                $settings = '{
                    "clients": [{
                        "id": "' . $uniqid . '",
                        "enable": true,
                        "email": "' . $remark . '",
                        "limitIp": 0,
                        "totalGB": 0,
                        "expiryTime": 0,
                        "subId": "' . $this->RandomString(16) . '"
                    }],
                    "decryption": "none",
                    "fallbacks": []
                }';
            } else {
                $settings = '{
                    "clients": [{
                        "id": "' . $uniqid . '",
                        "flow": "",
                        "email": "' . $remark . '"
                    }],
                    "decryption": "none",
                    "fallbacks": []
                }';
            }
        }

        // Construct data array for update
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
            'streamSettings' => ($netType == 'tcp') ? $tcpSettings : $wsSettings,
            'sniffing' => $row->sniffing
        ];

        // Perform login and get session
        $session = $this->authenticateAndGetSession($server_info);

        // Check login response
        if (!$session) {
            return ['success' => false, 'message' => 'Failed to authenticate'];
        }

        // Define endpoint URL based on server type
        $url = ($serverType == "sanaei") ? "$panel_url/panel/inbound/update/$iid" : "$panel_url/xui/inbound/update/$iid";

        // Configure and execute CURL request to update inbound settings
        $response = $this->executeCurlRequest($url, $dataArr, $session);

        return $response;
    }

    function editInboundTraffic($server_id, $uuid, $volume, $days, $editType = null)
    {
        global $connection;

        // Retrieve server configuration
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $panel_url = $server_info['panel_url'];
        $serverType = $server_info['type'];

        // Get JSON response
        $response = $this->getJson($server_id);
        if (!$response) return null;
        $response = $response->obj;

        // Find the inbound based on uuid
        foreach ($response as $row) {
            $clients = json_decode($row->settings)->clients;
            if ($clients[0]->id == $uuid || $clients[0]->password == $uuid) {
                $iid = $row->id;
                $remark = $row->remark;
                $found = true;
                break;
            }
        }

        if (!$found || !intval($iid)) return;

        // Prepare volume and expiry time adjustments
        $addVolume = 1073741824 * $volume;
        $addTime = $days * 86400;
        $newVolume = $row->total + $addVolume;
        $newExpiryTime = $row->expiryTime + $addTime;

        // Adjustments based on editType
        if ($editType == "traffic") {
            $dataArr = [
                'up' => $row->up,
                'down' => $row->down,
                'total' => $newVolume,
                'remark' => $remark,
                'enable' => 'true',
                'expiryTime' => $row->expiryTime,
                'listen' => '',
                'port' => $row->port,
                'protocol' => $row->protocol,
                'settings' => $row->settings,
                'streamSettings' => $row->streamSettings,
                'sniffing' => $row->sniffing
            ];
        } elseif ($editType == "time") {
            $dataArr = [
                'up' => $row->up,
                'down' => $row->down,
                'total' => $row->total,
                'remark' => $remark,
                'enable' => 'true',
                'expiryTime' => $newExpiryTime,
                'listen' => '',
                'port' => $row->port,
                'protocol' => $row->protocol,
                'settings' => $row->settings,
                'streamSettings' => $row->streamSettings,
                'sniffing' => $row->sniffing
            ];
        }

        // Perform login and get session
        $session = $this->authenticateAndGetSession($server_info);

        // Check login response
        if (!$session) {
            return ['success' => false, 'message' => 'Failed to authenticate'];
        }

        // Define endpoint URL based on server type
        $url = ($serverType == "sanaei") ? "$panel_url/panel/inbound/update/$iid" : "$panel_url/xui/inbound/update/$iid";

        // Configure and execute CURL request to update inbound traffic settings
        $response = $this->executeCurlRequest($url, $dataArr, $session);

        return $response;
    }

    function addUser($server_id, $client_id, $protocol, $port, $expiryTime, $remark, $volume, $netType, $security = 'none', $bypass = false, $planId = null)
    {
        global $connection;

        // Fetch server info from database
        $stmt = $connection->prepare("SELECT * FROM server_infos WHERE server_id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Extract server info parameters
        $panel_url = $server_info['panel_url'];
        $security = $server_info['security'];
        $tlsSettings = $server_info['tlsSettings'];
        $header_type = $server_info['header_type'];
        $request_header = $server_info['request_header'];
        $response_header = $server_info['response_header'];
        $sni = $server_info['sni'];
        $cookie = 'Cookie: session=' . $server_info['cookie'];
        $serverType = $server_info['type'];
        $xtlsTitle = ($serverType == "sanaei" || $serverType == "alireza") ? "XTLSSettings" : "xtlsSettings";
        $reality = $server_info['reality'];

        // Adjust settings based on server type and security
        if (!empty($sni) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $tlsSettings = json_decode($tlsSettings, true);
            $tlsSettings['serverName'] = $sni;
            $tlsSettings = json_encode($tlsSettings);
        }

        // Adjust volume to bytes
        $volume = ($volume == 0) ? 0 : floor($volume * 1073741824);

        // Get headers based on network type
        $headers = getNewHeaders($netType, $request_header, $response_header, $header_type);

        // Initialize settings and streamSettings based on protocol and security
        $settings = '';
        $streamSettings = '';

        //---------------------------------------Trojan------------------------------------//
        if ($protocol == 'trojan') {
            if ($security == 'none') {
                $tcpSettings = '{
                    "network": "tcp",
                    "security": "'.$security.'",
                    "tlsSettings": '.$tlsSettings.',
                    "tcpSettings": {
                        "header": '.$headers.'
                    }
                }';

                $wsSettings = '{
                    "network": "ws",
                    "security": "'.$security.'",
                    "tlsSettings": '.$tlsSettings.',
                    "wsSettings": {
                        "path": "/",
                        "headers": '.$headers.'
                    }
                }';

                if ($serverType == "sanaei" || $serverType == "alireza") {
                    $settings = '{
                        "clients": [
                            {
                                "id": "'.$client_id.'",
                                "enable": true,
                                "email": "' . $remark. '",
                                "limitIp": 0,
                                "totalGB": 0,
                                "expiryTime": 0,
                                "subId": "' . RandomString(16) . '"
                            }
                        ],
                        "decryption": "none",
                        "fallbacks": []
                    }';
                } else {
                    $settings = '{
                        "clients": [
                            {
                                "id": "'.$client_id.'",
                                "flow": "",
                                "email": "' . $remark. '"
                            }
                        ],
                        "decryption": "none",
                        "fallbacks": []
                    }';
                }
            } elseif ($security == 'xtls' && $serverType != "sanaei" && $serverType != "alireza") {
                $tcpSettings = '{
                    "network": "tcp",
                    "security": "'.$security.'",
                    "' . $xtlsTitle . '": '.$tlsSettings.',
                    "tcpSettings": {
                        "header": '.$headers.'
                    }
                }';

                $wsSettings = '{
                    "network": "ws",
                    "security": "'.$security.'",
                    "' . $xtlsTitle .'": '.$tlsSettings.',
                    "wsSettings": {
                        "path": "/",
                        "headers": '.$headers.'
                    }
                }';

                $settings = '{
                    "clients": [
                        {
                            "id": "'.$client_id.'",
                            "alterId": 0
                        }
                    ],
                    "decryption": "none",
                    "fallbacks": []
                }';
            } else {
                $tcpSettings = '{
                    "network": "tcp",
                    "security": "'.$security.'",
                    "tlsSettings": '.$tlsSettings.',
                    "tcpSettings": {
                        "header": '.$headers.'
                    }
                }';

                $wsSettings = '{
                    "network": "ws",
                    "security": "'.$security.'",
                    "tlsSettings": '.$tlsSettings.',
                    "wsSettings": {
                        "path": "/",
                        "headers": '.$headers.'
                    }
                }';

                if ($serverType == "sanaei" || $serverType == "alireza") {
                    $settings = '{
                        "clients": [
                            {
                                "password": "'.$client_id.'",
                                "enable": true,
                                "email": "' . $remark. '",
                                "limitIp": 0,
                                "totalGB": 0,
                                "expiryTime": 0,
                                "subId": "' . RandomString(16) . '"
                            }
                        ],
                        "fallbacks": []
                    }';
                } else {
                    $settings = '{
                        "clients": [
                            {
                                "password": "'.$client_id.'",
                                "flow": "",
                                "email": "' . $remark. '"
                            }
                        ],
                        "fallbacks": []
                    }';
                }
            }
        }

        //-------------------------------------- VLESS -------------------------------//
        elseif ($protocol == 'vless') {
            if ($bypass == true) {
                $wsSettings = '{
                    "network": "ws",
                    "security": "none",
                    "wsSettings": {
                        "path": "/wss' . $port . '",
                        "headers": {}
                    }
                }';

                if ($serverType == "sanaei" || $serverType == "alireza") {
                    $settings = '{
                        "clients": [
                            {
                                "id": "'.$client_id.'",
                                "enable": true,
                                "email": "' . $remark. '",
                                "limitIp": 0,
                                "totalGB": 0,
                                "expiryTime": 0,
                                "subId": "' . RandomString(16) . '"
                            }
                        ],
                        "decryption": "none",
                        "fallbacks": []
                    }';
                } else {
                    $settings = '{
                        "clients": [
                            {
                                "id": "'.$client_id.'",
                                "flow": "",
                                "email": "' . $remark. '"
                            }
                        ],
                        "decryption": "none",
                        "fallbacks": []
                    }';
                }
            } else {
                if ($security == 'tls') {
                    $tcpSettings = '{
                        "network": "tcp",
                        "security": "'.$security.'",
                        "tlsSettings": '.$tlsSettings.',
                        "tcpSettings": {
                            "header": '.$headers.'
                        }
                    }';

                    $wsSettings = '{
                        "network": "ws",
                        "security": "'.$security.'",
                        "tlsSettings": '.$tlsSettings.',
                        "wsSettings": {
                            "path": "/",
                            "headers": '.$headers.'
                        }
                    }';

                    if ($serverType == "sanaei" || $serverType == "alireza") {
                        $settings = '{
                            "clients": [
                                {
                                    "id": "'.$client_id.'",
                                    "enable": true,
                                    "email": "' . $remark. '",
                                    "limitIp": 0,
                                    "totalGB": 0,
                                    "expiryTime": 0,
                                    "subId": "' . RandomString(16) . '"
                                }
                            ],
                            "disableInsecureEncryption": false
                        }';
                    } else {
                        $settings = '{
                            "clients": [
                                {
                                    "id": "'.$client_id.'",
                                    "alterId": 0
                                }
                            ],
                            "disableInsecureEncryption": false
                        }';
                    }
                } elseif ($security == 'xtls' && $serverType != "sanaei" && $serverType != "alireza") {
                    $tcpSettings = '{
                        "network": "tcp",
                        "security": "'.$security.'",
                        "' . $xtlsTitle . '": '.$tlsSettings.',
                        "tcpSettings": {
                            "header": '.$headers.'
                        }
                    }';

                    $wsSettings = '{
                        "network": "ws",
                        "security": "'.$security.'",
                        "' . $xtlsTitle . '": '.$tlsSettings.',
                        "wsSettings": {
                            "path": "/",
                            "headers": '.$headers.'
                        }
                    }';

                    $settings = '{
                        "clients": [
                            {
                                "id": "'.$client_id.'",
                                "alterId": 0
                            }
                        ],
                        "disableInsecureEncryption": false
                    }';
                } else {
                    $tcpSettings = '{
                        "network": "tcp",
                        "security": "'.$security.'",
                        "tlsSettings": '.$tlsSettings.',
                        "tcpSettings": {
                            "header": '.$headers.'
                        }
                    }';

                    $wsSettings = '{
                        "network": "ws",
                        "security": "'.$security.'",
                        "tlsSettings": '.$tlsSettings.',
                        "wsSettings": {
                            "path": "/",
                            "headers": '.$headers.'
                        }
                    }';

                    if ($serverType == "sanaei" || $serverType == "alireza") {
                        $settings = '{
                            "clients": [
                                {
                                    "id": "'.$client_id.'",
                                    "enable": true,
                                    "email": "' . $remark. '",
                                    "limitIp": 0,
                                    "totalGB": 0,
                                    "expiryTime": 0,
                                    "subId": "' . RandomString(16) . '"
                                }
                            ],
                            "disableInsecureEncryption": false
                        }';
                    } else {
                        $settings = '{
                            "clients": [
                                {
                                    "id": "'.$client_id.'",
                                    "flow": "",
                                    "email": "' . $remark. '"
                                }
                            ],
                            "disableInsecureEncryption": false
                        }';
                    }
                }
            }
        }

        //-------------------------------------- Vmess ----------------------------------//
        elseif ($protocol == 'vmess') {
            if ($security == 'none') {
                $tcpSettings = '{
                    "network": "tcp",
                    "security": "'.$security.'",
                    "tlsSettings": '.$tlsSettings.',
                    "tcpSettings": {
                        "header": '.$headers.'
                    }
                }';

                $wsSettings = '{
                    "network": "ws",
                    "security": "'.$security.'",
                    "tlsSettings": '.$tlsSettings.',
                    "wsSettings": {
                        "path": "/",
                        "headers": '.$headers.'
                    }
                }';

                if ($serverType == "sanaei" || $serverType == "alireza") {
                    $settings = '{
                        "clients": [
                            {
                                "id": "'.$client_id.'",
                                "enable": true,
                                "email": "' . $remark. '",
                                "limitIp": 0,
                                "totalGB": 0,
                                "expiryTime": 0,
                                "subId": "' . RandomString(16) . '"
                            }
                        ],
                        "disableInsecureEncryption": false
                    }';
                } else {
                    $settings = '{
                        "clients": [
                            {
                                "id": "'.$client_id.'",
                                "flow": "",
                                "email": "' . $remark. '"
                            }
                        ],
                        "disableInsecureEncryption": false
                    }';
                }
            } elseif ($security == 'tls') {
                $tcpSettings = '{
                    "network": "tcp",
                    "security": "'.$security.'",
                    "tlsSettings": '.$tlsSettings.',
                    "tcpSettings": {
                        "header": '.$headers.'
                    }
                }';

                $wsSettings = '{
                    "network": "ws",
                    "security": "'.$security.'",
                    "tlsSettings": '.$tlsSettings.',
                    "wsSettings": {
                        "path": "/",
                        "headers": '.$headers.'
                    }
                }';

                if ($serverType == "sanaei" || $serverType == "alireza") {
                    $settings = '{
                        "clients": [
                            {
                                "id": "'.$client_id.'",
                                "enable": true,
                                "email": "' . $remark. '",
                                "limitIp": 0,
                                "totalGB": 0,
                                "expiryTime": 0,
                                "subId": "' . RandomString(16) . '"
                            }
                        ],
                        "disableInsecureEncryption": false
                    }';
                } else {
                    $settings = '{
                        "clients": [
                            {
                                "id": "'.$client_id.'",
                                "flow": "",
                                "email": "' . $remark. '"
                            }
                        ],
                        "disableInsecureEncryption": false
                    }';
                }
            }
        }

        //------------------------------------- Shadowsocks -----------------------------//
        elseif ($protocol == 'shadowsocks') {
            $security = 'aes-128-gcm';
            $streamSettings = '{
                "network": "tcp",
                "security": "none",
                "tlsSettings": '.$tlsSettings.',
                "tcpSettings": {
                    "header": '.$headers.'
                }
            }';

            if ($serverType == "sanaei" || $serverType == "alireza") {
                $settings = '{
                    "clients": [
                        {
                            "method": "aes-128-gcm",
                            "password": "'.$client_id.'",
                            "enable": true,
                            "email": "' . $remark. '",
                            "limitIp": 0,
                            "totalGB": 0,
                            "expiryTime": 0,
                            "subId": "' . RandomString(16) . '"
                        }
                    ]
                }';
            } else {
                $settings = '{
                    "clients": [
                        {
                            "method": "aes-128-gcm",
                            "password": "'.$client_id.'",
                            "flow": "",
                            "email": "' . $remark. '"
                        }
                    ]
                }';
            }
        }

        //-------------------------------------- HTTP -----------------------------//
        elseif ($protocol == 'http') {
            $streamSettings = '{
                "network": "tcp",
                "security": "none",
                "tlsSettings": '.$tlsSettings.',
                "tcpSettings": {
                    "header": '.$headers.'
                }
            }';

            if ($serverType == "sanaei" || $serverType == "alireza") {
                $settings = '{
                    "clients": [
                        {
                            "password": "'.$client_id.'",
                            "enable": true,
                            "email": "' . $remark. '",
                            "limitIp": 0,
                            "totalGB": 0,
                            "expiryTime": 0,
                            "subId": "' . RandomString(16) . '"
                        }
                    ],
                    "accountId": "0"
                }';
            } else {
                $settings = '{
                    "clients": [
                        {
                            "password": "'.$client_id.'",
                            "flow": "",
                            "email": "' . $remark. '"
                        }
                    ],
                    "accountId": "0"
                }';
            }
        }

        // Format streamSettings based on protocol and security
        if ($protocol == 'vless') {
            $streamSettings = json_encode([
                "network" => $netType,
                "security" => $security,
                $security == 'tls' ? 'tlsSettings' : ($security == 'xtls' ? $xtlsTitle : '') => $tlsSettings,
                $netType . "Settings" => json_decode($headers, true)
            ]);
        } elseif ($protocol == 'vmess') {
            $streamSettings = json_encode([
                "network" => $netType,
                "security" => $security,
                $security == 'tls' ? 'tlsSettings' : ($security == 'xtls' ? $xtlsTitle : '') => $tlsSettings,
                $netType . "Settings" => json_decode($headers, true)
            ]);
        } elseif ($protocol == 'shadowsocks' || $protocol == 'http') {
            $streamSettings = json_encode([
                "network" => $netType,
                "security" => $security,
                "tlsSettings" => json_decode($tlsSettings, true),
                $netType . "Settings" => json_decode($headers, true)
            ]);
        } elseif ($protocol == 'trojan') {
            if ($security == 'none') {
                $streamSettings = json_encode([
                    "network" => $netType,
                    "security" => $security,
                    "tlsSettings" => json_decode($tlsSettings, true),
                    $netType . "Settings" => json_decode($headers, true)
                ]);
            } elseif ($security == 'xtls' && $serverType != "sanaei" && $serverType != "alireza") {
                $streamSettings = json_encode([
                    "network" => $netType,
                    "security" => $security,
                    $xtlsTitle => json_decode($tlsSettings, true),
                    $netType . "Settings" => json_decode($headers, true)
                ]);
            } else {
                $streamSettings = json_encode([
                    "network" => $netType,
                    "security" => $security,
                    "tlsSettings" => json_decode($tlsSettings, true),
                    $netType . "Settings" => json_decode($headers, true)
                ]);
            }
        }

        // Prepare the final payload
        $payload = json_encode([
            "port" => $port,
            "protocol" => $protocol,
            "settings" => json_decode($settings, true),
            "streamSettings" => json_decode($streamSettings, true),
            "tag" => $remark,
            "sniffing" => [
                "enabled" => true,
                "destOverride" => ["http", "tls"]
            ]
        ]);

        // Send the payload to the server's API
        $ch = curl_init("$panel_url/add");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        // Check if the response indicates success or failure
        if ($response === false) {
            throw new Exception("Failed to add inbound: " . curl_error($ch));
        }

        // Parse the response and handle any errors
        $response_data = json_decode($response, true);
        if (isset($response_data['error'])) {
            throw new Exception("Error adding inbound: " . $response_data['error']);
        }

        // Return the added inbound information
        return $response_data;
    }

    function changeInboundState($server_id, $uuid)
    {
        global $connection;

        // Retrieve server configuration
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $panel_url = $server_info['panel_url'];
        $serverType = $server_info['type'];

        // Get JSON response
        $response = getJson($server_id);
        if (!$response) return null;
        $response = $response->obj;

        // Find the inbound based on uuid
        foreach ($response as $row) {
            $settings = json_decode($row->settings, true);
            $clients = $settings['clients'];
            if ($clients[0]['id'] == $uuid || $clients[0]['password'] == $uuid) {
                $inbound_id = $row->id;
                $enable = $row->enable;
                break;
            }
        }

        // Add missing fields if necessary
        if (!isset($settings['clients'][0]['subId']) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $settings['clients'][0]['subId'] = RandomString(16);
        }
        if (!isset($settings['clients'][0]['enable']) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $settings['clients'][0]['enable'] = true;
        }

        // Toggle enable state
        $newEnable = !$enable;

        // Prepare data array for updating inbound
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

        // Login to panel
        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];
        $loginUrl = $panel_url . '/login';
        $postFields = ['username' => $serverName, 'password' => $serverPass];

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
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        preg_match('/^Set-Cookie:\s*([^;]*)/mi', $header, $match);
        $session = $match[1];

        $loginResponse = json_decode($body, true);
        if (!$loginResponse['success']) {
            curl_close($curl);
            return $loginResponse;
        }

        // Update inbound
        $url = ($serverType == "sanaei") ? "$panel_url/panel/inbound/update/$inbound_id" : "$panel_url/xui/inbound/update/$inbound_id";
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $dataArr,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0',
                'Accept: application/json, text/plain, */*',
                'Accept-Language: en-US,en;q=0.5',
                'Cookie: ' . $session
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }

    function renewInboundUuid($server_id, $uuid)
    {
        global $connection;

        // Retrieve server configuration
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $panel_url = $server_info['panel_url'];
        $serverType = $server_info['type'];

        // Get JSON response
        $response = getJson($server_id);
        if (!$response) return null;
        $response = $response->obj;

        // Find the inbound based on uuid
        foreach ($response as $row) {
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

        // Generate new UUID
        $newUuid = generateRandomString(42, $protocol);
        if ($protocol == "trojan") {
            $settings['clients'][0]['password'] = $newUuid;
        } else {
            $settings['clients'][0]['id'] = $newUuid;
        }

        // Add missing fields if necessary
        if (!isset($settings['clients'][0]['subId']) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $settings['clients'][0]['subId'] = RandomString(16);
        }
        if (!isset($settings['clients'][0]['enable']) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $settings['clients'][0]['enable'] = true;
        }

        // Update settings JSON
        $settings = json_encode($settings, 488);

        // Prepare data array for updating inbound
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

        // Login to panel
        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];
        $loginUrl = $panel_url . '/login';
        $postFields = ['username' => $serverName, 'password' => $serverPass];

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
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        preg_match('/^Set-Cookie:\s*([^;]*)/mi', $header, $match);
        $session = $match[1];

        $loginResponse = json_decode($body, true);
        if (!$loginResponse['success']) {
            curl_close($curl);
            return $loginResponse;
        }

        // Update inbound
        $url = ($serverType == "sanaei") ? "$panel_url/panel/inbound/update/$inbound_id" : "$panel_url/xui/inbound/update/$inbound_id";
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $dataArr,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0',
                'Accept: application/json, text/plain, */*',
                'Accept-Language: en-US,en;q=0.5',
                'Cookie: ' . $session
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response);
        $response->newUuid = $newUuid;
        return $response;
    }

    function editClientRemark($server_id, $inbound_id, $uuid, $newRemark)
    {
        global $connection;

        // Fetch server configuration
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id = ?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $panel_url = $server_info['panel_url'];
        $cookie = 'Cookie: session=' . $server_info['cookie'];
        $serverType = $server_info['type'];

        // Fetch JSON response
        $response = getJson($server_id);
        if (!$response) return null;
        $response = $response->obj;

        // Locate the client and inbound details
        $client_key = 0;
        foreach ($response as $row) {
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

                        $total = $clientsStates[$emailKey]->total;
                        $up = $clientsStates[$emailKey]->up;
                        $enable = $clientsStates[$emailKey]->enable;
                        $down = $clientsStates[$emailKey]->down;
                        break;
                    }
                }
            }
        }

        // Update the client's remark
        $settings['clients'][$client_key]['email'] = $newRemark;
        if (!isset($settings['clients'][$client_key]['subId']) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $settings['clients'][$client_key]['subId'] = RandomString(16);
        }
        if (!isset($settings['clients'][$client_key]['enable']) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $settings['clients'][$client_key]['enable'] = true;
        }

        // Prepare data for updating
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

        // Login to the panel
        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];
        $loginUrl = $panel_url . '/login';
        $postFields = [
            "username" => $serverName,
            "password" => $serverPass
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

        $loginResponse = json_decode($body, true);
        if (!$loginResponse['success']) {
            curl_close($curl);
            return $loginResponse;
        }

        // Update client details
        if ($serverType == "sanaei" || $serverType == "alireza") {
            $newSetting = ['clients' => [$editedClient]];
            $dataArr = [
                "id" => $inbound_id,
                "settings" => json_encode($newSetting)
            ];
            $url = ($serverType == "sanaei") ? "$panel_url/panel/inbound/updateClient/" . rawurlencode($uuid) : "$panel_url/xui/inbound/updateClient/" . rawurlencode($uuid);

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
                    'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                    'Accept:  application/json, text/plain, */*',
                    'Accept-Language:  en-US,en;q=0.5',
                    'Accept-Encoding:  gzip, deflate',
                    'X-Requested-With:  XMLHttpRequest',
                    'Cookie: ' . $session
                ]
            ]);
        } else {
            curl_setopt_array($curl, [
                CURLOPT_URL => "$panel_url/xui/inbound/update/$inbound_id",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $dataArr,
                CURLOPT_HEADER => false,
                CURLOPT_HTTPHEADER => [
                    'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                    'Accept:  application/json, text/plain, */*',
                    'Accept-Language:  en-US,en;q=0.5',
                    'Accept-Encoding:  gzip, deflate',
                    'X-Requested-With:  XMLHttpRequest',
                    'Cookie: ' . $session
                ]
            ]);
        }

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }

    function editClientTraffic($server_id, $inbound_id, $uuid, $volume, $days, $editType = null)
    {
        global $connection;

        // Fetch server configuration
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id = ?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $panel_url = $server_info['panel_url'];
        $cookie = 'Cookie: session=' . $server_info['cookie'];
        $serverType = $server_info['type'];

        // Fetch JSON response
        $response = getJson($server_id);
        if (!$response) return null;
        $response = $response->obj;

        // Locate the client and inbound details
        $client_key = 0;
        foreach ($response as $row) {
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

                        $total = $clientsStates[$emailKey]->total;
                        $up = $clientsStates[$emailKey]->up;
                        $enable = $clientsStates[$emailKey]->enable;
                        $down = $clientsStates[$emailKey]->down;
                        break;
                    }
                }
            }
        }

        // Update the client's volume and expiry date
        if ($volume != 0) {
            $client_total = $settings['clients'][$client_key]['totalGB'];
            $extend_volume = floor($volume * 1073741824);
            $volume = ($client_total > 0) ? $client_total + $extend_volume : $extend_volume;
            $settings['clients'][$client_key]['totalGB'] = $volume;
        }
        if ($days != 0) {
            $currentExpiry = $settings['clients'][$client_key]['expiryTime'] ?? 0;
            $newExpiry = max($currentExpiry, time()) + $days * 24 * 3600;
            $settings['clients'][$client_key]['expiryTime'] = $newExpiry;
        }

        if (!isset($settings['clients'][$client_key]['subId']) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $settings['clients'][$client_key]['subId'] = RandomString(16);
        }
        if (!isset($settings['clients'][$client_key]['enable']) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $settings['clients'][$client_key]['enable'] = true;
        }

        // Prepare data for updating
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

        // Login to the panel
        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];
        $loginUrl = $panel_url . '/login';
        $postFields = [
            "username" => $serverName,
            "password" => $serverPass
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

        $loginResponse = json_decode($body, true);
        if (!$loginResponse['success']) {
            curl_close($curl);
            return $loginResponse;
        }

        // Update client details
        if ($serverType == "sanaei" || $serverType == "alireza") {
            $newSetting = ['clients' => [$editedClient]];
            $dataArr = [
                "id" => $inbound_id,
                "settings" => json_encode($newSetting)
            ];
            $url = ($serverType == "sanaei") ? "$panel_url/panel/inbound/updateClient/" . rawurlencode($uuid) : "$panel_url/xui/inbound/updateClient/" . rawurlencode($uuid);

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
                    'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                    'Accept:  application/json, text/plain, */*',
                    'Accept-Language:  en-US,en;q=0.5',
                    'Accept-Encoding:  gzip, deflate',
                    'X-Requested-With:  XMLHttpRequest',
                    'Cookie: ' . $session
                ]
            ]);
        } else {
            curl_setopt_array($curl, [
                CURLOPT_URL => "$panel_url/xui/inbound/update/$inbound_id",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $dataArr,
                CURLOPT_HEADER => false,
                CURLOPT_HTTPHEADER => [
                    'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                    'Accept:  application/json, text/plain, */*',
                    'Accept-Language:  en-US,en;q=0.5',
                    'Accept-Encoding:  gzip, deflate',
                    'X-Requested-With:  XMLHttpRequest',
                    'Cookie: ' . $session
                ]
            ]);
        }

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }

    function resetIpLog($server_id, $remark)
    {
        global $connection;

        // Fetch server configuration
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id = ?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $panel_url = $server_info['panel_url'];
        $serverType = $server_info['type'];
        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];

        // Login to the panel
        $loginUrl = $panel_url . '/login';
        $postFields = [
            "username" => $serverName,
            "password" => $serverPass
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

        $loginResponse = json_decode($body, true);
        if (!$loginResponse['success']) {
            curl_close($curl);
            return $loginResponse;
        }

        // Set URL based on server type
        $url = $serverType == "sanaei" ? $panel_url . "/panel/inbound/clearClientIps/" . urlencode($remark) : $panel_url . "/xui/inbound/clearClientIps/" . urlencode($remark);

        // Make request to clear IP logs
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

    function resetClientTraffic($server_id, $remark, $inboundId = null)
    {
        global $connection;

        // Fetch server configuration
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id = ?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $panel_url = $server_info['panel_url'];
        $serverType = $server_info['type'];
        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];

        // Login to the panel
        $loginUrl = $panel_url . '/login';
        $postFields = [
            "username" => $serverName,
            "password" => $serverPass
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

        $loginResponse = json_decode($body, true);
        if (!$loginResponse['success']) {
            curl_close($curl);
            return $loginResponse;
        }

        // Set URL based on server type and inbound ID
        if ($serverType == "sanaei") {
            $url = "$panel_url/panel/inbound/$inboundId/resetClientTraffic/" . rawurlencode($remark);
        } else {
            $url = $inboundId === null ? "$panel_url/xui/inbound/resetClientTraffic/" . rawurlencode($remark) : "$panel_url/xui/inbound/$inboundId/resetClientTraffic/" . rawurlencode($remark);
        }

        // Make request to reset client traffic
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

    function getNewHeaders($netType, $request_header, $response_header, $type)
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
        }

        return $headers;
    }

    function getConnectionLink($server_id, $uniqid, $protocol, $remark, $port, $netType, $inbound_id = 0, $bypass =
    false, $customPath = false, $customPort = 0, $customSni = null)
    {

        global $connection;
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $panel_url = parse_url($server_info['panel_url'], PHP_URL_HOST);
        $server_ip = empty($server_info['ip']) ? $panel_url : $server_info['ip'];
        $sni = $server_info['sni'];
        $header_type = $server_info['header_type'];
        $request_header = $server_info['request_header'];
        $response_header = $server_info['response_header'];
        $serverType = $server_info['type'];

        preg_match("/^Host:(.*)/i", $request_header, $hostMatch);
        $response = getJson($server_id)->obj;

        foreach ($response as $row) {
            if ($inbound_id == 0) {
                $clients = json_decode($row->settings)->clients;
                if ($clients[0]->id == $uniqid || $clients[0]->password == $uniqid) {
                    extractServerDetails($row, $serverType, $netType, $remark, $sni, $tlsStatus, $tlsSetting, $xtlsSetting, $settings, $fp, $spiderX, $pbk, $sid, $flow, $path, $host, $serviceName, $grpcSecurity, $alpn, $kcpType, $kcpSeed);
                    break;
                }
            } else {
                if ($row->id == $inbound_id) {
                    extractServerDetails($row, $serverType, $netType, $remark, $sni, $tlsStatus, $tlsSetting, $xtlsSetting, $settings, $fp, $spiderX, $pbk, $sid, $flow, $path, $host, $serviceName, $grpcSecurity, $alpn, $kcpType, $kcpSeed);
                    break;
                }
            }
        }

        $outputLink = generateConnectionLink($protocol, $uniqid, $remark, $port, $netType, $server_ip, $bypass, $customPath, $customPort, $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid, $serviceName, $grpcSecurity, $alpn, $kcpType, $kcpSeed);

        return $outputLink;

    }

    function extractServerDetails($row, $serverType, &$netType, &$remark, &$sni, &$tlsStatus, &$tlsSetting,
    &$xtlsSetting, &$settings, &$fp, &$spiderX, &$pbk, &$sid, &$flow, &$path, &$host, &$serviceName, &$grpcSecurity,
    &$alpn, &$kcpType, &$kcpSeed)
    {
        if ($serverType == "sanaei" || $serverType == "alireza") {
            $settings = json_decode($row->settings, true);
            $email = $settings['clients'][0]['email'];
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

    function generateConnectionLink($protocol, $uniqid, $remark, $port, $netType, $server_ip, $bypass, $customPath,
    $customPort, $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid,
    $serviceName, $grpcSecurity, $alpn, $kcpType, $kcpSeed)
    {

        $protocol = strtolower($protocol);
        $serverIp = explode("\n", $server_ip);
        $outputLinks = [];

        foreach ($serverIp as $server_ip) {
            $server_ip = trim(str_replace("\r", "", $server_ip));
            if ($protocol == 'vless') {
                $outputLinks[] = generateVlessLink($uniqid, $remark, $port, $netType, $server_ip, $bypass, $customPath, $customPort, $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid, $serviceName, $grpcSecurity, $alpn, $kcpType, $kcpSeed);
            } elseif ($protocol == 'trojan') {
                $outputLinks[] = generateTrojanLink($uniqid, $remark, $port, $netType, $server_ip, $bypass, $customPath, $customPort, $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid, $serviceName, $grpcSecurity, $alpn, $kcpType, $kcpSeed);
            } elseif ($protocol == 'vmess') {
                $outputLinks[] = generateVmessLink($uniqid, $remark, $port, $netType, $server_ip, $bypass, $customPath, $customPort, $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid, $serviceName, $grpcSecurity, $alpn, $kcpType, $kcpSeed);
            }
        }

        return implode("\n", $outputLinks);

    }

    function generateVlessLink($uniqid, $remark, $port, $netType, $server_ip, $bypass, $customPath, $customPort,
    $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid, $serviceName,
    $grpcSecurity, $alpn, $kcpType, $kcpSeed)
    {

        return "vless://$uniqid@$server_ip:$port?remarks=$remark&path=$path&security=$tlsStatus&encryption=none&alpn=$alpn&fp=$fp&sni=$sni&pbk=$pbk&sid=$sid&spiderX=$spiderX&serviceName=$serviceName&grpcSecurity=$grpcSecurity&host=$host";

    }

    function generateTrojanLink($uniqid, $remark, $port, $netType, $server_ip, $bypass, $customPath, $customPort,
    $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid, $serviceName,
    $grpcSecurity, $alpn, $kcpType, $kcpSeed)
    {

        return "trojan://$uniqid@$server_ip:$port?remarks=$remark&path=$path&security=$tlsStatus&alpn=$alpn&fp=$fp&sni=$sni&pbk=$pbk&sid=$sid&spiderX=$spiderX&serviceName=$serviceName&grpcSecurity=$grpcSecurity&host=$host";

    }

    function generateVmessLink($uniqid, $remark, $port, $netType, $server_ip, $bypass, $customPath, $customPort,
    $customSni, $sni, $header_type, $host, $tlsStatus, $path, $flow, $fp, $spiderX, $pbk, $sid, $serviceName,
    $grpcSecurity, $alpn, $kcpType, $kcpSeed)
    {

        $link = [
            'v' => '2',
            'ps' => $remark,
            'add' => $server_ip,
            'port' => $port,
            'id' => $uniqid,
            'aid' => '0',
            'scy' => 'auto',
            'net' => $netType,
            'type' => $header_type,
            'host' => $host,
            'path' => $path,
            'tls' => $tlsStatus,
            'fp' => $fp,
            'alpn' => $alpn,
            'pbk' => $pbk,
            'sid' => $sid,
            'spiderX' => $spiderX,
            'serviceName' => $serviceName,
            'grpcSecurity' => $grpcSecurity,
            'kcpType' => $kcpType,
            'kcpSeed' => $kcpSeed
        ];

        return 'vmess://' . base64_encode(json_encode($link, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    }

    function updateConfig($server_id, $inboundId, $protocol, $netType = 'tcp', $security = 'none', $bypass = false)
    {
        global $connection;

        // Fetch server configuration
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id = ?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$server_info) {
            return null;
        }

        // Extract server info
        $panel_url = $server_info['panel_url'];
        $security = $server_info['security'];
        $tlsSettings = $server_info['tlsSettings'];
        $header_type = $server_info['header_type'];
        $request_header = $server_info['request_header'];
        $response_header = $server_info['response_header'];
        $cookie = 'Cookie: session=' . $server_info['cookie'];
        $serverType = $server_info['type'];
        $sni = $server_info['sni'];
        $xtlsTitle = ($serverType == "sanaei" || $serverType == "alireza") ? "XTLSSettings" : "xtlsSettings";

        // Update TLS settings with SNI if applicable
        if (!empty($sni) && ($serverType == "sanaei" || $serverType == "alireza")) {
            $tlsSettings = json_decode($tlsSettings, true);
            $tlsSettings['serverName'] = $sni;
            $tlsSettings = json_encode($tlsSettings, JSON_UNESCAPED_UNICODE);
        }

        // Fetch server inbounds
        $response = getJson($server_id);
        if (!$response) {
            return null;
        }

        $response = $response->obj;
        foreach ($response as $row) {
            if ($row->id == $inboundId) {
                $iid = $row->id;
                $remark = $row->remark;
                $streamSettings = $row->streamSettings;
                $settings = $row->settings;
                break;
            }
        }

        if (!intval($iid)) {
            return;
        }

        $headers = getNewHeaders($netType, $request_header, $response_header, $header_type);
        $headers = empty($headers) ? "{}" : $headers;

        // Generate stream settings
        $streamSettings = generateStreamSettings($protocol, $netType, $security, $tlsSettings, $headers, $panel_url, $sni, $serverType, $xtlsTitle, $bypass);

        // Prepare data array for update
        $dataArr = array(
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
        );

        // Authenticate and update inbound
        return authenticateAndUpdateInbound($panel_url, $server_info, $dataArr, $iid, $serverType);
    }

    function generateStreamSettings($protocol, $netType, $security, $tlsSettings, $headers, $panel_url, $sni, $serverType, $xtlsTitle, $bypass)
    {
        if ($protocol == 'trojan') {
            if ($netType == 'grpc') {
                return generateGrpcSettings($security, $tlsSettings, $sni, $serverType, $panel_url);
            }

            $tcpSettings = generateTcpWsSettings('tcp', $security, $tlsSettings, $headers, $xtlsTitle, $serverType);
            $wsSettings = generateTcpWsSettings('ws', $security, $tlsSettings, $headers, $xtlsTitle, $serverType, $bypass);
            return ($netType == 'tcp') ? $tcpSettings : $wsSettings;
        }

        // Additional protocol handling can be added here
        return null;
    }

    function generateTcpWsSettings($network, $security, $tlsSettings, $headers, $xtlsTitle, $serverType, $bypass = false)
    {
        $path = $bypass ? "/wss" : "/";
        return json_encode([
            'network' => $network,
            'security' => $security,
            $xtlsTitle => ($security == 'xtls' && ($serverType != "sanaei" && $serverType != "alireza")) ? $tlsSettings : null,
            'tlsSettings' => ($security == 'tls') ? $tlsSettings : null,
            "{$network}Settings" => [
                'path' => $path,
                'header' => $headers
            ]
        ], JSON_UNESCAPED_UNICODE);
    }

    function generateGrpcSettings($security, $tlsSettings, $sni, $serverType, $panel_url)
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

    function authenticateAndUpdateInbound($panel_url, $server_info, $dataArr, $iid, $serverType)
    {
        $loginUrl = $panel_url . '/login';
        $postFields = array(
            "username" => $server_info['username'],
            "password" => $server_info['password']
        );

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

        $loginResponse = json_decode($body, true);
        if (!$loginResponse['success']) {
            curl_close($curl);
            return $loginResponse;
        }

        $url = ($serverType == "sanaei") ? "$panel_url/panel/inbound/update/$iid" : "$panel_url/xui/inbound/update/$iid";
        curl_setopt_array($curl, array(
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
            CURLOPT_HTTPHEADER => array(
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                'Accept: application/json, text/plain, */*',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate',
                'X-Requested-With: XMLHttpRequest',
                'Cookie: ' . $session
            )
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }

    function getNewCert($server_id)
    {
        global $connection;

        $server_info = getServerConfig($server_id);
        if (!$server_info) {
            return null;
        }

        $panel_url = $server_info['panel_url'];
        $cookie = 'Cookie: session=' . $server_info['cookie'];

        $session = authenticateAndGetSession($server_info);
        if (!$session) {
            return null;
        }

        $url = "$panel_url/server/getNewX25519Cert";

        return executeCurlRequest($url, $session);
    }

    function getServerConfig($server_id)
    {
        global $connection;

        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $server_info;
    }

    function authenticateAndGetSession($server_info)
    {
        $panel_url = $server_info['panel_url'];
        $loginUrl = $panel_url . '/login';

        $postFields = array(
            "username" => $server_info['username'],
            "password" => $server_info['password']
        );

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
        $body = substr($response, $header_size);
        preg_match('/^Set-Cookie:\s*([^;]*)/mi', $header, $match);
        $session = $match[1];

        $loginResponse = json_decode($body, true);
        if (!$loginResponse['success']) {
            curl_close($curl);
            return null;
        }

        curl_close($curl);
        return $session;
    }
}
