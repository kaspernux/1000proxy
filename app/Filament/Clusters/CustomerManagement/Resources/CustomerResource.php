<?php

namespace App\Filament\Clusters\CustomerManagement\Resources;

use App\Filament\Clusters\CustomerManagement\Resources\CustomerResource\Pages;
use App\Filament\Clusters\CustomerManagement;
use App\Models\Customer;
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
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
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
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $cluster = CustomerManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = '👥 Customers';

    protected static ?string $pluralModelLabel = 'Customers';

    protected static ?string $modelLabel = 'Customer';

    public static function getLabel(): string
    {
        return 'Customers';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('👤 Customer Information')
                        ->description('Basic customer details and account information')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-user')
                                    ->placeholder('Enter customer name')
                                    ->helperText('Customer full name'),

                                TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Customer::class, 'email', ignoreRecord: true)
                                    ->prefixIcon('heroicon-o-envelope')
                                    ->placeholder('customer@example.com')
                                    ->helperText('Customer email address (login)'),
                            ]),

                            Grid::make(2)->schema([
                                TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(20)
                                    ->prefixIcon('heroicon-o-phone')
                                    ->placeholder('+1 (555) 123-4567')
                                    ->helperText('Customer phone number'),

                                Select::make('locale')
                                    ->label('Language')
                                    ->options([
                                        'en' => '🇺🇸 English',
                                        'es' => '🇪🇸 Spanish',
                                        'fr' => '🇫🇷 French',
                                        'de' => '🇩🇪 German',
                                        'it' => '🇮🇹 Italian',
                                        'pt' => '🇵🇹 Portuguese',
                                        'ru' => '🇷🇺 Russian',
                                        'zh' => '🇨🇳 Chinese',
                                        'ja' => '🇯🇵 Japanese',
                                        'ko' => '🇰🇷 Korean',
                                    ])
                                    ->default('en')
                                    ->prefixIcon('heroicon-o-language')
                                    ->helperText('Customer preferred language'),
                            ]),

                            Grid::make(3)->schema([
                                TextInput::make('refcode')
                                    ->label('Referral Code')
                                    ->maxLength(50)
                                    ->prefixIcon('heroicon-o-gift')
                                    ->placeholder('REF123ABC')
                                    ->helperText('Customer unique referral code'),

                                Select::make('refered_by')
                                    ->label('Referred By')
                                    ->relationship('referrer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->prefixIcon('heroicon-o-users')
                                    ->helperText('Customer who referred this user'),

                                Select::make('timezone')
                                    ->label('Timezone')
                                    ->options([
                                        'UTC' => 'UTC (GMT+0)',
                                        'America/New_York' => 'EST (GMT-5)',
                                        'America/Los_Angeles' => 'PST (GMT-8)',
                                        'Europe/London' => 'GMT (GMT+0)',
                                        'Europe/Paris' => 'CET (GMT+1)',
                                        'Asia/Tokyo' => 'JST (GMT+9)',
                                        'Asia/Shanghai' => 'CST (GMT+8)',
                                        'Australia/Sydney' => 'AEST (GMT+10)',
                                    ])
                                    ->default('UTC')
                                    ->searchable()
                                    ->prefixIcon('heroicon-o-clock')
                                    ->helperText('Customer timezone'),
                            ]),
                        ])->columns(1),

                    Section::make('🔐 Account Settings')
                        ->description('Account status and security settings')
                        ->icon('heroicon-o-shield-check')
                        ->schema([
                            Grid::make(3)->schema([
                                Toggle::make('is_active')
                                    ->label('Account Active')
                                    ->default(true)
                                    ->helperText('Enable/disable customer account'),

                                Toggle::make('email_notifications')
                                    ->label('Email Notifications')
                                    ->default(true)
                                    ->helperText('Send email notifications to customer'),

                                Select::make('theme_mode')
                                    ->label('Theme Preference')
                                    ->options([
                                        'light' => '☀️ Light Mode',
                                        'dark' => '🌙 Dark Mode',
                                        'auto' => '🔄 Auto (System)',
                                    ])
                                    ->default('auto')
                                    ->prefixIcon('heroicon-o-computer-desktop')
                                    ->helperText('Customer theme preference'),
                            ]),

                            Grid::make(2)->schema([
                                TextInput::make('telegram_chat_id')
                                    ->label('Telegram Chat ID')
                                    ->numeric()
                                    ->prefixIcon('heroicon-o-chat-bubble-left-right')
                                    ->placeholder('123456789')
                                    ->helperText('Telegram bot chat ID for notifications'),

                                DateTimePicker::make('email_verified_at')
                                    ->label('Email Verified At')
                                    ->prefixIcon('heroicon-o-check-badge')
                                    ->helperText('When customer verified their email'),
                            ]),
                        ])->columns(1),

                    Section::make('🎯 Customer Properties')
                        ->description('Special customer attributes and settings')
                        ->icon('heroicon-o-star')
                        ->schema([
                            Grid::make(3)->schema([
                                Toggle::make('is_agent')
                                    ->label('Is Agent')
                                    ->helperText('Customer has agent privileges'),

                                TextInput::make('discount_percent')
                                    ->label('Discount %')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->placeholder('0')
                                    ->helperText('Customer discount percentage'),

                                DateTimePicker::make('agent_date')
                                    ->label('Agent Since')
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->helperText('Date customer became an agent')
                                    ->visible(fn (Forms\Get $get) => $get('is_agent')),
                            ]),

                            Textarea::make('spam_info')
                                ->label('Spam/Notes')
                                ->rows(3)
                                ->maxLength(1000)
                                ->placeholder('Any spam reports or special notes about this customer')
                                ->helperText('Internal notes about customer behavior'),
                        ])->columns(1),

                ])->columnSpan(2),

                Group::make()->schema([
                    Section::make('📊 Customer Statistics')
                        ->schema([
                            Placeholder::make('created_at')
                                ->label('Registration Date')
                                ->content(fn (Customer $record): string =>
                                    $record->created_at ? $record->created_at->format('M j, Y g:i A') : 'Not set')
                                ->extraAttributes(['class' => 'text-sm']),

                            Placeholder::make('last_login')
                                ->label('Last Login')
                                ->content(fn (Customer $record): string =>
                                    $record->updated_at ? $record->updated_at->diffForHumans() : 'Never')
                                ->extraAttributes(['class' => 'text-sm']),

                            Placeholder::make('orders_count')
                                ->label('Total Orders')
                                ->content(fn (Customer $record): string =>
                                    $record->orders()->count() . ' orders')
                                ->extraAttributes(['class' => 'text-sm']),

                            Placeholder::make('total_spent')
                                ->label('Total Spent')
                                ->content(fn (Customer $record): string =>
                                    '$' . number_format($record->orders()->where('payment_status', 'paid')->sum('grand_amount'), 2))
                                ->extraAttributes(['class' => 'text-sm']),

                            Placeholder::make('wallet_balance')
                                ->label('Wallet Balance')
                                ->content(fn (Customer $record): string =>
                                    $record->wallet ? '$' . number_format($record->wallet->balance, 2) : 'No wallet')
                                ->extraAttributes(['class' => 'text-sm']),

                            Placeholder::make('referrals_count')
                                ->label('Referrals Made')
                                ->content(fn (Customer $record): string =>
                                    $record->referredCustomers()->count() . ' customers')
                                ->extraAttributes(['class' => 'text-sm']),
                        ]),

                    Section::make('🚀 Quick Actions')
                        ->schema([
                            Placeholder::make('actions_info')
                                ->content('Use the action buttons to:')
                                ->extraAttributes(['class' => 'text-sm text-gray-600']),

                            Placeholder::make('actions_list')
                                ->content('• View customer orders<br>• Check wallet transactions<br>• Send notifications<br>• Manage account status')
                                ->extraAttributes(['class' => 'text-xs text-gray-500'])
                        ])
                        ->hidden(fn (?Customer $record) => $record === null),
                ])->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('👤')
                    ->circular()
                    ->defaultImageUrl(fn (Customer $record): string =>
                        'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF')
                    ->size(40),

                TextColumn::make('name')
                    ->label('🏷️ Customer Name')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->copyable()
                    ->weight('bold')
                    ->description(fn (Customer $record): string => $record->email)
                    ->color('primary'),

                TextColumn::make('email')
                    ->label('📧 Email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('is_active')
                    ->label('📊 Status')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => true,
                        'heroicon-o-x-circle' => false,
                    ]),

                TextColumn::make('orders_count')
                    ->label('🛒 Orders')
                    ->counts('orders')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('total_spent')
                    ->label('💰 Total Spent')
                    ->getStateUsing(fn (Customer $record): string =>
                        '$' . number_format($record->orders()->where('payment_status', 'paid')->sum('grand_amount'), 2))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->withSum(['orders' => function ($query) {
                            $query->where('payment_status', 'paid');
                        }], 'grand_amount')->orderBy('orders_sum_grand_amount', $direction);
                    })
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('wallet.balance')
                    ->label('👛 Wallet')
                    ->money()
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->toggleable(),

                BadgeColumn::make('is_agent')
                    ->label('🎯 Agent')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Agent' : 'Customer')
                    ->colors([
                        'warning' => true,
                        'gray' => false,
                    ])
                    ->icons([
                        'heroicon-o-star' => true,
                        'heroicon-o-user' => false,
                    ])
                    ->toggleable(),

                TextColumn::make('referrals_count')
                    ->label('🎁 Referrals')
                    ->counts('referredCustomers')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('locale')
                    ->label('🌐 Language')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'en' => '🇺🇸 EN',
                        'es' => '🇪🇸 ES',
                        'fr' => '🇫🇷 FR',
                        'de' => '🇩🇪 DE',
                        'it' => '🇮🇹 IT',
                        'pt' => '🇵🇹 PT',
                        'ru' => '🇷🇺 RU',
                        'zh' => '🇨🇳 ZH',
                        'ja' => '🇯🇵 JA',
                        'ko' => '🇰🇷 KO',
                        default => strtoupper($state)
                    })
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('email_verified_at')
                    ->label('✅ Verified')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('📅 Registered')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->description(fn (Customer $record): string =>
                        $record->created_at ? $record->created_at->diffForHumans() : '')
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('🔄 Last Active')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('telegram_status')
                    ->label('💬 Telegram')
                    ->getStateUsing(fn (Customer $record): string =>
                        $record->hasTelegramLinked() ? 'Linked' : 'Not Linked')
                    ->colors([
                        'success' => 'Linked',
                        'gray' => 'Not Linked',
                    ])
                    ->icons([
                        'heroicon-o-chat-bubble-left-right' => 'Linked',
                        'heroicon-o-minus-circle' => 'Not Linked',
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('active_clients_count')
                    ->label('🖥️ Active Services')
                    ->getStateUsing(fn (Customer $record): int =>
                        $record->clients()->where('enable', true)->count())
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('last_order_date')
                    ->label('🛍️ Last Order')
                    ->getStateUsing(fn (Customer $record): ?string =>
                        $record->orders()->latest()->first()?->created_at?->diffForHumans())
                    ->placeholder('No orders')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->withMax('orders', 'created_at')->orderBy('orders_max_created_at', $direction);
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Account Status')
                    ->options([
                        true => '✅ Active',
                        false => '❌ Inactive',
                    ]),

                SelectFilter::make('is_agent')
                    ->label('Customer Type')
                    ->options([
                        true => '🎯 Agents',
                        false => '👤 Regular Customers',
                    ]),

                SelectFilter::make('locale')
                    ->label('Language')
                    ->options([
                        'en' => '🇺🇸 English',
                        'es' => '🇪🇸 Spanish',
                        'fr' => '🇫🇷 French',
                        'de' => '🇩🇪 German',
                        'it' => '🇮🇹 Italian',
                        'pt' => '🇵🇹 Portuguese',
                        'ru' => '🇷🇺 Russian',
                        'zh' => '🇨🇳 Chinese',
                        'ja' => '🇯🇵 Japanese',
                        'ko' => '🇰🇷 Korean',
                    ])
                    ->multiple(),

                Filter::make('email_verified')
                    ->label('Email Verified')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at')),

                Filter::make('has_orders')
                    ->label('Has Orders')
                    ->query(fn (Builder $query): Builder => $query->has('orders')),

                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Registered From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Registered Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                Filter::make('wallet_balance')
                    ->form([
                        Forms\Components\TextInput::make('balance_from')
                            ->label('Wallet Balance From')
                            ->numeric(),
                        Forms\Components\TextInput::make('balance_to')
                            ->label('Wallet Balance To')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['balance_from'],
                                fn (Builder $query, $amount): Builder =>
                                    $query->whereHas('wallet', fn ($q) => $q->where('balance', '>=', $amount))
                            )
                            ->when(
                                $data['balance_to'],
                                fn (Builder $query, $amount): Builder =>
                                    $query->whereHas('wallet', fn ($q) => $q->where('balance', '<=', $amount))
                            );
                    }),

                SelectFilter::make('has_telegram')
                    ->label('Telegram Status')
                    ->options([
                        'linked' => '✅ Telegram Linked',
                        'not_linked' => '❌ No Telegram',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match($data['value'] ?? null) {
                            'linked' => $query->whereNotNull('telegram_chat_id'),
                            'not_linked' => $query->whereNull('telegram_chat_id'),
                            default => $query,
                        };
                    }),

                Filter::make('high_value_customers')
                    ->label('High Value Customers')
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('orders', function ($q) {
                            $q->selectRaw('sum(grand_amount) as total_spent')
                              ->groupBy('customer_id')
                              ->havingRaw('sum(grand_amount) >= 500');
                        });
                    }),

                Filter::make('recent_activity')
                    ->form([
                        Forms\Components\Select::make('activity_period')
                            ->label('Recent Activity')
                            ->options([
                                '24h' => 'Last 24 Hours',
                                '7d' => 'Last 7 Days',
                                '30d' => 'Last 30 Days',
                                '90d' => 'Last 90 Days',
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $period = $data['activity_period'] ?? null;
                        return match($period) {
                            '24h' => $query->where('updated_at', '>=', now()->subDay()),
                            '7d' => $query->where('updated_at', '>=', now()->subWeek()),
                            '30d' => $query->where('updated_at', '>=', now()->subMonth()),
                            '90d' => $query->where('updated_at', '>=', now()->subMonths(3)),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-o-eye'),

                EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil'),

                Action::make('toggle_status')
                    ->label(fn (Customer $record): string => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (Customer $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Customer $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Customer $record) {
                        $record->update(['is_active' => !$record->is_active]);

                        Notification::make()
                            ->title('Customer status updated')
                            ->body("Customer {$record->name} has been " . ($record->is_active ? 'activated' : 'deactivated'))
                            ->success()
                            ->send();
                    }),

                Action::make('view_orders')
                    ->label('View Orders')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('info')
                    ->url(fn (Customer $record): string =>
                        route('filament.admin.proxy-shop.resources.orders.index', ['tableFilters[customer_id][value]' => $record->id])),

                Action::make('view_wallet')
                    ->label('Wallet')
                    ->icon('heroicon-o-wallet')
                    ->color('warning')
                    ->url(fn (Customer $record): string =>
                        route('filament.admin.customer-management.resources.wallets.index', ['tableFilters[customer_id][value]' => $record->id]))
                    ->visible(fn (Customer $record): bool => $record->wallet !== null),

                Action::make('send_message')
                    ->label('Send Message')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->form([
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->label('Subject'),
                        Forms\Components\Textarea::make('message')
                            ->required()
                            ->label('Message')
                            ->rows(4),
                        Forms\Components\Toggle::make('send_email')
                            ->label('Send via Email')
                            ->default(true),
                        Forms\Components\Toggle::make('send_telegram')
                            ->label('Send via Telegram')
                            ->default(true)
                            ->visible(fn (Customer $record): bool => $record->hasTelegramLinked()),
                    ])
                    ->action(function (Customer $record, array $data) {
                        $messagesSent = 0;

                        try {
                            if ($data['send_email'] && $record->email_notifications && $record->email) {
                                Mail::send('emails.admin-notification', [
                                    'customer' => $record,
                                    'subject' => $data['subject'],
                                    'messageContent' => $data['message']
                                ], function ($message) use ($record, $data) {
                                    $message->to($record->email, $record->name)
                                           ->subject($data['subject']);
                                });
                                $messagesSent++;
                            }

                            if ($data['send_telegram'] && $record->hasTelegramLinked()) {
                                $telegramService = app(\App\Services\TelegramBotService::class);
                                $telegramService->sendDirectMessage(
                                    $record->telegram_chat_id,
                                    "📢 *{$data['subject']}*\n\n{$data['message']}"
                                );
                                $messagesSent++;
                            }

                            Notification::make()
                                ->title('Message sent successfully')
                                ->body("Sent {$messagesSent} message(s) to {$record->name}")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to send message')
                                ->body("Error: " . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('impersonate')
                    ->label('Login as Customer')
                    ->icon('heroicon-o-user-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('This will log you in as this customer. Use with caution.')
                    ->action(function (Customer $record) {
                        // Store current admin session
                        session(['admin_impersonating' => Auth::user()->id]);

                        // Login as customer
                        Auth::guard('customer')->login($record);

                        Notification::make()
                            ->title('Now logged in as customer')
                            ->body("You are now impersonating {$record->name}")
                            ->warning()
                            ->send();

                        // Redirect to customer dashboard
                        return redirect()->route('filament.customer.pages.dashboard');
                    })
                    ->visible(fn (): bool => Auth::check()),

                Action::make('view_activity')
                    ->label('View Activity')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->modalContent(function (Customer $record) {
                        $activities = [
                            'Last Login' => $record->last_login_at?->format('M j, Y H:i') ?? 'Never',
                            'Total Orders' => $record->orders()->count(),
                            'Completed Orders' => $record->orders()->where('order_status', 'completed')->count(),
                            'Active Services' => $record->clients()->where('is_active', true)->count(),
                            'Wallet Balance' => '$' . number_format($record->wallet?->balance ?? 0, 2),
                            'Total Spent' => '$' . number_format($record->orders()->where('payment_status', 'paid')->sum('grand_amount'), 2),
                            'Referrals Made' => $record->referredCustomers()->count(),
                            'Telegram Status' => $record->hasTelegramLinked() ? 'Linked' : 'Not Linked',
                        ];

                        $content = '<div class="space-y-3">';
                        foreach ($activities as $label => $value) {
                            $content .= "<div class='flex justify-between'><span class='font-medium'>{$label}:</span><span>{$value}</span></div>";
                        }
                        $content .= '</div>';

                        return new \Illuminate\Support\HtmlString($content);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Customers')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(fn (Customer $record) => $record->update(['is_active' => true]));

                            Notification::make()
                                ->title('Customers activated')
                                ->body("{$records->count()} customers have been activated.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Customers')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(fn (Customer $record) => $record->update(['is_active' => false]));

                            Notification::make()
                                ->title('Customers deactivated')
                                ->body("{$records->count()} customers have been deactivated.")
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('reset_passwords')
                        ->label('Reset Passwords')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalDescription('This will send password reset emails to selected customers.')
                        ->action(function (Collection $records) {
                            $passwordResetsSent = 0;

                            foreach ($records as $customer) {
                                try {
                                    // Generate password reset token and send notification
                                    $token = Str::random(60);
                                    $customer->forceFill([
                                        'remember_token' => hash('sha256', $token),
                                        'updated_at' => now(),
                                    ])->save();

                                    // Send password reset notification (this will use the customer's notification settings)
                                    $customer->sendPasswordResetNotification($token);
                                    $passwordResetsSent++;
                                } catch (\Exception $e) {
                                    Log::error("Failed to send password reset to customer {$customer->id}: " . $e->getMessage());
                                }
                            }

                            Notification::make()
                                ->title('Password reset emails sent')
                                ->body("Successfully sent {$passwordResetsSent} password reset emails.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('suspend_accounts')
                        ->label('Suspend Accounts')
                        ->icon('heroicon-o-pause-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription('This will suspend selected customer accounts and disable their services.')
                        ->action(function (Collection $records) {
                            $records->each(function (Customer $record) {
                                $record->update([
                                    'is_active' => false,
                                    'suspended_at' => now(),
                                    'suspension_reason' => 'Bulk suspension by admin'
                                ]);

                                // Disable all their server clients
                                $record->clients()->update(['is_active' => false]);
                            });

                            Notification::make()
                                ->title('Accounts suspended')
                                ->body("{$records->count()} customer accounts have been suspended.")
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('export_data')
                        ->label('Export Customer Data')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->action(function (Collection $records) {
                            // Create CSV export of customer data
                            $csvData = [];
                            $csvData[] = ['ID', 'Name', 'Email', 'Phone', 'Status', 'Created At', 'Wallet Balance', 'Total Orders', 'Last Login'];

                            foreach ($records as $customer) {
                                $csvData[] = [
                                    $customer->id,
                                    $customer->name,
                                    $customer->email,
                                    $customer->phone,
                                    $customer->is_active ? 'Active' : 'Inactive',
                                    $customer->created_at->format('Y-m-d H:i:s'),
                                    $customer->wallet?->balance ?? 0,
                                    $customer->orders()->count(),
                                    $customer->last_login_at?->format('Y-m-d H:i:s') ?? 'Never'
                                ];
                            }

                            $filename = 'customers_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
                            $filePath = storage_path('app/public/exports/' . $filename);

                            // Ensure directory exists
                            if (!file_exists(dirname($filePath))) {
                                mkdir(dirname($filePath), 0755, true);
                            }

                            $file = fopen($filePath, 'w');
                            foreach ($csvData as $row) {
                                fputcsv($file, $row);
                            }
                            fclose($file);

                            Notification::make()
                                ->title('Export completed')
                                ->body("Customer data exported successfully. Download: {$filename}")
                                ->success()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('download')
                                        ->label('Download')
                                        ->url(asset('storage/exports/' . $filename))
                                        ->openUrlInNewTab()
                                ])
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('send_notification')
                        ->label('Send Notification')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->form([
                            Forms\Components\TextInput::make('subject')
                                ->required()
                                ->label('Subject'),
                            Forms\Components\Textarea::make('message')
                                ->required()
                                ->label('Message')
                                ->rows(3),
                        ])
                        ->action(function (Collection $records, array $data) {
                            // Send notifications to selected customers
                            $notificationsSent = 0;

                            foreach ($records as $customer) {
                                try {
                                    // Send email notification if customer has email notifications enabled
                                    if ($customer->email_notifications && $customer->email) {
                                        Mail::send('emails.admin-notification', [
                                            'customer' => $customer,
                                            'subject' => $data['subject'],
                                            'messageContent' => $data['message']
                                        ], function ($message) use ($customer, $data) {
                                            $message->to($customer->email, $customer->name)
                                                   ->subject($data['subject']);
                                        });
                                        $notificationsSent++;
                                    }

                                    // Send Telegram notification if customer has Telegram linked
                                    if ($customer->hasTelegramLinked()) {
                                        $telegramService = app(\App\Services\TelegramBotService::class);
                                        $telegramService->sendDirectMessage(
                                            $customer->telegram_chat_id,
                                            "📢 *{$data['subject']}*\n\n{$data['message']}"
                                        );
                                        $notificationsSent++;
                                    }

                                } catch (\Exception $e) {
                                    Log::error("Failed to send notification to customer {$customer->id}: " . $e->getMessage());
                                }
                            }

                            Notification::make()
                                ->title('Notifications sent successfully')
                                ->body("Successfully sent {$notificationsSent} notifications to {$records->count()} customers.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('created_at', '>=', now()->subDays(7))->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('created_at', '>=', now()->subDays(7))->count() > 0 ? 'success' : null;
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['wallet']);
    }

    public static function getGlobalSearchAttributes(): array
    {
        return ['name', 'email', 'phone', 'refcode'];
    }
}
