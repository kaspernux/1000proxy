<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources\ServerPlanResource\Pages;

use App\Filament\Customer\Clusters\MyServices\Resources\ServerPlanResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewServerPlan extends ViewRecord
{
    protected static string $resource = ServerPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('order_plan')
                ->label('Order This Plan')
                ->icon('heroicon-o-shopping-cart')
                ->color('success')
                ->url(fn () => route('customer.order.create', ['plan' => $this->record->id]))
                ->visible(fn () => $this->record->in_stock),

            Actions\Action::make('compare_plans')
                ->label('Compare Plans')
                ->icon('heroicon-o-scale')
                ->color('info')
                ->url(fn () => route('customer.plans.compare'))
                ->openUrlInNewTab(),
        ];
    }

    public function getTitle(): string
    {
        return 'Plan: ' . $this->record->name;
    }

    public function getHeading(): string
    {
        return 'Server Plan Details';
    }
}
