<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement\Resources\ServerResource\Pages;
use App\Filament\Clusters\ServerManagement;
use App\Models\Server;
use App\Models\ServerBrand;
use App\Models\ServerCategory;
use App\Services\XUIService;
use Illuminate\Support\Facades\Log;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    protected static ?string $cluster = ServerManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = '🖥️ XUI Servers';

    protected static ?string $pluralModelLabel = 'XUI Servers';

    protected static ?string $modelLabel = 'XUI Server';

    public static function getLabel(): string
    {
        return 'XUI Servers';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('🔧 Basic Information')
                        ->description('Core server identification and categorization')
                        ->icon('heroicon-o-server')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->prefixIcon('heroicon-o-identification')
                                    ->placeholder('Enter server name')
                                    ->helperText('Server display name for identification'),

                                TextInput::make('country')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-map-pin')
                                    ->datalist([
                                        'United States', 'Germany', 'Netherlands', 'United Kingdom',
                                        'France', 'Canada', 'Japan', 'Singapore', 'Australia', 'Brazil'
                                    ])
                                    ->placeholder('Select or enter country')
                                    ->helperText('Server physical location'),
                            ]),

                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Select::make('server_category_id')
                                    ->label('Category')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->prefixIcon('heroicon-o-tag')
                                    ->placeholder('Select category')
                                    ->helperText('Server purpose category'),

                                Forms\Components\Select::make('server_brand_id')
                                    ->label('Brand')
                                    ->relationship('brand', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->prefixIcon('heroicon-o-building-office')
                                    ->placeholder('Select brand')
                                    ->helperText('Server provider brand'),
                            ]),

                            Textarea::make('description')
                                ->maxLength(1000)
                                ->rows(3)
                                ->placeholder('Enter server description and features')
                                ->helperText('Detailed server description and key features'),

                            Forms\Components\Grid::make(2)->schema([
                                TextInput::make('flag')
                                    ->maxLength(10)
                                    ->prefixIcon('heroicon-o-flag')
                                    ->placeholder('US, DE, UK')
                                    ->helperText('Country flag code (e.g., US, DE, UK)'),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'healthy' => '🟢 Healthy',
                                        'warning' => '🟡 Warning',
                                        'unhealthy' => '🔴 Unhealthy',
                                        'offline' => '⚫ Offline',
                                        'maintenance' => '🔧 Maintenance',
                                    ])
                                    ->default('healthy')
                                    ->prefixIcon('heroicon-o-heart')
                                    ->helperText('Current server operational status'),
                            ]),
                        ])->columns(1),

                    Section::make('🌐 Connection Settings')
                        ->description('Panel access and connectivity configuration')
                        ->icon('heroicon-o-link')
                        ->schema([
                            Forms\Components\Grid::make(3)->schema([
                                TextInput::make('host')
                                    ->label('Host/Hostname')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-server')
                                    ->placeholder('panel.example.com')
                                    ->helperText('Server hostname or domain'),

                                TextInput::make('ip')
                                    ->label('IP Address')
                                    ->maxLength(45)
                                    ->prefixIcon('heroicon-o-globe-alt')
                                    ->placeholder('192.168.1.100')
                                    ->rule('ip')
                                    ->helperText('Server IP address'),

                                TextInput::make('panel_url')
                                    ->label('Panel URL')
                                    ->url()
                                    ->prefixIcon('heroicon-o-link')
                                    ->placeholder('https://panel.example.com')
                                    ->helperText('Full URL to access the X-UI panel'),
                            ]),

                            Forms\Components\Grid::make(3)->schema([
                                TextInput::make('port')
                                    ->label('Main Port')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(65535)
                                    ->default(443)
                                    ->prefixIcon('heroicon-o-bolt')
                                    ->placeholder('443')
                                    ->helperText('Primary connection port'),

                                TextInput::make('panel_port')
                                    ->label('Panel Port')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(65535)
                                    ->default(54321)
                                    ->prefixIcon('heroicon-o-cog-6-tooth')
                                    ->placeholder('54321')
                                    ->helperText('X-UI panel access port'),

                                Forms\Components\Select::make('port_type')
                                    ->label('Port Type')
                                    ->options([
                                        'http' => '🌐 HTTP',
                                        'https' => '🔒 HTTPS',
                                        'tcp' => '📡 TCP',
                                        'udp' => '📊 UDP',
                                    ])
                                    ->default('https')
                                    ->prefixIcon('heroicon-o-shield-check'),
                            ]),

                            Forms\Components\Grid::make(2)->schema([
                                TextInput::make('username')
                                    ->label('Panel Username')
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-user')
                                    ->placeholder('admin')
                                    ->helperText('X-UI panel login username'),

                                TextInput::make('password')
                                    ->label('Panel Password')
                                    ->password()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-lock-closed')
                                    ->placeholder('Enter secure password')
                                    ->helperText('X-UI panel login password'),
                            ]),

                            TextInput::make('web_base_path')
                                ->label('Web Base Path')
                                ->maxLength(255)
                                ->default('/')
                                ->prefixIcon('heroicon-o-folder')
                                ->placeholder('/')
                                ->helperText('Panel base path (usually /)'),
                        ])->columns(1),

                    Section::make('🛡️ Advanced Configuration')
                        ->description('Protocol and security settings')
                        ->icon('heroicon-o-shield-check')
                        ->collapsible()
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Panel Type')
                                    ->options([
                                        'sanaei' => '⚡ X-RAY (3X-UI Sanaei)',
                                        'alireza' => '🔧 Alireza Panel',
                                        'marzban' => '🏗️ Marzban Panel',
                                        'other' => '🔗 Other Panel Type',
                                    ])
                                    ->default('sanaei')
                                    ->required()
                                    ->prefixIcon('heroicon-o-cog-6-tooth')
                                    ->helperText('Panel type determines API integration'),

                                Forms\Components\Select::make('security')
                                    ->label('Security Protocol')
                                    ->options([
                                        'tls' => '🔒 TLS',
                                        'reality' => '🛡️ Reality',
                                        'none' => '🔓 None',
                                    ])
                                    ->default('tls')
                                    ->prefixIcon('heroicon-o-shield-exclamation'),
                            ]),

                            Forms\Components\Grid::make(2)->schema([
                                TextInput::make('sni')
                                    ->label('SNI (Server Name Indication)')
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-identification')
                                    ->placeholder('example.com')
                                    ->helperText('SNI for TLS connections'),

                                Forms\Components\Select::make('header_type')
                                    ->label('Header Type')
                                    ->options([
                                        'none' => 'None',
                                        'http' => 'HTTP',
                                        'ws' => 'WebSocket',
                                        'grpc' => 'gRPC',
                                    ])
                                    ->default('none')
                                    ->prefixIcon('heroicon-o-document-text'),
                            ]),

                            Textarea::make('request_header')
                                ->label('Request Headers')
                                ->rows(3)
                                ->placeholder('{"Host": "example.com"}')
                                ->helperText('Custom request headers (JSON format)'),

                            Textarea::make('response_header')
                                ->label('Response Headers')
                                ->rows(3)
                                ->placeholder('{"Content-Type": "application/json"}')
                                ->helperText('Custom response headers (JSON format)'),

                            Forms\Components\Grid::make(2)->schema([
                                Textarea::make('reality')
                                    ->label('Reality Settings')
                                    ->rows(4)
                                    ->placeholder('{"dest": "google.com:443", "serverNames": ["google.com"]}')
                                    ->helperText('Reality protocol configuration (JSON format)'),

                                Textarea::make('tlsSettings')
                                    ->label('TLS Settings')
                                    ->rows(4)
                                    ->placeholder('{"serverName": "example.com", "certificates": []}')
                                    ->helperText('TLS configuration settings (JSON format)'),
                            ]),

                            Textarea::make('xui_config')
                                ->label('XUI Configuration')
                                ->rows(5)
                                ->placeholder('{"api_timeout": 30, "retry_count": 3}')
                                ->helperText('Additional XUI panel configuration (JSON format)'),

                            Textarea::make('connection_settings')
                                ->label('Connection Settings')
                                ->rows(4)
                                ->placeholder('{"keep_alive": true, "compression": false}')
                                ->helperText('Advanced connection settings (JSON format)'),
                        ])->columns(1),
                ])->columnSpan(2),

                Group::make()->schema([
                    Section::make('📊 Status & Monitoring')
                        ->description('Server operational status and metrics')
                        ->icon('heroicon-o-chart-bar')
                        ->schema([
                            TextInput::make('health_status')
                                ->label('Health Status')
                                ->maxLength(50)
                                ->default('unknown')
                                ->prefixIcon('heroicon-o-heart')
                                ->placeholder('healthy, warning, error')
                                ->helperText('Detailed health status'),

                            Textarea::make('health_message')
                                ->label('Health Message')
                                ->rows(2)
                                ->placeholder('Latest health check result')
                                ->helperText('Latest health check message'),

                            Forms\Components\Grid::make(1)->schema([
                                TextInput::make('total_clients')
                                    ->label('Total Clients')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefixIcon('heroicon-o-users')
                                    ->helperText('Total registered clients'),

                                TextInput::make('active_clients')
                                    ->label('Active Clients')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefixIcon('heroicon-o-user-group')
                                    ->helperText('Currently active connections'),

                                TextInput::make('total_traffic_mb')
                                    ->label('Total Traffic (MB)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefixIcon('heroicon-o-arrow-trending-up')
                                    ->helperText('Total data transfer in MB'),

                                TextInput::make('total_inbounds')
                                    ->label('Total Inbounds')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefixIcon('heroicon-o-inbox-stack')
                                    ->helperText('Number of inbound configurations'),

                                TextInput::make('active_inbounds')
                                    ->label('Active Inbounds')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefixIcon('heroicon-o-inbox')
                                    ->helperText('Currently active inbounds'),
                            ]),
                        ]),

                    Section::make('⚙️ Capacity & Limits')
                        ->description('Server capacity configuration')
                        ->icon('heroicon-o-scale')
                        ->schema([
                            TextInput::make('max_clients_per_inbound')
                                ->label('Max Clients per Inbound')
                                ->numeric()
                                ->default(100)
                                ->minValue(1)
                                ->maxValue(10000)
                                ->prefixIcon('heroicon-o-user-plus')
                                ->helperText('Client limit per inbound'),

                            TextInput::make('total_online_clients')
                                ->label('Total Online Clients')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->prefixIcon('heroicon-o-signal')
                                ->helperText('Real-time online client count'),
                        ]),

                    Section::make('🤖 Automation Settings')
                        ->description('Automated management configuration')
                        ->icon('heroicon-o-cog-8-tooth')
                        ->collapsible()
                        ->schema([
                            Toggle::make('auto_provisioning')
                                ->label('🚀 Auto Provisioning')
                                ->default(false)
                                ->helperText('Enable automatic client provisioning'),

                            Toggle::make('auto_sync_enabled')
                                ->label('🔄 Auto Sync')
                                ->default(true)
                                ->helperText('Enable automatic synchronization'),

                            Toggle::make('auto_cleanup_depleted')
                                ->label('🧹 Auto Cleanup Depleted')
                                ->default(false)
                                ->helperText('Automatically remove depleted clients'),

                            Toggle::make('backup_notifications_enabled')
                                ->label('📧 Backup Notifications')
                                ->default(true)
                                ->helperText('Enable backup notification emails'),

                            TextInput::make('sync_interval_minutes')
                                ->label('Sync Interval (Minutes)')
                                ->numeric()
                                ->default(30)
                                ->minValue(1)
                                ->maxValue(1440)
                                ->prefixIcon('heroicon-o-clock')
                                ->helperText('Synchronization interval in minutes'),
                        ]),

                    Section::make('🔌 API Configuration')
                        ->description('X-UI API settings and session management')
                        ->icon('heroicon-o-rss')
                        ->collapsible()
                        ->schema([
                            TextInput::make('api_version')
                                ->label('API Version')
                                ->maxLength(20)
                                ->default('v1')
                                ->prefixIcon('heroicon-o-code-bracket')
                                ->helperText('X-UI API version'),

                            Forms\Components\Grid::make(2)->schema([
                                TextInput::make('api_timeout')
                                    ->label('API Timeout (seconds)')
                                    ->numeric()
                                    ->default(30)
                                    ->minValue(5)
                                    ->maxValue(300)
                                    ->prefixIcon('heroicon-o-clock'),

                                TextInput::make('api_retry_count')
                                    ->label('API Retry Count')
                                    ->numeric()
                                    ->default(3)
                                    ->minValue(1)
                                    ->maxValue(10)
                                    ->prefixIcon('heroicon-o-arrow-path'),
                            ]),

                            TextInput::make('login_attempts')
                                ->label('Login Attempts')
                                ->numeric()
                                ->default(0)
                                ->disabled()
                                ->prefixIcon('heroicon-o-shield-exclamation')
                                ->helperText('Failed login attempt count'),

                            Textarea::make('session_cookie')
                                ->label('Session Cookie')
                                ->rows(3)
                                ->disabled()
                                ->helperText('Current X-UI session cookie'),
                        ]),

                    Section::make('🔐 XUI Session Management')
                        ->description('X-UI panel session and authentication tracking')
                        ->icon('heroicon-o-key')
                        ->collapsible()
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Placeholder::make('session_expires_info')
                                    ->label('Session Expires')
                                    ->content(fn (?Server $record): string =>
                                        $record && $record->session_expires_at ?
                                        $record->session_expires_at->format('M j, Y g:i A') : 'No active session'),

                                Placeholder::make('last_login_info')
                                    ->label('Last Panel Login')
                                    ->content(fn (?Server $record): string =>
                                        $record && $record->last_login_at ?
                                        $record->last_login_at->format('M j, Y g:i A') : 'Never logged in'),
                            ]),

                            Forms\Components\Grid::make(2)->schema([
                                TextInput::make('login_attempts')
                                    ->label('Failed Login Attempts')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->prefixIcon('heroicon-o-shield-exclamation')
                                    ->helperText('Failed login attempt count'),

                                Placeholder::make('last_login_attempt_info')
                                    ->label('Last Login Attempt')
                                    ->content(fn (?Server $record): string =>
                                        $record && $record->last_login_attempt_at ?
                                        $record->last_login_attempt_at->format('M j, Y g:i A') : 'No attempts recorded'),
                            ]),
                        ]),

                    Section::make('⚡ Performance & Traffic')
                        ->description('Real-time server performance metrics')
                        ->icon('heroicon-o-chart-bar-square')
                        ->collapsible()
                        ->schema([
                            Textarea::make('performance_metrics')
                                ->label('Performance Metrics')
                                ->rows(4)
                                ->disabled()
                                ->placeholder('{"cpu_usage": 45, "memory_usage": 60, "disk_usage": 30}')
                                ->helperText('Real-time performance data (JSON format)'),

                            Textarea::make('global_traffic_stats')
                                ->label('Global Traffic Statistics')
                                ->rows(4)
                                ->disabled()
                                ->placeholder('{"total_up": 1024, "total_down": 2048, "current_connections": 50}')
                                ->helperText('Global traffic statistics (JSON format)'),

                            Placeholder::make('last_global_sync_info')
                                ->label('Last Global Sync')
                                ->content(fn (?Server $record): string =>
                                    $record && $record->last_global_sync_at ?
                                    $record->last_global_sync_at->format('M j, Y g:i A') : 'Never synced'),
                        ]),

                    Section::make('🚨 Monitoring & Alerts')
                        ->description('Server monitoring and alert configuration')
                        ->icon('heroicon-o-bell-alert')
                        ->collapsible()
                        ->schema([
                            Textarea::make('alert_settings')
                                ->label('Alert Settings')
                                ->rows(4)
                                ->placeholder('{"cpu_threshold": 80, "memory_threshold": 85, "disk_threshold": 90}')
                                ->helperText('Alert thresholds and settings (JSON format)'),

                            Textarea::make('monitoring_thresholds')
                                ->label('Monitoring Thresholds')
                                ->rows(4)
                                ->placeholder('{"response_time": 5000, "uptime_threshold": 99.5}')
                                ->helperText('Monitoring thresholds configuration (JSON format)'),

                            Textarea::make('provisioning_rules')
                                ->label('Provisioning Rules')
                                ->rows(4)
                                ->placeholder('{"auto_create_inbound": true, "default_protocol": "vless"}')
                                ->helperText('Automated provisioning rules (JSON format)'),
                        ]),

                    Section::make('🔧 API Configuration')
                        ->description('X-UI API settings and rate limiting')
                        ->icon('heroicon-o-rss')
                        ->collapsible()
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                TextInput::make('api_version')
                                    ->label('API Version')
                                    ->maxLength(20)
                                    ->default('v1')
                                    ->prefixIcon('heroicon-o-code-bracket')
                                    ->helperText('X-UI API version'),

                                TextInput::make('api_timeout')
                                    ->label('API Timeout (seconds)')
                                    ->numeric()
                                    ->default(30)
                                    ->minValue(5)
                                    ->maxValue(300)
                                    ->prefixIcon('heroicon-o-clock')
                                    ->helperText('API request timeout'),
                            ]),

                            Forms\Components\Grid::make(2)->schema([
                                TextInput::make('api_retry_count')
                                    ->label('API Retry Count')
                                    ->numeric()
                                    ->default(3)
                                    ->minValue(1)
                                    ->maxValue(10)
                                    ->prefixIcon('heroicon-o-arrow-path')
                                    ->helperText('Number of retry attempts'),

                                Textarea::make('api_capabilities')
                                    ->label('API Capabilities')
                                    ->rows(3)
                                    ->placeholder('["inbound_management", "client_management", "traffic_monitoring"]')
                                    ->helperText('Available API capabilities (JSON array)'),
                            ]),

                            Textarea::make('api_rate_limits')
                                ->label('API Rate Limits')
                                ->rows(3)
                                ->placeholder('{"requests_per_minute": 60, "burst_limit": 10}')
                                ->helperText('API rate limiting configuration (JSON format)'),
                        ]),

                    Section::make('ℹ️ System Information')
                        ->description('System timestamps and metadata')
                        ->icon('heroicon-o-information-circle')
                        ->collapsible()
                        ->schema([
                            Placeholder::make('created_info')
                                ->label('Created')
                                ->content(fn (?Server $record): string =>
                                    $record ? $record->created_at->format('M j, Y g:i A') : 'Not created yet'),

                            Placeholder::make('updated_info')
                                ->label('Last Updated')
                                ->content(fn (?Server $record): string =>
                                    $record ? $record->updated_at->diffForHumans() : 'Never updated'),

                            Placeholder::make('last_connected_info')
                                ->label('Last Connection Test')
                                ->content(fn (?Server $record): string =>
                                    $record && $record->last_connected_at ?
                                    $record->last_connected_at->format('M j, Y g:i A') : 'Never tested'),

                            Placeholder::make('last_health_check_info')
                                ->label('Last Health Check')
                                ->content(fn (?Server $record): string =>
                                    $record && $record->last_health_check_at ?
                                    $record->last_health_check_at->format('M j, Y g:i A') : 'Never checked'),

                            Placeholder::make('last_login_info')
                                ->label('Last Panel Login')
                                ->content(fn (?Server $record): string =>
                                    $record && $record->last_login_at ?
                                    $record->last_login_at->format('M j, Y g:i A') : 'Never logged in'),

                            Placeholder::make('session_expires_info')
                                ->label('Session Expires')
                                ->content(fn (?Server $record): string =>
                                    $record && $record->session_expires_at ?
                                    $record->session_expires_at->format('M j, Y g:i A') : 'No active session'),
                        ]),
                ])->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('🏷️ Server Name')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-server')
                    ->copyable()
                    ->copyMessage('Server name copied')
                    ->copyMessageDuration(1500)
                    ->weight('bold'),

                TextColumn::make('country')
                    ->label('🌍 Location')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-map-pin')
                    ->formatStateUsing(fn (string $state, Server $record): string =>
                        $record->flag ? "{$record->flag} {$state}" : $state)
                    ->badge()
                    ->color('info'),

                BadgeColumn::make('status')
                    ->label('📊 Status')
                    ->colors([
                        'success' => 'healthy',
                        'warning' => 'warning',
                        'danger' => ['unhealthy', 'offline'],
                        'secondary' => 'maintenance',
                    ])
                    ->icons([
                        'heroicon-o-heart' => 'healthy',
                        'heroicon-o-exclamation-triangle' => 'warning',
                        'heroicon-o-x-circle' => ['unhealthy', 'offline'],
                        'heroicon-o-wrench-screwdriver' => 'maintenance',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'healthy' => '🟢 Healthy',
                        'warning' => '🟡 Warning',
                        'unhealthy' => '🔴 Unhealthy',
                        'offline' => '⚫ Offline',
                        'maintenance' => '🔧 Maintenance',
                        default => ucfirst($state)
                    }),

                TextColumn::make('category.name')
                    ->label('🏷️ Category')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('brand.name')
                    ->label('🏢 Brand')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                BadgeColumn::make('xui_session_status')
                    ->label('🔐 XUI Session')
                    ->getStateUsing(fn (Server $record): string =>
                        $record->session_expires_at && $record->session_expires_at > now()
                            ? 'active'
                            : 'expired'
                    )
                    ->colors([
                        'success' => 'active',
                        'danger' => 'expired',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'active',
                        'heroicon-o-x-circle' => 'expired',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'active' => '🟢 Active',
                        'expired' => '🔴 Expired',
                        default => '❓ Unknown'
                    })
                    ->toggleable(),

                TextColumn::make('type')
                    ->label('⚙️ Panel Type')
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'sanaei' => '⚡ 3X-UI Sanaei',
                        'alireza' => '🔧 Alireza Panel',
                        'marzban' => '🏗️ Marzban Panel',
                        default => ucfirst($state)
                    }),

                TextColumn::make('host')
                    ->label('🌐 Host/IP')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Host copied')
                    ->fontFamily('mono')
                    ->toggleable(),

                TextColumn::make('active_clients')
                    ->label('👥 Active')
                    ->numeric()
                    ->sortable()
                    ->icon('heroicon-o-users')
                    ->color('success')
                    ->alignCenter(),

                TextColumn::make('total_clients')
                    ->label('📊 Total')
                    ->numeric()
                    ->sortable()
                    ->icon('heroicon-o-user-group')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_traffic_mb')
                    ->label('📈 Traffic (MB)')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (?string $state): string =>
                        $state ? number_format((float) $state, 2) : '0')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_inbounds')
                    ->label('📥 Inbounds')
                    ->numeric()
                    ->sortable()
                    ->icon('heroicon-o-inbox-stack')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                IconColumn::make('auto_provisioning')
                    ->label('🚀 Auto Provision')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('auto_sync_enabled')
                    ->label('🔄 Auto Sync')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_health_check_at')
                    ->label('💓 Last Health Check')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->icon('heroicon-o-heart')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_connected_at')
                    ->label('🔗 Last Connection')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->icon('heroicon-o-link')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('📅 Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('📊 Status Filter')
                    ->options([
                        'healthy' => '🟢 Healthy',
                        'warning' => '🟡 Warning',
                        'unhealthy' => '🔴 Unhealthy',
                        'offline' => '⚫ Offline',
                        'maintenance' => '🔧 Maintenance',
                    ])
                    ->multiple(),

                SelectFilter::make('server_category_id')
                    ->label('🏷️ Category Filter')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                SelectFilter::make('server_brand_id')
                    ->label('🏢 Brand Filter')
                    ->relationship('brand', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                SelectFilter::make('type')
                    ->label('⚙️ Panel Type Filter')
                    ->options([
                        'sanaei' => '⚡ 3X-UI Sanaei',
                        'alireza' => '🔧 Alireza Panel',
                        'marzban' => '🏗️ Marzban Panel',
                        'other' => '🔗 Other',
                    ])
                    ->multiple(),

                SelectFilter::make('country')
                    ->label('🌍 Country Filter')
                    ->options(function () {
                        return Server::distinct()
                            ->pluck('country', 'country')
                            ->filter()
                            ->toArray();
                    })
                    ->multiple()
                    ->searchable(),

                Filter::make('auto_provisioning')
                    ->label('🚀 Auto Provisioning Enabled')
                    ->query(fn (Builder $query): Builder => $query->where('auto_provisioning', true)),

                Filter::make('auto_sync_enabled')
                    ->label('🔄 Auto Sync Enabled')
                    ->query(fn (Builder $query): Builder => $query->where('auto_sync_enabled', true)),

                Filter::make('has_active_clients')
                    ->label('👥 Has Active Clients')
                    ->query(fn (Builder $query): Builder => $query->where('active_clients', '>', 0)),

                Filter::make('recent_health_check')
                    ->label('💓 Recent Health Check (Last 24h)')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('last_health_check_at', '>=', now()->subDay())),
            ])
            ->actions([
                Action::make('test_connection')
                    ->label('Test Connection')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->tooltip('Test X-UI panel connection')
                    ->action(function (Server $record) {
                        try {
                            $xuiService = new XUIService($record);
                            $result = $xuiService->testConnection();

                            if ($result) {
                                $record->update([
                                    'last_connected_at' => now(),
                                    'status' => 'healthy'
                                ]);

                                Notification::make()
                                    ->title('🟢 Connection Successful')
                                    ->body("Successfully connected to {$record->name}")
                                    ->success()
                                    ->send();
                            } else {
                                $record->update(['status' => 'unhealthy']);

                                Notification::make()
                                    ->title('🔴 Connection Failed')
                                    ->body("Failed to connect to {$record->name}")
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('❌ Connection Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('sync_data')
                    ->label('Sync Data')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->tooltip('Synchronize server data from X-UI panel')
                    ->action(function (Server $record) {
                        try {
                            $xuiService = new XUIService($record);
                            $inboundCount = $xuiService->syncAllInbounds();
                            $clientCount = $xuiService->syncAllClients();

                            $record->update(['last_global_sync_at' => now()]);

                            Notification::make()
                                ->title('🔄 Sync Successful')
                                ->body("Synchronized {$inboundCount} inbounds and {$clientCount} clients for {$record->name}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('❌ Sync Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('reset_session')
                    ->label('Reset Session')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->tooltip('Reset XUI session and force re-authentication')
                    ->action(function (Server $record) {
                        $record->update([
                            'session_cookie' => null,
                            'session_expires_at' => null,
                            'login_attempts' => 0,
                        ]);

                        Notification::make()
                            ->title('🔐 Session Reset')
                            ->body("XUI session reset for {$record->name}")
                            ->success()
                            ->send();
                    }),

                Action::make('view_inbounds')
                    ->label('View Inbounds')
                    ->icon('heroicon-o-inbox-stack')
                    ->color('info')
                    ->tooltip('View all inbounds on this server')
                    ->url(fn (Server $record): string =>
                        route('filament.admin.server-management.resources.server-inbounds.index', [
                            'tableFilters[server_id][value]' => $record->id,
                        ])
                    ),

                Action::make('view_clients')
                    ->label('View Clients')
                    ->icon('heroicon-o-users')
                    ->color('success')
                    ->tooltip('View all clients on this server')
                    ->url(fn (Server $record): string =>
                        route('filament.admin.server-management.resources.server-clients.index', [
                            'tableFilters[server_id][value]' => $record->id,
                        ])
                    ),

                Action::make('online_clients')
                    ->label('Online Clients')
                    ->icon('heroicon-o-signal')
                    ->color('success')
                    ->tooltip('View currently online clients')
                    ->action(function (Server $record) {
                        try {
                            $xuiService = new XUIService($record);
                            $onlineClients = $xuiService->getOnlineClients();

                            Notification::make()
                                ->title('📊 Online Clients')
                                ->body("Currently online: " . count($onlineClients) . " clients")
                                ->info()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('❌ Error Getting Online Clients')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                ViewAction::make()
                    ->color('info'),
                EditAction::make()
                    ->color('warning'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('bulk_test_connection')
                        ->label('🔗 Test Connections')
                        ->icon('heroicon-o-signal')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $successful = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                try {
                                    $xuiService = new XUIService($record);
                                    $result = $xuiService->testConnection();

                                    if ($result) {
                                        $record->update([
                                            'last_connected_at' => now(),
                                            'status' => 'healthy'
                                        ]);
                                        $successful++;
                                    } else {
                                        $record->update(['status' => 'unhealthy']);
                                        $failed++;
                                    }
                                } catch (\Exception $e) {
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->title('🔗 Bulk Connection Test Complete')
                                ->body("✅ Successful: {$successful}, ❌ Failed: {$failed}")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_sync_data')
                        ->label('🔄 Sync All Data')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $successful = 0;
                            $failed = 0;
                            $totalInbounds = 0;
                            $totalClients = 0;

                            foreach ($records as $record) {
                                try {
                                    $xuiService = new XUIService($record);
                                    $inboundCount = $xuiService->syncAllInbounds();
                                    $clientCount = $xuiService->syncAllClients();

                                    $totalInbounds += $inboundCount;
                                    $totalClients += $clientCount;

                                    $record->update(['last_global_sync_at' => now()]);
                                    $successful++;
                                } catch (\Exception $e) {
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->title('🔄 Bulk Sync Complete')
                                ->body("✅ Successful: {$successful}, ❌ Failed: {$failed} | Synced: {$totalInbounds} inbounds, {$totalClients} clients")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_enable_auto_provisioning')
                        ->label('🚀 Enable Auto Provisioning')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            foreach ($records as $record) {
                                $record->update(['auto_provisioning' => true]);
                            }

                            Notification::make()
                                ->title('🚀 Auto Provisioning Enabled')
                                ->body("Enabled auto provisioning for {$count} servers")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_enable_auto_sync')
                        ->label('🔄 Enable Auto Sync')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            foreach ($records as $record) {
                                $record->update(['auto_sync_enabled' => true]);
                            }

                            Notification::make()
                                ->title('🔄 Auto Sync Enabled')
                                ->body("Enabled auto sync for {$count} servers")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_reset_sessions')
                        ->label('🔐 Reset XUI Sessions')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Reset XUI Sessions')
                        ->modalDescription('This will clear all stored session cookies and force re-authentication on next API call.')
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            foreach ($records as $record) {
                                $record->update([
                                    'session_cookie' => null,
                                    'session_expires_at' => null,
                                    'login_attempts' => 0,
                                ]);
                            }

                            Notification::make()
                                ->title('🔐 Sessions Reset')
                                ->body("Reset XUI sessions for {$count} servers")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_health_check')
                        ->label('💓 Health Check')
                        ->icon('heroicon-o-heart')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $healthy = 0;
                            $unhealthy = 0;

                            foreach ($records as $record) {
                                try {
                                    $xuiService = new XUIService($record);
                                    $isHealthy = $xuiService->testConnection();

                                    if ($isHealthy) {
                                        $record->update([
                                            'status' => 'healthy',
                                            'last_health_check_at' => now(),
                                            'health_status' => 'healthy',
                                            'health_message' => 'Server is responding normally'
                                        ]);
                                        $healthy++;
                                    } else {
                                        $record->update([
                                            'status' => 'unhealthy',
                                            'last_health_check_at' => now(),
                                            'health_status' => 'unhealthy',
                                            'health_message' => 'Server not responding'
                                        ]);
                                        $unhealthy++;
                                    }
                                } catch (\Exception $e) {
                                    $record->update([
                                        'status' => 'offline',
                                        'last_health_check_at' => now(),
                                        'health_status' => 'error',
                                        'health_message' => $e->getMessage()
                                    ]);
                                    $unhealthy++;
                                }
                            }

                            Notification::make()
                                ->title('💓 Health Check Complete')
                                ->body("✅ Healthy: {$healthy}, ❌ Unhealthy: {$unhealthy}")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_backup_create')
                        ->label('💾 Create Backups')
                        ->icon('heroicon-o-cloud-arrow-down')
                        ->color('secondary')
                        ->action(function (Collection $records) {
                            $successful = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                try {
                                    $xuiService = new XUIService($record);
                                    $result = $xuiService->createBackup();

                                    if ($result) {
                                        $successful++;
                                    } else {
                                        $failed++;
                                    }
                                } catch (\Exception $e) {
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->title('💾 Backup Creation Complete')
                                ->body("✅ Successful: {$successful}, ❌ Failed: {$failed}")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_cleanup_depleted')
                        ->label('🧹 Cleanup Depleted Clients')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cleanup Depleted Clients')
                        ->modalDescription('This will remove all clients that have exceeded their traffic limits. This action cannot be undone.')
                        ->action(function (Collection $records) {
                            $totalCleaned = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                try {
                                    $xuiService = new XUIService($record);
                                    // Get all inbounds for this server and clean each one
                                    $inbounds = $xuiService->listInbounds();

                                    foreach ($inbounds as $inbound) {
                                        if (isset($inbound['id'])) {
                                            $cleaned = $xuiService->deleteDepletedClients($inbound['id']);
                                            if ($cleaned) {
                                                $totalCleaned++;
                                            }
                                        }
                                    }
                                } catch (\Exception $e) {
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->title('🧹 Cleanup Complete')
                                ->body("✅ Cleaned {$totalCleaned} inbounds, ❌ Failed: {$failed}")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('reset_all_traffics')
                        ->label('📊 Reset Client Traffics')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Reset All Client Traffics')
                        ->modalDescription('This will reset traffic statistics for all clients on the selected servers.')
                        ->action(function (Collection $records) {
                            $successCount = 0;
                            $failCount = 0;

                            foreach ($records as $server) {
                                try {
                                    $xuiService = new XUIService($server);
                                    // Get all inbounds and reset client traffics
                                    $inbounds = $xuiService->listInbounds();

                                    foreach ($inbounds as $inbound) {
                                        if (isset($inbound['id']) && isset($inbound['clientStats'])) {
                                            foreach ($inbound['clientStats'] as $client) {
                                                $xuiService->resetClientTraffic($inbound['id'], $client['email']);
                                            }
                                        }
                                    }
                                    $successCount++;
                                } catch (\Exception $e) {
                                    $failCount++;
                                    Log::error("Failed to reset traffics for server {$server->name}: " . $e->getMessage());
                                }
                            }

                            Notification::make()
                                ->title('📊 Traffic Reset Complete')
                                ->body("Reset traffics: {$successCount} succeeded, {$failCount} failed")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('create_backups')
                        ->label('📦 Create Backups')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Create Backups')
                        ->modalDescription('This will create backups on the selected servers.')
                        ->action(function (Collection $records) {
                            $successCount = 0;
                            $failCount = 0;

                            foreach ($records as $server) {
                                try {
                                    $xuiService = new XUIService($server);
                                    $xuiService->createBackup();
                                    $successCount++;
                                } catch (\Exception $e) {
                                    $failCount++;
                                    Log::error("Failed to create backup for server {$server->name}: " . $e->getMessage());
                                }
                            }

                            Notification::make()
                                ->title('📦 Backup Creation Complete')
                                ->body("Created backups: {$successCount} succeeded, {$failCount} failed")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('update_server_status')
                        ->label('🔄 Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->action(function (Collection $records) {
                            $successCount = 0;
                            $failCount = 0;

                            foreach ($records as $server) {
                                try {
                                    $xuiService = new XUIService($server);
                                    $isOnline = $xuiService->testConnection();

                                    $server->update([
                                        'status' => $isOnline ? 'online' : 'offline',
                                        'last_checked_at' => now(),
                                    ]);

                                    $successCount++;
                                } catch (\Exception $e) {
                                    $server->update([
                                        'status' => 'offline',
                                        'last_checked_at' => now(),
                                    ]);
                                    $failCount++;
                                }
                            }

                            Notification::make()
                                ->title('🔄 Status Update Complete')
                                ->body("Updated status: {$successCount} succeeded, {$failCount} failed")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('30s') // Auto-refresh every 30 seconds
            ->extremePaginationLinks();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServers::route('/'),
            'create' => Pages\CreateServer::route('/create'),
            'view' => Pages\ViewServer::route('/{record}'),
            'edit' => Pages\EditServer::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            // Relation managers can be added here when they exist
        ];
    }
}
