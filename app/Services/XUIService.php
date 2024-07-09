<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\ServerConfig;
use App\Models\ServerPlan;
use App\Models\ServerInfo;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class XUIController extends Controller
{
    protected $baseUrl;
    protected $token;
    protected $username;
    protected $password;
    protected $httpClient;

    public function __construct(Server $server)
    {
        $this->baseUrl = $server->config->panel_url;
        $this->username = $server->config->username;
        $this->password = $server->config->password;

        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'cookies' => true, // Enable cookies handling
        ]);

        $this->token = $this->login();
    }

    protected function login()
    {
        $response = $this->httpClient->post('/login', [
            'form_params' => [
                'username' => $this->username,
                'password' => $this->password,
            ],
        ]);

        return $response->getHeader('Set-Cookie');
    }

    function RandomString($count = 9, $type = "all")
    {
        if ($type == "all") {
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789';
        } elseif ($type == "small") {
            $characters = 'abcdef123456789';
        } elseif ($type == "domain") {
            $characters = 'abcdefghijklmnopqrstuvwxyz';
        }

        $randstring = '';

        for ($i = 0; $i < $count; $i++) {
            $randstring .= $characters[rand(0, strlen($characters) - 1)];
            }

        return $randstring;
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

    function generateRandomString($length, $protocol)
    {
        return ($protocol == 'trojan') ? substr(md5(time()), 5, 15) : generateUID();
    }

    public function addInboundAccount(Request $request, $server_id, $customer_id, $server_inbound_id, $expiryTime, $remark, $volume, $limitIp = 1, $newarr = '', $planId = null)
    {
        $server_info = Server::findOrFail($server_id)->config;

        $response = $this->getJson($server_id);
        if (!$response) return null;
        $response = $response->obj;

        foreach ($response as $row) {
            if ($row->id == $server_inbound_id) {
                $iid = $row->id;
                $protocol = $row->protocol;
                break;
            }
        }

        if (!intval($iid)) return "Inbound not found";

        $settings = json_decode($row->settings, true);
        $id_label = $protocol == 'trojan' ? 'password' : 'id';

        if ($newarr == '') {
            $newClient = [
                "$id_label" => $customer_id,
                "enable" => true,
                "email" => $remark,
                "limitIp" => $limitIp,
                "totalGB" => $volume,
                "expiryTime" => $expiryTime,
                "subId" => RandomString(16)
            ];

            $settings['clients'][] = $newClient;
        } elseif (is_array($newarr)) {
            $settings['clients'][] = $newarr;
        }

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
            'sniffing' => $row->sniffing,
        ];

        $url = $server_info->type == 'sanaei' ? '/panel/inbound/addClient/' : '/xui/inbound/addClient/';

        $response = $this->httpClient->post($url, [
            'form_params' => $dataArr,
            'headers' => [
                'Cookie' => $this->token,
            ],
        ]);

        return $response->getBody()->getContents();
    }

    public function deleteInbound(Request $request, $server_id, $uuid, $delete = 0)
    {
        $server_info = Server::findOrFail($server_id)->config;

        $response = $this->getJson($server_id);
        if (!$response) return null;
        $response = $response->obj;

        foreach ($response as $row) {
            $clients = json_decode($row->settings)->clients;
            if ($clients[0]->id == $uuid || $clients[0]->password == $uuid) {
                $inbound_id = $row->id;
                $protocol = $row->protocol;
                $uniqid = $protocol == 'trojan' ? $clients[0]->password : $clients[0]->id;
                $netType = json_decode($row->streamSettings)->network;
                $oldData = [
                    'total' => $row->total,
                    'up' => $row->up,
                    'down' => $row->down,
                    'volume' => ((int)$row->total - (int)$row->up - (int)$row->down),
                    'port' => $row->port,
                    'protocol' => $protocol,
                    'expiryTime' => $row->expiryTime,
                    'uniqid' => $uniqid,
                    'netType' => $netType,
                    'security' => json_decode($row->streamSettings)->security,
                ];
                break;
            }
        }

        if ($delete == 1) {
            $url = $server_info->type == 'sanaei' ? '/panel/inbound/del/' . $inbound_id : '/xui/inbound/del/' . $inbound_id;

            $response = $this->httpClient->post($url, [
                'form_params' => [],
                'headers' => [
                    'Cookie' => $this->token,
                ],
            ]);

            return $response->getBody()->getContents();
        }

        return $oldData;
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
        $response = getJson($server_id);
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
        $headers = getNewHeaders($netType, $request_header, $response_header, $header_type);
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
                        "subId": "' . RandomString(16) . '"
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

        // Prepare login and session handling
        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];
        $loginUrl = $panel_url . '/login';
        $postFields = [
            "username" => $serverName,
            "password" => $serverPass
        ];

        // Perform login
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

        // Check login response
        $loginResponse = json_decode($body, true);
        if (!$loginResponse['success']) {
            curl_close($curl);
            return $loginResponse;
        }

        // Define endpoint URL based on server type
        $url = ($serverType == "sanaei") ? "$panel_url/panel/inbound/update/$iid" : "$panel_url/xui/inbound/update/$iid";

        // Configure and execute CURL request to update inbound settings
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($dataArr),
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Content-Type: application/json",
                "Cookie: $session"
            ]
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

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
        $response = getJson($server_id);
        if (!$response) return null;
        $response = $response->obj;

        // Find the inbound based on uuid
        foreach ($response as $row) {
            $clients = json_decode($row->settings)->clients;
            if ($clients[0]->id == $uuid || $clients[0]->password == $uuid) {
                $inbound_id = $row->id;
                $total = $row->total;
                $up = $row->up;
                $down = $row->down;
                $expiryTime = $row->expiryTime;
                $port = $row->port;
                $netType = json_decode($row->streamSettings)->network;
                $email = $clients[0]->email;
                break;
            }
        }

        // Update expiry date if days are provided
        if ($days != 0) {
            $now_microdate = floor(microtime(true) * 1000);
            $extend_date = (864000 * $days * 100);
            $expire_microdate = ($editType == "renew") ? $now_microdate + $extend_date : max($now_microdate, $expiryTime) + $extend_date;
        }

        // Update volume if provided
        if ($volume != 0) {
            $leftGB = $total - $up - $down;
            $extend_volume = floor($volume * 1073741824);
            if ($editType == "renew") {
                $total = $extend_volume;
                $up = 0;
                $down = 0;
                resetClientTraffic($server_id, $email, $inbound_id);
            } else {
                $total = max($leftGB, 0) + $extend_volume;
            }
        }

        // Prepare data array for updating inbound
        $dataArr = [
            'up' => $up,
            'down' => $down,
            'total' => $total ?? $row->total,
            'remark' => $row->remark,
            'enable' => 'true',
            'expiryTime' => $expire_microdate ?? $row->expiryTime,
            'listen' => '',
            'port' => $row->port,
            'protocol' => $row->protocol,
            'settings' => $row->settings,
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
        resetIpLog($server_id, $email);
        return json_decode($response);
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


    function getJson($server_id)
    {
        $server = Server::findOrFail($server_id); // Assuming Server model exists with appropriate relationships

        $response = Http::asForm()->post("{$server->config->panel_url}/login", [
            'username' => $server->config->username,
            'password' => $server->config->password,
        ]);

        $session = $response->cookie('session');

        $loginResponse = $response->json();

        if (!$loginResponse['success']) {
            return $loginResponse;
        }

        if ($server->config->type == "sanaei") {
            $url = "{$server->config->panel_url}/panel/inbound/list";
        } else {
            $url = "{$server->config->panel_url}/xui/inbound/list";
        }

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Accept-Encoding' => 'gzip, deflate',
            'X-Requested-With' => 'XMLHttpRequest',
            'Cookie' => $session,
        ])->post($url);

        return $response->json();
    }


}
