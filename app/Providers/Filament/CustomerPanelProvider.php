<?php

namespace App\Providers\Filament;

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
use App\Filament\Customer\Widgets\ConfigurationOverviewWidget;
use App\Filament\Customer\Widgets\CustomerRecentOrdersWidget;
use App\Http\Middleware\SyncCustomerPreferences;
use App\Http\Middleware\SetLocaleFromSession;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\AuthenticateSession as AuthenticateSessionMiddleware;
use App\Http\Middleware\ShareErrorsFromSession as ShareErrorsFromSessionMiddleware;
use App\Http\Middleware\DispatchServingFilamentEvent as DispatchServingFilamentEventMiddleware;
use Filament\Enums\ThemeMode;
use Filament\Facades\Filament;
use Filament\Support\Enums\Width;

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
            ->maxContentWidth(Width::Full)
            // Use the global app.css as the Filament theme stylesheet
                // Use native Filament v4 theme (no custom viteTheme override)
            ->widgets([
                CustomerStatsOverview::class,
                SupportOverviewWidget::class,
                DownloadOverviewWidget::class,
                ConfigurationOverviewWidget::class,
                CustomerRecentOrdersWidget::class,
            ])
            ->defaultThemeMode(match (session('theme_mode', 'system')) {
                'dark' => ThemeMode::Dark,
                'light' => ThemeMode::Light,
                default => ThemeMode::System,
            })
            // Enable Filament's database notifications bell in the topbar (lazy-loaded by default)
            ->databaseNotifications(true, livewireComponent: null, isLazy: true)
            // Poll every 30s by default; adjust if you want more/less frequent updates
            ->databaseNotificationsPolling('30s')
            ->discoverPages(   in: app_path('Filament/Customer/Pages'),       for: 'App\\Filament\\Customer\\Pages')
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
                    ->label(fn () => 'Active Clients: ' . \App\Models\ServerClient::where('customer_id', auth('customer')->id())->where('status', 'active')->count())
                    ->icon('heroicon-o-server-stack')
                    ->visible(fn (): bool => auth('customer')->check()),

                // 2) Wallet balance — only when logged in:
                MenuItem::make('wallet')
                    ->label(fn () => 'Wallet: $' . number_format(auth('customer')->user()->getWallet()->balance, 2))
                    ->icon('heroicon-o-currency-dollar')
                    ->visible(fn (): bool => auth('customer')->check()),

                // 3) New orders — only when logged in:
                MenuItem::make('orders_new')
                    ->label(fn () => 'New: ' . auth('customer')->user()->orders()->where('order_status', 'new')->count())
                    ->icon('heroicon-o-shopping-cart')
                    // Removed OrderResource reference
                    ->visible(fn (): bool => auth('customer')->check()),
                // 3) Completed orders — only when logged in:
                MenuItem::make('orders_completed')
                    ->label(fn () => 'Completed: ' . auth('customer')->user()->orders()->where('order_status', 'completed')->count())
                    ->icon('heroicon-o-check-circle')
                    // Removed OrderResource reference
                    ->visible(fn (): bool => auth('customer')->check()),
                // 3) Processing orders — only when logged in:
                MenuItem::make('orders_processing')
                    ->label(fn () => 'Processing: ' . auth('customer')->user()->orders()->where('order_status', 'processing')->count())
                    ->icon('heroicon-o-arrow-path')
                    // Removed OrderResource reference
                    ->visible(fn (): bool => auth('customer')->check()),
                // 3) Canceled orders — only when logged in:
                MenuItem::make('orders_disputed')
                    ->label(fn () => 'Dispute: ' . auth('customer')->user()->orders()->where('order_status', 'dispute')->count())
                    ->icon('heroicon-o-shield-exclamation')
                    // Removed OrderResource reference
                    ->visible(fn (): bool => auth('customer')->check()),
                // 4) Profile (edit your own record) — keyed so Filament wires up its own “profile” logic:
                'profile' => MenuItem::make('profile')
                    ->label('Settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->url(fn () => route('filament.customer.pages.user-profile'))
                    ->visible(fn (): bool => auth('customer')->check()),

                // 5) Logout — wired up automatically by Filament when keyed “logout”
                'logout'  => MenuItem::make('logout')
                    ->label('Log out')
                    ->icon('heroicon-o-arrow-left-start-on-rectangle')
                    ->url(fn () => url('/logout')),
            ])
            ->bootUsing(function(){
                // Ensure SweetAlert2 + Livewire Alert scripts are available inside the Filament customer panel
                \Filament\Support\Facades\FilamentView::registerRenderHook(
                    \Filament\View\PanelsRenderHook::BODY_END,
                    fn () => view('partials.livewire-alert-filament'),
                );

                // Inject shared Filament custom CSS (scoped) used by both panels
                \Filament\Support\Facades\FilamentView::registerRenderHook(
                    \Filament\View\PanelsRenderHook::HEAD_END,
                    fn () => view('partials.filament-custom-theme'),
                );

                // Add aggregated Tailwind custom theme for Filament panels (keeps native theme)
                \Filament\Support\Facades\FilamentView::registerRenderHook(
                    \Filament\View\PanelsRenderHook::HEAD_END,
                    fn () => new \Illuminate\Support\HtmlString( app(\Illuminate\Foundation\Vite::class)(['resources/css/filament/custom-panels.css']) ),
                );

                // Quick actions: move next to the page title/actions for better context
                \Filament\Support\Facades\FilamentView::registerRenderHook(
                    \Filament\View\PanelsRenderHook::PAGE_HEADER_ACTIONS_AFTER,
                    function () {
                        return <<<'HTML'
                            <div class="hidden md:flex items-center gap-2">
                                <!-- <a href="/account/my-active-servers" class="inline-flex items-center gap-1 px-2 py-1 text-sm rounded bg-emerald-600 text-white hover:bg-emerald-700 hs-transition-opacity"> 
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h3.75M3.75 3h16.5M3.75 3H6m14.25 0v11.25A2.25 2.25 0 0118 16.5h-2.25m4.5-13.5H18m0 0H6M9.75 16.5H6m3.75 0h3.75m0 0V21m0-4.5H18" /></svg>
                                    <span>Active</span>
                                </a>
                                <a href="/account/server-browsing" class="inline-flex items-center gap-1 px-2 py-1 text-sm rounded bg-sky-600 text-white hover:bg-sky-700 hs-transition-opacity">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h15.75c.621 0 1.125.504 1.125 1.125v6.75A2.625 2.625 0 0118.375 22.5H5.625A2.625 2.625 0 013 19.875v-6.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12V8.625a5.25 5.25 0 1110.5 0V12" /></svg>
                                    <span>Browse</span>
                                </a>
                                <a href="/account/wallet-management" class="inline-flex items-center gap-1 px-2 py-1 text-sm rounded bg-amber-600 text-white hover:bg-amber-700 hs-transition-opacity">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.75V8.25A2.25 2.25 0 0018.75 6h-15A2.25 2.25 0 001.5 8.25v7.5A2.25 2.25 0 003.75 18h12.75A2.25 2.25 0 0018.75 15.75V12.75M21 12.75h-3.75a2.25 2.25 0 100 4.5H21v-4.5z" /></svg>
                                    <span>Wallet</span>
                                </a> -->
                                <button id="theme-toggle" type="button" class="inline-flex items-center gap-1 px-2 py-1 text-sm rounded bg-gray-700 text-white hover:bg-gray-800 hs-transition-opacity">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4"><path d="M21.752 15.002A9.718 9.718 0 0019.5 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-.79.091-1.56.263-2.296A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" /></svg>
                                    <span>Theme</span>
                                </button>
                            </div>
                            <script>
                                (function(){
                                    document.addEventListener('click', function(e){
                                        if (e.target && (e.target.id === 'theme-toggle' || e.target.closest('#theme-toggle'))) {
                                            const root = document.documentElement;
                                            const isDark = root.classList.toggle('dark');
                                            fetch('/api/theme', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, body: JSON.stringify({ mode: isDark ? 'dark' : 'light' }) }).catch(()=>{});
                                        }
                                    });
                                })();
                            </script>
                        HTML;
                    }
                );

                // Mobile-friendly quick actions below header widgets
                \Filament\Support\Facades\FilamentView::registerRenderHook(
                    \Filament\View\PanelsRenderHook::PAGE_HEADER_WIDGETS_AFTER,
                    function () {
                        return <<<'HTML'
                            <div class="md:hidden grid grid-cols-3 gap-2 mt-2">
                                <!-- <a href="/account/my-active-servers" class="inline-flex items-center justify-center gap-1 px-2 py-2 text-sm rounded bg-emerald-600 text-white hover:bg-emerald-700">
                                    <span>Active</span>
                                </a>
                                <a href="/account/server-browsing" class="inline-flex items-center justify-center gap-1 px-2 py-2 text-sm rounded bg-sky-600 text-white hover:bg-sky-700">
                                    <span>Browse</span>
                                </a> -->
                                <a href="/account/wallet-management" class="inline-flex items-center justify-center gap-1 px-2 py-2 text-sm rounded bg-amber-600 text-white hover:bg-amber-700">
                                    <span>Wallet</span>
                                </a>
                            </div>
                        HTML;
                    }
                );
            });
    }
}
