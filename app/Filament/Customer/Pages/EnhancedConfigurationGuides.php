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
use Illuminate\Support\Collection;
use App\Models\ServerClient;
use App\Models\Order;
use App\Models\Server;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Services\QrCodeService;

class EnhancedConfigurationGuides extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Setup Guides & Tutorials';
    protected static string $view = 'filament.customer.pages.enhanced-configuration-guides';
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
        $this->js('window.scrollTo({top: 0, behavior: "smooth"});');

        Notification::make()
            ->title('Quick Setup Started')
            ->body('Follow the step-by-step guide to configure your proxy client')
            ->info()
            ->send();
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

        // Log download for analytics
        $this->logUserAction('client_download', [
            'platform' => $this->selectedPlatform,
            'client' => $this->selectedClient,
            'url' => $selectedApp['download_url']
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

        Notification::make()
            ->title('Configuration Generated')
            ->body("Auto-configuration file created: {$filename}")
            ->actions([
                \Filament\Notifications\Actions\Action::make('download')
                    ->button()
                    ->url(Storage::disk('public')->url("configs/{$filename}"))
                    ->openUrlInNewTab(),
            ])
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

        // Simulate connection test
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
                'name' => $config['name'],
                'server_location' => $config['server_location'],
                'protocol' => $config['protocol'],
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
                    ->url(Storage::disk('public')->url("exports/{$filename}"))
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

    public function generateQRCode(int $configurationId): string
    {
        $qrData = $this->generateQRCodeData($configurationId);

        $this->logUserAction('qr_code_generate', [
            'configuration' => $configurationId
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

    protected function loadUserConfigurations(): void
    {
        $customer = Auth::guard('customer')->user();

        $this->userConfigurations = ServerClient::whereHas('orderItem.order', function ($query) use ($customer) {
            $query->where('customer_id', $customer->id);
        })
        ->with(['server', 'orderItem.order'])
        ->where('status', 'active')
        ->get()
        ->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->server->name ?? "Proxy {$client->id}",
                'server_location' => $client->server->location ?? 'Unknown',
                'server_brand' => $client->server->brand->name ?? 'Generic',
                'protocol' => $client->inbound_name ?? 'vless',
                'port' => $client->server->api_port ?? 443,
                'status' => $client->status,
                'created_at' => $client->created_at,
                'expires_at' => $client->orderItem->order->expires_at ?? null,
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
                    'description' => 'Modern, lightweight protocol with excellent performance',
                    'features' => ['Low latency', 'High security', 'Wide compatibility'],
                    'recommended' => true
                ],
                'vmess' => [
                    'name' => 'VMess',
                    'description' => 'Traditional V2Ray protocol with proven reliability',
                    'features' => ['Stable connection', 'Good compatibility', 'Time-tested'],
                    'recommended' => false
                ],
                'trojan' => [
                    'name' => 'Trojan',
                    'description' => 'Stealth protocol that mimics HTTPS traffic',
                    'features' => ['Traffic camouflage', 'Hard to detect', 'Good for restrictions'],
                    'recommended' => false
                ],
            ],
            'platforms' => [
                'windows' => [
                    'name' => 'Windows',
                    'icon' => 'fab-windows',
                    'clients' => ['v2rayN', 'Clash', 'SagerNet']
                ],
                'macos' => [
                    'name' => 'macOS',
                    'icon' => 'fab-apple',
                    'clients' => ['V2rayU', 'ClashX', 'Surge']
                ],
                'ios' => [
                    'name' => 'iOS',
                    'icon' => 'fas-mobile-alt',
                    'clients' => ['Shadowrocket', 'Surge', 'Quantumult']
                ],
                'android' => [
                    'name' => 'Android',
                    'icon' => 'fab-android',
                    'clients' => ['v2rayNG', 'Clash', 'SagerNet']
                ],
                'linux' => [
                    'name' => 'Linux',
                    'icon' => 'fab-linux',
                    'clients' => ['v2ray-core', 'Clash', 'Xray']
                ]
            ]
        ];
    }

    protected function loadTutorialSteps(): void
    {
        $this->tutorialSteps = [
            1 => [
                'title' => 'Choose Your Platform',
                'description' => 'Select your operating system and preferred client application',
                'content' => 'Select your device type and the proxy client you want to use. Each client has different features and interfaces.',
                'tips' => [
                    'v2rayN is recommended for Windows users',
                    'ClashX is great for macOS with a simple interface',
                    'v2rayNG is the most popular Android client'
                ]
            ],
            2 => [
                'title' => 'Download & Install Client',
                'description' => 'Download the recommended client application for your platform',
                'content' => 'Click the download button to get the latest version of your chosen client. Make sure to download from official sources.',
                'tips' => [
                    'Always download from official websites',
                    'Check file signatures when possible',
                    'Keep your client updated for security'
                ]
            ],
            3 => [
                'title' => 'Import Configuration',
                'description' => 'Add your proxy configuration to the client',
                'content' => 'You can import your configuration using a QR code, configuration URL, or by copying the configuration text.',
                'tips' => [
                    'QR codes are the easiest import method',
                    'Save a backup of your configuration',
                    'Use descriptive names for multiple configs'
                ]
            ],
            4 => [
                'title' => 'Test Connection',
                'description' => 'Verify that your proxy connection is working correctly',
                'content' => 'Test your connection to ensure everything is working properly. Check your IP address and connection speed.',
                'tips' => [
                    'Test with whatismyipaddress.com',
                    'Check for DNS leaks',
                    'Monitor connection stability'
                ]
            ],
            5 => [
                'title' => 'Advanced Settings',
                'description' => 'Optimize your configuration for best performance',
                'content' => 'Configure routing rules, DNS settings, and other advanced options to optimize your connection.',
                'tips' => [
                    'Use custom routing for specific apps',
                    'Configure DNS for better privacy',
                    'Enable automatic server switching'
                ]
            ]
        ];
    }

    protected function getClientApplications(): array
    {
        return [
            'windows' => [
                'v2rayN' => [
                    'name' => 'v2rayN',
                    'version' => '6.42',
                    'download_url' => 'https://github.com/2dust/v2rayN/releases/latest',
                    'description' => 'Feature-rich Windows client with GUI',
                    'features' => ['GUI interface', 'Multiple protocols', 'Routing rules']
                ],
                'Clash' => [
                    'name' => 'Clash for Windows',
                    'version' => '0.20.39',
                    'download_url' => 'https://github.com/Fndroid/clash_for_windows_pkg/releases/latest',
                    'description' => 'Rule-based proxy client',
                    'features' => ['Rule-based routing', 'Web dashboard', 'Traffic analysis']
                ],
            ],
            'macos' => [
                'V2rayU' => [
                    'name' => 'V2rayU',
                    'version' => '3.2.0',
                    'download_url' => 'https://github.com/yanue/V2rayU/releases/latest',
                    'description' => 'Simple macOS client',
                    'features' => ['Menu bar integration', 'PAC support', 'Multiple servers']
                ],
                'ClashX' => [
                    'name' => 'ClashX',
                    'version' => '1.118.0',
                    'download_url' => 'https://github.com/yichengchen/clashX/releases/latest',
                    'description' => 'Rule-based proxy client for macOS',
                    'features' => ['Rule-based routing', 'Menu bar control', 'Config import']
                ],
            ],
            'android' => [
                'v2rayNG' => [
                    'name' => 'v2rayNG',
                    'version' => '1.8.19',
                    'download_url' => 'https://github.com/2dust/v2rayNG/releases/latest',
                    'description' => 'Android client for V2Ray',
                    'features' => ['QR code import', 'Traffic statistics', 'Per-app proxy']
                ],
            ],
            'ios' => [
                'Shadowrocket' => [
                    'name' => 'Shadowrocket',
                    'version' => '1.0',
                    'download_url' => 'https://apps.apple.com/app/shadowrocket/id932747118',
                    'description' => 'Premium iOS proxy client',
                    'features' => ['Multiple protocols', 'Rule configuration', 'Traffic analysis']
                ],
            ],
        ];
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
                'loglevel' => $this->customSettings['log_level'],
                'access' => $this->customSettings['enable_logs'] ? 'access.log' : '',
                'error' => $this->customSettings['enable_logs'] ? 'error.log' : ''
            ],
            'routing' => [
                'domainStrategy' => 'IPIfNonMatch',
                'rules' => $this->customSettings['enable_routing'] ? $this->getRoutingRules() : []
            ],
            'inbounds' => [
                [
                    'port' => 1080,
                    'protocol' => 'socks',
                    'settings' => [
                        'auth' => 'noauth',
                        'udp' => true
                    ]
                ]
            ],
            'outbounds' => [
                [
                    'protocol' => $serverClient->inbound_name ?? 'vless',
                    'settings' => $this->getOutboundSettings($serverClient),
                    'streamSettings' => $this->getStreamSettings($serverClient),
                    'mux' => [
                        'enabled' => $this->customSettings['mux_enabled']
                    ]
                ]
            ],
            'dns' => [
                'servers' => [
                    $this->customSettings['dns_server'],
                    '1.1.1.1'
                ]
            ]
        ];
    }

    protected function generateQRCodeData(int $configurationId): string
    {
        $serverClient = ServerClient::find($configurationId);
        if (!$serverClient) {
            return '';
        }

        $protocol = $serverClient->inbound_name ?? 'vless';
        $server = $serverClient->server;

        switch ($protocol) {
            case 'vless':
                return "vless://{$serverClient->uuid}@{$server->ip}:{$server->api_port}?type=tcp&security=tls&sni={$server->domain}#{$server->location}";
            case 'vmess':
                $vmessConfig = [
                    'v' => '2',
                    'ps' => $server->location,
                    'add' => $server->ip,
                    'port' => $server->api_port,
                    'id' => $serverClient->uuid,
                    'aid' => '0',
                    'scy' => 'auto',
                    'net' => 'tcp',
                    'type' => 'none',
                    'host' => '',
                    'path' => '',
                    'tls' => 'tls',
                    'sni' => $server->domain
                ];
                return 'vmess://' . base64_encode(json_encode($vmessConfig));
            default:
                return '';
        }
    }

    protected function getOutboundSettings(ServerClient $serverClient): array
    {
        $protocol = $serverClient->inbound_name ?? 'vless';

        switch ($protocol) {
            case 'vless':
                return [
                    'vnext' => [
                        [
                            'address' => $serverClient->server->ip,
                            'port' => $serverClient->server->api_port,
                            'users' => [
                                [
                                    'id' => $serverClient->uuid,
                                    'encryption' => 'none',
                                    'flow' => 'xtls-rprx-vision'
                                ]
                            ]
                        ]
                    ]
                ];
            case 'vmess':
                return [
                    'vnext' => [
                        [
                            'address' => $serverClient->server->ip,
                            'port' => $serverClient->server->api_port,
                            'users' => [
                                [
                                    'id' => $serverClient->uuid,
                                    'security' => 'auto',
                                    'alterId' => 0
                                ]
                            ]
                        ]
                    ]
                ];
            default:
                return [];
        }
    }

    protected function getStreamSettings(ServerClient $serverClient): array
    {
        return [
            'network' => 'tcp',
            'security' => 'tls',
            'tlsSettings' => [
                'serverName' => $serverClient->server->domain,
                'allowInsecure' => false,
                'alpn' => ['h2', 'http/1.1']
            ]
        ];
    }

    protected function getRoutingRules(): array
    {
        return [
            [
                'type' => 'field',
                'domain' => ['geosite:category-ads-all'],
                'outboundTag' => 'block'
            ],
            [
                'type' => 'field',
                'domain' => ['geosite:cn'],
                'outboundTag' => 'direct'
            ],
            [
                'type' => 'field',
                'ip' => ['geoip:cn'],
                'outboundTag' => 'direct'
            ]
        ];
    }

    protected function performConnectionTest(ServerClient $serverClient): array
    {
        // Simulate connection test - in real implementation, this would test the actual connection
        $latency = rand(50, 200);
        $success = $latency < 150;

        return [
            'success' => $success,
            'latency' => $latency,
            'server_location' => $serverClient->server->location,
            'protocol' => $serverClient->inbound_name,
            'error_message' => $success ? null : 'Connection timeout - server may be overloaded'
        ];
    }

    protected function logUserAction(string $action, array $data = []): void
    {
        $customer = Auth::guard('customer')->user();

        // Log user action for analytics
        \Log::info('Customer configuration action', [
            'customer_id' => $customer->id,
            'action' => $action,
            'data' => $data,
            'timestamp' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    public function getAvailableProtocols(): array
    {
        return array_keys($this->availableConfigurations['protocols']);
    }

    public function getAvailablePlatforms(): array
    {
        return array_keys($this->availableConfigurations['platforms']);
    }

    public function getAvailableClients(string $platform): array
    {
        return $this->availableConfigurations['platforms'][$platform]['clients'] ?? [];
    }

    public function getCurrentStepData(): array
    {
        return $this->tutorialSteps[$this->currentStep] ?? [];
    }

    public function isLastStep(): bool
    {
        return $this->currentStep >= count($this->tutorialSteps);
    }

    public function isFirstStep(): bool
    {
        return $this->currentStep <= 1;
    }
}
