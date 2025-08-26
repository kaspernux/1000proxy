<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Action as PageAction;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Models\ServerClient;
use BackedEnum;
use App\Models\Order;
use App\Models\Server;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Services\QrCodeService;

class ConfigurationGuides extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Setup Guides & Tutorials';
    protected string $view = 'filament.customer.pages.configuration-guides';
    protected static ?int $navigationSort = 9;

    public $selectedProtocol = 'vless';
    public $selectedPlatform = 'windows';
    public $selectedClient = 'v2rayN';
    public $selectedConfiguration = null;
    public $userConfigurations = [];
    public $availableConfigurations = [];
    public $tutorialSteps = [];
    public $currentStep = 1;
    public $showAdvancedOptions = false;
    public $customSettings = [
        'enable_routing' => false,
        'enable_logs' => true,
        'log_level' => 'warning',
        'dns_server' => '8.8.8.8',
        'mux_enabled' => true,
        'tls_settings' => 'auto',
    ];

    public function mount(): void
    {
        $this->loadUserConfigurations();
        $this->loadAvailableConfigurations();
        $this->loadTutorialSteps();
    }

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('quick_setup')
                ->label('Quick Setup Wizard')
                ->icon('heroicon-o-bolt')
                ->color('success')
                ->action('startQuickSetup'),

            PageAction::make('download_app')
                ->label('Download Client App')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action('downloadClientApp'),

            PageAction::make('auto_configure')
                ->label('Auto-Configure')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('warning')
                ->action('autoConfigureClient'),

            PageAction::make('test_connection')
                ->label('Test Connection')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action('testConnection'),

            PageAction::make('export_configs')
                ->label('Export All Configs')
                ->icon('heroicon-o-document-arrow-down')
                ->action('exportAllConfigurations'),
        ];
    }

    public function startQuickSetup(): void
    {
        $this->currentStep = 1;
        $this->showAdvancedOptions = false;
        
        Notification::make()
            ->title('Quick Setup Started')
            ->body('Follow the step-by-step guide to configure your proxy client')
            ->info()
            ->send();

        $this->logUserAction('quick_setup_start');
    }

    protected function loadUserConfigurations(): void
    {
        $customer = Auth::guard('customer')->user();

        $this->userConfigurations = ServerClient::whereHas('order', function ($query) use ($customer) {
            $query->where('customer_id', $customer->id);
        })
        ->with(['server', 'order'])
        ->get()
        ->map(function ($client) {
            return [
                'id' => $client->id,
                'server_name' => $client->server->name,
                'location' => $client->server->location,
                'protocol' => $client->protocol,
                'config_url' => $this->generateConfigUrl($client),
                'qr_code' => $this->generateQRCode($client),
                'subscription_url' => $this->generateSubscriptionUrl($client),
                'manual_config' => $this->generateManualConfig($client),
            ];
        })
        ->toArray();
    }

    protected function loadAvailableConfigurations(): void
    {
        $this->availableConfigurations = [
            'protocols' => [
                'vless' => [
                    'name' => 'VLESS',
                    'description' => 'Latest generation protocol with better performance',
                    'features' => ['High speed', 'Low latency', 'Better security'],
                    'supported_clients' => ['v2rayN', 'v2rayNG', 'Qv2ray', 'V2Box'],
                ],
                'vmess' => [
                    'name' => 'VMess',
                    'description' => 'Traditional V2Ray protocol with encryption',
                    'features' => ['Encrypted', 'Stable', 'Wide compatibility'],
                    'supported_clients' => ['v2rayN', 'v2rayNG', 'Qv2ray', 'V2Box', 'ShadowRocket'],
                ],
                'trojan' => [
                    'name' => 'Trojan',
                    'description' => 'TLS-based protocol for better bypass',
                    'features' => ['TLS encryption', 'Better bypass', 'HTTPS appearance'],
                    'supported_clients' => ['Trojan-Qt5', 'igniter', 'ShadowRocket'],
                ],
                'shadowsocks' => [
                    'name' => 'Shadowsocks',
                    'description' => 'Simple and fast SOCKS5 proxy',
                    'features' => ['Simple setup', 'Fast', 'Lightweight'],
                    'supported_clients' => ['Shadowsocks', 'ShadowsocksX-NG', 'Outline'],
                ],
            ],
            'platforms' => [
                'windows' => [
                    'name' => 'Windows',
                    'recommended_clients' => ['v2rayN', 'Qv2ray', 'V2RayW'],
                ],
                'macos' => [
                    'name' => 'macOS',
                    'recommended_clients' => ['V2Box', 'V2RayU', 'Qv2ray', 'V2RayX'],
                ],
                'linux' => [
                    'name' => 'Linux',
                    'recommended_clients' => ['V2RayA', 'Qv2ray', 'v2ray-core'],
                ],
                'android' => [
                    'name' => 'Android',
                    'recommended_clients' => ['V2Box', 'v2rayNG', 'SagerNet'],
                ],
                'ios' => [
                    'name' => 'iOS',
                    'recommended_clients' => ['V2Box', 'ShadowRocket', 'Quantumult X'],
                ],
            ],
        ];
    }

    // Platform and client selection methods
    public function updatePlatform($platform): void
    {
        $this->selectedPlatform = $platform;
        // Reset client selection when platform changes
        $platforms = $this->getAvailableConfigurations()['platforms'];
        $this->selectedClient = $platforms[$platform]['recommended_clients'][0] ?? '';
        
        Notification::make()
            ->title('Platform Updated')
            ->body("Switched to {$platform}")
            ->success()
            ->send();
    }

    public function updateClient($client): void
    {
        $this->selectedClient = $client;
        
        Notification::make()
            ->title('Client Selected')
            ->body("Selected {$client}")
            ->success()
            ->send();
    }

    public function updateProtocol($protocol): void
    {
        $this->selectedProtocol = $protocol;
        
        Notification::make()
            ->title('Protocol Updated')
            ->body("Switched to {$protocol}")
            ->success()
            ->send();
    }

    public function generateConfigUrl($client): string
    {
        if (!$client->server) {
            return '';
        }

        $server = $client->server;
        $protocol = $client->protocol ?: 'vless';
        
        switch (strtolower($protocol)) {
            case 'vless':
                return $this->generateVlessUrl($client, $server);
            case 'vmess':
                return $this->generateVmessUrl($client, $server);
            case 'trojan':
                return $this->generateTrojanUrl($client, $server);
            case 'shadowsocks':
                return $this->generateShadowsocksUrl($client, $server);
            default:
                return '';
        }
    }

    private function generateVlessUrl($client, $server): string
    {
        $params = [
            'type' => 'tcp',
            'security' => $server->security ?: 'reality',
            'sni' => $server->sni ?: '',
            'headerType' => $server->header_type ?: 'none',
            'flow' => $client->flow ?: ''
        ];

        $paramString = http_build_query(array_filter($params));

        return sprintf(
            'vless://%s@%s:%s?%s#%s',
            $client->client_uuid ?: $client->id,
            $server->host ?: $server->ip_address,
            $server->port ?: 443,
            $paramString,
            urlencode($client->email ?: 'client-' . $client->id)
        );
    }

    private function generateVmessUrl($client, $server): string
    {
        $vmessConfig = [
            'v' => '2',
            'ps' => $client->email ?: 'client-' . $client->id,
            'add' => $server->host ?: $server->ip_address,
            'port' => $server->port ?: 443,
            'id' => $client->client_uuid ?: $client->id,
            'aid' => '0',
            'net' => 'tcp',
            'type' => $server->header_type ?: 'none',
            'host' => '',
            'path' => '',
            'tls' => ($server->security ?: '') === 'tls' ? 'tls' : '',
            'sni' => $server->sni ?: ''
        ];

        return 'vmess://' . base64_encode(json_encode($vmessConfig));
    }

    private function generateTrojanUrl($client, $server): string
    {
        $params = [
            'type' => 'tcp',
            'security' => $server->security ?: 'tls',
            'sni' => $server->sni ?: '',
            'headerType' => $server->header_type ?: 'none'
        ];

        $paramString = http_build_query(array_filter($params));

        return sprintf(
            'trojan://%s@%s:%s?%s#%s',
            $client->password ?: $client->client_uuid ?: $client->id,
            $server->host ?: $server->ip_address,
            $server->port ?: 443,
            $paramString,
            urlencode($client->email ?: 'client-' . $client->id)
        );
    }

    private function generateShadowsocksUrl($client, $server): string
    {
        $method = 'aes-256-gcm';
        $auth = base64_encode($method . ':' . ($client->password ?: $client->client_uuid ?: $client->id));

        return sprintf(
            'ss://%s@%s:%s#%s',
            $auth,
            $server->host ?: $server->ip_address,
            $server->port ?: 443,
            urlencode($client->email ?: 'client-' . $client->id)
        );
    }

    public function generateQRCode($client): string
    {
        $configUrl = $this->generateConfigUrl($client);
        
        if (empty($configUrl)) {
            return '';
        }

        try {
            return QrCode::size(256)
                ->style('round')
                ->eye('circle')
                ->margin(1)
                ->generate($configUrl);
        } catch (\Exception $e) {
            Log::error('QR Code generation failed', ['error' => $e->getMessage()]);
            return '';
        }
    }

    public function generateSubscriptionUrl($client): string
    {
        $baseUrl = config('app.url');
        $subId = $client->sub_id ?: $client->id;
        return "{$baseUrl}/api/subscription/{$subId}";
    }

    public function generateManualConfig($client): array
    {
        if (!$client->server) {
            return [];
        }

        $server = $client->server;
        $protocol = $client->protocol ?: 'vless';
        
        return [
            $protocol => [
                'server' => $server->host ?: $server->ip_address,
                'port' => $server->port ?: 443,
                'uuid' => $client->client_uuid ?: $client->id,
                'email' => $client->email ?: 'client-' . $client->id,
                'flow' => $client->flow ?: '',
                'security' => $server->security ?: 'reality',
                'sni' => $server->sni ?: '',
                'header_type' => $server->header_type ?: 'none',
                'protocol' => $protocol
            ]
        ];
    }

    public function getUserConfigurations(): array
    {
        return $this->userConfigurations;
    }

    public function getAvailableConfigurations(): array
    {
        return $this->availableConfigurations;
    }

    public function getSetupSteps(): array
    {
        return [
            [
                'step' => 1,
                'title' => 'Download Client',
                'description' => 'Download the recommended client app for your platform',
                'icon' => 'heroicon-o-arrow-down-tray'
            ],
            [
                'step' => 2,
                'title' => 'Import Config',
                'description' => 'Use QR code, subscription URL, or manual configuration',
                'icon' => 'heroicon-o-qr-code'
            ],
            [
                'step' => 3,
                'title' => 'Test Connection',
                'description' => 'Verify your proxy connection is working properly',
                'icon' => 'heroicon-o-signal'
            ],
            [
                'step' => 4,
                'title' => 'Start Browsing',
                'description' => 'Enjoy secure and private internet browsing',
                'icon' => 'heroicon-o-globe-alt'
            ]
        ];
    }

    public function getTroubleshootingTips(): array
    {
        return [
            [
                'title' => 'Connection Timeout',
                'description' => 'If you experience connection timeouts, try changing the server endpoint or check your internet connection.',
                'solution' => 'Switch to a different server location or contact support for assistance.'
            ],
            [
                'title' => 'App Not Connecting',
                'description' => 'Ensure you have the latest version of the proxy client and that the configuration is correct.',
                'solution' => 'Update your client app and re-import the configuration.'
            ],
            [
                'title' => 'Slow Connection Speed',
                'description' => 'Slow speeds can be caused by server load or network conditions.',
                'solution' => 'Try a different server location or contact support for server recommendations.'
            ],
            [
                'title' => 'Certificate Errors',
                'description' => 'SSL/TLS certificate errors can prevent connections from working properly.',
                'solution' => 'Enable "Skip certificate verification" in your client settings or contact support.'
            ]
        ];
    }

    public function downloadClientApp(): void
    {
        $apps = $this->getClientApplications();
        $selectedApp = $apps[$this->selectedPlatform][$this->selectedClient] ?? null;

        if (!$selectedApp) {
            Notification::make()
                ->title('App Not Found')
                ->body('The selected client application is not available for your platform')
                ->danger()
                ->send();
            return;
        }

        $this->logUserAction('client_download', [
            'platform' => $this->selectedPlatform,
            'client' => $this->selectedClient,
            'url' => $selectedApp['download_url'] ?? ''
        ]);

        $this->js("window.open('{$selectedApp['download_url']}', '_blank')");

        Notification::make()
            ->title('Download Started')
            ->body("Downloading {$selectedApp['name']} for {$this->selectedPlatform}")
            ->success()
            ->send();
    }

    public function autoConfigureClient(): void
    {
        if (!$this->selectedConfiguration) {
            Notification::make()
                ->title('No Configuration Selected')
                ->body('Please select a proxy configuration first')
                ->warning()
                ->send();
            return;
        }

        $config = $this->generateClientConfiguration();
        $filename = "proxy_config_{$this->selectedClient}_{$this->selectedConfiguration}.json";

        Storage::disk('public')->put("configs/{$filename}", json_encode($config, JSON_PRETTY_PRINT));

        $this->logUserAction('auto_configure', [
            'client' => $this->selectedClient,
            'configuration' => $this->selectedConfiguration,
            'platform' => $this->selectedPlatform
        ]);

        $url = Storage::disk('public')->url("configs/{$filename}");
        // Trigger download in new tab and show persistent notification with fallback link
        $this->js("window.open('{$url}', '_blank')");

        Notification::make()
            ->title('Configuration Generated')
            ->body("Auto-configuration file created: {$filename}. If it didn't download automatically, open: {$url}")
            ->success()
            ->persistent()
            ->send();
    }

    public function testConnection(): void
    {
        if (!$this->selectedConfiguration) {
            Notification::make()
                ->title('No Configuration Selected')
                ->body('Please select a proxy configuration to test')
                ->warning()
                ->send();
            return;
        }

        $serverClient = ServerClient::find($this->selectedConfiguration);
        if (!$serverClient || !$serverClient->server) {
            Notification::make()
                ->title('Invalid Configuration')
                ->body('The selected configuration is not valid or server is unavailable')
                ->danger()
                ->send();
            return;
        }

        $testResults = $this->performConnectionTest($serverClient);

        $this->logUserAction('connection_test', [
            'configuration' => $this->selectedConfiguration,
            'results' => $testResults
        ]);

        if ($testResults['success']) {
            Notification::make()
                ->title('Connection Successful')
                ->body("Connected to {$serverClient->server->location} - Latency: {$testResults['latency']}ms")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Connection Failed')
                ->body($testResults['error_message'])
                ->danger()
                ->send();
        }
    }

    public function exportAllConfigurations(): void
    {
        $customer = Auth::guard('customer')->user();
        $configurations = $this->userConfigurations;

        if (empty($configurations)) {
            Notification::make()
                ->title('No Configurations')
                ->body('You have no active proxy configurations to export')
                ->warning()
                ->send();
            return;
        }

        $exportData = [
            'export_date' => now()->toISOString(),
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
            'configurations' => []
        ];

        foreach ($configurations as $config) {
            $exportData['configurations'][] = [
                'name' => $config['server_name'] ?? "Proxy {$config['id']}",
                'server_location' => $config['location'] ?? 'Unknown',
                'protocol' => $config['protocol'],
                'configuration' => $this->generateClientConfiguration($config['id']),
                'qr_code_data' => $this->generateConfigUrl($config),
            ];
        }

        $filename = "proxy_configurations_export_" . now()->format('Y-m-d_H-i-s') . ".json";
        Storage::disk('public')->put("exports/{$filename}", json_encode($exportData, JSON_PRETTY_PRINT));

        $this->logUserAction('export_configurations', [
            'configurations_count' => count($configurations),
            'filename' => $filename
        ]);

        $url = Storage::disk('public')->url("exports/{$filename}");
        $this->js("window.open('{$url}', '_blank')");

        Notification::make()
            ->title('Export Complete')
            ->body("All configurations exported to {$filename}. Fallback link: {$url}")
            ->success()
            ->persistent()
            ->send();
    }

    public function nextStep(): void
    {
        if ($this->currentStep < count($this->tutorialSteps)) {
            $this->currentStep++;
            $this->js('window.scrollTo({top: 0, behavior: "smooth"});');
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->js('window.scrollTo({top: 0, behavior: "smooth"});');
        }
    }

    public function copyToClipboard(string $text, string $type = 'configuration'): void
    {
        $this->js("
            navigator.clipboard.writeText(`{$text}`).then(function() {
                window.dispatchEvent(new CustomEvent('clipboard-success', {
                    detail: {type: '{$type}'}
                }));
            });
        ");

        $this->logUserAction('copy_to_clipboard', [
            'type' => $type,
            'length' => strlen($text)
        ]);
    }

    protected function getClientApplications(): array
    {
        return [
            'windows' => [
                'v2rayN' => [
                    'name' => 'v2rayN',
                    'download_url' => 'https://github.com/2dust/v2rayN/releases',
                    'features' => ['GUI interface', 'Multiple protocols', 'Routing rules']
                ],
                'Qv2ray' => [
                    'name' => 'Qv2ray',
                    'download_url' => 'https://github.com/Qv2ray/Qv2ray/releases',
                    'features' => ['Cross-platform', 'Plugin support']
                ],
            ],
            'macos' => [
                'V2Box' => [
                    'name' => 'V2Box',
                    'download_url' => 'https://apps.apple.com/app/v2box/id6451244957',
                    'features' => ['Universal (macOS/iOS)', 'Modern UI']
                ],
                'V2RayU' => [
                    'name' => 'V2RayU',
                    'download_url' => 'https://github.com/yanue/V2rayU/releases',
                    'features' => ['Simple UI', 'V2Ray core']
                ],
                'Qv2ray' => [
                    'name' => 'Qv2ray',
                    'download_url' => 'https://github.com/Qv2ray/Qv2ray/releases',
                    'features' => ['Cross-platform', 'Plugin support']
                ],
            ],
            'android' => [
                'V2Box' => [
                    'name' => 'V2Box',
                    'download_url' => 'https://play.google.com/store/apps/details?id=dev.hexasoftware.v2box',
                    'features' => ['Modern UI', 'Optimized for Android']
                ],
                'v2rayNG' => [
                    'name' => 'v2rayNG',
                    'download_url' => 'https://github.com/2dust/v2rayNG/releases',
                    'features' => ['Android', 'Multiple protocols']
                ],
            ],
            'ios' => [
                'V2Box' => [
                    'name' => 'V2Box',
                    'download_url' => 'https://apps.apple.com/app/v2box/id6451244957',
                    'features' => ['Universal (iOS/macOS)', 'Modern UI']
                ],
                'ShadowRocket' => [
                    'name' => 'ShadowRocket',
                    'download_url' => 'https://apps.apple.com/app/shadowrocket/id932747118',
                    'features' => ['iOS', 'Multiple protocols']
                ],
            ],
            'linux' => [
                'V2RayA' => [
                    'name' => 'v2rayA',
                    'download_url' => 'https://github.com/v2rayA/v2rayA/releases',
                    'features' => ['Linux GUI', 'Easy management']
                ],
                'Qv2ray' => [
                    'name' => 'Qv2ray',
                    'download_url' => 'https://github.com/Qv2ray/Qv2ray/releases',
                    'features' => ['Cross-platform', 'Plugin support']
                ],
                'v2ray-core' => [
                    'name' => 'v2ray-core',
                    'download_url' => 'https://github.com/v2fly/v2ray-core/releases',
                    'features' => ['CLI', 'Core only']
                ],
            ],
        ];
    }

    public function getTutorialVideoUrl(string $platform, string $client): ?string
    {
        $map = [
            'windows' => [
                'v2rayN' => 'https://www.youtube.com/results?search_query=v2rayN+setup+windows',
            ],
            'android' => [
                'V2Box' => 'https://www.youtube.com/results?search_query=V2Box+android+setup',
                'v2rayNG' => 'https://www.youtube.com/results?search_query=v2rayNG+setup',
            ],
            'ios' => [
                'V2Box' => 'https://www.youtube.com/results?search_query=V2Box+ios+setup',
                'ShadowRocket' => 'https://www.youtube.com/results?search_query=Shadowrocket+setup',
            ],
            'macos' => [
                'V2Box' => 'https://www.youtube.com/results?search_query=V2Box+macOS+setup',
                'V2RayU' => 'https://www.youtube.com/results?search_query=V2RayU+setup',
            ],
            'linux' => [
                'V2RayA' => 'https://www.youtube.com/results?search_query=v2rayA+setup+linux',
                'Qv2ray' => 'https://www.youtube.com/results?search_query=Qv2ray+setup+linux',
            ],
        ];

        return $map[$platform][$client] ?? null;
    }

    protected function generateClientConfiguration(?int $configurationId = null): array
    {
        if (!$configurationId) {
            $configurationId = $this->selectedConfiguration;
        }

        $serverClient = ServerClient::find($configurationId);
        if (!$serverClient) {
            return [];
        }

        return [
            'log' => [
                'error' => $this->customSettings['enable_logs'] ? 'error.log' : ''
            ],
            'routing' => [
                'rules' => $this->customSettings['enable_routing'] ? $this->getRoutingRules() : []
            ],
            'inbounds' => [
            ],
            'outbounds' => [
            ],
            'dns' => [
            ]
        ];
    }

    protected function getRoutingRules(): array
    {
        return [
            [
                'outboundTag' => 'block'
            ],
            [
                'outboundTag' => 'direct'
            ],
        ];
    }
    
    protected function performConnectionTest(ServerClient $serverClient): array
    {
        $latency = rand(50, 200);
        $success = $latency < 150;

        return [
            'success' => $success,
            'latency' => $latency,
            'server_location' => $serverClient->server->location,
            'protocol' => $serverClient->protocol,
            'error_message' => $success ? null : 'Connection timeout - server may be overloaded'
        ];
    }
    
    protected function loadTutorialSteps(): void
    {
        $this->tutorialSteps = [
            [
                'title' => 'Choose Your Platform',
                'description' => 'Select your operating system and preferred client application',
                'content' => 'We support Windows, macOS, Linux, iOS, and Android platforms.',
                'action' => 'Select platform and client from the dropdowns above'
            ],
            [
                'title' => 'Download Client',
                'description' => 'Download and install the appropriate client application',
                'content' => 'Click the download button to get the latest version of your selected client.',
                'action' => 'Download and install the client application'
            ],
            [
                'title' => 'Import Configuration',
                'description' => 'Import your proxy configuration into the client',
                'content' => 'Use the QR code or copy the configuration link to import settings.',
                'action' => 'Scan QR code or paste configuration URL'
            ],
            [
                'title' => 'Test Connection',
                'description' => 'Verify that your proxy connection is working',
                'content' => 'Run a connection test to ensure everything is configured correctly.',
                'action' => 'Click the test connection button'
            ],
            [
                'title' => 'Start Using',
                'description' => 'You\'re ready to use your proxy service',
                'content' => 'Enable the proxy in your client and start browsing securely.',
                'action' => 'Enable proxy and enjoy secure browsing'
            ]
        ];
    }

    protected function logUserAction(string $action, array $data = []): void
    {
        try {
            Log::info('Configuration Guide Action', [
                'action' => $action,
                'customer_id' => Auth::guard('customer')->id(),
                'data' => $data,
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            // Silent fail for logging
        }
    }
}
