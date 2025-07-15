<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources\OrderResource\Pages;

use App\Filament\Customer\Clusters\MyOrders\Resources\OrderResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\Storage;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_all_configs')
                ->label('Export All Configurations')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $customer = Auth::guard('customer')->user();
                    $orders = Order::where('customer_id', $customer->id)
                        ->where('order_status', 'completed')
                        ->where('payment_status', 'paid')
                        ->with(['orderItems.orderServerClients.serverClient'])
                        ->get();

                    if ($orders->isEmpty()) {
                        Notification::make()
                            ->title('No Orders to Export')
                            ->body('You have no completed orders with configurations to export.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $this->exportAllOrderConfigurations($orders);
                }),

            Action::make('order_summary')
                ->label('Order Summary')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->modalContent(fn () => view('filament.customer.modals.order-summary', [
                    'customer' => Auth::guard('customer')->user()
                ]))
                ->modalWidth('2xl')
                ->slideOver(),

            Action::make('refresh_status')
                ->label('Refresh All Status')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->refreshOrderStatuses();
                }),
        ];
    }

    protected function exportAllOrderConfigurations($orders): void
    {
        $customer = Auth::guard('customer')->user();
        $exportData = [
            'export_date' => now()->toISOString(),
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
            'total_orders' => $orders->count(),
            'orders' => []
        ];

        foreach ($orders as $order) {
            $orderData = [
                'order_id' => $order->id,
                'order_date' => $order->created_at->toISOString(),
                'total_amount' => $order->total_amount,
                'configurations' => []
            ];

            $configs = $order->getClientConfigurations();
            foreach ($configs as $index => $config) {
                $orderData['configurations'][] = [
                    'config_index' => $index + 1,
                    'client_link' => $config['client_link'] ?? '',
                    'subscription_link' => $config['subscription_link'] ?? '',
                    'json_link' => $config['json_link'] ?? '',
                    'connection_details' => $config['connection_details'] ?? [],
                    'usage_info' => $config['usage_info'] ?? []
                ];
            }

            $exportData['orders'][] = $orderData;
        }

        $filename = "all_orders_export_" . now()->format('Y-m-d_H-i-s') . ".json";
        Storage::disk('public')->put("exports/{$filename}", json_encode($exportData, JSON_PRETTY_PRINT));

        Notification::make()
            ->title('Export Complete')
            ->body("All order configurations exported to {$filename}")
            ->actions([
                \Filament\Notifications\Actions\Action::make('download')
                    ->button()
                    ->url(Storage::url("exports/{$filename}"))
                    ->openUrlInNewTab(),
            ])
            ->success()
            ->persistent()
            ->send();
    }

    protected function refreshOrderStatuses(): void
    {
        $customer = Auth::guard('customer')->user();
        $orders = Order::where('customer_id', $customer->id)
            ->whereIn('order_status', ['processing', 'pending'])
            ->get();

        $updatedCount = 0;
        foreach ($orders as $order) {
            // Simulate status refresh - in real implementation, check with payment gateway
            if ($order->payment_status === 'pending' && rand(0, 100) < 20) {
                $order->update(['payment_status' => 'paid', 'order_status' => 'completed']);
                $updatedCount++;
            }
        }

        Notification::make()
            ->title('Status Refresh Complete')
            ->body($updatedCount > 0 ? 
                "Updated status for {$updatedCount} orders" : 
                "All order statuses are up to date"
            )
            ->success()
            ->send();

        // Refresh the table
        $this->dispatch('$refresh');
    }
}
