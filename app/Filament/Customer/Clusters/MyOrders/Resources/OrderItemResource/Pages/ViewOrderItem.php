<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources\OrderItemResource\Pages;

use App\Filament\Customer\Clusters\MyOrders\Resources\OrderItemResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use App\Services\QrCodeService;
use App\Models\ServerClient;
use Illuminate\Support\Facades\Auth;

class ViewOrderItem extends ViewRecord
{
    protected static string $resource = OrderItemResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_config')
                ->label('Download Configuration')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->visible(fn (): bool => $this->record->provisioning_status === 'active')
                ->action(function () {
                    $this->downloadItemConfiguration();
                }),

            Action::make('download_qr_code')
                ->label('Download QR Code')
                ->icon('heroicon-o-qr-code')
                ->color('info')
                ->visible(fn (): bool => $this->record->provisioning_status === 'active')
                ->action(function () {
                    $this->downloadItemQrCode();
                }),

            Action::make('view_order')
                ->label('View Parent Order')
                ->icon('heroicon-o-shopping-bag')
                ->color('gray')
                ->url(fn (): string => 
                    route('filament.customer.resources.my-orders.orders.view', ['record' => $this->record->order_id])
                ),

            Action::make('refresh_status')
                ->label('Refresh Status')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->refreshItemStatus();
                }),
        ];
    }

    protected function downloadItemConfiguration(): void
    {
        $client = ServerClient::where('plan_id', $this->record->server_plan_id)
            ->where('email', 'LIKE', '%#ID ' . Auth::guard('customer')->id())
            ->first();

        if (!$client) {
            Notification::make()
                ->title('Configuration Not Found')
                ->body('No configuration found for this item.')
                ->warning()
                ->send();
            return;
        }

        try {
            $config = $client->config_data ?? $client->client_link;
            
            if (empty($config)) {
                Notification::make()
                    ->title('Configuration Not Available')
                    ->body('Configuration is not yet available for this item.')
                    ->warning()
                    ->send();
                return;
            }

            // Save to temp file and provide download link
            $filename = "config_order_item_{$this->record->id}_" . now()->format('Y-m-d_H-i-s') . ".txt";
            $tempPath = storage_path("app/public/temp_downloads/{$filename}");
            
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            
            file_put_contents($tempPath, $config);
            
            Notification::make()
                ->title('Configuration Ready')
                ->body('Your configuration file has been prepared for download.')
                ->success()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('download')
                        ->label('Download Now')
                        ->url(asset("storage/temp_downloads/{$filename}"))
                        ->openUrlInNewTab()
                ])
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Download Failed')
                ->body('Could not download configuration. Please try again later.')
                ->danger()
                ->send();
        }
    }

    protected function downloadItemQrCode(): void
    {
        $client = ServerClient::where('plan_id', $this->record->server_plan_id)
            ->where('email', 'LIKE', '%#ID ' . Auth::guard('customer')->id())
            ->first();

        if (!$client || !$client->client_link) {
            Notification::make()
                ->title('QR Code Not Available')
                ->body('No client configuration found to generate QR code.')
                ->warning()
                ->send();
            return;
        }

        try {
            $qrCodeService = app(QrCodeService::class);
            $qrCodeBase64 = $qrCodeService->generateClientQrCode($client->client_link, [
                'colorScheme' => 'primary',
                'style' => 'dot',
                'eye' => 'circle'
            ]);
            
            // Convert base64 to binary and save temporarily
            $qrCodeBinary = base64_decode(str_replace('data:image/png;base64,', '', $qrCodeBase64));
            $filename = "qr_code_order_item_{$this->record->id}_" . now()->format('Y-m-d_H-i-s') . ".png";
            $tempPath = storage_path("app/public/temp_downloads/{$filename}");
            
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            
            file_put_contents($tempPath, $qrCodeBinary);
            
            Notification::make()
                ->title('QR Code Ready')
                ->body('Your QR code has been generated and is ready for download.')
                ->success()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('download')
                        ->label('Download QR Code')
                        ->url(asset("storage/temp_downloads/{$filename}"))
                        ->openUrlInNewTab()
                ])
                ->send();
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('QR Code Generation Failed')
                ->body('Unable to generate QR code. Please try again later.')
                ->danger()
                ->send();
        }
    }

    protected function refreshItemStatus(): void
    {
        // Refresh the item status from the server
        $this->record->refresh();
        
        Notification::make()
            ->title('Status Refreshed')
            ->body('Order item status has been updated.')
            ->success()
            ->send();

        // Refresh the page data
        $this->refreshFormData([
            'provisioning_status',
            'updated_at'
        ]);
    }
}
