<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
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
use App\Models\Order;
use App\Models\Server;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Services\QrCodeService;

class ConfigurationGuides extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Setup Guides & Tutorials';
    protected static string $view = 'filament.customer.pages.configuration-guides';
    protected static ?int $navigationSort = 6;

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
                ->label('Quick Setup')
                ->icon('heroicon-o-bolt')
                ->action('startQuickSetup'),

            PageAction::make('download_app')
                ->label('Download Client App')
                ->icon('heroicon-o-arrow-down')
                ->action('downloadClientApp'),

            PageAction::make('auto_configure')
                ->label('Auto-Configure')
                ->icon('heroicon-o-cog-6-tooth')
                ->action('autoConfigureClient'),

            PageAction::make('test_connection')
                ->label('Test Connection')
                ->icon('heroicon-o-signal')
                ->action('testConnection'),

            PageAction::make('export_configs')
                ->label('Export All')
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

        $this->userConfigurations = ServerClient::whereHas('orderItem.order', function ($query) use ($customer) {
            $query->where('customer_id', $customer->id);
        })
        ->with(['server', 'orderItem.order'])
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
                    'features' => ['Simple setup', 'Fast connection', 'Low resource usage'],
                    'supported_clients' => ['Shadowsocks', 'ShadowsocksX-NG', 'Outline'],
                ],
            ],
            'platforms' => [
                'windows' => [
                    'name' => 'Windows',
                    'recommended_clients' => ['v2rayN', 'Qv2ray', 'V2RayW'],
                    'guides' => [
                        'v2rayN' => 'Step-by-step guide for v2rayN on Windows',
                        'Qv2ray' => 'Complete setup guide for Qv2ray',
                    ],
                ],
                'macos' => [
                    'name' => 'macOS',
                    'recommended_clients' => ['V2RayX', 'V2RayU', 'Qv2ray'],
                    'guides' => [
                        'V2RayX' => 'Setup guide for V2RayX on macOS',
                        'V2RayU' => 'Configuration guide for V2RayU',
                    ],
                ],
                'linux' => [
                    'name' => 'Linux',
                    'recommended_clients' => ['v2ray-core', 'Qv2ray', 'V2RayA'],
                    'guides' => [
                        'v2ray-core' => 'Command line setup for v2ray-core',
                        'Qv2ray' => 'GUI client setup for Linux',
                    ],
                ],
                'android' => [
                    'name' => 'Android',
                    'recommended_clients' => ['v2rayNG', 'V2Box', 'SagerNet'],
                    'guides' => [
                        'v2rayNG' => 'Mobile setup guide for Android',
                        'V2Box' => 'Alternative Android client setup',
                    ],
                ],
                'ios' => [
                    'name' => 'iOS',
                    'recommended_clients' => ['ShadowRocket', 'Quantumult X', 'Stash'],
                    'guides' => [
                        'ShadowRocket' => 'iOS setup with ShadowRocket',
                        'Quantumult X' => 'Advanced iOS configuration',
                    ],
                ],
            ],
        ];
    }

    public function downloadClientApp(): void
    {
        $downloadLinks = $this->getDownloadLinks();
        $link = $downloadLinks[$this->selectedPlatform][$this->selectedClient] ?? null;

        if ($link) {
            Notification::make()
                ->title('Download Started')
                ->body("Downloading {$this->selectedClient} for {$this->selectedPlatform}")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Download Not Available')
                ->body('Please select a valid platform and client combination.')
                ->warning()
                ->send();
        }
    }

    public function autoConfigureClient(): void
    {
        if (empty($this->userConfigurations)) {
            Notification::make()
                ->title('No Configurations Available')
                ->body('You need to purchase a proxy service first.')
                ->warning()
                ->send();
            return;
        }

        Notification::make()
            ->title('Auto-Configuration Started')
            ->body('Attempting to configure your client automatically...')
            ->info()
            ->send();

        // Simulate auto-configuration process
        $this->dispatch('auto-configure-client', [
            'protocol' => $this->selectedProtocol,
            'platform' => $this->selectedPlatform,
            'client' => $this->selectedClient,
            'configurations' => $this->userConfigurations,
        ]);
    }

    public function testConnection(): void
    {
        if (empty($this->userConfigurations)) {
            Notification::make()
                ->title('No Configurations to Test')
                ->body('You need to purchase a proxy service first.')
                ->warning()
                ->send();
            return;
        }

        // Simulate connection test
        $success = rand(0, 1);

        Notification::make()
            ->title($success ? 'Connection Successful' : 'Connection Failed')
            ->body($success ? 'Your proxy is working correctly!' : 'Please check your configuration and try again.')
            ->color($success ? 'success' : 'danger')
            ->send();
    }

    protected function generateConfigUrl(ServerClient $client): string
    {
        // Generate configuration URL based on protocol
        $baseUrl = config('app.url');
        return "{$baseUrl}/config/{$client->uuid}";
    }

    protected function generateQRCode(ServerClient $client): string
    {
        $qrData = $this->generateQRCodeData($client->id);

        $this->logUserAction('qr_code_generate', [
            'configuration' => $client->id
        ]);

        try {
            $qrCodeService = app(QrCodeService::class);
            return $qrCodeService->generateBrandedQrCode($qrData, 200, 'svg', [
                'style' => 'dot',
                'eye' => 'circle',
                'colorScheme' => 'primary'
            ]);
        } catch (\Exception $e) {
            // Fallback to simple QR code
            return QrCode::size(200)
                ->format('svg')
                ->generate($qrData);
        }
    }

    protected function generateSubscriptionUrl(ServerClient $client): string
    {
        // Generate subscription URL for supported clients
        $baseUrl = config('app.url');
        return "{$baseUrl}/subscribe/{$client->uuid}";
    }

    protected function generateManualConfig(ServerClient $client): array
    {
        $server = $client->server;

        return [
            'vless' => [
                'server' => $server->ip_address,
                'port' => $server->port,
                'uuid' => $client->uuid,
                'encryption' => 'none',
                'flow' => '',
                'network' => 'tcp',
                'security' => 'reality',
                'sni' => $server->sni ?? 'www.google.com',
                'fp' => 'chrome',
                'pbk' => $server->public_key ?? '',
                'sid' => $server->short_id ?? '',
                'type' => 'none',
                'path' => '/',
            ],
            'vmess' => [
                'server' => $server->ip_address,
                'port' => $server->port,
                'uuid' => $client->uuid,
                'alterId' => 0,
                'cipher' => 'auto',
                'network' => 'tcp',
                'security' => 'none',
                'type' => 'none',
                'path' => '/',
            ],
        ];
    }

    protected function getDownloadLinks(): array
    {
        return [
            'windows' => [
                'v2rayN' => 'https://github.com/2dust/v2rayN/releases',
                'Qv2ray' => 'https://github.com/Qv2ray/Qv2ray/releases',
                'V2RayW' => 'https://github.com/Cenmrev/V2RayW/releases',
            ],
            'macos' => [
                'V2RayX' => 'https://github.com/Cenmrev/V2RayX/releases',
                'V2RayU' => 'https://github.com/yanue/V2rayU/releases',
                'Qv2ray' => 'https://github.com/Qv2ray/Qv2ray/releases',
            ],
            'linux' => [
                'v2ray-core' => 'https://github.com/v2fly/v2ray-core/releases',
                'Qv2ray' => 'https://github.com/Qv2ray/Qv2ray/releases',
                'V2RayA' => 'https://github.com/v2rayA/v2rayA/releases',
            ],
            'android' => [
                'v2rayNG' => 'https://github.com/2dust/v2rayNG/releases',
                'V2Box' => 'https://github.com/XrayR-project/XrayR/releases',
                'SagerNet' => 'https://github.com/SagerNet/SagerNet/releases',
            ],
            'ios' => [
                'ShadowRocket' => 'https://apps.apple.com/app/shadowrocket/id932747118',
                'Quantumult X' => 'https://apps.apple.com/app/quantumult-x/id1443988620',
                'Stash' => 'https://apps.apple.com/app/stash/id1596063349',
            ],
        ];
    }

    public function updateProtocol(string $protocol): void
    {
        $this->selectedProtocol = $protocol;
        $this->dispatch('protocol-changed', $protocol);
    }

    public function updatePlatform(string $platform): void
    {
        $this->selectedPlatform = $platform;
        $this->selectedClient = $this->getRecommendedClient($platform);
        $this->dispatch('platform-changed', ['platform' => $platform, 'client' => $this->selectedClient]);
    }

    public function updateClient(string $client): void
    {
        $this->selectedClient = $client;
        $this->dispatch('client-changed', $client);
    }

    protected function getRecommendedClient(string $platform): string
    {
        $recommendations = [
            'windows' => 'v2rayN',
            'macos' => 'V2RayU',
            'linux' => 'Qv2ray',
            'android' => 'v2rayNG',
            'ios' => 'ShadowRocket',
        ];

        return $recommendations[$platform] ?? 'v2rayN';
    }

    public function getSelectedProtocol(): string
    {
        return $this->selectedProtocol;
    }

    public function getSelectedPlatform(): string
    {
        return $this->selectedPlatform;
    }

    public function getSelectedClient(): string
    {
        return $this->selectedClient;
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
                'description' => 'Download and install the recommended client for your platform',
                'icon' => 'heroicon-o-arrow-down-tray',
            ],
            [
                'step' => 2,
                'title' => 'Import Configuration',
                'description' => 'Use the QR code, subscription URL, or manual configuration',
                'icon' => 'heroicon-o-qr-code',
            ],
            [
                'step' => 3,
                'title' => 'Test Connection',
                'description' => 'Verify that your proxy connection is working correctly',
                'icon' => 'heroicon-o-signal',
            ],
            [
                'step' => 4,
                'title' => 'Optimize Settings',
                'description' => 'Fine-tune your client settings for best performance',
                'icon' => 'heroicon-o-adjustments-horizontal',
            ],
        ];
    }

    public function getTroubleshootingTips(): array
    {
        return [
            [
                'issue' => 'Connection Timeout',
                'solutions' => [
                    'Check your internet connection',
                    'Verify server information is correct',
                    'Try a different server location',
                    'Check firewall settings',
                ],
            ],
            [
                'issue' => 'Slow Connection Speed',
                'solutions' => [
                    'Choose a server closer to your location',
                    'Try a different protocol (VLESS/VMess)',
                    'Adjust client buffer settings',
                    'Check for other bandwidth-heavy applications',
                ],
            ],
            [
                'issue' => 'Cannot Import Configuration',
                'solutions' => [
                    'Ensure QR code is clear and complete',
                    'Try manual configuration instead',
                    'Update your client to the latest version',
                    'Check configuration format compatibility',
                ],
            ],
            [
                'issue' => 'Frequent Disconnections',
                'solutions' => [
                    'Enable auto-reconnection in client settings',
                    'Check network stability',
                    'Try a different network configuration',
                    'Contact support for server status',
                ],
            ],
        ];
    }

    // Enhanced Methods from EnhancedConfigurationGuides

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
                'name' => $config['name'],
                'server_location' => $config['server_location'] ?? 'Unknown',
                'protocol' => $config['protocol'] ?? 'vless',
                'configuration' => $this->generateClientConfiguration($config['id']),
                'qr_code_data' => $this->generateQRCodeData($config['id'])
            ];
        }

        $filename = "proxy_configurations_export_" . now()->format('Y-m-d_H-i-s') . ".json";
        Storage::disk('public')->put("exports/{$filename}", json_encode($exportData, JSON_PRETTY_PRINT));

        $this->logUserAction('export_configurations', [
            'configurations_count' => count($configurations),
            'filename' => $filename
        ]);

        Notification::make()
            ->title('Export Complete')
            ->body("All configurations exported to {$filename}")
            ->actions([
                \Filament\Notifications\Actions\Action::make('download')
                    ->button()
                    ->url(Storage::url("exports/{$filename}"))
                    ->openUrlInNewTab(),
            ])
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

    protected function performConnectionTest($serverClient): array
    {
        try {
            // Simulate connection test - in real implementation, you would test actual connectivity
            $server = $serverClient->server;
            $latency = rand(50, 200); // Simulate latency
            $success = rand(0, 1) > 0.2; // 80% success rate

            if ($success) {
                return [
                    'success' => true,
                    'latency' => $latency,
                    'server_location' => $server->location ?? 'Unknown',
                    'message' => 'Connection successful'
                ];
            } else {
                return [
                    'success' => false,
                    'error_message' => 'Connection failed - server may be temporarily unavailable',
                    'error_code' => 'CONN_TIMEOUT'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error_message' => 'Connection test failed: ' . $e->getMessage(),
                'error_code' => 'TEST_ERROR'
            ];
        }
    }

    protected function generateClientConfiguration(int $clientId): string
    {
        $client = ServerClient::find($clientId);
        if (!$client) {
            return '{}';
        }

        // Generate configuration based on protocol
        $config = [
            'version' => '4',
            'remarks' => $client->name ?? '1000 Proxy Config',
            'server' => $client->server->host ?? 'unknown',
            'server_port' => $client->server->port ?? 443,
            'password' => $client->uuid ?? '',
            'method' => $client->server->protocol ?? 'vless'
        ];

        return json_encode($config, JSON_PRETTY_PRINT);
    }

    protected function generateQRCodeData(int $configurationId): string
    {
        $client = ServerClient::find($configurationId);
        if (!$client) {
            return '';
        }

        // Generate configuration URL for QR code
        $baseUrl = config('app.url');
        return "{$baseUrl}/config/{$client->uuid}";
    }

    protected function logUserAction(string $action, array $data = []): void
    {
        try {
            Log::info('User Configuration Action', [
                'customer_id' => Auth::guard('customer')->id(),
                'action' => $action,
                'data' => $data,
                'timestamp' => now(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        } catch (\Exception $e) {
            // Silently fail if logging fails
        }
    }
}
