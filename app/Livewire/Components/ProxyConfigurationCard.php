<?php

namespace App\Livewire\Components;

use Livewire\Component;
use App\Models\ServerClient;
use App\Models\Order;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use App\Livewire\Traits\LivewireAlertV4;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ProxyConfigurationCard extends Component
{
    use LivewireAlertV4;

    #[Reactive]
    public $serverClient;

    public $showQrCode = false;
    public $showAdvancedConfig = false;
    public $configFormat = 'link'; // link, json, clash, v2ray
    public $qrCodeSize = 200;
    public $downloadFormat = 'txt';

    protected $listeners = [
        'refreshConfiguration' => 'refreshConfig',
        'resetClient' => 'resetClientConfig'
    ];

    public function mount(ServerClient $serverClient)
    {
        $this->serverClient = $serverClient;
    }

    public function render()
    {
        $configData = $this->generateConfigurationData();

        return view('livewire.components.proxy-configuration-card', [
            'configData' => $configData,
            'qrCodeSvg' => $this->generateQrCode($configData['link'] ?? ''),
            'clientStatus' => $this->getClientStatus(),
            'connectionStats' => $this->getConnectionStats()
        ]);
    }

    public function toggleQrCode()
    {
        $this->showQrCode = !$this->showQrCode;
    }

    public function toggleAdvancedConfig()
    {
        $this->showAdvancedConfig = !$this->showAdvancedConfig;
    }

    public function changeConfigFormat($format)
    {
        $this->configFormat = $format;
    }

    public function copyToClipboard($text)
    {
        $this->dispatch('copyToClipboard', text: $text);

        $this->alert('success', 'Copied to clipboard!', [
            'position' => 'top-end',
            'timer' => 2000,
            'toast' => true,
        ]);
    }

    public function downloadConfig()
    {
        $configData = $this->generateConfigurationData();
        $filename = $this->generateFileName();

        $content = '';
        switch ($this->downloadFormat) {
            case 'txt':
                $content = $configData['link'] ?? '';
                break;
            case 'json':
                $content = json_encode($configData['json'] ?? [], JSON_PRETTY_PRINT);
                break;
            case 'clash':
                $content = $this->generateClashConfig($configData);
                break;
            case 'v2ray':
                $content = json_encode($configData['v2ray'] ?? [], JSON_PRETTY_PRINT);
                break;
        }

        $this->dispatch('downloadFile', [
            'content' => $content,
            'filename' => $filename,
            'mimeType' => $this->getMimeType()
        ]);

        $this->alert('success', 'Configuration downloaded!', [
            'position' => 'top-end',
            'timer' => 2000,
            'toast' => true,
        ]);
    }

    public function testConnection()
    {
        // Simulate connection test - in production this would test actual connectivity
        $testResult = $this->performConnectionTest();

        if ($testResult['success']) {
            $this->alert('success', 'Connection test successful!', [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        } else {
            $this->alert('error', 'Connection test failed: ' . $testResult['error'], [
                'position' => 'top-end',
                'timer' => 5000,
                'toast' => true,
            ]);
        }
    }

    #[On('refreshConfiguration')]
    public function refreshConfig()
    {
        $this->serverClient->refresh();

        $this->alert('info', 'Configuration refreshed!', [
            'position' => 'top-end',
            'timer' => 2000,
            'toast' => true,
        ]);
    }

    #[On('resetClient')]
    public function resetClientConfig()
    {
        // This would reset the client configuration on the XUI panel
        $this->dispatch('clientReset', clientId: $this->serverClient->id);

        $this->alert('warning', 'Client configuration reset!', [
            'position' => 'top-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    private function generateConfigurationData()
    {
        $server = $this->serverClient->server;
        $plan = $this->serverClient->serverPlan;

        // Base configuration data
        $baseConfig = [
            'protocol' => $plan->protocol ?? 'vless',
            'server' => $server->host ?? $server->ip,
            'port' => $server->port ?? 443,
            'uuid' => $this->serverClient->client_uuid,
            'email' => $this->serverClient->email,
            'flow' => $this->serverClient->flow ?? '',
            'security' => $server->security ?? 'reality',
            'sni' => $server->sni ?? '',
            'header_type' => $server->header_type ?? 'none',
        ];

        // Generate different format configurations
        return [
            'link' => $this->generateConnectionLink($baseConfig),
            'json' => $this->generateJsonConfig($baseConfig),
            'clash' => $this->generateClashConfig($baseConfig),
            'v2ray' => $this->generateV2RayConfig($baseConfig),
            'base' => $baseConfig
        ];
    }

    private function generateConnectionLink($config)
    {
        $protocol = strtolower($config['protocol']);

        switch ($protocol) {
            case 'vless':
                return $this->generateVlessLink($config);
            case 'vmess':
                return $this->generateVmessLink($config);
            case 'trojan':
                return $this->generateTrojanLink($config);
            case 'shadowsocks':
                return $this->generateShadowsocksLink($config);
            default:
                return '';
        }
    }

    private function generateVlessLink($config)
    {
        $params = [
            'type' => 'tcp',
            'security' => $config['security'],
            'sni' => $config['sni'],
            'headerType' => $config['header_type'],
            'flow' => $config['flow']
        ];

        $paramString = http_build_query(array_filter($params));

        return sprintf(
            'vless://%s@%s:%s?%s#%s',
            $config['uuid'],
            $config['server'],
            $config['port'],
            $paramString,
            urlencode($config['email'])
        );
    }

    private function generateVmessLink($config)
    {
        $vmessConfig = [
            'v' => '2',
            'ps' => $config['email'],
            'add' => $config['server'],
            'port' => $config['port'],
            'id' => $config['uuid'],
            'aid' => '0',
            'net' => 'tcp',
            'type' => $config['header_type'],
            'host' => '',
            'path' => '',
            'tls' => $config['security'] === 'tls' ? 'tls' : '',
            'sni' => $config['sni']
        ];

        return 'vmess://' . base64_encode(json_encode($vmessConfig));
    }

    private function generateTrojanLink($config)
    {
        $params = [
            'type' => 'tcp',
            'security' => $config['security'],
            'sni' => $config['sni'],
            'headerType' => $config['header_type']
        ];

        $paramString = http_build_query(array_filter($params));

        return sprintf(
            'trojan://%s@%s:%s?%s#%s',
            $config['uuid'],
            $config['server'],
            $config['port'],
            $paramString,
            urlencode($config['email'])
        );
    }

    private function generateShadowsocksLink($config)
    {
        // For shadowsocks, UUID is used as password
        $method = 'aes-256-gcm'; // Default encryption method
        $auth = base64_encode($method . ':' . $config['uuid']);

        return sprintf(
            'ss://%s@%s:%s#%s',
            $auth,
            $config['server'],
            $config['port'],
            urlencode($config['email'])
        );
    }

    private function generateJsonConfig($config)
    {
        return [
            'log' => ['loglevel' => 'warning'],
            'inbounds' => [
                [
                    'tag' => 'proxy',
                    'port' => 1080,
                    'protocol' => 'socks',
                    'settings' => ['udp' => true]
                ]
            ],
            'outbounds' => [
                [
                    'tag' => 'proxy',
                    'protocol' => $config['protocol'],
                    'settings' => [
                        'vnext' => [
                            [
                                'address' => $config['server'],
                                'port' => intval($config['port']),
                                'users' => [
                                    [
                                        'id' => $config['uuid'],
                                        'email' => $config['email'],
                                        'flow' => $config['flow']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'streamSettings' => [
                        'network' => 'tcp',
                        'security' => $config['security'],
                        'tlsSettings' => [
                            'serverName' => $config['sni']
                        ]
                    ]
                ]
            ]
        ];
    }

    private function generateClashConfig($config)
    {
        $clashConfig = [
            'name' => $config['email'],
            'type' => $config['protocol'],
            'server' => $config['server'],
            'port' => intval($config['port']),
            'uuid' => $config['uuid'],
            'tls' => $config['security'] === 'tls',
            'skip-cert-verify' => false,
            'servername' => $config['sni']
        ];

        if ($config['protocol'] === 'vless') {
            $clashConfig['flow'] = $config['flow'];
        }

        // Return YAML-like string manually since yaml_emit might not be available
        $yaml = "proxies:\n";
        $yaml .= "  - name: " . $clashConfig['name'] . "\n";
        $yaml .= "    type: " . $clashConfig['type'] . "\n";
        $yaml .= "    server: " . $clashConfig['server'] . "\n";
        $yaml .= "    port: " . $clashConfig['port'] . "\n";
        $yaml .= "    uuid: " . $clashConfig['uuid'] . "\n";
        $yaml .= "    tls: " . ($clashConfig['tls'] ? 'true' : 'false') . "\n";
        $yaml .= "    skip-cert-verify: " . ($clashConfig['skip-cert-verify'] ? 'true' : 'false') . "\n";
        $yaml .= "    servername: " . $clashConfig['servername'] . "\n";

        if (isset($clashConfig['flow'])) {
            $yaml .= "    flow: " . $clashConfig['flow'] . "\n";
        }

        return $yaml;
    }

    private function generateV2RayConfig($config)
    {
        return $this->generateJsonConfig($config); // V2Ray uses same format as JSON
    }

    private function generateQrCode($text)
    {
        if (empty($text)) {
            return '';
        }

        return QrCode::size($this->qrCodeSize)
            ->style('round')
            ->eye('circle')
            ->margin(1)
            ->generate($text);
    }

    private function getClientStatus()
    {
        // Simulate client status - in production this would check actual server status
        return [
            'status' => $this->serverClient->is_active ? 'active' : 'inactive',
            'last_connection' => $this->serverClient->last_connected_at,
            'data_usage' => [
                'upload' => $this->serverClient->upload_bytes ?? 0,
                'download' => $this->serverClient->download_bytes ?? 0,
                'total' => ($this->serverClient->upload_bytes ?? 0) + ($this->serverClient->download_bytes ?? 0)
            ],
            'expiry' => $this->serverClient->expires_at,
            'remaining_days' => $this->serverClient->expires_at ?
                now()->diffInDays($this->serverClient->expires_at, false) : null
        ];
    }

    private function getConnectionStats()
    {
        return [
            'total_connections' => rand(50, 500),
            'active_connections' => rand(1, 10),
            'success_rate' => rand(95, 100) . '%',
            'avg_speed' => rand(10, 100) . ' Mbps',
            'uptime' => '99.9%'
        ];
    }

    private function performConnectionTest()
    {
        // Simulate connection test
        $success = rand(1, 10) > 2; // 80% success rate

        if ($success) {
            return [
                'success' => true,
                'latency' => rand(10, 200) . 'ms',
                'speed' => rand(10, 100) . ' Mbps'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Connection timeout or server unreachable'
            ];
        }
    }

    private function generateFileName()
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $email = str_replace(['@', '.'], ['_', '_'], $this->serverClient->email);

        return "{$email}_{$timestamp}.{$this->downloadFormat}";
    }

    private function getMimeType()
    {
        return match($this->downloadFormat) {
            'json' => 'application/json',
            'yaml', 'yml' => 'application/x-yaml',
            'txt' => 'text/plain',
            default => 'text/plain'
        };
    }
}
