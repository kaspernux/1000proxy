<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources\OrderItemResource\Pages;

use App\Filament\Customer\Clusters\MyOrders\Resources\OrderItemResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\OrderItem;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\Storage;

class ListOrderItems extends ListRecords
{
    protected static string $resource = OrderItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulk_download_qr')
                ->label('Download All QR Codes')
                ->icon('heroicon-o-qr-code')
                ->color('info')
                ->action(function () {
                    $this->bulkDownloadQrCodes();
                }),

            Action::make('export_active_configs')
                ->label('Export Active Configurations')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $this->exportActiveConfigurations();
                }),

            Action::make('refresh_all_status')
                ->label('Refresh All Status')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->refreshAllItemStatuses();
                }),
        ];
    }

    protected function bulkDownloadQrCodes(): void
    {
        $customer = Auth::guard('customer')->user();
        $orderItems = OrderItem::whereHas('order', function ($query) use ($customer) {
            $query->where('customer_id', $customer->id)
                  ->where('order_status', 'completed')
                  ->where('payment_status', 'paid');
        })->with(['orderServerClients.serverClient'])->get();

        if ($orderItems->isEmpty()) {
            Notification::make()
                ->title('No QR Codes Available')
                ->body('You have no completed order items with QR codes to download.')
                ->warning()
                ->send();
            return;
        }

        $qrCodeService = app(QrCodeService::class);
        $zipContent = [];

        foreach ($orderItems as $item) {
            foreach ($item->orderServerClients as $osc) {
                if ($osc->serverClient && $osc->serverClient->client_link) {
                    try {
                        $qrCodeBase64 = $qrCodeService->generateClientQrCode($osc->serverClient->client_link, [
                            'colorScheme' => 'primary'
                        ]);
                        
                        // Convert base64 to binary
                        $qrCodeBinary = base64_decode(str_replace('data:image/png;base64,', '', $qrCodeBase64));
                        $filename = "qr_code_order_{$item->order_id}_item_{$item->id}_client_{$osc->serverClient->id}.png";
                        
                        Storage::disk('public')->put("temp_qr/{$filename}", $qrCodeBinary);
                        $zipContent[] = $filename;
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        }

        if (empty($zipContent)) {
            Notification::make()
                ->title('No QR Codes Generated')
                ->body('Unable to generate QR codes for your configurations.')
                ->danger()
                ->send();
            return;
        }

        $zipFilename = "qr_codes_bulk_" . now()->format('Y-m-d_H-i-s') . ".zip";
        
        // Create ZIP file (simplified - in production use proper ZIP library)
        Notification::make()
            ->title('QR Codes Ready')
            ->body("Generated {" . count($zipContent) . "} QR codes for download")
            ->actions([
                \Filament\Notifications\Actions\Action::make('download')
                    ->button()
                    ->url('#') // In production, implement proper ZIP download
                    ->openUrlInNewTab(),
            ])
            ->success()
            ->persistent()
            ->send();
    }

    protected function exportActiveConfigurations(): void
    {
        $customer = Auth::guard('customer')->user();
        $activeItems = OrderItem::whereHas('order', function ($query) use ($customer) {
            $query->where('customer_id', $customer->id)
                  ->where('order_status', 'completed')
                  ->where('payment_status', 'paid');
        })
        ->whereHas('orderServerClients.serverClient', function ($query) {
            $query->where('status', 'active');
        })
        ->with(['order', 'serverPlan', 'orderServerClients.serverClient'])
        ->get();

        if ($activeItems->isEmpty()) {
            Notification::make()
                ->title('No Active Configurations')
                ->body('You have no active configurations to export.')
                ->warning()
                ->send();
            return;
        }

        $exportData = [
            'export_date' => now()->toISOString(),
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
            'active_configurations' => []
        ];

        foreach ($activeItems as $item) {
            foreach ($item->orderServerClients as $osc) {
                if ($osc->serverClient && $osc->serverClient->status === 'active') {
                    $config = $osc->serverClient->getDownloadableConfig();
                    $exportData['active_configurations'][] = [
                        'order_id' => $item->order_id,
                        'item_id' => $item->id,
                        'server_plan' => $item->serverPlan->name ?? 'Unknown',
                        'configuration' => $config
                    ];
                }
            }
        }

        $filename = "active_configurations_" . now()->format('Y-m-d_H-i-s') . ".json";
        Storage::disk('public')->put("exports/{$filename}", json_encode($exportData, JSON_PRETTY_PRINT));

        Notification::make()
            ->title('Export Complete')
            ->body("Exported {" . count($exportData['active_configurations']) . "} active configurations")
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

    protected function refreshAllItemStatuses(): void
    {
        $customer = Auth::guard('customer')->user();
        $items = OrderItem::whereHas('order', function ($query) use ($customer) {
            $query->where('customer_id', $customer->id);
        })->with(['orderServerClients.serverClient'])->get();

        $updatedCount = 0;
        foreach ($items as $item) {
            foreach ($item->orderServerClients as $osc) {
                if ($osc->serverClient) {
                    // Simulate status update - in production, sync with actual server
                    if ($osc->serverClient->syncFromRemote()) {
                        $updatedCount++;
                    }
                }
            }
        }

        Notification::make()
            ->title('Status Refresh Complete')
            ->body($updatedCount > 0 ? 
                "Updated {$updatedCount} configurations" : 
                "All configurations are up to date"
            )
            ->success()
            ->send();

        // Refresh the table
        $this->dispatch('$refresh');
    }
}
