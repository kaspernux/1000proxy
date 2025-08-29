<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ToggleGroup;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;

class EditServer extends EditRecord
{
    protected static string $resource = ServerResource::class;

    public function getFormSchema(): array
    {
        return [
            Wizard::make()
                ->columnSpanFull()
                ->extraAttributes(['class' => 'w-full'])
                ->steps([
                    Step::make('Basics')
                        ->icon('heroicon-o-server')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('name')->required()->maxLength(255)->placeholder('My EU Proxy 01'),
                                Select::make('server_category_id')->label('Category')->relationship('category', 'name')->searchable()->preload()->placeholder('General'),
                            ]),
                            Grid::make(2)->schema([
                                Select::make('server_brand_id')->label('Brand')->relationship('brand', 'name')->searchable()->preload()->placeholder('Select provider'),
                                TextInput::make('country')->label('Country')->maxLength(255)->placeholder('Germany'),
                            ]),
                            Textarea::make('description')->rows(3)->placeholder('Short description or notes (optional)'),
                            ColorPicker::make('flag')->label('Flag Color')->helperText('Pick a color for the server flag'),
                        ])->columns(1),
                    Step::make('Connection')
                        ->icon('heroicon-o-link')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('panel_url')->label('Panel URL')->url()->placeholder('https://panel.example.com'),
                                TextInput::make('host')->label('Host/Hostname')->maxLength(255)->placeholder('panel.example.com'),
                                TextInput::make('panel_port')->label('Panel Port')->numeric()->minValue(1)->maxValue(65535)->default(2053)->placeholder('2053'),
                            ]),
                            Grid::make(3)->schema([
                                TextInput::make('ip_address')->label('IP Address')->required()->rules(['ip'])->placeholder('192.0.2.10'),
                                TextInput::make('web_base_path')->label('Web Base Path')->default('/')->placeholder('/'),
                                Select::make('port_type')->label('Port Type')->options(['https' => 'HTTPS','http' => 'HTTP','tcp' => 'TCP','udp' => 'UDP'])->default('https'),
                            ]),
                            Grid::make(2)->schema([
                                TextInput::make('username')->label('Panel Username')->required()->placeholder('admin'),
                                TextInput::make('password')->label('Panel Password')->password()->required()->placeholder('••••••••'),
                            ]),
                        ])->columns(1),
                    Step::make('Security & Protocol')
                        ->icon('heroicon-o-shield-check')
                        ->schema([
                            Grid::make(3)->schema([
                                Select::make('type')->label('Panel Type')->options(['sanaei' => '3X-UI (Sanaei)','alireza' => 'Alireza','marzban' => 'Marzban','other' => 'Other'])->default('sanaei')->required(),
                                Select::make('security')->label('Security')->options(['tls' => 'TLS','reality' => 'Reality','none' => 'None'])->default('tls'),
                                Select::make('header_type')->label('Header Type')->options(['none' => 'None','http' => 'HTTP','ws' => 'WebSocket','grpc' => 'gRPC'])->default('none'),
                            ]),
                            Grid::make(3)->schema([
                                TextInput::make('sni')->label('SNI')->placeholder('example.com'),
                                TextInput::make('port')->label('Main Port')->numeric()->minValue(1)->maxValue(65535)->placeholder('443'),
                                TextInput::make('flag')->label('Flag (ISO code)')->maxLength(10)->placeholder('DE'),
                            ]),
                            ToggleGroup::make('protocols')->options(['vless' => 'VLESS','vmess' => 'VMESS','trojan' => 'TROJAN','shadowsocks' => 'SHADOWSOCKS'])->label('Supported Protocols')->helperText('Select supported protocols'),
                        ])->columns(1),
                    Step::make('Automation')
                        ->icon('heroicon-o-cog-8-tooth')
                        ->schema([
                            Grid::make(3)->schema([
                                Toggle::make('auto_sync_enabled')->label('Auto Sync')->default(true),
                                Toggle::make('auto_provisioning')->label('Auto Provisioning')->default(false),
                                TextInput::make('sync_interval_minutes')->label('Sync Interval (min)')->numeric()->minValue(1)->maxValue(1440)->default(30),
                            ]),
                            FileUpload::make('server_icon')->label('Server Icon')->image()->maxSize(1024)->helperText('Upload a custom server icon'),
                        ])->columns(1),
                ])
                ->visible(fn ($context) => $context !== 'view'),
            Section::make('Live Metrics')
                ->icon('heroicon-o-chart-bar')
                ->description('Real-time server metrics and status')
                ->schema([
                    Placeholder::make('metrics')->content('Metrics widget goes here (Livewire/Filament widget integration)'),
                ])
                ->columnSpanFull(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
