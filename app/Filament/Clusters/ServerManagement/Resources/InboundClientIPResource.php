<?php
namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\InboundClientIPResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\InboundClientIPResource\RelationManagers;
use App\Models\InboundClientIP;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Group;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use BackedEnum;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Infolist;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use App\Models\ServerClient;
use App\Models\Server;
use App\Services\XUIService;

class InboundClientIPResource extends Resource
{
    protected static ?string $model = InboundClientIP::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $cluster = ServerManagement::class;

    protected static UnitEnum|string|null $navigationGroup = 'TRAFFIC MONITORING';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'client_email';

    public static function getLabel(): string
    {
        return 'Client IPs';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Create-only wizard
                Wizard::make()->label('Add Client IPs')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'w-full'])
                    ->visibleOn('create')
                    ->steps([
                        Step::make('Client')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\TextInput::make('client_email')->label('Client Email')->email()->required()->maxLength(255),
                            ])->columns(1),
                        Step::make('IPs')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Forms\Components\Textarea::make('ips')
                                    ->label('IP Addresses')
                                    ->required()
                                    ->rows(8)
                                    ->placeholder("192.168.1.1\n10.0.0.1\n2001:0db8::1")
                                    ->helperText('Enter one IP per line. IPv4 and IPv6 supported.'),
                            ])->columns(1),
                    ]),

                Group::make()->schema([
                    Section::make('📧 Client Information')->schema([
                        TextInput::make('client_email')
                            ->label('Client Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1)
                            ->helperText('Email identifier of the client'),
                    ])->columns(1),

                    Section::make('🌐 IP Address Information')->schema([
                        Textarea::make('ips')
                            ->label('IP Addresses')
                            ->required()
                            ->rows(5)
                            ->placeholder('192.168.1.1' . PHP_EOL . '10.0.0.1' . PHP_EOL . '172.16.0.1')
                            ->columnSpanFull()
                            ->helperText('Enter IP addresses (one per line). Supports IPv4 and IPv6 formats.'),
                    ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Client IP Details')
                ->persistTab()
                ->tabs([
                    Tabs\Tab::make('Overview')
                        ->icon('heroicon-m-eye')
                        ->schema([
                            InfolistSection::make('Summary')
                                ->columns(3)
                                ->schema([
                                            TextEntry::make('client_email')->label('Client Email')->color('primary'),
                                            TextEntry::make('ips')->label('IP Addresses')->formatStateUsing(function ($s, $record) {
                                                // Attempt to show live IPs from 3X-UI when available
                                                try {
                                                    $serverClient = static::resolveServerClientByEmail($record->client_email);
                                                    if ($serverClient) {
                                                        $server = $serverClient->inbound?->server ?? $serverClient->server ?? null;
                                                    } else {
                                                        $server = null;
                                                    }
                                                    if ($server) {
                                                        $idPart = $serverClient->id ?? ($record->client_email ?: 'unknown');
                                                        $key = "xui_client_ips_{$server->id}_{$idPart}";
                                                        $live = Cache::remember($key, 30, function () use ($server, $serverClient, $record) {
                                                            try {
                                                                $svc = new XUIService($server);
                                                                // Prefer UUID lookup when available
                                                                if (!empty($serverClient->id)) {
                                                                    $remote = $svc->getClientByUuid($serverClient->id);
                                                                } else {
                                                                    $remote = $svc->getClientByEmail($record->client_email);
                                                                }
                                                                // If remote returns client object with IPs under obj->ips, try client ips endpoint
                                                                $ips = $svc->getClientIps($serverClient->id ?: $record->client_email);
                                                                return $ips;
                                                            } catch (\Throwable $t) {
                                                                return null;
                                                            }
                                                        });
                                                        if (is_array($live) && count($live) > 0) {
                                                            return implode(', ', $live);
                                                        }
                                                    }
                                                } catch (\Throwable $t) {
                                                    // ignore and fall through to stored ips
                                                }
                                                $stored = trim((string) $s);
                                                if ($stored === '') return '(none)';
                                                return str_replace("\n", ', ', $stored);
                                            })->wrap(),
                                ]),
                        ]),
                    Tabs\Tab::make('Meta')
                        ->icon('heroicon-m-clock')
                        ->schema([
                            InfolistSection::make('Timestamps')
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('created_at')->label('Created')->since(),
                                    TextEntry::make('updated_at')->label('Updated')->since(),
                                ]),
                        ]),
                ])
                ->contained(true)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $table = $table
            ->columns([
                TextColumn::make('client_email')
                    ->label('Client Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Client email identifier'),

                TextColumn::make('ip_count')
                    ->label('IP Count')
                    ->getStateUsing(function ($record) {
                        return count(array_filter(explode("\n", $record->ips)));
                    })
                    ->badge()
                    ->color('info')
                    ->tooltip('Number of IP addresses'),

                TextColumn::make('ips')
                    ->label('IP Addresses')
                    ->limit(50)
                    ->getStateUsing(function ($record) {
                        try {
                            $serverClient = static::resolveServerClientByEmail($record->client_email);
                            if ($serverClient) {
                                $server = $serverClient->inbound?->server ?? $serverClient->server ?? null;
                            } else {
                                $server = null;
                            }
                            if ($server) {
                                $idPart = $serverClient->id ?? ($record->client_email ?: 'unknown');
                                $key = "xui_client_ips_{$server->id}_{$idPart}";
                                $live = Cache::remember($key, 30, function () use ($server, $serverClient, $record) {
                                    try {
                                        $svc = new XUIService($server);
                                        $identifier = $serverClient->id ?: $record->client_email;
                                        return $svc->getClientIps($identifier);
                                    } catch (\Throwable $t) {
                                        return null;
                                    }
                                });
                                if (is_array($live) && count($live) > 0) {
                                    return implode(', ', $live);
                                }
                            }
                        } catch (\Throwable $t) {
                            // fall back to stored ips
                        }
                        $stored = trim((string) $record->ips);
                        if ($stored === '') return '(none)';
                        return str_replace("\n", ', ', $stored);
                    })
                    ->tooltip(fn ($record) => $record->ips)
                    ->searchable()
                    ->wrap(),

                TextColumn::make('ip_types')
                    ->label('IP Types')
                    ->getStateUsing(function ($record) {
                        $ips = array_filter(explode("\n", $record->ips));
                        $ipv4Count = 0;
                        $ipv6Count = 0;

                        foreach ($ips as $ip) {
                            $ip = trim($ip);
                            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                                $ipv4Count++;
                            } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                                $ipv6Count++;
                            }
                        }

                        $types = [];
                        if ($ipv4Count > 0) $types[] = "IPv4: {$ipv4Count}";
                        if ($ipv6Count > 0) $types[] = "IPv6: {$ipv6Count}";
                        return implode(', ', $types) ?: 'Invalid IPs';
                    })
                    ->badge()
                    ->color(function ($record) {
                        $ips = array_filter(explode("\n", $record->ips));
                        foreach ($ips as $ip) {
                            $ip = trim($ip);
                            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                                return 'danger';
                            }
                        }
                        return 'success';
                    })
                    ->tooltip('IP address type breakdown'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('has_ipv4')
                    ->toggle()
                    ->label('Has IPv4')
                    ->query(function (Builder $query): Builder {
                        return $query->where('ips', 'REGEXP', '^([0-9]{1,3}\.){3}[0-9]{1,3}');
                    }),

                Filter::make('has_ipv6')
                    ->toggle()
                    ->label('Has IPv6')
                    ->query(function (Builder $query): Builder {
                        return $query->where('ips', 'REGEXP', ':');
                    }),

                Filter::make('multiple_ips')
                    ->toggle()
                    ->label('Multiple IPs')
                    ->query(function (Builder $query): Builder {
                        return $query->where('ips', 'LIKE', '%' . PHP_EOL . '%');
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->tooltip('View IP details'),

                    EditAction::make()
                        ->tooltip('Edit IP addresses'),

                    Action::make('refresh_from_xui')
                        ->label('Refresh from X-UI')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->tooltip('Pull latest client IPs from 3X-UI')
                        ->action(function ($record) {
                            $serverClient = static::resolveServerClientByEmail($record->client_email);
                            if (!$serverClient) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No matching client')
                                    ->body('Could not resolve server for this email.')
                                    ->warning()
                                    ->send();
                                return;
                            }
                            $server = $serverClient->inbound?->server ?? $serverClient->server;
                            if (!$server instanceof Server) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Server not found')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            $xui = new XUIService($server);
                            // Prefer uuid lookup if available
                            $identifier = $serverClient->id ?: $record->client_email;
                            $ipsList = $xui->getClientIps($identifier);
                            if (empty($ipsList)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No IP records')
                                    ->body('3X-UI returned no IPs for this client.')
                                    ->warning()
                                    ->send();
                                return;
                            }
                            $ipsText = implode("\n", array_map('strval', $ipsList));
                            $record->update(['ips' => $ipsText]);
                            \Filament\Notifications\Notification::make()
                                ->title('IPs refreshed')
                                ->success()
                                ->send();
                        }),

                    Action::make('clear_ips_remote')
                        ->label('Clear IPs (X-UI)')
                        ->icon('heroicon-o-trash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Clear IP logs in X-UI?')
                        ->modalDescription('This will clear the client IP log on the remote panel and locally.')
                        ->action(function ($record) {
                            $serverClient = ServerClient::query()->where('email', $record->client_email)->first();
                            $remoteOk = false;
                            try {
                                if ($serverClient && ($server = $serverClient->server ?? $serverClient->inbound?->server)) {
                                    $xui = new XUIService($server);
                                    $remoteOk = $xui->clearClientIps($record->client_email);
                                }
                            } catch (\Throwable $e) {
                                // continue
                            }
                            $record->update(['ips' => '']);
                            \Filament\Notifications\Notification::make()
                                ->title($remoteOk ? 'IPs cleared (remote + local)' : 'IPs cleared (local)')
                                ->success()
                                ->send();
                        }),

                    Action::make('validate_ips')
                        ->label('Validate IPs')
                        ->icon('heroicon-o-shield-check')
                        ->color('info')
                        ->action(function ($record) {
                            $ips = array_filter(explode("\n", $record->ips));
                            $validIPs = [];
                            $invalidIPs = [];

                            foreach ($ips as $ip) {
                                $ip = trim($ip);
                                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                                    $validIPs[] = $ip;
                                } else {
                                    $invalidIPs[] = $ip;
                                }
                            }

                            $message = "Valid IPs: " . count($validIPs);
                            if (count($invalidIPs) > 0) {
                                $message .= "\nInvalid IPs: " . count($invalidIPs) . " (" . implode(', ', $invalidIPs) . ")";
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('IP Validation Results')
                                ->body($message)
                                ->color(count($invalidIPs) > 0 ? 'warning' : 'success')
                                ->send();
                        })
                        ->tooltip('Validate IP address formats'),

                    Action::make('copy_ips')
                        ->label('Copy IPs')
                        ->icon('heroicon-o-clipboard-document')
                        ->action(function ($record) {
                            \Filament\Notifications\Notification::make()
                                ->title('IPs copied to clipboard')
                                ->body('IP addresses have been copied.')
                                ->success()
                                ->send();
                        })
                        ->tooltip('Copy IP addresses to clipboard'),

                    DeleteAction::make()
                        ->tooltip('Delete IP record'),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->tooltip('Delete selected IP records'),

                    BulkAction::make('validate_all_ips')
                        ->label('Validate All IPs')
                        ->icon('heroicon-o-shield-check')
                        ->color('info')
                        ->action(function ($records) {
                            $totalValid = 0;
                            $totalInvalid = 0;

                            foreach ($records as $record) {
                                $ips = array_filter(explode("\n", $record->ips));
                                foreach ($ips as $ip) {
                                    $ip = trim($ip);
                                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                                        $totalValid++;
                                    } else {
                                        $totalInvalid++;
                                    }
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Bulk IP Validation Results')
                                ->body("Valid IPs: {$totalValid}, Invalid IPs: {$totalInvalid}")
                                ->color($totalInvalid > 0 ? 'warning' : 'success')
                                ->send();
                        })
                        ->tooltip('Validate all IP addresses in selected records'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');

        return \App\Filament\Concerns\HasPerformanceOptimizations::applyTablePreset($table, [
            'defaultPage' => 25,
            'empty' => [
                'icon' => 'heroicon-o-globe-alt',
                'heading' => 'No client IPs yet',
                'description' => 'Add a record or adjust filters.',
            ],
        ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add relation managers if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInboundClientIPS::route('/'),
            'create' => Pages\CreateInboundClientIP::route('/create'),
            'view' => Pages\ViewInboundClientIP::route('/{record}'),
            'edit' => Pages\EditInboundClientIP::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['client_email', 'ips'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 20 ? 'success' : 'warning';
    }

    /**
     * Robust resolver: find a ServerClient by email or fallback to sub_id or id
     */
    protected static function resolveServerClientByEmail(string $email)
    {
        // direct match
        $client = ServerClient::query()->where('email', $email)->first();
        if ($client) return $client;

        // try matching by sub_id
        $client = ServerClient::query()->where('sub_id', $email)->first();
        if ($client) return $client;

        // try uuid/id
        $client = ServerClient::query()->where('id', $email)->orWhere('uuid', $email)->first();
        if ($client) return $client;

        // try loose contains (some panels append +subid to email local part)
        $client = ServerClient::query()->where('email', 'like', '%' . explode('@', $email)[0] . '%')->first();
        return $client;
    }
}
