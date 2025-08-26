<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use UnitEnum;
use BackedEnum;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use App\Filament\Concerns\HasPerformanceOptimizations;
use Illuminate\Support\Str;

class ActivityLogResource extends Resource
{
    use HasPerformanceOptimizations;

    protected static ?string $model = ActivityLog::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static string|UnitEnum|null $navigationGroup = 'System';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'activity-logs';
    protected static ?string $modelLabel = 'Activity Log';
    protected static ?string $pluralModelLabel = 'Activity Logs';

    public static function canAccess(): bool
    {
        // Allow route access for authenticated users; page-level checks will enforce 403 for non-admin.
        return auth()->check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Only show in navigation for administrators
        return (bool) auth()->user()?->hasRole('admin');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Textarea::make('properties')->disabled(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        // Use only components available in the current Filament Infolists package (TextEntry, KeyValueEntry)
        // Avoid Section/Grid/Group which may not be installed in this environment.
        return $schema->schema([
            TextEntry::make('created_at')->label('When')->dateTime()->since(),
            TextEntry::make('action')->badge()->color(fn ($state) => match($state){'created'=>'success','updated'=>'warning','deleted'=>'danger',default=>'gray'}),
            TextEntry::make('subject_display')->label('Subject'),
            TextEntry::make('ip_address')->label('IP'),

            TextEntry::make('user.name')->label('User')->icon('heroicon-o-user'),
            TextEntry::make('customer.name')->label('Customer')->icon('heroicon-o-user-circle'),
            TextEntry::make('user_agent')->label('User Agent')->copyable(),

            KeyValueEntry::make('properties.attributes')->label('Attributes')->default([]),
            KeyValueEntry::make('properties.changes')->label('Changes')->default([]),
        ]);
    }

    /**
     * Provide a safe base Eloquent query for the resource so Filament can
     * resolve table queries and summaries without relying on a null model.
     */
    public static function getEloquentQuery(): Builder
    {
        return ActivityLog::query()->with(['user', 'customer']);
    }

    public static function table(Table $table): Table
    {
        $table = $table
            ->query(function (): Builder {
                return ActivityLog::query()->with(['user', 'customer']);
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->alignRight()
                    ->extraAttributes(['class' => 'font-mono text-xs'])
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable()
                    ->label('When')
                    ->icon('heroicon-m-clock')
                    ->tooltip(fn ($record) => optional($record->created_at)?->toDateTimeString())
                    ->toggleable(),
                Tables\Columns\TextColumn::make('causer_display')
                    ->label('Causer')
                    ->getStateUsing(fn ($record) => $record->user?->name ?? $record->customer?->name ?? 'System')
                    ->description(function ($record) {
                        if ($record->user_id) {
                            $role = \Illuminate\Support\Str::headline((string) ($record->user->role ?? 'User'));
                            return $role . ' ' . $record->user_id;
                        }
                        if ($record->customer_id) {
                            return 'Customer ' . $record->customer_id;
                        }
                        return 'System';
                    })
                    ->icon(fn ($record) => $record->user_id ? 'heroicon-m-user' : ($record->customer_id ? 'heroicon-m-user-circle' : 'heroicon-m-cpu-chip'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->getStateUsing(fn ($record) => $record->user_id ? (Str::headline((string) ($record->user->role ?? 'User')) . ' ' . $record->user_id) : null)
                    ->description(fn ($record) => $record->user?->name)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->icon('heroicon-m-user-circle')
                    ->searchable()
                    ->description(fn ($record) => $record->customer_id ? ('Customer ' . $record->customer_id) : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->colors([
                        'success' => ['created', 'login'],
                        'warning' => ['updated', 'password_reset'],
                        'danger' => ['deleted'],
                        'info' => ['logout'],
                    ])
                    ->icons([
                        'heroicon-m-plus-circle' => 'created',
                        'heroicon-m-pencil-square' => 'updated',
                        'heroicon-m-trash' => 'deleted',
                        'heroicon-m-arrow-right-on-rectangle' => 'logout',
                        'heroicon-m-key' => 'login',
                        'heroicon-m-shield-exclamation' => 'password_reset',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject_display')
                    ->label('Subject')
                    ->formatStateUsing(fn ($record) => $record->subject_display)
                    ->description(fn ($record) => $record->subject_type ? (class_basename($record->subject_type) . ' #' . $record->subject_id) : null)
                    ->url(function ($record) {
                        // Attempt to link to a Filament resource view page if available
                        try {
                            $type = $record->subject_type;
                            if (!$type || !$record->subject_id) return null;
                            $map = [
                                \App\Models\Customer::class => \App\Filament\Clusters\CustomerManagement\Resources\CustomerResource::class,
                                \App\Models\Order::class => \App\Filament\Clusters\ProxyShop\Resources\OrderResource::class,
                                \App\Models\ServerClient::class => \App\Filament\Clusters\ServerManagement\Resources\ServerClientResource::class,
                                \App\Models\ServerInbound::class => \App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource::class,
                                \App\Models\ServerInfo::class => \App\Filament\Clusters\ServerManagement\Resources\ServerInfoResource::class,
                                \App\Models\Server::class => \App\Filament\Clusters\ServerManagement\Resources\ServerResource::class,
                                \App\Models\Invoice::class => \App\Filament\Clusters\ProxyShop\Resources\InvoiceResource::class,
                            ];
                            $resource = $map[$type] ?? null;
                            if ($resource && method_exists($resource, 'getUrl')) {
                                return $resource::getUrl('view', ['record' => $record->subject_id]);
                            }
                        } catch (\Throwable $e) {
                            return null;
                        }
                        return null;
                    })
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('Subject ID')
                    ->alignRight()
                    ->extraAttributes(['class' => 'font-mono text-xs'])
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('subject_type_short')
                    ->label('Type')
                    ->getStateUsing(fn ($record) => $record->subject_type ? class_basename($record->subject_type) : null)
                    ->badge()
                    ->color(fn ($state) => match((string) $state) {
                        'ServerPlan' => 'info',
                        'Order' => 'secondary',
                        'Invoice' => 'warning',
                        'Server', 'ServerClient', 'ServerInbound', 'ServerInfo' => 'success',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                // ——— Enhanced Server Plan parameters surfaced from activity properties ———
                Tables\Columns\TextColumn::make('plan_price')
                    ->label('Price')
                    ->getStateUsing(function ($record) {
                        $props = (array) ($record->properties ?? []);
                        $price = $props['attributes']['price']
                            ?? ($props['changes']['price']['after'] ?? null)
                            ?? ($record->subject?->price ?? null);
                        return is_null($price) ? null : '$' . number_format((float) $price, 2);
                    })
                    ->extraAttributes(['class' => 'font-mono text-xs'])
                    ->alignRight()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('billing_cycle')
                    ->label('Cycle')
                    ->getStateUsing(function ($record) {
                        $props = (array) ($record->properties ?? []);
                        return $props['attributes']['billing_cycle']
                            ?? ($props['changes']['billing_cycle']['after'] ?? null)
                            ?? ($record->subject?->billing_cycle ?? null);
                    })
                    ->badge()
                    ->color(fn ($state) => match((string) $state) {
                        'monthly' => 'info',
                        'yearly' => 'success',
                        'weekly' => 'warning',
                        'daily', 'hourly' => 'gray',
                        'quarterly' => 'secondary',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('days')
                    ->label('Days')
                    ->getStateUsing(function ($record) {
                        $props = (array) ($record->properties ?? []);
                        return $props['attributes']['days']
                            ?? ($props['changes']['days']['after'] ?? null)
                            ?? ($record->subject?->days ?? null);
                    })
                    ->badge()
                    ->color('secondary')
                    ->alignRight()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('protocol')
                    ->label('Proto')
                    ->getStateUsing(function ($record) {
                        $props = (array) ($record->properties ?? []);
                        return $props['attributes']['protocol']
                            ?? ($props['changes']['protocol']['after'] ?? null)
                            ?? ($record->subject?->protocol ?? null);
                    })
                    ->badge()
                    ->color(fn ($state) => match((string) $state) {
                        'vless' => 'info',
                        'vmess' => 'secondary',
                        'trojan' => 'danger',
                        'shadowsocks' => 'success',
                        'mixed' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('country_code')
                    ->label('Country')
                    ->getStateUsing(function ($record) {
                        $props = (array) ($record->properties ?? []);
                        $code = $props['attributes']['country_code']
                            ?? ($props['changes']['country_code']['after'] ?? null)
                            ?? ($record->subject?->country_code ?? null);
                        return $code ? strtoupper((string) $code) : null;
                    })
                    ->formatStateUsing(function ($state) {
                        if (! $state) return null;
                        $state = strtoupper((string) $state);
                        $flag = function (string $cc): string {
                            $cc = strtoupper($cc);
                            if (strlen($cc) !== 2) return '';
                            $base = 127397; // 0x1F1E6 - 'A'
                            $chars = [ord($cc[0]) + $base, ord($cc[1]) + $base];
                            return mb_chr($chars[0], 'UTF-8') . mb_chr($chars[1], 'UTF-8');
                        };
                        return $state . ' ' . $flag($state);
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('visibility')
                    ->label('Visibility')
                    ->getStateUsing(function ($record) {
                        $props = (array) ($record->properties ?? []);
                        return $props['attributes']['visibility']
                            ?? ($props['changes']['visibility']['after'] ?? null)
                            ?? ($record->subject?->visibility ?? null);
                    })
                    ->badge()
                    ->color(fn ($state) => match((string) $state) { 'public' => 'success', 'private' => 'gray', 'hidden' => 'warning', default => 'gray' })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('volume')
                    ->label('Volume (GB)')
                    ->getStateUsing(function ($record) {
                        $props = (array) ($record->properties ?? []);
                        return $props['attributes']['volume']
                            ?? ($props['changes']['volume']['after'] ?? null)
                            ?? ($record->subject?->volume ?? null);
                    })
                    ->badge()
                    ->color('secondary')
                    ->alignRight()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('supports_ipv6')
                    ->label('IPv6')
                    ->boolean()
                    ->trueIcon('heroicon-m-check-circle')
                    ->falseIcon('heroicon-m-no-symbol')
                    ->getStateUsing(function ($record) {
                        $props = (array) ($record->properties ?? []);
                        $val = $props['attributes']['supports_ipv6']
                            ?? ($props['changes']['supports_ipv6']['after'] ?? null)
                            ?? ($record->subject?->supports_ipv6 ?? null);
                        return filter_var($val, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('unlimited_traffic')
                    ->label('Unlimited')
                    ->boolean()
                    ->trueIcon('heroicon-m-check-circle')
                    ->falseIcon('heroicon-m-no-symbol')
                    ->getStateUsing(function ($record) {
                        $props = (array) ($record->properties ?? []);
                        $val = $props['attributes']['unlimited_traffic']
                            ?? ($props['changes']['unlimited_traffic']['after'] ?? null)
                            ?? ($record->subject?->unlimited_traffic ?? null);
                        return filter_var($val, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('Agent')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->user_agent)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('properties')
                    ->label('Properties')
                    ->formatStateUsing(function ($state) {
                        try {
                            $json = is_array($state) ? json_encode($state, JSON_UNESCAPED_SLASHES) : (string) $state;
                            return Str::limit($json ?? '—', 120);
                        } catch (\Throwable $e) {
                            return '—';
                        }
                    })
                    ->extraAttributes(['class' => 'font-mono text-xs'])
                    ->wrap()
                    ->tooltip('Open row action “View JSON” for full details')
                    ->searchable(query: function (Builder $query, string $search) {
                        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
                        $query->where('properties', 'like', $like);
                    })
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('changed_count')
                    ->label('Δ')
                    ->getStateUsing(function ($record) {
                        $changes = $record->properties['changes'] ?? [];
                        return is_array($changes) ? count($changes) : 0;
                    })
                    ->badge()
                    ->color(fn ($state) => (int) $state > 0 ? 'warning' : 'gray')
                    ->tooltip(function ($record) {
                        $changes = $record->properties['changes'] ?? [];
                        if (! is_array($changes) || empty($changes)) return 'No changes';
                        $keys = array_keys($changes);
                        $list = implode(', ', array_slice($keys, 0, 8));
                        return count($keys) > 8 ? $list . '…' : $list;
                    })
                    ->alignRight()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('attributes_count')
                    ->label('Attrs')
                    ->getStateUsing(function ($record) {
                        $attrs = $record->properties['attributes'] ?? [];
                        return is_array($attrs) ? count($attrs) : 0;
                    })
                    ->badge()
                    ->color('secondary')
                    ->alignRight()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    \Filament\Actions\Action::make('filter_by_causer')
                        ->label('Filter by causer')
                        ->icon('heroicon-o-funnel')
                        ->visible(fn ($record) => $record->user_id || $record->customer_id)
                        ->action(function ($record) {
                            $filters = [];
                            if ($record->user_id) { $filters['causer'] = ['user_id' => $record->user_id]; }
                            if ($record->customer_id) { $filters['causer'] = array_merge($filters['causer'] ?? [], ['customer_id' => $record->customer_id]); }
                            $this->tableFilters = array_merge($this->tableFilters ?? [], $filters);
                        }),
                    \Filament\Actions\Action::make('view_diff')
                        ->label('View Diff')
                        ->icon('heroicon-o-arrows-right-left')
                        ->visible(fn ($record) => ($record->action === 'updated') && !empty($record->properties['changes'] ?? null))
                        ->modalHeading('Changes')
                        ->modalContent(function ($record) {
                            $changes = $record->properties['changes'] ?? [];
                            $html = '<div class="space-y-2">';
                            foreach ($changes as $key => $value) {
                                $before = htmlspecialchars(json_encode($value['before'] ?? null, JSON_UNESCAPED_SLASHES));
                                $after = htmlspecialchars(json_encode($value['after'] ?? $value, JSON_UNESCAPED_SLASHES));
                                $html .= "<div class='text-xs'><strong>{$key}</strong><div class='grid grid-cols-2 gap-2 font-mono'><div class='bg-red-50 dark:bg-red-900/20 p-2 rounded'>- {$before}</div><div class='bg-green-50 dark:bg-green-900/20 p-2 rounded'>+ {$after}</div></div></div>";
                            }
                            $html .= '</div>';
                            return new \Illuminate\Support\HtmlString($html);
                        })
                        ->requiresConfirmation(),
                    \Filament\Actions\Action::make('view_json')
                        ->label('View JSON')
                        ->icon('heroicon-o-code-bracket-square')
                        ->modalHeading('Log Properties')
                        ->modalContent(fn ($record) => new \Illuminate\Support\HtmlString('<pre class="text-sm">'.e(json_encode($record->properties, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)).'</pre>'))
                        ->requiresConfirmation(),
                    \Filament\Actions\DeleteAction::make()->visible(fn () => auth()->user()?->hasRole('super-admin')),
                ])->label('Actions')->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_today')->label('Today')
                    ->query(fn (Builder $q) => $q->whereDate('created_at', now()->toDateString())),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('to'),
                    ])->query(function (Builder $q, array $data) {
                        if (! empty($data['from'])) {
                            $q->whereDate('created_at', '>=', $data['from']);
                        }
                        if (! empty($data['to'])) {
                            $q->whereDate('created_at', '<=', $data['to']);
                        }
                        return $q;
                    }),
                Tables\Filters\Filter::make('protocol')
                    ->form([
                        Forms\Components\Select::make('protocol')->options([
                            'vless' => 'vless',
                            'vmess' => 'vmess',
                            'trojan' => 'trojan',
                            'shadowsocks' => 'shadowsocks',
                            'mixed' => 'mixed',
                        ])->native(false)->placeholder('Any'),
                    ])->query(function (Builder $q, array $data) {
                        if ($protocol = $data['protocol'] ?? null) {
                            $q->where(function (Builder $q2) use ($protocol) {
                                $q2->where('subject_type', \App\Models\ServerPlan::class)
                                   ->where(function (Builder $q3) use ($protocol) {
                                       $q3->whereJsonContains('properties->attributes->protocol', $protocol)
                                          ->orWhereJsonContains('properties->changes->protocol->after', $protocol);
                                   });
                            });
                        }
                        return $q;
                    }),
                Tables\Filters\Filter::make('causer')
                    ->form([
                        Forms\Components\Select::make('user_id')->label('User')->relationship('user', 'name')->searchable(),
                        Forms\Components\Select::make('customer_id')->label('Customer')->relationship('customer', 'name')->searchable(),
                    ])->query(function (Builder $q, array $data) {
                        if (!empty($data['user_id'])) {
                            $q->where('user_id', $data['user_id']);
                        }
                        if (!empty($data['customer_id'])) {
                            $q->where('customer_id', $data['customer_id']);
                        }
                        return $q;
                    }),
                Tables\Filters\Filter::make('action')
                    ->form([
                        Forms\Components\Select::make('action')->options([
                            'created' => 'Created',
                            'updated' => 'Updated',
                            'deleted' => 'Deleted',
                        ]),
                    ])->query(fn (Builder $q, array $data) => ($data['action'] ?? null) ? $q->where('action', $data['action']) : $q),
                Tables\Filters\Filter::make('has_changes')
                    ->label('Has Changes')
                    ->form([
                        Forms\Components\ToggleButtons::make('enabled')->boolean()->inline()->label('Only updated with changes'),
                    ])->query(function (Builder $q, array $data) {
                        if (!empty($data['enabled'])) {
                            $q->where('action', 'updated')->whereNotNull('properties->changes');
                        }
                        return $q;
                    }),
                Tables\Filters\Filter::make('subject_type')
                    ->form([
                        Forms\Components\Select::make('subject_type')
                            ->label('Subject')
                            ->options(collect([
                                'Order', 'Server', 'PaymentMethod', 'Customer', 'ServerPlan', 'ServerClient', 'Invoice',
                            ])->mapWithKeys(fn ($c) => ["App\\Models\\$c" => $c])),
                    ])
                    ->query(fn (Builder $q, array $data) => ($data['subject_type'] ?? null) ? $q->where('subject_type', $data['subject_type']) : $q),
                Tables\Filters\Filter::make('billing_cycle')
                    ->form([
                        Forms\Components\Select::make('billing_cycle')->options([
                            'hourly' => 'hourly', 'daily' => 'daily', 'weekly' => 'weekly', 'monthly' => 'monthly', 'quarterly' => 'quarterly', 'yearly' => 'yearly',
                        ])->native(false)->placeholder('Any'),
                    ])->query(function (Builder $q, array $data) {
                        if ($cycle = $data['billing_cycle'] ?? null) {
                            $q->where(function (Builder $q2) use ($cycle) {
                                $q2->where('subject_type', \App\Models\ServerPlan::class)
                                   ->where(function (Builder $q3) use ($cycle) {
                                       $q3->whereJsonContains('properties->attributes->billing_cycle', $cycle)
                                          ->orWhereJsonContains('properties->changes->billing_cycle->after', $cycle);
                                   });
                            });
                        }
                        return $q;
                    }),
                Tables\Filters\Filter::make('visibility')
                    ->form([
                        Forms\Components\Select::make('visibility')->options([
                            'public' => 'public', 'private' => 'private', 'hidden' => 'hidden',
                        ])->native(false)->placeholder('Any'),
                    ])->query(function (Builder $q, array $data) {
                        if ($visibility = $data['visibility'] ?? null) {
                            $q->where(function (Builder $q2) use ($visibility) {
                                $q2->where('subject_type', \App\Models\ServerPlan::class)
                                   ->where(function (Builder $q3) use ($visibility) {
                                       $q3->whereJsonContains('properties->attributes->visibility', $visibility)
                                          ->orWhereJsonContains('properties->changes->visibility->after', $visibility);
                                   });
                            });
                        }
                        return $q;
                    }),
                Tables\Filters\Filter::make('country_code')
                    ->form([
                        Forms\Components\TextInput::make('country_code')->placeholder('e.g., US')->maxLength(2),
                    ])->query(function (Builder $q, array $data) {
                        if ($code = strtoupper((string) ($data['country_code'] ?? ''))) {
                            $q->where(function (Builder $q2) use ($code) {
                                $q2->where('subject_type', \App\Models\ServerPlan::class)
                                   ->where(function (Builder $q3) use ($code) {
                                       $q3->where('properties->attributes->country_code', $code)
                                          ->orWhere('properties->changes->country_code->after', $code);
                                   });
                            });
                        }
                        return $q;
                    }),
                Tables\Filters\Filter::make('supports_ipv6')
                    ->form([
                        Forms\Components\ToggleButtons::make('supports_ipv6')->boolean()->inline()->label('IPv6'),
                    ])->query(function (Builder $q, array $data) {
                        if (array_key_exists('supports_ipv6', $data) && $data['supports_ipv6'] !== null) {
                            $want = (bool) $data['supports_ipv6'];
                            $q->where(function (Builder $q2) use ($want) {
                                $q2->where('subject_type', \App\Models\ServerPlan::class)
                                   ->where(function (Builder $q3) use ($want) {
                                       $q3->where('properties->attributes->supports_ipv6', $want)
                                          ->orWhere('properties->changes->supports_ipv6->after', $want);
                                   });
                            });
                        }
                        return $q;
                    }),
                Tables\Filters\Filter::make('unlimited_traffic')
                    ->form([
                        Forms\Components\ToggleButtons::make('unlimited_traffic')->boolean()->inline()->label('Unlimited'),
                    ])->query(function (Builder $q, array $data) {
                        if (array_key_exists('unlimited_traffic', $data) && $data['unlimited_traffic'] !== null) {
                            $want = (bool) $data['unlimited_traffic'];
                            $q->where(function (Builder $q2) use ($want) {
                                $q2->where('subject_type', \App\Models\ServerPlan::class)
                                   ->where(function (Builder $q3) use ($want) {
                                       $q3->where('properties->attributes->unlimited_traffic', $want)
                                          ->orWhere('properties->changes->unlimited_traffic->after', $want);
                                   });
                            });
                        }
                        return $q;
                    }),
            ])
            ->defaultSort('id', 'desc')
            ->bulkActions([
                \Filament\Actions\BulkAction::make('export_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Collection $records) {
                        $filename = 'activity-logs-' . now()->format('Ymd-His') . '.csv';

                        return response()->streamDownload(function () use ($records) {
                            $out = fopen('php://output', 'w');
                            fputcsv($out, ['When', 'User', 'Action', 'Subject', 'Subject ID', 'IP Address']);

                            foreach ($records as $log) {
                                fputcsv($out, [
                                    optional($log->created_at)?->toDateTimeString(),
                                    optional($log->user)?->name,
                                    $log->action,
                                    class_basename($log->subject_type),
                                    $log->subject_id,
                                    $log->ip_address,
                                ]);
                            }

                            fclose($out);
                        }, $filename, [
                            'Content-Type' => 'text/csv',
                        ]);
                    })
                    ->deselectRecordsAfterCompletion(),
                \Filament\Actions\DeleteBulkAction::make()->visible(fn () => auth()->user()?->hasRole('super-admin')),
            ])
            // Header column toggle action removed for compatibility with current Filament version
            ;

        return self::applyTablePreset($table, [
            'defaultPage' => 50,
            'empty' => [
                'icon' => 'heroicon-o-clipboard',
                'heading' => 'No activity yet',
                'description' => 'System activity will appear here as users and services perform actions.',
            ],
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}
