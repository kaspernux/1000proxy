<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class XUIController extends Controller
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
        $response = Http::asForm()->post("{$this->baseUrl}/login", [
            'username' => $this->username,
            'password' => $this->password,
        ]);

        return $response->cookie('session');
    }

    function addInboundAccount($server_id, $customer_id, $server_inbound_id, $expiryTime, $remark, $volume, $limitIp = 1, $newarr = '', $planId = null){
        global $connection;
        $stmt = $connection->prepare("SELECT * FROM server_inbounds WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $panel_url = $server_info['panel_url'];
        $cookie = 'Cookie: session='.$server_info['cookie'];
        $serverType = $server_info['type'];
        $reality = $server_info['reality'];
        $volume = ($volume == 0) ? 0 : floor($volume * 1073741824);

        $response = getJson($server_id);
        if(!$response) return null;
        $response = $response->obj;
        foreach($response as $row){
            if($row->id == $inbound_id) {
                $iid = $row->id;
                $protocol = $row->protocol;
                break;
            }
        }
        if(!intval($iid)) return "inbound not Found";

        $settings = json_decode($row->settings, true);
        $id_label = $protocol == 'trojan' ? 'password' : 'id';
        if($newarr == ''){
            if($serverType == "sanaei" || $serverType == "alireza"){
                if($reality == "true"){
                    $stmt = $connection->prepare("SELECT * FROM `server_plans` WHERE `id`=?");
                    $stmt->bind_param("i", $planId);
                    $stmt->execute();
                    $file_detail = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    $flow = isset($file_detail['flow']) && $file_detail['flow'] != "None" ? $file_detail['flow'] : "";

                    $newClient = [
                        "$id_label" => $client_id,
                        "enable" => true,
                        "email" => $remark,
                        "limitIp" => $limitip,
                        "flow" => $flow,
                        "totalGB" => $volume,
                        "expiryTime" => $expiryTime,
                        "subId" => RandomString(16)
                    ];
                }else{
                    $newClient = [
                        "$id_label" => $client_id,
                        "enable" => true,
                        "email" => $remark,
                        "limitIp" => $limitip,
                        "totalGB" => $volume,
                        "expiryTime" => $expiryTime,
                        "subId" => RandomString(16)
                    ];
                }
            }else{
                $newClient = [
                    "$id_label" => $client_id,
                    "flow" => "",
                    "email" => $remark,
                    "limitIp" => $limitip,
                    "totalGB" => $volume,
                    "expiryTime" => $expiryTime
                ];
            }
            $settings['clients'][] = $newClient;
        }elseif(is_array($newarr)) $settings['clients'][] = $newarr;

        $settings['clients'] = array_values($settings['clients']);
        $settings = json_encode($settings);

        $dataArr = array('up' => $row->up,'down' => $row->down,'total' => $row->total,'remark' => $row->remark,'enable' => 'true',
            'expiryTime' => $row->expiryTime, 'listen' => '','port' => $row->port,'protocol' => $row->protocol,'settings' => $settings,
            'streamSettings' => $row->streamSettings, 'sniffing' => $row->sniffing);

        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];

        $loginUrl = $panel_url . '/login';

        $postFields = array(
            "username" => $serverName,
            "password" => $serverPass
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

        $loginResponse = json_decode($body,true);
        if(!$loginResponse['success']){
            curl_close($curl);
            return $loginResponse;
        }

        if($serverType == "sanaei" || $serverType == "alireza"){
            $newSetting = array();
            if($newarr == '')$newSetting['clients'][] = $newClient;
            elseif(is_array($newarr)) $newSetting['clients'][] = $newarr;

            $newSetting = json_encode($newSetting);
            $dataArr = array(
                "id"=>$inbound_id,
                "settings" => $newSetting
                );

            if($serverType == "sanaei") $url = "$panel_url/panel/inbound/addClient/";
            else $url = "$panel_url/xui/inbound/addClient/";

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
                    'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                    'Accept:  application/json, text/plain, */*',
                    'Accept-Language:  en-US,en;q=0.5',
                    'Accept-Encoding:  gzip, deflate',
                    'X-Requested-With:  XMLHttpRequest',
                    'Cookie: ' . $session
                )
            ));
        }else{
            curl_setopt_array($curl, array(
                CURLOPT_URL => "$panel_url/xui/inbound/update/$iid",
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
                    'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                    'Accept:  application/json, text/plain, */*',
                    'Accept-Language:  en-US,en;q=0.5',
                    'Accept-Encoding:  gzip, deflate',
                    'X-Requested-With:  XMLHttpRequest',
                    'Cookie: ' . $session
                )
            ));
        }

        $response = curl_exec($curl);
        curl_close($curl);
        return $response = json_decode($response);

    }
    function deleteInbound($server_id, $uuid, $delete = 0){
        global $connection;
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $panel_url = $server_info['panel_url'];
        $cookie = 'Cookie: session='.$server_info['cookie'];
        $serverType = $server_info['type'];

        $response = getJson($server_id);
        if(!$response) return null;
        $response = $response->obj;
        foreach($response as $row){
            $clients = json_decode($row->settings)->clients;
            if($clients[0]->id == $uuid || $clients[0]->password == $uuid) {
                $inbound_id = $row->id;
                $protocol = $row->protocol;
                $uniqid = ($protocol == 'trojan') ? json_decode($row->settings)->clients[0]->password : json_decode($row->settings)->clients[0]->id;
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
        if($delete == 1){
            $serverName = $server_info['username'];
            $serverPass = $server_info['password'];

            $loginUrl = $panel_url . '/login';

            $postFields = array(
                "username" => $serverName,
                "password" => $serverPass
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

            $loginResponse = json_decode($body,true);
            if(!$loginResponse['success']){
                curl_close($curl);
                return $loginResponse;
            }

            if($serverType == "sanaei") $url = "$panel_url/panel/inbound/del/$inbound_id";
            else $url = "$panel_url/xui/inbound/del/$inbound_id";

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
                CURLOPT_HEADER => false,
                CURLOPT_HTTPHEADER => array(
                    'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                    'Accept:  application/json, text/plain, */*',
                    'Accept-Language:  en-US,en;q=0.5',
                    'Accept-Encoding:  gzip, deflate',
                    'X-Requested-With:  XMLHttpRequest',
                    'Cookie: ' . $session
                )
            ));
            $response = curl_exec($curl);
            curl_close($curl);
        }
        return $oldData;
    }
    function editInboundRemark($server_id, $uuid, $newRemark){
        global $connection;
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $panel_url = $server_info['panel_url'];
        $cookie = 'Cookie: session='.$server_info['cookie'];
        $serverType = $server_info['type'];

        $response = getJson($server_id);
        if(!$response) return null;
        $response = $response->obj;
        foreach($response as $row){
            $clients = json_decode($row->settings)->clients;
            if($clients[0]->id == $uuid || $clients[0]->password == $uuid) {
                $inbound_id = $row->id;
                $total = $row->total;
                $up = $row->up;
                $down = $row->down;
                $expiryTime = $row->expiryTime;
                $port = $row->port;
                $netType = json_decode($row->streamSettings)->network;
                break;
            }
        }


        $dataArr = array('up' => $up,'down' => $down,'total' => $total,'remark' => $newRemark,'enable' => 'true',
            'expiryTime' => $row->expiryTime, 'listen' => '','port' => $row->port,'protocol' => $row->protocol,'settings' => $row->settings,
            'streamSettings' => $row->streamSettings, 'sniffing' => $row->sniffing);


        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];

        $loginUrl = $panel_url . '/login';

        $postFields = array(
            "username" => $serverName,
            "password" => $serverPass
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

        $loginResponse = json_decode($body,true);
        if(!$loginResponse['success']){
            curl_close($curl);
            return $loginResponse;
        }

        if($serverType == "sanaei") $url = "$panel_url/panel/inbound/update/$inbound_id";
        else $url = "$panel_url/xui/inbound/update/$inbound_id";

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 15,      // timeout on connect
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $dataArr,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array(
                'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                'Accept:  application/json, text/plain, */*',
                'Accept-Language:  en-US,en;q=0.5',
                'Accept-Encoding:  gzip, deflate',
                'X-Requested-With:  XMLHttpRequest',
                'Cookie: ' . $session
            )
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response = json_decode($response);
    }
    function editInboundTraffic($server_id, $uuid, $volume, $days, $editType = null){
        global $connection;
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $panel_url = $server_info['panel_url'];
        $cookie = 'Cookie: session='.$server_info['cookie'];
        $serverType = $server_info['type'];

        $response = getJson($server_id);
        if(!$response) return null;
        $response = $response->obj;
        foreach($response as $row){
            $clients = json_decode($row->settings)->clients;
            if($clients[0]->id == $uuid || $clients[0]->password == $uuid) {
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
        if($days != 0) {
            $now_microdate = floor(microtime(true) * 1000);
            $extend_date = (864000 * $days * 100);
            if($editType == "renew") $expire_microdate = $now_microdate + $extend_date;
            else $expire_microdate = ($now_microdate > $expiryTime) ? $now_microdate + $extend_date : $expiryTime + $extend_date;
        }

        if($volume != 0){
            $leftGB = $total - $up - $down;
            $extend_volume = floor($volume * 1073741824);
            if($editType == "renew"){
                $total = $extend_volume;
                $up = 0;
                $down = 0;
                $volume = $extend_volume;
                if($serverType == "sanaei" || $serverType == "alireza") resetClientTraffic($server_id, $email, $inbound_id);
                else resetClientTraffic($server_id, $email);
            }
            else $total = ($leftGB > 0) ? $total + $extend_volume : $extend_volume;
        }

        $dataArr = array('up' => $up,'down' => $down,'total' => is_null($total) ? $row->total : $total,'remark' => $row->remark,'enable' => 'true',
            'expiryTime' => is_null($expire_microdate) ? $row->expiryTime : $expire_microdate, 'listen' => '','port' => $row->port,'protocol' => $row->protocol,'settings' => $row->settings,
            'streamSettings' => $row->streamSettings, 'sniffing' => $row->sniffing);


        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];

        $loginUrl = $panel_url . '/login';

        $postFields = array(
            "username" => $serverName,
            "password" => $serverPass
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

        $loginResponse = json_decode($body,true);
        if(!$loginResponse['success']){
            curl_close($curl);
            return $loginResponse;
        }

        if($serverType == "sanaei") $url = "$panel_url/panel/inbound/update/$inbound_id";
        else $url = "$panel_url/xui/inbound/update/$inbound_id";

        $phost = str_ireplace('https://','',str_ireplace('http://','',$panel_url));
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 15,      // timeout on connect
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $dataArr,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array(
                'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                'Accept:  application/json, text/plain, */*',
                'Accept-Language:  en-US,en;q=0.5',
                'Accept-Encoding:  gzip, deflate',
                'X-Requested-With:  XMLHttpRequest',
                'Cookie: ' . $session
            )
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        resetIpLog($server_id, $email);
        return $response = json_decode($response);
    }
    function changeInboundState($server_id, $uuid){
        global $connection;
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $panel_url = $server_info['panel_url'];
        $cookie = 'Cookie: session='.$server_info['cookie'];
        $serverType = $server_info['type'];

        $response = getJson($server_id);
        if(!$response) return null;
        $response = $response->obj;
        foreach($response as $row){
            $settings = json_decode($row->settings, true);
            $clients = $settings['clients'];
            if($clients[0]['id'] == $uuid || $clients[0]['password'] == $uuid) {
                $inbound_id = $row->id;
                $enable = $row->enable;
                break;
            }
        }

        if(!isset($settings['clients'][0]['subId']) && ($serverType == "sanaei" || $serverType == "alireza")) $settings['clients'][0]['subId'] = RandomString(16);
        if(!isset($settings['clients'][0]['enable']) && ($serverType == "sanaei" || $serverType == "alireza")) $settings['clients'][0]['enable'] = true;

        $editedClient = $settings['clients'][$client_key];
        $settings['clients'] = array_values($settings['clients']);
        $settings = json_encode($settings,488);

        $newEnable = $enable == true?false:true;

        $dataArr = array('up' => $row->up,'down' => $row->down,'total' => $row->total,'remark' => $row->remark,'enable' => $newEnable,
            'expiryTime' => $row->expiryTime, 'listen' => '','port' => $row->port,'protocol' => $row->protocol,'settings' => $settings,
            'streamSettings' => $row->streamSettings, 'sniffing' => $row->sniffing);


        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];

        $loginUrl = $panel_url . '/login';

        $postFields = array(
            "username" => $serverName,
            "password" => $serverPass
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


        $loginResponse = json_decode($body,true);
        if(!$loginResponse['success']){
            curl_close($curl);
            return $loginResponse;
        }

        if($serverType == "sanaei") $url = "$panel_url/panel/inbound/update/$inbound_id";
        else $url = "$panel_url/xui/inbound/update/$inbound_id";

        $phost = str_ireplace('https://','',str_ireplace('http://','',$panel_url));
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 15,      // timeout on connect
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $dataArr,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array(
                'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                'Accept:  application/json, text/plain, */*',
                'Accept-Language:  en-US,en;q=0.5',
                'Accept-Encoding:  gzip, deflate',
                'X-Requested-With:  XMLHttpRequest',
                'Cookie: ' . $session
            )
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response);
        return $response;

    }
    function renewInboundUuid($server_id, $uuid){
        global $connection;
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $panel_url = $server_info['panel_url'];
        $cookie = 'Cookie: session='.$server_info['cookie'];
        $serverType = $server_info['type'];

        $response = getJson($server_id);
        if(!$response) return null;
        $response = $response->obj;
        foreach($response as $row){
            $settings = json_decode($row->settings, true);
            $clients = $settings['clients'];
            if($clients[0]['id'] == $uuid || $clients[0]['password'] == $uuid) {
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

        $newUuid = generateRandomString(42,$protocol);
        if($protocol == "trojan") $settings['clients'][0]['password'] = $newUuid;
        else $settings['clients'][0]['id'] = $newUuid;
        if(!isset($settings['clients'][0]['subId']) && ($serverType == "sanaei" || $serverType == "alireza")) $settings['clients'][0]['subId'] = RandomString(16);
        if(!isset($settings['clients'][0]['enable']) && ($serverType == "sanaei" || $serverType == "alireza")) $settings['clients'][0]['enable'] = true;

        $editedClient = $settings['clients'][$client_key];
        $settings['clients'] = array_values($settings['clients']);
        $settings = json_encode($settings,488);


        $dataArr = array('up' => $row->up,'down' => $row->down,'total' => $row->total,'remark' => $row->remark,'enable' => 'true',
            'expiryTime' => $row->expiryTime, 'listen' => '','port' => $row->port,'protocol' => $row->protocol,'settings' => $settings,
            'streamSettings' => $row->streamSettings, 'sniffing' => $row->sniffing);


        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];

        $loginUrl = $panel_url . '/login';

        $postFields = array(
            "username" => $serverName,
            "password" => $serverPass
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

        $loginResponse = json_decode($body,true);
        if(!$loginResponse['success']){
            curl_close($curl);
            return $loginResponse;
        }

        if($serverType == "sanaei") $url = "$panel_url/panel/inbound/update/$inbound_id";
        else $url = "$panel_url/xui/inbound/update/$inbound_id";

        $phost = str_ireplace('https://','',str_ireplace('http://','',$panel_url));
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 15,      // timeout on connect
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $dataArr,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array(
                'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                'Accept:  application/json, text/plain, */*',
                'Accept-Language:  en-US,en;q=0.5',
                'Accept-Encoding:  gzip, deflate',
                'X-Requested-With:  XMLHttpRequest',
                'Cookie: ' . $session
            )
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response);
        $response->newUuid = $newUuid;
        return $response;

    }
    function addClient($server_id, $client_id, $protocol, $port, $expiryTime, $remark, $volume, $netType, $security = 'none', $bypass = false, $planId = null){
        global $connection;
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $panel_url = $server_info['panel_url'];
        $security = $server_info['security'];
        $tlsSettings = $server_info['tlsSettings'];
        $header_type = $server_info['header_type'];
        $request_header = $server_info['request_header'];
        $response_header = $server_info['response_header'];
        $sni = $server_info['sni'];
        $cookie = 'Cookie: session='.$server_info['cookie'];
        $serverType = $server_info['type'];
        $xtlsTitle = ($serverType == "sanaei" || $serverType == "alireza")?"XTLSSettings":"xtlsSettings";
        $reality = $server_info['reality'];

        if(!empty($sni) && ($serverType == "sanaei" || $serverType == "alireza")){
            $tlsSettings = json_decode($tlsSettings,true);
            $tlsSettings['serverName'] = $sni;
            $tlsSettings = json_encode($tlsSettings);
        }

        $volume = ($volume == 0) ? 0 : floor($volume * 1073741824);
        $headers = getNewHeaders($netType, $request_header, $response_header, $header_type);

        //---------------------------------------Trojan------------------------------------//
        if($protocol == 'trojan'){
            // protocol trojan
            if($security == 'none'){

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

                if($serverType == "sanaei" || $serverType == "alireza"){
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
                }else{
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
            }elseif($security == 'xtls' && $serverType != "sanaei" && $serverType != "alireza") {
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
                        "id": "'.$uniqid.'",
                        "alterId": 0
                        }
                    ],
                    "decryption": "none",
                    "fallbacks": []
                    }';
                    }

            else{
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
            if($serverType == "sanaei" || $serverType == "alireza"){
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
            }else{
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

        $streamSettings = ($netType == 'tcp') ? $tcpSettings : $wsSettings;
        if($netType == 'grpc'){
            $keyFileInfo = json_decode($tlsSettings,true);
            $certificateFile = "/root/cert.crt";
            $keyFile = '/root/private.key';

            if(isset($keyFileInfo['certificates'])){
                $certificateFile = $keyFileInfo['certificates'][0]['certificateFile'];
                $keyFile = $keyFileInfo['certificates'][0]['keyFile'];
            }

            if($security == 'tls') {
                $streamSettings = '{
                    "network": "grpc",
                    "security": "tls",
                    "tlsSettings": {
                        "serverName": "' .
                        (!empty($sni) && ($serverType == "sanaei" || $serverType == "alireza") ?  $sni: parse_url($panel_url, PHP_URL_HOST))
                        . '",
                        "certificates": [
                        {
                            "certificateFile": "' . $certificateFile . '",
                            "keyFile": "' . $keyFile . '"
                        }
                        ],
                        "alpn": []'
                        .'
                    },
                    "grpcSettings": {
                        "serviceName": ""
                    }
                        }';
                }else{
                $streamSettings = '{
            "network": "grpc",
            "security": "none",
            "grpcSettings": {
                "serviceName": "' . parse_url($panel_url, PHP_URL_HOST) . '"
            }
            }';
        }
        }

        // trojan
        $dataArr = array('up' => '0','down' => '0','total' => $volume,'remark' => $remark,'enable' => 'true','expiryTime' => $expiryTime,'listen' => '','port' => $port,'protocol' => $protocol,'settings' => $settings,'streamSettings' => $streamSettings,
                'sniffing' => '{
                "enabled": true,
                "destOverride": [
                "http",
                "tls"
                ]
                }');
        }else {
        //-------------------------------------- vmess vless -------------------------------//
            if($bypass == true){
                $wsSettings = '{
                    "network": "ws",
                    "security": "none",
                    "wsSettings": {
                        "path": "/wss' . $port . '",
                        "headers": {}
                    }
                    }';
                if($serverType == "sanaei" || $serverType == "alireza"){
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
                }else{
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
            }else{
                if($security == 'tls') {
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
                if($serverType == "sanaei" || $serverType == "alireza"){
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
                }else{
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
                }elseif($security == 'xtls' && $serverType != "sanaei" && $serverType != "alireza") {
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
                }else {
                    $tcpSettings = '{
                "network": "tcp",
                "security": "none",
                "tcpSettings": {
                    "header": '.$headers.'
                }
                }';
                    $wsSettings = '{
                "network": "ws",
                "security": "none",
                "wsSettings": {
                    "path": "/",
                    "headers": '.$headers.'
                }
                }';
                if($serverType == "sanaei" || $serverType == "alireza"){
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
                }else{
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
                }
            }


            if($protocol == 'vless'){
                if($serverType =="sanaei" || $serverType == "alireza"){
                    if($reality == "true"){
                        $stmt = $connection->prepare("SELECT * FROM `server_plans` WHERE `id`=?");
                        $stmt->bind_param("i", $planId);
                        $stmt->execute();
                        $file_detail = $stmt->get_result()->fetch_assoc();
                        $stmt->close();

                        $dest = !empty($file_detail['dest'])?$file_detail['dest']:"yahoo.com";
                        $serverNames = !empty($file_detail['serverNames'])?$file_detail['serverNames']:
                                    '[
                                        "yahoo.com",
                                        "www.yahoo.com"
                                    ]';
                        $spiderX = !empty($file_detail['spiderX'])?$file_detail['spiderX']:"";
                        $flow = isset($file_detail['flow']) && $file_detail['flow'] != "None" ? $file_detail['flow'] : "";



                        $certInfo = getNewCert($server_id)->obj;
                        $publicKey = $certInfo->publicKey;
                        $privateKey = $certInfo->privateKey;
                        $shortId = RandomString(8, "small");
                        $serverName = json_decode($tlsSettings,true)['serverName'];
                        if($netType == "grpc"){
                            $tcpSettings = '{
                            "network": "grpc",
                            "security": "reality",
                            "realitySettings": {
                                "show": false,
                                "xver": 0,
                                "dest": "' . $dest . '",
                                "serverNames":' . $serverNames . ',
                                "privateKey": "' . $privateKey . '",
                                "minClient": "",
                                "maxClient": "",
                                "maxTimediff": 0,
                                "shortIds": [
                                "' . $shortId .'"
                                ],
                                "settings": {
                                "publicKey": "' . $publicKey . '",
                                "fingerprint": "firefox",
                                "serverName": "' . $serverName . '",
                                "spiderX": "' . $spiderX . '"
                                }
                            },
                            "grpcSettings": {
                                "serviceName": "",
                                "multiMode": false
                            }
                            }';
                        }else{
                            $tcpSettings = '{
                            "network": "tcp",
                            "security": "reality",
                            "realitySettings": {
                                "show": false,
                                "xver": 0,
                                "dest": "' . $dest . '",
                                "serverNames":' . $serverNames . ',
                                "privateKey": "' . $privateKey . '",
                                "minClient": "",
                                "maxClient": "",
                                "maxTimediff": 0,
                                "shortIds": [
                                "' . $shortId .'"
                                ],
                                "settings": {
                                "publicKey": "' . $publicKey . '",
                                "fingerprint": "firefox",
                                "serverName": "' . $serverName . '",
                                "spiderX": "' . $spiderX . '"
                                }
                            },
                            "tcpSettings": {
                                "acceptProxyProtocol": false,
                                "header": '.$headers.'
                            }
                            }';
                        }
                        $settings = '{
                        "clients": [
                            {
                            "id": "'.$client_id.'",
                            "enable": true,
                            "email": "' . $remark. '",
                            "flow": "' . $flow .'",
                            "limitIp": 0,
                            "totalGB": 0,
                            "expiryTime": 0,
                            "subId": "' . RandomString(16) . '"
                            }
                        ],
                        "decryption": "none",
                        "fallbacks": []
                        }';
                        $netType = "tcp";
                    }else{
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
                    }
                }else{
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
            }

            $streamSettings = ($netType == 'tcp') ? $tcpSettings : $wsSettings;
            if($netType == 'grpc' && $reality != "true"){
                $keyFileInfo = json_decode($tlsSettings,true);
                $certificateFile = "/root/cert.crt";
                $keyFile = '/root/private.key';

                if(isset($keyFileInfo['certificates'])){
                    $certificateFile = $keyFileInfo['certificates'][0]['certificateFile'];
                    $keyFile = $keyFileInfo['certificates'][0]['keyFile'];
                }

                if($security == 'tls') {
                    $streamSettings = '{
                        "network": "grpc",
                        "security": "tls",
                        "tlsSettings": {
                            "serverName": "' . parse_url($panel_url, PHP_URL_HOST) . '",
                            "certificates": [
                            {
                                "certificateFile": "' . $certificateFile . '",
                                "keyFile": "' . $keyFile . '"
                            }
                            ],
                            "alpn": []
                        },
                        "grpcSettings": {
                            "serviceName": ""
                        }
                        }';
                }else{
                $streamSettings = '{
                    "network": "grpc",
                    "security": "none",
                    "grpcSettings": {
                        "serviceName": "' . parse_url($panel_url, PHP_URL_HOST) . '"
                    }
                    }';
                }
            }

            if(($serverType == "sanaei" || $serverType == "alireza") && $reality == "true"){
                $sniffing = '{
                "enabled": true,
                "destOverride": [
                    "http",
                    "tls",
                    "quic"
                ]
                }';
            }else{
                $sniffing = '{
                "enabled": true,
                "destOverride": [
                    "http",
                    "tls"
                ]
                }';
            }
            // vmess - vless
            $dataArr = array('up' => '0','down' => '0','total' => $volume, 'remark' => $remark,'enable' => 'true','expiryTime' => $expiryTime,'listen' => '','port' => $port,'protocol' => $protocol,'settings' => $settings,'streamSettings' => $streamSettings
            ,'sniffing' => $sniffing);
        }

        $phost = str_ireplace('https://','',str_ireplace('http://','',$panel_url));
        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];

        $loginUrl = $panel_url . '/login';

        $postFields = array(
            "username" => $serverName,
            "password" => $serverPass
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

        $loginResponse = json_decode($body,true);

        if(!$loginResponse['success']){
            curl_close($curl);
            return $loginResponse;
        }

        if($serverType == "sanaei") $url = "$panel_url/panel/inbound/add";
        else $url = "$panel_url/xui/inbound/add";

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
                'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                'Accept:  application/json, text/plain, */*',
                'Accept-Language:  en-US,en;q=0.5',
                'Accept-Encoding:  gzip, deflate',
                'X-Requested-With:  XMLHttpRequest',
                'Cookie: ' . $session
            )
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }
    function deleteClient($server_id, $inbound_id, $uuid, $delete = 0){
        global $connection;
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $panel_url = $server_info['panel_url'];
        $serverType = $server_info['type'];

        $response = getJson($server_id);
        if(!$response) return null;
        $response = $response->obj;
        $old_data = []; $oldclientstat = [];
        foreach($response as $row){
            if($row->id == $inbound_id) {
                $settings = json_decode($row->settings);
                $clients = $settings->clients;

                $clientsStates = $row->clientStats;
                foreach($clients as $key => $client){
                    if($client->id == $uuid || $client->password == $uuid){
                        $old_data = $client;
                        unset($clients[$key]);
                        $email = $client->email;
                        $emails = array_column($clientsStates,'email');
                        $emailKey = array_search($email,$emails);

                        $total = $clientsStates[$emailKey]->total;
                        $up = $clientsStates[$emailKey]->up;
                        $enable = $clientsStates[$emailKey]->enable;
                        $down = $clientsStates[$emailKey]->down;
                        break;
                    }
                }
            }
        }
        $settings->clients = $clients;
        $settings = json_encode($settings);

        if($delete == 1){
            $dataArr = array('up' => $row->up,'down' => $row->down,'total' => $row->total,'remark' => $row->remark,'enable' => 'true',
            'expiryTime' => $row->expiryTime, 'listen' => '','port' => $row->port,'protocol' => $row->protocol,'settings' => $settings,
            'streamSettings' => $row->streamSettings, 'sniffing' => $row->sniffing);

            $serverName = $server_info['username'];
            $serverPass = $server_info['password'];

            $loginUrl = $panel_url . '/login';

            $postFields = array(
                "username" => $serverName,
                "password" => $serverPass
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

            $loginResponse = json_decode($body,true);

            if(!$loginResponse['success']){
                curl_close($curl);
                return $loginResponse;
            }

            if($serverType == "sanaei" || $serverType == "alireza"){
                if($serverType == "sanaei") $url = "$panel_url/panel/inbound/" . $inbound_id . "/delClient/" . rawurlencode($uuid);
                elseif($serverType == "alireza") $url = "$panel_url/xui/inbound/" . $inbound_id . "/delClient/" . rawurlencode($uuid);

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
                        'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                        'Accept:  application/json, text/plain, */*',
                        'Accept-Language:  en-US,en;q=0.5',
                        'Accept-Encoding:  gzip, deflate',
                        'X-Requested-With:  XMLHttpRequest',
                        'Cookie: ' . $session
                    )
                ));
            }else{
                curl_setopt_array($curl, array(
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
                    CURLOPT_HTTPHEADER => array(
                        'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                        'Accept:  application/json, text/plain, */*',
                        'Accept-Language:  en-US,en;q=0.5',
                        'Accept-Encoding:  gzip, deflate',
                        'X-Requested-With:  XMLHttpRequest',
                        'Cookie: ' . $session
                    )
                ));
            }

            $response = curl_exec($curl);
            curl_close($curl);
        }
        return ['id' => $old_data->id,'expiryTime' => $old_data->expiryTime, 'limitIp' => $old_data->limitIp, 'flow' => $old_data->flow, 'total' => $total, 'up' => $up, 'down' => $down,];

    }













    // FROM HOLD PROJECT

    function RandomString($count = 9, $type = "all") {
        if($type == "all") $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789';
        elseif($type == "small") $characters = 'abcdef123456789';
        elseif($type == "domain") $characters = 'abcdefghijklmnopqrstuvwxyz';

        $randstring = null;
        for ($i = 0; $i < $count; $i++) {
            $randstring .= $characters[
                rand(0, strlen($characters)-1)
            ];
        }
        return $randstring;
    }
    function generateUID(){
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

        return sprintf('%08s-%04s-%04x-%04x-%012s', $time_low, $time_mid, $time_hi_and_version, $clock_seq_hi_and_reserved, $node);
    }
    function checkStep($table){
        global $connection;

        if($table == "server_plans") $stmt = $connection->prepare("SELECT * FROM `server_plans` WHERE `active` = 0");
        if($table == "server_categories") $stmt = $connection->prepare("SELECT * FROM `server_categories` WHERE `active` = 0");

        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res['step'];
    }

    function generateRandomString($length, $protocol) {
        return ($protocol == 'trojan') ? substr(md5(time()),5,15) : generateUID();
    }

    function sumerize($amount){
        $gb = $amount / (1024 * 1024 * 1024);
        if($gb > 1){
        return round($gb,2) . " GB";
        }
        else{
            $gb *= 1024;
            return round($gb,2) . " GB";
        }

    }

    function sumerize2($amount){
        $gb = $amount / (1024 * 1024 * 1024);
        return round($gb,2);
    }

    function changeClientState($server_id, $inbound_id, $uuid){
        global $connection;
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $panel_url = $server_info['panel_url'];
        $cookie = 'Cookie: session='.$server_info['cookie'];
        $serverType = $server_info['type'];

        $response = getJson($server_id);
        if(!$response) return null;
        $response = $response->obj;
        $client_key = -1;
        foreach($response as $row){
            if($row->id == $inbound_id) {
                $settings = json_decode($row->settings, true);
                $clients = $settings['clients'];

                foreach($clients as $key => $client){
                    if($client['id'] == $uuid || $client['password'] == $uuid){
                        $client_key = $key;
                        $enable = $client['enable'];
                        break;
                    }
                }
            }
        }
        if($client_key == -1) return null;

        if(!isset($settings['clients'][$client_key]['subId']) && ($serverType == "sanaei" || $serverType == "alireza")) $settings['clients'][$client_key]['subId'] = RandomString(16);
        $settings['clients'][$client_key]['enable'] = $enable == true?false:true;

        $editedClient = $settings['clients'][$client_key];
        $settings['clients'] = array_values($settings['clients']);
        $settings = json_encode($settings,488);
        $dataArr = array('up' => $row->up,'down' => $row->down,'total' => $row->total,'remark' => $row->remark,'enable' => 'true',
            'expiryTime' => $row->expiryTime, 'listen' => '','port' => $row->port,'protocol' => $row->protocol,'settings' => $settings,
            'streamSettings' => $row->streamSettings, 'sniffing' => $row->sniffing);

        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];

        $loginUrl = $panel_url . '/login';

        $postFields = array(
            "username" => $serverName,
            "password" => $serverPass
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

        $loginResponse = json_decode($body,true);
        if(!$loginResponse['success']){
            curl_close($curl);
            return $loginResponse;
        }

        if($serverType == "sanaei" || $serverType == "alireza"){

            $newSetting = array();
            $newSetting['clients'][] = $editedClient;
            $newSetting = json_encode($newSetting);

            $dataArr = array(
                "id"=>$inbound_id,
                "settings" => $newSetting
                );

            if($serverType == "sanaei") $url = "$panel_url/panel/inbound/updateClient/" . rawurlencode($uuid);
            else $url = "$panel_url/xui/inbound/updateClient/" . rawurlencode($uuid);

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
                    'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                    'Accept:  application/json, text/plain, */*',
                    'Accept-Language:  en-US,en;q=0.5',
                    'Accept-Encoding:  gzip, deflate',
                    'X-Requested-With:  XMLHttpRequest',
                    'Cookie: ' . $session
                )
            ));
        }else{
            curl_setopt_array($curl, array(
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
                CURLOPT_HTTPHEADER => array(
                    'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                    'Accept:  application/json, text/plain, */*',
                    'Accept-Language:  en-US,en;q=0.5',
                    'Accept-Encoding:  gzip, deflate',
                    'X-Requested-With:  XMLHttpRequest',
                    'Cookie: ' . $session
                )
            ));
        }

        $response = curl_exec($curl);
        $response = json_decode($response);
        curl_close($curl);
        return $response;

    }
    function renewClientUuid($server_id, $inbound_id, $uuid){
        global $connection;
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $panel_url = $server_info['panel_url'];
        $cookie = 'Cookie: session='.$server_info['cookie'];
        $serverType = $server_info['type'];

        $response = getJson($server_id);
        if(!$response) return null;
        $response = $response->obj;
        $client_key = -1;
        foreach($response as $row){
            if($row->id == $inbound_id) {
                $settings = json_decode($row->settings, true);
                $clients = $settings['clients'];

                foreach($clients as $key => $client){
                    if($client['id'] == $uuid || $client['password'] == $uuid){
                        $protocol = $row->protocol;
                        $client_key = $key;
                        break;
                    }
                }
            }
        }
        if($client_key == -1) return null;

        $newUuid = generateRandomString(42,$protocol);
        if($protocol == "trojan") $settings['clients'][$client_key]['password'] = $newUuid;
        else $settings['clients'][$client_key]['id'] = $newUuid;
        if(!isset($settings['clients'][$client_key]['subId']) && ($serverType == "sanaei" || $serverType == "alireza")) $settings['clients'][$client_key]['subId'] = RandomString(16);
        if(!isset($settings['clients'][$client_key]['enable']) && ($serverType == "sanaei" || $serverType == "alireza")) $settings['clients'][$client_key]['enable'] = true;

        $editedClient = $settings['clients'][$client_key];
        $settings['clients'] = array_values($settings['clients']);
        $settings = json_encode($settings,488);
        $dataArr = array('up' => $row->up,'down' => $row->down,'total' => $row->total,'remark' => $row->remark,'enable' => 'true',
            'expiryTime' => $row->expiryTime, 'listen' => '','port' => $row->port,'protocol' => $row->protocol,'settings' => $settings,
            'streamSettings' => $row->streamSettings, 'sniffing' => $row->sniffing);

        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];

        $loginUrl = $panel_url . '/login';

        $postFields = array(
            "username" => $serverName,
            "password" => $serverPass
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

        $loginResponse = json_decode($body,true);
        if(!$loginResponse['success']){
            curl_close($curl);
            return $loginResponse;
        }

        if($serverType == "sanaei" || $serverType == "alireza"){

            $newSetting = array();
            $newSetting['clients'][] = $editedClient;
            $newSetting = json_encode($newSetting);

            $dataArr = array(
                "id"=>$inbound_id,
                "settings" => $newSetting
                );

            if($serverType == "sanaei") $url = "$panel_url/panel/inbound/updateClient/" . rawurlencode($uuid);
            else $url = "$panel_url/xui/inbound/updateClient/" . rawurlencode($uuid);

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
                    'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                    'Accept:  application/json, text/plain, */*',
                    'Accept-Language:  en-US,en;q=0.5',
                    'Accept-Encoding:  gzip, deflate',
                    'X-Requested-With:  XMLHttpRequest',
                    'Cookie: ' . $session
                )
            ));
        }else{
            curl_setopt_array($curl, array(
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
                CURLOPT_HTTPHEADER => array(
                    'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                    'Accept:  application/json, text/plain, */*',
                    'Accept-Language:  en-US,en;q=0.5',
                    'Accept-Encoding:  gzip, deflate',
                    'X-Requested-With:  XMLHttpRequest',
                    'Cookie: ' . $session
                )
            ));
        }

        $response = curl_exec($curl);
        $response = json_decode($response);
        $response->newUuid = $newUuid;

        curl_close($curl);
        return $response;

    }
    function editClientRemark($server_id, $inbound_id, $uuid, $newRemark){
        global $connection;
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $panel_url = $server_info['panel_url'];
        $cookie = 'Cookie: session='.$server_info['cookie'];
        $serverType = $server_info['type'];

        $response = getJson($server_id);
        if(!$response) return null;
        $response = $response->obj;
        $client_key = 0;
        foreach($response as $row){
            if($row->id == $inbound_id) {
                $settings = json_decode($row->settings, true);
                $clients = $settings['clients'];

                $clientsStates = $row->clientStats;
                foreach($clients as $key => $client){
                    if($client['id'] == $uuid || $client['password'] == $uuid){
                        $client_key = $key;
                        $email = $client['email'];
                        $emails = array_column($clientsStates,'email');
                        $emailKey = array_search($email,$emails);

                        $total = $clientsStates[$emailKey]->total;
                        $up = $clientsStates[$emailKey]->up;
                        $enable = $clientsStates[$emailKey]->enable;
                        $down = $clientsStates[$emailKey]->down;
                        break;
                    }
                }
            }
        }
        $settings['clients'][$client_key]['email'] = $newRemark;
        if(!isset($settings['clients'][$client_key]['subId']) && ($serverType == "sanaei" || $serverType == "alireza")) $settings['clients'][$client_key]['subId'] = RandomString(16);
        if(!isset($settings['clients'][$client_key]['enable']) && ($serverType == "sanaei" || $serverType == "alireza")) $settings['clients'][$client_key]['enable'] = true;

        $editedClient = $settings['clients'][$client_key];
        $settings['clients'] = array_values($settings['clients']);
        $settings = json_encode($settings);
        $dataArr = array('up' => $row->up,'down' => $row->down,'total' => $row->total,'remark' => $row->remark,'enable' => 'true',
            'expiryTime' => $row->expiryTime, 'listen' => '','port' => $row->port,'protocol' => $row->protocol,'settings' => $settings,
            'streamSettings' => $row->streamSettings, 'sniffing' => $row->sniffing);

        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];

        $loginUrl = $panel_url . '/login';

        $postFields = array(
            "username" => $serverName,
            "password" => $serverPass
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

        $loginResponse = json_decode($body,true);
        if(!$loginResponse['success']){
            curl_close($curl);
            return $loginResponse;
        }

        if($serverType == "sanaei" || $serverType == "alireza"){

            $newSetting = array();
            $newSetting['clients'][] = $editedClient;
            $newSetting = json_encode($newSetting);

            $dataArr = array(
                "id"=>$inbound_id,
                "settings" => $newSetting
                );

            if($serverType == "sanaei") $url = "$panel_url/panel/inbound/updateClient/" . rawurlencode($uuid);
            else $url = "$panel_url/xui/inbound/updateClient/" . rawurlencode($uuid);

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
                    'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                    'Accept:  application/json, text/plain, */*',
                    'Accept-Language:  en-US,en;q=0.5',
                    'Accept-Encoding:  gzip, deflate',
                    'X-Requested-With:  XMLHttpRequest',
                    'Cookie: ' . $session
                )
            ));
        }else{
            curl_setopt_array($curl, array(
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
                CURLOPT_HTTPHEADER => array(
                    'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                    'Accept:  application/json, text/plain, */*',
                    'Accept-Language:  en-US,en;q=0.5',
                    'Accept-Encoding:  gzip, deflate',
                    'X-Requested-With:  XMLHttpRequest',
                    'Cookie: ' . $session
                )
            ));
        }

        $response = curl_exec($curl);
        curl_close($curl);
        return $response = json_decode($response);

    }
    function editClientTraffic($server_id, $inbound_id, $uuid, $volume, $days, $editType = null){
        global $connection;
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $panel_url = $server_info['panel_url'];
        $cookie = 'Cookie: session='.$server_info['cookie'];
        $serverType = $server_info['type'];

        $response = getJson($server_id);
        if(!$response) return null;
        $response = $response->obj;
        $client_key = 0;
        foreach($response as $row){
            if($row->id == $inbound_id) {
                $settings = json_decode($row->settings, true);
                $clients = $settings['clients'];

                $clientsStates = $row->clientStats;
                foreach($clients as $key => $client){
                    if($client['id'] == $uuid || $client['password'] == $uuid){
                        $client_key = $key;
                        $email = $client['email'];
                        $emails = array_column($clientsStates,'email');
                        $emailKey = array_search($email,$emails);

                        $total = $clientsStates[$emailKey]->total;
                        $up = $clientsStates[$emailKey]->up;
                        $enable = $clientsStates[$emailKey]->enable;
                        $down = $clientsStates[$emailKey]->down;
                        break;
                    }
                }
            }
        }
        if($volume != 0){
            $client_total = $settings['clients'][$client_key]['totalGB'];// - $up - $down;
            $extend_volume = floor($volume * 1073741824);
            $volume = ($client_total > 0) ? $client_total + $extend_volume : $extend_volume;
            if($editType == "renew"){
                $volume = $extend_volume;
                if($serverType == "sanaei" || $serverType == "alireza") resetClientTraffic($server_id, $email, $inbound_id);
                else resetClientTraffic($server_id, $email);
            }
            $settings['clients'][$client_key]['totalGB'] = $volume;
            if(!isset($settings['clients'][$client_key]['subId']) && ($serverType == "sanaei" || $serverType == "alireza")) $settings['clients'][$client_key]['subId'] = RandomString(16);
            if(!isset($settings['clients'][$client_key]['enable']) && ($serverType == "sanaei" || $serverType == "alireza")) $settings['clients'][$client_key]['enable'] = true;
        }

        if($days != 0){
            $expiryTime = $settings['clients'][$client_key]['expiryTime'];
            $now_microdate = floor(microtime(true) * 1000);
            $extend_date = (864000 * $days * 100);
            if($editType == "renew") $expire_microdate = $now_microdate + $extend_date;
            else $expire_microdate = ($now_microdate > $expiryTime) ? $now_microdate + $extend_date : $expiryTime + $extend_date;
            $settings['clients'][$client_key]['expiryTime'] = $expire_microdate;
            if(!isset($settings['clients'][$client_key]['subId']) && ($serverType == "sanaei" || $serverType == "alireza")) $settings['clients'][$client_key]['subId'] = RandomString(16);
            if(!isset($settings['clients'][$client_key]['enable']) && ($serverType == "sanaei" || $serverType == "alireza")) $settings['clients'][$client_key]['enable'] = true;
        }
        $editedClient = $settings['clients'][$client_key];
        $settings['clients'] = array_values($settings['clients']);
        $settings = json_encode($settings);
        $dataArr = array('up' => $row->up,'down' => $row->down,'total' => $row->total,'remark' => $row->remark,'enable' => 'true',
            'expiryTime' => $row->expiryTime, 'listen' => '','port' => $row->port,'protocol' => $row->protocol,'settings' => $settings,
            'streamSettings' => $row->streamSettings, 'sniffing' => $row->sniffing);

        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];

        $loginUrl = $panel_url . '/login';

        $postFields = array(
            "username" => $serverName,
            "password" => $serverPass
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

        $loginResponse = json_decode($body,true);
        if(!$loginResponse['success']){
            curl_close($curl);
            return $loginResponse;
        }

        if($serverType == "sanaei" || $serverType == "alireza"){

            $newSetting = array();
            $newSetting['clients'][] = $editedClient;
            $newSetting = json_encode($newSetting);

            $dataArr = array(
                "id"=>$inbound_id,
                "settings" => $newSetting
                );

            if($serverType == "sanaei") $url = "$panel_url/panel/inbound/updateClient/" . rawurlencode($uuid);
            else $url = "$panel_url/xui/inbound/updateClient/" . rawurlencode($uuid);

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
                    'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                    'Accept:  application/json, text/plain, */*',
                    'Accept-Language:  en-US,en;q=0.5',
                    'Accept-Encoding:  gzip, deflate',
                    'X-Requested-With:  XMLHttpRequest',
                    'Cookie: ' . $session
                )
            ));
        }else{
            curl_setopt_array($curl, array(
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
                CURLOPT_HTTPHEADER => array(
                    'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                    'Accept:  application/json, text/plain, */*',
                    'Accept-Language:  en-US,en;q=0.5',
                    'Accept-Encoding:  gzip, deflate',
                    'X-Requested-With:  XMLHttpRequest',
                    'Cookie: ' . $session
                )
            ));
        }

        $response = curl_exec($curl);
        curl_close($curl);
        resetIpLog($server_id, $email);
        return $response = json_decode($response);

    }

    function resetIpLog($server_id, $remark){
        global $connection;
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $panel_url = $server_info['panel_url'];
        $cookie = 'Cookie: session='.$server_info['cookie'];
        $serverType = $server_info['type'];


        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];

        $loginUrl = $panel_url . '/login';

        $postFields = array(
            "username" => $serverName,
            "password" => $serverPass
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

        $loginResponse = json_decode($body,true);
        if(!$loginResponse['success']){
            curl_close($curl);
            return $loginResponse;
        }

        if($serverType == "sanaei") $url = $panel_url. "/panel/inbound/clearClientIps/" . urlencode($remark);
        else $url = $panel_url. "/xui/inbound/clearClientIps/" . urlencode($remark);

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
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array(
                'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                'Accept:  application/json, text/plain, */*',
                'Accept-Language:  en-US,en;q=0.5',
                'Accept-Encoding:  gzip, deflate',
                'X-Requested-With:  XMLHttpRequest',
                'Cookie: ' . $session
            )
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response = json_decode($response);
    }
    function resetClientTraffic($server_id, $remark, $inboundId = null){
        global $connection;
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $panel_url = $server_info['panel_url'];
        $cookie = 'Cookie: session='.$server_info['cookie'];
        $serverType = $server_info['type'];


        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];

        $loginUrl = $panel_url . '/login';

        $postFields = array(
            "username" => $serverName,
            "password" => $serverPass
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

        $loginResponse = json_decode($body,true);
        if(!$loginResponse['success']){
            curl_close($curl);
            return $loginResponse;
        }
        if($serverType == "sanaei") $url = "$panel_url/panel/inbound/$inboundId/resetClientTraffic/" . rawurlencode($remark);
        elseif($inboundId == null) $url = "$panel_url/xui/inbound/resetClientTraffic/" . rawurlencode($remark);
        else $url = "$panel_url/xui/inbound/$inboundId/resetClientTraffic/" . rawurlencode($remark);
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
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array(
                'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                'Accept:  application/json, text/plain, */*',
                'Accept-Language:  en-US,en;q=0.5',
                'Accept-Encoding:  gzip, deflate',
                'X-Requested-With:  XMLHttpRequest',
                'Cookie: ' . $session
            )
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response = json_decode($response);
    }

    function getNewHeaders($netType, $request_header, $response_header, $type){
        global $connection;
        $input = explode(':', $request_header);
        $key = $input[0];
        $value = $input[1];

        $input = explode(':', $response_header);
        $reskey = $input[0];
        $resvalue = $input[1];

        $headers = '';
        if( $netType == 'tcp'){
            if($type == 'none') {
                $headers = '{
                "type": "none"
                }';
            }else {
                $headers = '{
                "type": "http",
                "request": {
                    "method": "GET",
                    "path": [
                    "/"
                    ],
                    "headers": {
                    "'.$key.'": [
                        "'.$value.'"
                    ]
                    }
                },
                "response": {
                    "version": "1.1",
                    "status": "200",
                    "reason": "OK",
                    "headers": {
                    "'.$reskey.'": [
                        "'.$resvalue.'"
                    ]
                    }
                }
                }';
            }

        }elseif( $netType == 'ws'){
            if($type == 'none') {
                $headers = '{}';
            }else {
                $headers = '{
                "'.$key.'": "'.$value.'"
                }';
            }
        }
        return $headers;

    }

    function getConnectionLink($server_id, $uniqid, $protocol, $remark, $port, $netType, $inbound_id = 0, $bypass = false, $customPath = false, $customPort = 0, $customSni = null){
        global $connection;
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $panel_url = $server_info['panel_url'];
        $server_ip = $server_info['ip'];
        $sni = $server_info['sni'];
        $header_type = $server_info['header_type'];
        $request_header = $server_info['request_header'];
        $response_header = $server_info['response_header'];
        $cookie = 'Cookie: session='.$server_info['cookie'];
        $serverType = $server_info['type'];
        preg_match("/^Host:(.*)/i",$request_header,$hostMatch);

        $panel_url = str_ireplace('http://','',$panel_url);
        $panel_url = str_ireplace('https://','',$panel_url);
        $panel_url = strtok($panel_url,":");
        if($server_ip == '') $server_ip = $panel_url;

        $response = getJson($server_id)->obj;
        foreach($response as $row){
            if($inbound_id == 0){
                $clients = json_decode($row->settings)->clients;
                if($clients[0]->id == $uniqid || $clients[0]->password == $uniqid) {
                    if($serverType == "sanaei" || $serverType == "alireza"){
                        $settings = json_decode($row->settings,true);
                        $email = $settings['clients'][0]['email'];
                        // $remark = (!empty($row->remark)?($row->remark . "-"):"") . $email;
                        $remark = $row->remark;
                    }
                    $tlsStatus = json_decode($row->streamSettings)->security;
                    $tlsSetting = json_decode($row->streamSettings)->tlsSettings;
                    $xtlsSetting = json_decode($row->streamSettings)->xtlsSettings;
                    $netType = json_decode($row->streamSettings)->network;
                    if($netType == 'tcp') {
                        $header_type = json_decode($row->streamSettings)->tcpSettings->header->type;
                        $path = json_decode($row->streamSettings)->tcpSettings->header->request->path[0];
                        $host = json_decode($row->streamSettings)->tcpSettings->header->request->headers->Host[0];

                        if($tlsStatus == "reality"){
                            $realitySettings = json_decode($row->streamSettings)->realitySettings;
                            $fp = $realitySettings->settings->fingerprint;
                            $spiderX = $realitySettings->settings->spiderX;
                            $pbk = $realitySettings->settings->publicKey;
                            $sni = $realitySettings->serverNames[0];
                            $flow = $settings['clients'][0]['flow'];
                            $sid = $realitySettings->shortIds[0];
                        }
                    }
                    if($netType == 'ws') {
                        $header_type = json_decode($row->streamSettings)->wsSettings->header->type;
                        $path = json_decode($row->streamSettings)->wsSettings->path;
                        $host = json_decode($row->streamSettings)->wsSettings->headers->Host;
                    }
                    if($header_type == 'http' && empty($host)){
                        $request_header = explode(':', $request_header);
                        $host = $request_header[1];
                    }
                    if($netType == 'grpc') {
                        if($tlsStatus == 'tls'){
                            $alpn = $tlsSetting->certificates->alpn;
                            if(isset($tlsSetting->serverName)) $sni = $tlsSetting->serverName;
                            if(isset($tlsSetting->settings->serverName)) $sni = $tlsSetting->settings->serverName;
                        }
                        elseif($tlsStatus == "reality"){
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
                    if($tlsStatus == 'tls'){
                        $serverName = $tlsSetting->serverName;
                        if(isset($tlsSetting->serverName)) $sni = $tlsSetting->serverName;
                        if(isset($tlsSetting->settings->serverName)) $sni = $tlsSetting->settings->serverName;
                    }
                    if($tlsStatus == "xtls"){
                        $serverName = $xtlsSetting->serverName;
                        $alpn = $xtlsSetting->alpn;
                        if(isset($xtlsSetting->serverName)) $sni = $xtlsSetting->serverName;
                        if(isset($xtlsSetting->settings->serverName)) $sni = $xtlsSetting->settings->serverName;
                    }
                    if($netType == 'kcp'){
                        $kcpSettings = json_decode($row->streamSettings)->kcpSettings;
                        $kcpType = $kcpSettings->header->type;
                        $kcpSeed = $kcpSettings->seed;
                    }

                    break;
                }
            }else{
                if($row->id == $inbound_id) {
                    if($serverType == "sanaei" || $serverType == "alireza"){
                        $settings = json_decode($row->settings);
                        $clients = $settings->clients;
                        foreach($clients as $key => $client){
                            if($client->id == $uniqid || $client->password == $uniqid){
                                $flow = $client->flow;
                                break;
                            }
                        }
                        // $remark = (!empty($row->remark)?($row->remark . "-"):"") . $remark;
                        $remark = $remark;
                    }

                    $port = $row->port;
                    $tlsStatus = json_decode($row->streamSettings)->security;
                    $tlsSetting = json_decode($row->streamSettings)->tlsSettings;
                    $xtlsSetting = json_decode($row->streamSettings)->xtlsSettings;
                    $netType = json_decode($row->streamSettings)->network;
                    if($netType == 'tcp') {
                        $header_type = json_decode($row->streamSettings)->tcpSettings->header->type;
                        $path = json_decode($row->streamSettings)->tcpSettings->header->request->path[0];
                        $host = json_decode($row->streamSettings)->tcpSettings->header->request->headers->Host[0];

                        if($tlsStatus == "reality"){
                            $realitySettings = json_decode($row->streamSettings)->realitySettings;
                            $fp = $realitySettings->settings->fingerprint;
                            $spiderX = $realitySettings->settings->spiderX;
                            $pbk = $realitySettings->settings->publicKey;
                            $sni = $realitySettings->serverNames[0];
                            $sid = $realitySettings->shortIds[0];
                        }
                    }elseif($netType == 'ws') {
                        $header_type = json_decode($row->streamSettings)->wsSettings->header->type;
                        $path = json_decode($row->streamSettings)->wsSettings->path;
                        $host = json_decode($row->streamSettings)->wsSettings->headers->Host;
                    }elseif($netType == 'grpc') {
                        if($tlsStatus == 'tls'){
                            $alpn = $tlsSetting->alpn;
                            if(isset($tlsSetting->serverName)) $sni = $tlsSetting->serverName;
                            if(isset($tlsSetting->settings->serverName)) $sni = $tlsSetting->settings->serverName;
                        }
                        elseif($tlsStatus == "reality"){
                            $realitySettings = json_decode($row->streamSettings)->realitySettings;
                            $fp = $realitySettings->settings->fingerprint;
                            $spiderX = $realitySettings->settings->spiderX;
                            $pbk = $realitySettings->settings->publicKey;
                            $sni = $realitySettings->serverNames[0];
                            $sid = $realitySettings->shortIds[0];
                        }
                        $grpcSecurity = json_decode($row->streamSettings)->security;
                        $serviceName = json_decode($row->streamSettings)->grpcSettings->serviceName;
                    }elseif($netType == 'kcp'){
                        $kcpSettings = json_decode($row->streamSettings)->kcpSettings;
                        $kcpType = $kcpSettings->header->type;
                        $kcpSeed = $kcpSettings->seed;
                    }
                    if($tlsStatus == 'tls'){
                        $serverName = $tlsSetting->serverName;
                        if(isset($tlsSetting->serverName)) $sni = $tlsSetting->serverName;
                        if(isset($tlsSetting->settings->serverName)) $sni = $tlsSetting->settings->serverName;
                    }
                    if($tlsStatus == "xtls"){
                        $serverName = $xtlsSetting->serverName;
                        $alpn = $xtlsSetting->alpn;
                        if(isset($xtlsSetting->serverName)) $sni = $xtlsSetting->serverName;
                        if(isset($xtlsSetting->settings->serverName)) $sni = $xtlsSetting->settings->serverName;
                    }

                    break;
                }
            }


        }
        $protocol = strtolower($protocol);
        $serverIp = explode("\n",$server_ip);
        $outputLink = array();
        foreach($serverIp as $server_ip){
            $server_ip = str_replace("\r","",($server_ip));
            if($inbound_id == 0) {
                if($protocol == 'vless'){
                    if($bypass == true){
                        if(empty($host) && isset($hostMatch[1])) $host = $hostMatch[1];

                        if(!empty($host)){
                            $parseAdd = parse_url($host);
                            $parseAdd = $parseAdd['host']??$parseAdd['path'];
                            $explodeAdd = explode(".", $parseAdd);
                            $subDomain = RandomString(4,"domain");
                            if($customSni != null) $sni = $customSni;
                            else{
                                if(count($explodeAdd) >= 3) $sni = $uniqid . "." . $explodeAdd[1] . "." . $explodeAdd[2];
                                else $sni = $uniqid . "." . $host;
                            }
                        }
                    }
                    $psting = '';
                    if(($header_type == 'http' && $bypass != true && $netType != "grpc") || ($netType == "ws" && !empty($host) && $bypass != true)) $psting .= "&path=/&host=$host";;
                    if($netType == 'tcp' and $header_type == 'http') $psting .= '&headerType=http';
                    if(strlen($sni) > 1 && $tlsStatus != "reality") $psting .= "&sni=$sni";
                    if(strlen($serverName)>1 && $tlsStatus=="xtls") $server_ip = $serverName;
                    if($tlsStatus == "xtls" && $netType == "tcp") $psting .= "&flow=xtls-rprx-direct";
                    if($tlsStatus=="reality") $psting .= "&fp=$fp&pbk=$pbk&sni=$sni" . ($flow != ""?"&flow=$flow":"") . "&sid=$sid&spx=$spiderX";
                    if($bypass == true) $psting .= "&path=" . rawurlencode($path . ($customPath == true?"?ed=2048":"")) . "&encryption=none&host=$host";
                    $outputlink = "$protocol://$uniqid@$server_ip:" . ($bypass == true?($customPort!="0"?$customPort:"443"):$port) . "?type=$netType&security=" . ($bypass==true?"tls":$tlsStatus) . "{$psting}#$remark";
                    if($netType == 'grpc' && $tlsStatus != "reality"){
                        if($tlsStatus == 'tls'){
                            $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus&serviceName=$serviceName&sni=$sni#$remark";
                        }else{
                            $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus&serviceName=$serviceName#$remark";
                        }

                    }
                }

                if($protocol == 'trojan'){
                    $psting = '';
                    if($header_type == 'http') $psting .= "&path=/&host=$host";
                    if($netType == 'tcp' and $header_type == 'http') $psting .= '&headerType=http';
                    if(strlen($sni) > 1) $psting .= "&sni=$sni";
                    $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus{$psting}#$remark";

                    if($netType == 'grpc'){
                        if($tlsStatus == 'tls'){
                            $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus&serviceName=$serviceName&sni=$sni#$remark";
                        }else{
                            $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus&serviceName=$serviceName#$remark";
                        }

                    }
                }elseif($protocol == 'vmess'){
                    $vmessArr = [
                        "v"=> "2",
                        "ps"=> $remark,
                        "add"=> $server_ip,
                        "port"=> $bypass == true?($customPort!=0?$customPort:443):$port,
                        "id"=> $uniqid,
                        "aid"=> 0,
                        "net"=> $netType,
                        "type"=> $kcpType ? $kcpType : "none",
                        "host"=> ($bypass == true && empty($host))? $server_ip:(is_null($host) ? '' : $host),
                        "path"=> ($bypass == true)?($path . ($customPath == true?"?ed=2048":"")):((is_null($path) and $path != '') ? '/' : (is_null($path) ? '' : $path)),
                        "tls"=> $bypass == true?"tls":((is_null($tlsStatus)) ? 'none' : $tlsStatus)
                    ];

                    if($bypass == true){
                        if(empty($host) && isset($hostMatch[1])) $host = $hostMatch[1];

                        if(!empty($host)){
                            $parseAdd = parse_url($host);
                            $parseAdd = $parseAdd['host']??$parseAdd['path'];
                            $explodeAdd = explode(".", $parseAdd);
                            $subDomain = RandomString(4,"domain");
                            if($customSni != null) $sni = $customSni;
                            else{
                                if(count($explodeAdd) >= 3) $sni = $uniqid . "." . $explodeAdd[1] . "." . $explodeAdd[2];
                                else $sni = $uniqid . "." . $host;
                            }

                            $vmessArr['alpn'] = 'http/1.1';
                        }
                    }
                    if($header_type == 'http' && $bypass != true){
                        $vmessArr['path'] = "/";
                        $vmessArr['type'] = $header_type;
                        $vmessArr['host'] = $host;
                    }
                    if($netType == 'grpc'){
                        if(!is_null($alpn) and json_encode($alpn) != '[]' and $alpn != '') $vmessArr['alpn'] = $alpn;
                        if(strlen($serviceName) > 1) $vmessArr['path'] = $serviceName;
                        $vmessArr['type'] = $grpcSecurity;
                        $vmessArr['scy'] = 'auto';
                    }
                    if($netType == 'kcp'){
                        $vmessArr['path'] = $kcpSeed ? $kcpSeed : $vmessArr['path'];
                    }
                    if(strlen($sni) > 1) $vmessArr['sni'] = $sni;
                    $urldata = base64_encode(json_encode($vmessArr,JSON_UNESCAPED_SLASHES,JSON_PRETTY_PRINT));
                    $outputlink = "vmess://$urldata";
                }
            }else {
                if($protocol == 'vless'){
                    if($bypass == true){
                        if(empty($host) && isset($hostMatch[1])) $host = $hostMatch[1];

                        if(!empty($host)){
                            $parseAdd = parse_url($host);
                            $parseAdd = $parseAdd['host']??$parseAdd['path'];
                            $explodeAdd = explode(".", $parseAdd);
                            $subDomain = RandomString(4,"domain");
                            if($customSni != null) $sni = $customSni;
                            else{
                                if(count($explodeAdd) >= 3) $sni = $uniqid . "." . $explodeAdd[1] . "." . $explodeAdd[2];
                                else $sni = $uniqid . "." .$host;
                            }
                        }
                    }

                    if(strlen($sni) > 1 && $tlsStatus != "reality") $psting = "&sni=$sni"; else $psting = '';
                    if($netType == 'tcp'){
                        if($netType == 'tcp' and $header_type == 'http') $psting .= '&headerType=http';
                        if($tlsStatus=="xtls") $psting .= "&flow=xtls-rprx-direct";
                        if($tlsStatus=="reality") $psting .= "&fp=$fp&pbk=$pbk&sni=$sni" . ($flow != ""?"&flow=$flow":"") . "&sid=$sid&spx=$spiderX";
                        if($header_type == "http") $psting .= "&path=/&host=$host";
                        $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus{$psting}#$remark";
                    }elseif($netType == 'ws'){
                        if($bypass == true)$outputlink = "$protocol://$uniqid@$server_ip:" . ($customPort!=0?$customPort:"443") . "?type=$netType&security=tls&path=" . rawurlencode($path . ($customPath == true?"?ed=2048":"")) . "&encryption=none&host=$host{$psting}#$remark";
                        else $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus&path=/&host=$host{$psting}#$remark";
                    }
                    elseif($netType == 'kcp')
                        $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus&headerType=$kcpType&seed=$kcpSeed#$remark";
                    elseif($netType == 'grpc'){
                        if($tlsStatus == 'tls'){
                            $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus&serviceName=$serviceName&sni=$sni#$remark";
                        }
                        elseif($tlsStatus=="reality"){
                            $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus&serviceName=$serviceName&fp=$fp&pbk=$pbk&sni=$sni" . ($flow != ""?"&flow=$flow":"") . "&sid=$sid&spx=$spiderX#$remark";
                        }
                        else{
                            $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus&serviceName=$serviceName#$remark";
                        }
                    }
                }elseif($protocol == 'trojan'){
                    $psting = '';
                    if($header_type == 'http') $psting .= "&path=/&host=$host";
                    if($netType == 'tcp' and $header_type == 'http') $psting .= '&headerType=http';
                    if(strlen($sni) > 1) $psting .= "&sni=$sni";
                    $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus{$psting}#$remark";

                    if($netType == 'grpc'){
                        if($tlsStatus == 'tls'){
                            $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus&serviceName=$serviceName&sni=$sni#$remark";
                        }else{
                            $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus&serviceName=$serviceName#$remark";
                        }

                    }
                }elseif($protocol == 'vmess'){
                    $vmessArr = [
                        "v"=> "2",
                        "ps"=> $remark,
                        "add"=> $server_ip,
                        "port"=> $bypass == true?($customPort!=0?$customPort:443):$port,
                        "id"=> $uniqid,
                        "aid"=> 0,
                        "net"=> $netType,
                        "type"=> ($header_type) ? $header_type : ($kcpType ? $kcpType : "none"),
                        "host"=> ($bypass == true && empty($host))?$server_ip:(is_null($host) ? '' : $host),
                        "path"=> ($bypass == true)?($path . ($customPath == true?"?ed=2048":"")) :((is_null($path) and $path != '') ? '/' : (is_null($path) ? '' : $path)),
                        "tls"=> $bypass == true?"tls":((is_null($tlsStatus)) ? 'none' : $tlsStatus)
                    ];
                    if($bypass == true){
                        if(empty($host) && isset($hostMatch[1])) $host = $hostMatch[1];

                        if(!empty($host)){
                            $subDomain = RandomString(4, "domain");
                            $parseAdd = parse_url($host);
                            $parseAdd = $parseAdd['host']??$parseAdd['path'];
                            $explodeAdd = explode(".", $parseAdd);
                            if($customSni != null) $sni = $customSni;
                            else{
                                if(count($explodeAdd) >= 3) $sni = $uniqid . "." . $explodeAdd[1] . "." .$explodeAdd[2];
                                else $sni = $uniqid . "." . $host;
                            }

                            $vmessArr['alpn'] = 'http/1.1';
                        }
                    }
                    if($netType == 'grpc'){
                        if(!is_null($alpn) and json_encode($alpn) != '[]' and $alpn != '') $vmessArr['alpn'] = $alpn;
                        if(strlen($serviceName) > 1) $vmessArr['path'] = $serviceName;
                        $vmessArr['type'] = $grpcSecurity;
                        $vmessArr['scy'] = 'auto';
                    }
                    if($netType == 'kcp'){
                        $vmessArr['path'] = $kcpSeed ? $kcpSeed : $vmessArr['path'];
                    }

                    if(strlen($sni) > 1) $vmessArr['sni'] = $sni;
                    $urldata = base64_encode(json_encode($vmessArr,JSON_UNESCAPED_SLASHES,JSON_PRETTY_PRINT));
                    $outputlink = "vmess://$urldata";
                }
            }
            $outputLink[] = $outputlink;
        }

        return $outputLink;
    }




    function getJson($server_id){
        global $connection;
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $panel_url = $server_info['panel_url'];
        $cookie = 'Cookie: session='.$server_info['cookie'];

        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];
        $serverType = $server_info['type'];

        $loginUrl = $panel_url . '/login';

        $postFields = array(
            "username" => $serverName,
            "password" => $serverPass
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

        $loginResponse = json_decode($body,true);

        if(!$loginResponse['success']){
            curl_close($curl);
            return $loginResponse;
        }
        if($serverType == "sanaei") $url = "$panel_url/panel/inbound/list";
        else $url = "$panel_url/xui/inbound/list";
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
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array(
                'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                'Accept:  application/json, text/plain, */*',
                'Accept-Language:  en-US,en;q=0.5',
                'Accept-Encoding:  gzip, deflate',
                'X-Requested-With:  XMLHttpRequest',
                'Cookie: ' . $session
            ),
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }
    function getNewCert($server_id){
        global $connection;
        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $server_id);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $panel_url = $server_info['panel_url'];
        $cookie = 'Cookie: session='.$server_info['cookie'];

        $serverName = $server_info['username'];
        $serverPass = $server_info['password'];

        $loginUrl = $panel_url . '/login';

        $postFields = array(
            "username" => $serverName,
            "password" => $serverPass
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

        $loginResponse = json_decode($body,true);
        if(!$loginResponse['success']){
            curl_close($curl);
            return $loginResponse;
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => "$panel_url/server/getNewX25519Cert",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array(
                'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
                'Accept:  application/json, text/plain, */*',
                'Accept-Language:  en-US,en;q=0.5',
                'Accept-Encoding:  gzip, deflate',
                'X-Requested-With:  XMLHttpRequest',
                'Cookie: ' . $session
            )
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response = json_decode($response);
    }






    function getPlanDetailsKeys($planId){
        global $connection, $mainValues, $buttonValues;
        $stmt = $connection->prepare("SELECT * FROM `server_plans` WHERE `id`=?");
        $stmt->bind_param("i", $planId);
        $stmt->execute();
        $pdResult = $stmt->get_result();
        $pd = $pdResult->fetch_assoc();
        $stmt->close();


        $stmt = $connection->prepare("SELECT * FROM server_config WHERE id=?");
        $stmt->bind_param("i", $pd['server_id']);
        $stmt->execute();
        $server_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $reality = $server_info['reality'];


        if($pdResult->num_rows == 0) return null;
        else {
            $id=$pd['id'];
            $name=$pd['title'];
            $price=$pd['price'];
            $acount =$pd['acount'];
            $bypass = $pd['bypass'];
            $customPath = $pd['custom_path']==true?$buttonValues['on']:$buttonValues['off'];
            $dest = $pd['dest']??" ";
            $spiderX = $pd['spiderX']??" ";
            $serverName = $pd['serverNames']??" ";
            $flow = $pd['flow'];
            $customPort = $pd['custom_port'];
            $customSni = $pd['custom_sni']??" ";

            $stmt = $connection->prepare("SELECT * FROM `orders_list` WHERE `status`=1 AND `fileid`=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $wizwizplanaccnumber = $stmt->get_result()->num_rows;
            $stmt->close();

            $srvid= $pd['server_id'];
            $keyboard = [
                ($bypass==true?[['text'=>"* نوع پلن: رهگذر *",'callback_data'=>'wizwizch']]:[]),
                ($bypass==true?[
                    ['text'=>$customPath,'callback_data'=>'changeCustomPath' . $id],
                    ['text'=>"Path Custom",'callback_data'=>'wizwizch'],
                    ]:[]),
                ($bypass==true?[
                    ['text'=>$customPort,'callback_data'=>'changeCustomPort' . $id],
                    ['text'=>"پورت دلخواه",'callback_data'=>'wizwizch'],
                    ]:[]),
                ($bypass==true?[
                    ['text'=>$customSni,'callback_data'=>'changeCustomSni' . $id],
                    ['text'=>"sni دلخواه",'callback_data'=>'wizwizch'],
                    ]:[]),
                [['text'=>$name,'callback_data'=>"wizwizplanname$id"],['text'=>"🔮 نام پلن",'callback_data'=>"wizwizch"]],
                ($reality == "true"?[['text'=>$dest,'callback_data'=>"editDestName$id"],['text'=>"dest",'callback_data'=>"wizwizch"]]:[]),
                ($reality == "true"?[['text'=>$serverName,'callback_data'=>"editServerNames$id"],['text'=>"serverNames",'callback_data'=>"wizwizch"]]:[]),
                ($reality == "true"?[['text'=>$spiderX,'callback_data'=>"editSpiderX$id"],['text'=>"spiderX",'callback_data'=>"wizwizch"]]:[]),
                ($reality == "true"?[['text'=>$flow,'callback_data'=>"editFlow$id"],['text'=>"flow",'callback_data'=>"wizwizch"]]:[]),
                [['text'=>$wizwizplanaccnumber,'callback_data'=>"wizwizch"],['text'=>"🎗 تعداد اکانت های فروخته شده",'callback_data'=>"wizwizch"]],
                ($pd['inbound_id'] != 0?[['text'=>"$acount",'callback_data'=>"wizwizplanslimit$id"],['text'=>"🚪 تغییر ظرفیت کانفیگ",'callback_data'=>"wizwizch"]]:[]),
                ($pd['inbound_id'] != 0?[['text'=>$pd['inbound_id'],'callback_data'=>"wizwizplansinobundid$id"],['text'=>"🚪 سطر کانفیگ",'callback_data'=>"wizwizch"]]:[]),
                [['text'=>"✏️ ویرایش توضیحات",'callback_data'=>"wizwizplaneditdes$id"]],
                [['text'=>number_format($price) . " تومان",'callback_data'=>"wizwizplanrial$id"],['text'=>"💰 قیمت پلن",'callback_data'=>"wizwizch"]],
                [['text'=>"♻️ دریافت لیست اکانت ها",'callback_data'=>"wizwizplanacclist$id"]],
                ($server_info['type'] == "marzban"?[['text'=>"انتخاب Host",'callback_data'=>"marzbanHostSettings" . $id]]:[]),
                [['text'=>"✂️ حذف",'callback_data'=>"wizwizplandelete$id"]],
                [['text' => $buttonValues['back_button'], 'callback_data' =>"plansList$srvid"]]
                ];
            return json_encode(['inline_keyboard'=>$keyboard]);
        }
    }
    function getUserOrderDetailKeys($id){
        global $connection, $botState, $mainValues, $buttonValues, $botUrl;
        $stmt = $connection->prepare("SELECT * FROM `orders_list` WHERE `id`=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $order = $stmt->get_result();
        $stmt->close();


        if($order->num_rows==0){
            return null;
        }else {
            $order = $order->fetch_assoc();
            $userId = $order['userid'];
            $firstName = bot('getChat',['chat_id'=>$userId])->result->first_name ?? " ";
            $fid = $order['fileid'];
            $stmt = $connection->prepare("SELECT * FROM `server_plans` WHERE `id`=? AND `active`=1");
            $stmt->bind_param("i", $fid);
            $stmt->execute();
            $respd = $stmt->get_result();
            $stmt->close();
            $bypass = $order['bypass'];
            $agentBought = $order['agent_bought'];
            $isAgentBought = $agentBought == true?"بله":"نخیر";

            if($respd){
                $respd = $respd->fetch_assoc();

                $stmt = $connection->prepare("SELECT * FROM `server_categories` WHERE `id`=?");
                $stmt->bind_param("i", $respd['catid']);
                $stmt->execute();
                $cadquery = $stmt->get_result();
                $stmt->close();


                if($cadquery) {
                    $catname = $cadquery->fetch_assoc()['title'];
                    $name = $catname." ".$respd['title'];
                }else $name = "$id";

            }else $name = "$id";

            $date = jdate("Y-m-d H:i",$order['date']);
            $expire_date = jdate("Y-m-d H:i",$order['expire_date']);
            $remark = $order['remark'];
            $uuid = $order['uuid']??"0";
            $acc_link = json_decode($order['link']);
            $protocol = $order['protocol'];
            $token = $order['token'];
            $server_id = $order['server_id'];
            $inbound_id = $order['inbound_id'];
            $link_status = $order['expire_date'] > time()  ? $buttonValues['active'] : $buttonValues['deactive'];
            $price = $order['amount'];

            $stmt = $connection->prepare("SELECT * FROM `server_config` WHERE `id` = ?");
            $stmt->bind_param('i', $server_id);
            $stmt->execute();
            $serverConfig = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $serverType = $serverConfig['type'];
            $panelUrl = $serverConfig['panel_url'];

            if($serverType == "marzban"){
                $info = getMarzbanUser($server_id, $remark);
                $enable = $info->status =="active"?true:false;
                $total = $info->data_limit;
                $usedTraffic = $info->used_traffic;

                $leftgb = round( ($total - $usedTraffic) / 1073741824, 2) . " GB";
            }else{
                $response = getJson($server_id)->obj;
                if($inbound_id == 0) {
                    foreach($response as $row){
                        $clients = json_decode($row->settings)->clients;
                        if($clients[0]->id == $uuid || $clients[0]->password == $uuid) {
                            $total = $row->total;
                            $up = $row->up;
                            $enable = $row->enable;
                            $down = $row->down;
                            $netType = json_decode($row->streamSettings)->network;
                            $security = json_decode($row->streamSettings)->security;
                            break;
                        }
                    }
                }else {
                    foreach($response as $row){
                        if($row->id == $inbound_id) {
                            $netType = json_decode($row->streamSettings)->network;
                            $security = json_decode($row->streamSettings)->security;
                            $clientsStates = $row->clientStats;
                            $clients = json_decode($row->settings)->clients;
                            foreach($clients as $key => $client){
                                if($client->id == $uuid || $client->password == $uuid){
                                    $email = $client->email;
                                    $emails = array_column($clientsStates,'email');
                                    $emailKey = array_search($email,$emails);

                                    $total = $clientsStates[$emailKey]->total;
                                    $up = $clientsStates[$emailKey]->up;
                                    $enable = $clientsStates[$emailKey]->enable;
                                    if(!$client->enable) $enable = false;
                                    $down = $clientsStates[$emailKey]->down;
                                    break;
                                }
                            }
                        }
                    }
                }
                $leftgb = round( ($total - $up - $down) / 1073741824, 2) . " GB";
            }
            $configLinks = "";
            foreach($acc_link as $acc_link){
                $configLinks .= $botState['configLinkState'] != "off"?"\n <code>$acc_link</code>":"";
            }
            $keyboard = array();
            if($inbound_id == 0){
                if($protocol == 'trojan') {
                    if($security == "xtls"){
                        $keyboard = [
                            [
                                ['text' => $userId, 'callback_data' => "wizwizch"],
                                ['text' => "آیدی کاربر", 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $firstName, 'callback_data' => "wizwizch"],
                                ['text' => "اسم کاربر", 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $isAgentBought, 'callback_data' => "wizwizch"],
                                ['text' => "خرید نماینده", 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$name", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['plan_name'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['buy_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$expire_date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['expire_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => " $leftgb", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['volume_left'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $buttonValues['selected_protocol'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $protocol == 'trojan' ? '☑️ trojan' : 'trojan', 'callback_data' => "wizwizch"],
                                ['text' => $protocol == 'vless' ? '☑️ vless' : 'vless', 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text'=>($enable == true?$buttonValues['disable_config']:$buttonValues['enable_config']),'callback_data'=>"changeUserConfigState" . $order['id']],
                                ['text'=>$buttonValues['delete_config'],'callback_data'=>"delUserConfig" . $order['id']],
                                ]
                        ];

                    }else{
                        $keyboard = [
                            [
                                ['text' => $userId, 'callback_data' => "wizwizch"],
                                ['text' => "آیدی کاربر", 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $firstName, 'callback_data' => "wizwizch"],
                                ['text' => "اسم کاربر", 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $isAgentBought, 'callback_data' => "wizwizch"],
                                ['text' => "خرید نماینده", 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$name", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['plan_name'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['buy_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$expire_date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['expire_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => " $leftgb", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['volume_left'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $buttonValues['selected_protocol'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $protocol == 'trojan' ? '☑️ trojan' : 'trojan', 'callback_data' => "wizwizch"],
                                ['text' => $protocol == 'vmess' ? '☑️ vmess' : 'vmess', 'callback_data' => "wizwizch"],
                                ['text' => $protocol == 'vless' ? '☑️ vless' : 'vless', 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text'=>($enable == true?$buttonValues['disable_config']:$buttonValues['enable_config']),'callback_data'=>"changeUserConfigState" . $order['id']],
                                ['text'=>$buttonValues['delete_config'],'callback_data'=>"delUserConfig" . $order['id']],
                                ]
                        ];


                    }
                }else {
                    if($netType == "grpc"){
                        $keyboard = [
                            [
                                ['text' => $userId, 'callback_data' => "wizwizch"],
                                ['text' => "آیدی کاربر", 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $firstName, 'callback_data' => "wizwizch"],
                                ['text' => "اسم کاربر", 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $isAgentBought, 'callback_data' => "wizwizch"],
                                ['text' => "خرید نماینده", 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$name", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['plan_name'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['buy_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$expire_date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['expire_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => " $leftgb", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['volume_left'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $buttonValues['selected_protocol'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $protocol == 'vmess' ? '☑️ vmess' : 'vmess', 'callback_data' => "wizwizch"],
                                ['text' => $protocol == 'vless' ? '☑️ vless' : 'vless', 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text'=>($enable == true?$buttonValues['disable_config']:$buttonValues['enable_config']),'callback_data'=>"changeUserConfigState" . $order['id']],
                                ['text'=>$buttonValues['delete_config'],'callback_data'=>"delUserConfig" . $order['id']],
                                ]
                        ];


                    }
                    elseif($netType == "tcp" && $security == "xtls"){
                        $keyboard = [
                            [
                                ['text' => $userId, 'callback_data' => "wizwizch"],
                                ['text' => "آیدی کاربر", 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $firstName, 'callback_data' => "wizwizch"],
                                ['text' => "اسم کاربر", 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $isAgentBought, 'callback_data' => "wizwizch"],
                                ['text' => "خرید نماینده", 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$name", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['plan_name'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['buy_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$expire_date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['expire_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => " $leftgb", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['volume_left'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $buttonValues['selected_protocol'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $protocol == 'trojan' ? '☑️ trojan' : 'trojan', 'callback_data' => "wizwizch"],
                                ['text' => $protocol == 'vless' ? '☑️ vless' : 'vless', 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text'=>($enable == true?$buttonValues['disable_config']:$buttonValues['enable_config']),'callback_data'=>"changeUserConfigState" . $order['id']],
                                ['text'=>$buttonValues['delete_config'],'callback_data'=>"delUserConfig" . $order['id']],
                                ]
                        ];

                    }
                    else{
                        $keyboard = [
                            [
                                ['text' => $userId, 'callback_data' => "wizwizch"],
                                ['text' => "آیدی کاربر", 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $firstName, 'callback_data' => "wizwizch"],
                                ['text' => "اسم کاربر", 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $isAgentBought, 'callback_data' => "wizwizch"],
                                ['text' => "خرید نماینده", 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$name", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['plan_name'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['buy_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$expire_date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['expire_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => " $leftgb", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['volume_left'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => $buttonValues['selected_protocol'], 'callback_data' => "wizwizch"],
                            ],
                            ($bypass == true?
                            [
                                ['text' => $protocol == 'vmess' ? '☑️ vmess' : 'vmess', 'callback_data' => "wizwizch"],
                                ['text' => $protocol == 'vless' ? '☑️ vless' : 'vless', 'callback_data' => "wizwizch"],
                            ]:
                                [
                                ['text' => $protocol == 'trojan' ? '☑️ trojan' : 'trojan', 'callback_data' => "wizwizch"],
                                ['text' => $protocol == 'vmess' ? '☑️ vmess' : 'vmess', 'callback_data' => "wizwizch"],
                                ['text' => $protocol == 'vless' ? '☑️ vless' : 'vless', 'callback_data' => "wizwizch"],
                            ]),
                            [
                                ['text'=>($enable == true?$buttonValues['disable_config']:$buttonValues['enable_config']),'callback_data'=>"changeUserConfigState" . $order['id']],
                                ['text'=>$buttonValues['delete_config'],'callback_data'=>"delUserConfig" . $order['id']],
                                ]
                        ];

                    }
                }
            }else{
                $keyboard = [
                    [
                        ['text' => $userId, 'callback_data' => "wizwizch"],
                        ['text' => "آیدی کاربر", 'callback_data' => "wizwizch"],
                    ],
                    [
                        ['text' => $firstName, 'callback_data' => "wizwizch"],
                        ['text' => "اسم کاربر", 'callback_data' => "wizwizch"],
                    ],
                    [
                        ['text' => $isAgentBought, 'callback_data' => "wizwizch"],
                        ['text' => "خرید نماینده", 'callback_data' => "wizwizch"],
                    ],
                    [
                        ['text' => "$name", 'callback_data' => "wizwizch"],
                        ['text' => $buttonValues['plan_name'], 'callback_data' => "wizwizch"],
                    ],
                    [
                        ['text' => "$date ", 'callback_data' => "wizwizch"],
                        ['text' => $buttonValues['buy_date'], 'callback_data' => "wizwizch"],
                    ],
                    [
                        ['text' => "$expire_date ", 'callback_data' => "wizwizch"],
                        ['text' => $buttonValues['expire_date'], 'callback_data' => "wizwizch"],
                    ],
                    [
                        ['text' => " $leftgb", 'callback_data' => "wizwizch"],
                        ['text' => $buttonValues['volume_left'], 'callback_data' => "wizwizch"],
                    ],
                    [
                        ['text' => $buttonValues['selected_protocol'], 'callback_data' => "wizwizch"],
                    ],
                    [
                        ['text' => " $protocol ☑️", 'callback_data' => "wizwizch"],
                    ],
                    [
                        ['text'=>($enable == true?$buttonValues['disable_config']:$buttonValues['enable_config']),'callback_data'=>"changeUserConfigState" . $order['id']],
                        ['text'=>$buttonValues['delete_config'],'callback_data'=>"delUserConfig" . $order['id']],
                        ]
                ];


            }


            $stmt= $connection->prepare("SELECT * FROM `server_info` WHERE `id`=?");
            $stmt->bind_param("i", $server_id);
            $stmt->execute();
            $server_info = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if($serverType == "marzban") $subLink = $botState['subLinkState'] == "on"?"<code>" . $panelUrl . "/sub/" . $token . "</code>":"";
            else $subLink = $botState['subLinkState']=="on"?"<code>" . $botUrl . "settings/subLink.php?token=" . $token . "</code>":"";


            $enable = $enable == true? $buttonValues['active']:$buttonValues['deactive'];
            $msg = str_replace(['STATE', 'NAME','CONNECT-LINK', 'SUB-LINK'], [$enable, $remark, $configLinks, $subLink], $mainValues['config_details_message']);

            $keyboard[] = [['text' => $buttonValues['back_button'], 'callback_data' => "managePanel"]];
            return ["keyboard"=>json_encode([
                        'inline_keyboard' => $keyboard
                    ]),
                    "msg"=>$msg];
        }
    }
    function getOrderDetailKeys($from_id, $id){
        global $connection, $botState, $mainValues, $buttonValues, $botUrl;
        $stmt = $connection->prepare("SELECT * FROM `orders_list` WHERE `userid`=? AND `id`=?");
        $stmt->bind_param("ii", $from_id, $id);
        $stmt->execute();
        $order = $stmt->get_result();
        $stmt->close();


        if($order->num_rows==0){
            return null;
        }else {
            $order = $order->fetch_assoc();
            $fid = $order['fileid'];
            $stmt = $connection->prepare("SELECT * FROM `server_plans` WHERE `id`=? AND `active`=1");
            $stmt->bind_param("i", $fid);
            $stmt->execute();
            $respd = $stmt->get_result();
            $stmt->close();
            $bypass = $order['bypass'];
            $agentBought = $order['agent_bought'];

            if($respd){
                $respd = $respd->fetch_assoc();

                $stmt = $connection->prepare("SELECT * FROM `server_categories` WHERE `id`=?");
                $stmt->bind_param("i", $respd['catid']);
                $stmt->execute();
                $cadquery = $stmt->get_result();
                $stmt->close();


                if($cadquery) {
                    $catname = $cadquery->fetch_assoc()['title'];
                    $name = $catname." ".$respd['title'];
                }else $name = "$id";

            }else $name = "$id";

            $date = jdate("Y-m-d H:i",$order['date']);
            $expire_date = jdate("Y-m-d H:i",$order['expire_date']);
            $remark = $order['remark'];
            $uuid = $order['uuid']??"0";
            $acc_link = json_decode($order['link']);
            $protocol = $order['protocol'];
            $token = $order['token'];
            $server_id = $order['server_id'];
            $inbound_id = $order['inbound_id'];
            $link_status = $order['expire_date'] > time()  ? $buttonValues['active'] : $buttonValues['deactive'];
            $price = $order['amount'];

            $stmt = $connection->prepare("SELECT * FROM `server_config` WHERE `id` = ?");
            $stmt->bind_param('i', $server_id);
            $stmt->execute();
            $serverConfig = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $serverType = $serverConfig['type'];
            $panel_url = $serverConfig['panel_url'];

            if($serverType == "marzban"){
                $info = getMarzbanUser($server_id, $remark);
                $enable = $info->status =="active"?true:false;
                $total = $info->data_limit;
                $usedTraffic = $info->used_traffic;

                $leftgb = round( ($total - $usedTraffic) / 1073741824, 2) . " GB";
            }else{
                $response = getJson($server_id)->obj;
                if($response){
                    if($inbound_id == 0) {
                        foreach($response as $row){
                            $clients = json_decode($row->settings)->clients;
                            if($clients[0]->id == $uuid || $clients[0]->password == $uuid) {
                                $total = $row->total;
                                $up = $row->up;
                                $down = $row->down;
                                $enable = $row->enable;
                                $expiryTime = $row->expiryTime;

                                $netType = json_decode($row->streamSettings)->network;
                                $security = json_decode($row->streamSettings)->security;

                                $clientsStates = $row->clientStats;

                                $inboundEmail = $clients[0]->email;
                                $allEmails = array_column($clientsStates,'email');
                                $clienEmailKey = array_search($inboundEmail,$allEmails);

                                $clientTotal = $clientsStates[$clienEmailKey]->total;
                                $clientUp = $clientsStates[$clienEmailKey]->up;
                                $clientDown = $clientsStates[$clienEmailKey]->down;
                                $clientExpiryTime = $clientsStates[$clienEmailKey]->expiryTime;

                                if($clientTotal != 0 && $clientTotal != null && $clientExpiryTime != 0 && $clientExpiryTime != null){
                                    $up += $clientUp;
                                    $down += $clientDown;
                                    $total = $clientTotal;
                                }

                                break;
                            }
                        }
                    }else {
                        foreach($response as $row){
                            if($row->id == $inbound_id) {
                                $netType = json_decode($row->streamSettings)->network;
                                $security = json_decode($row->streamSettings)->security;

                                $clientsStates = $row->clientStats;
                                $clients = json_decode($row->settings)->clients;
                                foreach($clients as $key => $client){
                                    if($client->id == $uuid || $client->password == $uuid){
                                        $email = $client->email;
                                        $emails = array_column($clientsStates,'email');
                                        $emailKey = array_search($email,$emails);

                                        $total = $clientsStates[$emailKey]->total;
                                        $up = $clientsStates[$emailKey]->up;
                                        $enable = $clientsStates[$emailKey]->enable;
                                        if(!$client->enable) $enable = false;
                                        $down = $clientsStates[$emailKey]->down;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    $leftgb = round( ($total - $up - $down) / 1073741824, 2) . " GB";
                }else $leftgb = "⚠️";
            }
            $configLinks = "";
            foreach($acc_link as $acc_link){
                $configLinks .= ($botState['configLinkState'] != "off"?"\n <code>$acc_link</code>":"");
            }
            $keyboard = array();
            if($inbound_id == 0){
                if($protocol == 'trojan') {
                    if($security == "xtls"){
                        $keyboard = [
                            [
                                ['text' => "$name", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['plan_name'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['buy_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$expire_date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['expire_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => " $leftgb", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['volume_left'], 'callback_data' => "wizwizch"],
                            ],
                            ($serverType != "marzban"?[
                                ['text' => $buttonValues['selected_protocol'], 'callback_data' => "wizwizch"],
                            ]:[]),
                            ($serverType != "marzban"?[
                                ['text' => $protocol == 'trojan' ? '☑️ trojan' : 'trojan', 'callback_data' => ($botState['changeProtocolState']=="on"?"changeAccProtocol{$fid}_{$id}_trojan":"changeProtocolIsDisable")],
                                ['text' => $protocol == 'vless' ? '☑️ vless' : 'vless', 'callback_data' => ($botState['changeProtocolState']=="on"?"changeAccProtocol{$fid}_{$id}_vless":"changeProtocolIsDisable")],
                            ]:[]),
                        ];

                        $temp = array();
                        if($price != 0 && $agentBought == true){
                            if($botState['renewAccountState']=="on") $temp[] = ['text' => $buttonValues['renew_config'], 'callback_data' => "renewAccount$id" ];
                            if($botState['switchLocationState']=="on") $temp[] = ['text' => $buttonValues['change_config_location'], 'callback_data' => "switchLocation{$id}_{$server_id}_{$leftgb}_".$order['expire_date']];
                        }
                        if(count($temp)>0) array_push($keyboard, $temp);
                    }else{
                        $keyboard = [
                            [
                                ['text' => "$name", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['plan_name'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['buy_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$expire_date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['expire_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => " $leftgb", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['volume_left'], 'callback_data' => "wizwizch"],
                            ],
                            ($serverType != "marzban"?[
                                ['text' => $buttonValues['selected_protocol'], 'callback_data' => "wizwizch"],
                            ]:[]),
                            ($serverType != "marzban"?[
                                ['text' => $protocol == 'trojan' ? '☑️ trojan' : 'trojan', 'callback_data' => ($botState['changeProtocolState']=="on"?"changeAccProtocol{$fid}_{$id}_trojan":"changeProtocolIsDisable")],
                                ['text' => $protocol == 'vmess' ? '☑️ vmess' : 'vmess', 'callback_data' => ($botState['changeProtocolState']=="on"?"changeAccProtocol{$fid}_{$id}_vmess":"changeProtocolIsDisable")],
                                ['text' => $protocol == 'vless' ? '☑️ vless' : 'vless', 'callback_data' => ($botState['changeProtocolState']=="on"?"changeAccProtocol{$fid}_{$id}_vless":"changeProtocolIsDisable")],
                            ]:[]),
                        ];


                        $temp = array();
                        if($price != 0 || $agentBought == true){
                            if($botState['renewAccountState']=="on") $temp[] = ['text' => $buttonValues['renew_config'], 'callback_data' => "renewAccount$id" ];
                            if($botState['switchLocationState']=="on") $temp[] = ['text' => $buttonValues['change_config_location'], 'callback_data' => "switchLocation{$id}_{$server_id}_{$leftgb}_".$order['expire_date'] ];
                        }
                        if(count($temp)>0) array_push($keyboard, $temp);
                    }
                }else {
                    if($netType == "grpc"){
                        $keyboard = [
                            [
                                ['text' => "$name", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['plan_name'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['buy_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$expire_date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['expire_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => " $leftgb", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['volume_left'], 'callback_data' => "wizwizch"],
                            ],
                            ($serverType != "marzban"?[
                                ['text' => $buttonValues['selected_protocol'], 'callback_data' => "wizwizch"],
                            ]:[]),
                            ($serverType != "marzban"?[
                                ['text' => $protocol == 'vmess' ? '☑️ vmess' : 'vmess', 'callback_data' => ($botState['changeProtocolState']=="on"?"changeAccProtocol{$fid}_{$id}_vmess":"changeProtocolIsDisable")],
                                ['text' => $protocol == 'vless' ? '☑️ vless' : 'vless', 'callback_data' => ($botState['changeProtocolState']=="on"?"changeAccProtocol{$fid}_{$id}_vless":"changeProtocolIsDisable")],
                            ]:[])
                        ];


                        $temp = array();
                        if($price != 0 || $agentBought == true){
                            if($botState['renewAccountState']=="on") $temp[] = ['text' => $buttonValues['renew_config'], 'callback_data' => "renewAccount$id" ];
                            if($botState['switchLocationState']=="on") $temp[] = ['text' => $buttonValues['change_config_location'], 'callback_data' => "switchLocation{$id}_{$server_id}_{$leftgb}_".$order['expire_date'] ];
                        }
                        if(count($temp)>0) array_push($keyboard, $temp);
                    }
                    elseif($netType == "tcp" && $security == "xtls"){
                        $keyboard = [
                            [
                                ['text' => "$name", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['plan_name'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['buy_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$expire_date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['expire_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => " $leftgb", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['volume_left'], 'callback_data' => "wizwizch"],
                            ],
                            ($serverType != "marzban"?[
                                ['text' => $buttonValues['selected_protocol'], 'callback_data' => "wizwizch"],
                            ]:[]),
                            ($serverType != "marzban"?[
                                ['text' => $protocol == 'trojan' ? '☑️ trojan' : 'trojan', 'callback_data' => ($botState['changeProtocolState']=="on"?"changeAccProtocol{$fid}_{$id}_trojan":"changeProtocolIsDisable")],
                                ['text' => $protocol == 'vless' ? '☑️ vless' : 'vless', 'callback_data' => ($botState['changeProtocolState']=="on"?"changeAccProtocol{$fid}_{$id}_vless":"changeProtocolIsDisable")],
                            ]:[])
                        ];

                        $temp = array();
                        if($price != 0 || $agentBought == true){
                            if($botState['renewAccountState']=="on") $temp[] = ['text' => $buttonValues['renew_config'], 'callback_data' => "renewAccount$id" ];
                            if($botState['switchLocationState']=="on") $temp[] = ['text' => $buttonValues['change_config_location'], 'callback_data' => "switchLocation{$id}_{$server_id}_{$leftgb}_".$order['expire_date'] ];
                        }
                        if(count($temp)>0) array_push($keyboard, $temp);

                    }
                    else{
                        $keyboard = [
                            [
                                ['text' => "$name", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['plan_name'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['buy_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => "$expire_date ", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['expire_date'], 'callback_data' => "wizwizch"],
                            ],
                            [
                                ['text' => " $leftgb", 'callback_data' => "wizwizch"],
                                ['text' => $buttonValues['volume_left'], 'callback_data' => "wizwizch"],
                            ],
                            ($serverType != "marzban"?[
                                ['text' => $buttonValues['selected_protocol'], 'callback_data' => "wizwizch"],
                            ]:[]),
                            ($serverType != "marzban"?($bypass == true?
                            [
                                ['text' => $protocol == 'vmess' ? '☑️ vmess' : 'vmess', 'callback_data' => ($botState['changeProtocolState']=="on"?"changeAccProtocol{$fid}_{$id}_vmess":"changeProtocolIsDisable")],
                                ['text' => $protocol == 'vless' ? '☑️ vless' : 'vless', 'callback_data' => ($botState['changeProtocolState']=="on"?"changeAccProtocol{$fid}_{$id}_vless":"changeProtocolIsDisable")],
                            ]:
                                [
                                ['text' => $protocol == 'trojan' ? '☑️ trojan' : 'trojan', 'callback_data' => ($botState['changeProtocolState']=="on"?"changeAccProtocol{$fid}_{$id}_trojan":"changeProtocolIsDisable")],
                                ['text' => $protocol == 'vmess' ? '☑️ vmess' : 'vmess', 'callback_data' => ($botState['changeProtocolState']=="on"?"changeAccProtocol{$fid}_{$id}_vmess":"changeProtocolIsDisable")],
                                ['text' => $protocol == 'vless' ? '☑️ vless' : 'vless', 'callback_data' => ($botState['changeProtocolState']=="on"?"changeAccProtocol{$fid}_{$id}_vless":"changeProtocolIsDisable")],
                            ]):[])
                        ];

                        $temp = array();
                        if($price != 0 || $agentBought == true){
                            if($botState['renewAccountState']=="on") $temp[] = ['text' => $buttonValues['renew_config'], 'callback_data' => "renewAccount$id" ];
                            if($botState['switchLocationState']=="on" && $bypass != true) $temp[] = ['text' => $buttonValues['change_config_location'], 'callback_data' => "switchLocation{$id}_{$server_id}_{$leftgb}_".$order['expire_date'] ];
                        }
                        if(count($temp)>0) array_push($keyboard, $temp);

                    }
                }
            }else{
                $keyboard = [
                    [
                        ['text' => "$name", 'callback_data' => "wizwizch"],
                        ['text' => $buttonValues['plan_name'], 'callback_data' => "wizwizch"],
                    ],
                    [
                        ['text' => "$date ", 'callback_data' => "wizwizch"],
                        ['text' => $buttonValues['buy_date'], 'callback_data' => "wizwizch"],
                    ],
                    [
                        ['text' => "$expire_date ", 'callback_data' => "wizwizch"],
                        ['text' => $buttonValues['expire_date'], 'callback_data' => "wizwizch"],
                    ],
                    [
                        ['text' => " $leftgb", 'callback_data' => "wizwizch"],
                        ['text' => $buttonValues['volume_left'], 'callback_data' => "wizwizch"],
                    ],
                    ($serverType != "marzban"?[
                        ['text' => $buttonValues['selected_protocol'], 'callback_data' => "wizwizch"],
                    ]:[]),
                    ($serverType != "marzban"?[
                        ['text' => " $protocol ☑️", 'callback_data' => "wizwizch"],
                    ]:[])
                ];

                $temp = array();
                if($price != 0 || $agentBought == true){
                    if($botState['renewAccountState']=="on") $temp[] = ['text' => $buttonValues['renew_config'], 'callback_data' => "renewAccount$id" ];
                    if($botState['switchLocationState']=="on" && $bypass != true) $temp[] = ['text' => $buttonValues['change_config_location'], 'callback_data' => "switchLocation{$id}_{$server_id}_{$leftgb}_".$order['expire_date'] ];
                }
                if(count($temp)>0) array_push($keyboard, $temp);

            }


            $stmt= $connection->prepare("SELECT * FROM `server_info` WHERE `id`=?");
            $stmt->bind_param("i", $server_id);
            $stmt->execute();
            $server_info = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if($serverType == "marzban") $subLink = $botState['subLinkState'] == "on"?"<code>" . $panel_url . "/sub/" . $token . "</code>":"";
            else $subLink = $botState['subLinkState']=="on"?"<code>" . $botUrl . "settings/subLink.php?token=" . $token . "</code>":"";

            $enable = $enable == true? $buttonValues['active']:$buttonValues['deactive'];
            $msg = str_replace(['STATE', 'NAME','CONNECT-LINK', 'SUB-LINK'], [$enable, $remark, $configLinks, $subLink], $mainValues['config_details_message']);


            $extrakey = [];
            if($botState['increaseVolumeState']=="on" && ($price != 0 || $agentBought == true)) $extrakey[] = ['text' => $buttonValues['increase_config_volume'], 'callback_data' => "increaseAVolume{$id}"];
            if($botState['increaseTimeState']=="on" && ($price != 0 || $agentBought == true)) $extrakey[] = ['text' => $buttonValues['increase_config_days'], 'callback_data' => "increaseADay{$id}"];
            $keyboard[] = $extrakey;


            if($botState['renewConfigLinkState'] == "on" && $botState['updateConfigLinkState'] == "on") $keyboard[] = [['text'=>$buttonValues['renew_connection_link'],'callback_data'=>'changAccountConnectionLink' . $id],['text'=>$buttonValues['update_config_connection'],'callback_data'=>'updateConfigConnectionLink' . $id]];
            elseif($botState['renewConfigLinkState'] == "on") $keyboard[] = [['text'=>$buttonValues['renew_connection_link'],'callback_data'=>'changAccountConnectionLink' . $id]];
            elseif($botState['updateConfigLinkState'] == "on") $keyboard[] = [['text'=>$buttonValues['update_config_connection'],'callback_data'=>'updateConfigConnectionLink' . $id]];

            $temp = [];
            if($botState['qrConfigState'] == "on") $temp[] = ['text'=>$buttonValues['qr_config'],'callback_data'=>"showQrConfig" . $id];
            if($botState['qrSubState'] == "on") $temp[] = ['text'=>$buttonValues['qr_sub'],'callback_data'=>"showQrSub" . $id];
            array_push($keyboard, $temp);

            $keyboard[] = [['text' => $buttonValues['delete_config'], 'callback_data' => "deleteMyConfig" . $id]];
            $keyboard[] = [['text' => $buttonValues['back_button'], 'callback_data' => ($agentBought == true?"agentConfigsList":"mySubscriptions")]];
            return ["keyboard"=>json_encode([
                        'inline_keyboard' => $keyboard
                    ]),
                    "msg"=>$msg];
        }
    }

}
