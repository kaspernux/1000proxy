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
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Group;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\Http;

class InboundClientIPResource extends Resource
{
    protected static ?string $model = InboundClientIP::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $cluster = ServerManagement::class;

    protected static ?string $navigationGroup = 'TRAFFIC MONITORING';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'client_email';

    public static function getLabel(): string
    {
        return 'Client IPs';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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

    public static function table(Table $table): Table
    {
        return $table
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

                    Tables\Actions\BulkAction::make('validate_all_ips')
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
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
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
}
