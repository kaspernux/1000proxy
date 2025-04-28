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
use App\Filament\Customer\Clusters\MySupport\Resources\ServerReviewResource;
use App\Filament\Customer\Clusters\MySupport\Resources\ServerRatingResource;
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
            Action::make('submitReview')
                ->label('📝 Submit a Review')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->url(fn (): string => ServerReviewResource::getUrl('create', [
                    'server' => request()->query('server'),
                ]))
                ->color('primary')
                ->outlined(),

            Action::make('rateServer')
                ->label('⭐ Rate a Server')
                ->icon('heroicon-o-star')
                ->url(fn (): string => ServerRatingResource::getUrl('create', [
                    'server' => request()->query('server'),
                ]))
                ->color('warning')
                ->outlined(),
        ];
    }
}
