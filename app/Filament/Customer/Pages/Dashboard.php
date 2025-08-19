<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Widgets\Widget;
use Filament\Actions\Action;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Filament\Customer\Widgets\CustomerStatsOverview;
use App\Filament\Customer\Widgets\SupportOverviewWidget;
use App\Filament\Customer\Widgets\DownloadOverviewWidget;
use App\Filament\Customer\Widgets\ConfigurationOverviewWidget;
use App\Filament\Customer\Widgets\CustomerRecentOrdersWidget;
use App\Models\Wallet;
use App\Models\Order;
use App\Models\ServerClient;
use BackedEnum;

class Dashboard extends Page
{

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-home';
    protected static ?string $title      = 'Dashboard';
    protected string $view = 'filament.customer.pages.dashboard';
    protected static ?int $navigationSort = 1;
    protected static bool $shouldRegisterNavigation = true; // explicit

    /**
     * @return array<string,int>|int
     */
    public function getColumns(): array|int
    {
        return [ 'md' => 2, 'xl' => 3 ];
    }


    protected function getHeaderActions(): array
    {
        return [
            Action::make('home')
                ->label('Home')
                ->icon('heroicon-o-home')
                ->color('gray')
                ->url(fn (): string => url('/')),

            Action::make('myActiveServers')
                ->label('My Active Servers')
                ->icon('heroicon-o-server-stack')
                ->color('primary')
                ->url(fn (): string => route('filament.customer.pages.my-active-servers')),

            Action::make('browseServers')
                ->label('Browse Servers')
                ->icon('heroicon-o-server')
                ->color('info')
                ->url(fn (): string => route('filament.customer.pages.server-browsing')),
        ];
    }

    /**
     * @return array<class-string<Widget>>
     */
    public function getWidgets(): array
    {
        return [
            CustomerStatsOverview::class,
            SupportOverviewWidget::class,
            ConfigurationOverviewWidget::class,
            CustomerRecentOrdersWidget::class,
            DownloadOverviewWidget::class,
        ];
    }

    /**
     * Return widgets that should be rendered. For now, all widgets are visible.
     * Matches what Filament's Dashboard base would normally expose.
     *
     * @return array<class-string<Widget>>
     */
    public function getVisibleWidgets(): array
    {
        return $this->getWidgets();
    }

    /**
     * Additional data passed into widgets component (none for now).
     *
     * @return array<string,mixed>
     */
    public function getWidgetData(): array
    {
        return [];
    }

    /**
     * Accepts either a Form (legacy / when filters feature expects a form) or an Infolist (newer Filament internals) and returns it untouched.
     * This neutral implementation prevents the TypeError that occurred when Filament attempted to inject an Infolist into a method strictly typed for Form.
     * If you later want to add dashboard-level filters, you can detect the instance type and configure accordingly.
     */
    public function filtersForm(Form|Infolist $form): Form|Infolist
    {
        return $form; // No filters for now.
    }

    /**
     * Livewire mount hook - log when the dashboard component initializes.
     */
    public function mount(): void
    {
        Log::info('Customer Dashboard mount executed', [
            'class' => static::class,
            'shouldRegisterNavigation' => static::$shouldRegisterNavigation,
        ]);
    }

    /**
     * Override to inject logging while preserving default navigation item generation.
     *
     * @return array<\Filament\Navigation\NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        Log::info('Customer Dashboard getNavigationItems invoked', [
            'class' => static::class,
            'shouldRegisterNavigation' => static::$shouldRegisterNavigation,
        ]);
        return parent::getNavigationItems();
    }
}
