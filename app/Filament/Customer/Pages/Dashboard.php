<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use App\Filament\Customer\Widgets\CustomerStatsOverview;
use App\Filament\Customer\Widgets\SupportOverviewWidget;
use App\Filament\Customer\Widgets\DownloadOverviewWidget;
use App\Models\Wallet;
use App\Models\Order;
use App\Models\ServerClient;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $routePath = '/'; // make it the customer home
    protected static ?string $title      = 'Dashboard';

    public function getColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                // future filters here...
            ]);
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

    public static function getNavigationItems(): array
    {
        return [
            \Filament\Navigation\NavigationItem::make()
                ->label('Home')
                ->icon('heroicon-o-home')
                ->url(url('/'))
                ->group('Back to Home page')
                ->sort(0),
        ];
    }
}
