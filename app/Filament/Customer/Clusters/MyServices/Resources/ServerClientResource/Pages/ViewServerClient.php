<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources\ServerClientResource\Pages;

use App\Filament\Customer\Clusters\MyServices\Resources\ServerClientResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewServerClient extends ViewRecord
{
    protected static string $resource = ServerClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_config')
                ->label('Download Config')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn () => route('customer.download-config', $this->record->id))
                ->openUrlInNewTab(),

            Actions\Action::make('copy_subscription')
                ->label('Copy Subscription')
                ->icon('heroicon-o-rss')
                ->color('warning')
                ->action(function () {
                    $url = route('customer.subscription', ['sub_id' => $this->record->sub_id]);
                    $this->js("navigator.clipboard.writeText('$url')");
                    $this->notification()->title('Subscription URL copied to clipboard')->success()->send();
                })
                ->visible(fn () => !empty($this->record->sub_id)),
        ];
    }

    public function getTitle(): string
    {
        return 'Client: ' . $this->record->email;
    }

    public function getHeading(): string
    {
        return 'Proxy Client Details';
    }
}
