<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources\SubscriptionResource\Pages;

use App\Filament\Customer\Clusters\MyServices\Resources\SubscriptionResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manage_stripe')
                ->label('Manage in Stripe')
                ->icon('heroicon-o-credit-card')
                ->color('info')
                ->url(fn () => "https://dashboard.stripe.com/subscriptions/{$this->record->stripe_id}")
                ->openUrlInNewTab()
                ->visible(fn () => !empty($this->record->stripe_id)),

            Actions\Action::make('cancel_subscription')
                ->label('Cancel Subscription')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel Subscription')
                ->modalDescription('Are you sure you want to cancel this subscription? This action cannot be undone.')
                ->action(fn () => $this->record->cancel())
                ->visible(fn () => $this->record->stripe_status === 'active'),

            Actions\Action::make('resume_subscription')
                ->label('Resume Subscription')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Resume Subscription')
                ->modalDescription('Are you sure you want to resume this subscription?')
                ->action(fn () => $this->record->renew())
                ->visible(fn () => $this->record->onGracePeriod()),
        ];
    }

    public function getTitle(): string
    {
        return 'Subscription: ' . $this->record->name;
    }

    public function getHeading(): string
    {
        return 'Subscription Details';
    }
}
