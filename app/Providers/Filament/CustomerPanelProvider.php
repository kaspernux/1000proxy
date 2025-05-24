<?php

namespace App\Providers\Filament;

use Filament\Facades\Filament;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;

use App\Http\Middleware\AuthenticateCustomer;
use App\Filament\Customer\Widgets\CustomerStatsOverview;
use App\Filament\Customer\Widgets\SupportOverviewWidget;
use App\Filament\Customer\Widgets\DownloadOverviewWidget;
use App\Filament\Customer\Clusters\MyWallet\Resources\WalletResource;
use App\Filament\Customer\Clusters\MyOrders\Resources\OrderResource;
use App\Filament\Customer\Clusters\MyTools\Resources\CustomerResource;
use App\Filament\Customer\Resources\CustomerServerClientResource;
use App\Http\Middleware\SyncCustomerPreferences;
use App\Http\Middleware\SetLocaleFromSession;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\AuthenticateSession as AuthenticateSessionMiddleware;
use App\Http\Middleware\ShareErrorsFromSession as ShareErrorsFromSessionMiddleware;
use App\Http\Middleware\DispatchServingFilamentEvent as DispatchServingFilamentEventMiddleware;
use Filament\Enums\ThemeMode;

class CustomerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('customer')
            ->path('account')
            ->login()              // /account/login
            ->passwordReset()      // /account/password-reset
            ->revealablePasswords(false)
            ->profile(isSimple: false)
            ->colors([
                'primary' => \Filament\Support\Colors\Color::Green,
            ])
            ->widgets([
                CustomerStatsOverview::class,
                SupportOverviewWidget::class,
                DownloadOverviewWidget::class,
            ])
            ->defaultThemeMode(match (session('theme_mode', 'system')) {
                'dark' => ThemeMode::Dark,
                'light' => ThemeMode::Light,
                default => ThemeMode::System,
            })
            ->discoverResources(in: app_path('Filament/Customer/Resources'), for: 'App\\Filament\\Customer\\Resources')
            ->discoverPages(   in: app_path('Filament/Customer/Pages'),       for: 'App\\Filament\\Customer\\Pages')
            ->discoverClusters(in: app_path('Filament/Customer/Clusters'),    for: 'App\\Filament\\Customer\\Clusters')
            ->authGuard('customer')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SyncCustomerPreferences::class,
                SetLocaleFromSession::class,
            ])
            ->authMiddleware([
                AuthenticateCustomer::class,
            ])

            // **Pass an array**, not a closure, to userMenuItems():
            ->userMenuItems([
                // 1) Active clients — only when logged in as a customer:
                MenuItem::make('Proxys')
                    ->label(fn () => 'Active Proxies: ' . auth('customer')->user()->hasclients()->count())
                    ->icon('heroicon-o-server-stack')
                    ->url(fn (): string => CustomerServerClientResource::getUrl('index', [], 'customer'))
                    ->visible(fn (): bool => auth('customer')->check()),

                // 2) Wallet balance — only when logged in:
                MenuItem::make('wallet')
                    ->label(fn () => 'Wallet: $' . number_format(auth('customer')->user()->getWallet()->balance, 2))
                    ->icon('heroicon-o-currency-dollar')
                    ->url(fn (): string => WalletResource::getUrl('index', [], 'customer'))
                    ->visible(fn (): bool => auth('customer')->check()),

                // 3) New orders — only when logged in:
                MenuItem::make('orders_new')
                    ->label(fn () => 'New: ' . auth('customer')->user()->orders()->where('order_status', 'new')->count())
                    ->icon('heroicon-o-shopping-cart')
                    ->url(fn (): string => OrderResource::getUrl('index', ['filters' => ['order_status' => 'new']], 'customer'))
                    ->visible(fn (): bool => auth('customer')->check()),
                // 3) Completed orders — only when logged in:
                MenuItem::make('orders_completed')
                    ->label(fn () => 'Completed: ' . auth('customer')->user()->orders()->where('order_status', 'completed')->count())
                    ->icon('heroicon-o-check-circle')
                    ->url(fn (): string => OrderResource::getUrl('index', ['filters' => ['order_status' => 'completed']], 'customer'))
                    ->visible(fn (): bool => auth('customer')->check()),
                // 3) Processing orders — only when logged in:
                MenuItem::make('orders_processing')
                    ->label(fn () => 'Processing: ' . auth('customer')->user()->orders()->where('order_status', 'processing')->count())
                    ->icon('heroicon-o-arrow-path')
                    ->url(fn (): string => OrderResource::getUrl('index', ['filters' => ['order_status' => 'processing']], 'customer'))
                    ->visible(fn (): bool => auth('customer')->check()),
                // 3) Canceled orders — only when logged in:
                MenuItem::make('orders_disputed')
                    ->label(fn () => 'Dispute: ' . auth('customer')->user()->orders()->where('order_status', 'dispute')->count())
                    ->icon('heroicon-o-shield-exclamation')
                    ->url(fn (): string => OrderResource::getUrl('index', ['filters' => ['order_status' => 'dispute']], 'customer'))
                    ->visible(fn (): bool => auth('customer')->check()),
                // 4) Profile (edit your own record) — keyed so Filament wires up its own “profile” logic:
                'profile' => MenuItem::make('profile')
                    ->label('Settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->url(fn () => CustomerResource::getUrl('edit', parameters: [], panel: 'customer'))
                    ->visible(fn (): bool => auth('customer')->check()),

                // 5) Logout — wired up automatically by Filament when keyed “logout”
                'logout'  => MenuItem::make('logout')
                    ->label('Log out')
                    ->icon('heroicon-o-arrow-left-start-on-rectangle'),
            ]);
    }
}
