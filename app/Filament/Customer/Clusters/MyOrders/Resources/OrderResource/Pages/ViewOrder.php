<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources\OrderResource\Pages;

use App\Filament\Customer\Clusters\MyOrders\Resources\OrderResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\Storage;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_configs')
                ->label('Download All Configurations')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->visible(fn (): bool => 
                    $this->record->order_status === 'completed' && 
                    $this->record->payment_status === 'paid'
                )
                ->action(function () {
                    $this->downloadOrderConfigurations();
                }),

            Action::make('download_qr_codes')
                ->label('Download QR Codes')
                ->icon('heroicon-o-qr-code')
                ->color('info')
                ->visible(fn (): bool => 
                    $this->record->order_status === 'completed' && 
                    $this->record->payment_status === 'paid'
                )
                ->action(function () {
                    $this->downloadOrderQrCodes();
                }),

            Action::make('view_invoice')
                ->label('View Invoice')
                ->icon('heroicon-o-receipt-percent')
                ->color('gray')
                ->visible(fn (): bool => $this->record->invoice)
                ->url(fn (): string => 
                    route('filament.customer.resources.my-orders.invoices.view', ['record' => $this->record->invoice])
                ),
        ];
    }

    protected function downloadOrderConfigurations(): void
    {
        $configs = $this->record->getClientConfigurations();
        
        if (empty($configs)) {
            Notification::make()
                ->title('No Configurations Available')
                ->body('This order does not have any client configurations to download.')
                ->warning()
                ->send();
            return;
        }

        $exportData = [
            'order_id' => $this->record->id,
            'order_date' => $this->record->created_at->toISOString(),
            'customer_email' => $this->record->customer->email,
            'total_amount' => $this->record->total_amount,
            'configurations' => $configs
        ];

        $filename = "order_{$this->record->id}_configurations_" . now()->format('Y-m-d_H-i-s') . ".json";
        $content = json_encode($exportData, JSON_PRETTY_PRINT);

        // Save to temp file and notify user
        $tempPath = storage_path("app/public/temp_downloads/{$filename}");
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        
        file_put_contents($tempPath, $content);
        
        Notification::make()
            ->title('Configurations Ready')
            ->body('Your order configurations have been prepared for download.')
            ->success()
            ->actions([
                \Filament\Notifications\Actions\Action::make('download')
                    ->label('Download Now')
                    ->url(asset("storage/temp_downloads/{$filename}"))
                    ->openUrlInNewTab()
            ])
            ->send();
    }

    protected function downloadOrderQrCodes(): void
    {
        $qrCodeService = app(QrCodeService::class);
        $configs = $this->record->getClientConfigurations();
        
        if (empty($configs)) {
            Notification::make()
                ->title('No QR Codes Available')
                ->body('This order does not have any configurations to generate QR codes for.')
                ->warning()
                ->send();
            return;
        }

        $zipContent = [];

        foreach ($configs as $index => $config) {
            if (!empty($config['client_link'])) {
                try {
                    $qrCodeBase64 = $qrCodeService->generateClientQrCode($config['client_link'], [
                        'colorScheme' => 'primary',
                        'style' => 'dot',
                        'eye' => 'circle'
                    ]);
                    
                    // Convert base64 to binary
                    $qrCodeBinary = base64_decode(str_replace('data:image/png;base64,', '', $qrCodeBase64));
                    $filename = "qr_code_order_{$this->record->id}_config_" . ($index + 1) . ".png";
                    
                    Storage::disk('public')->put("temp_qr/{$filename}", $qrCodeBinary);
                    $zipContent[] = $filename;
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        if (empty($zipContent)) {
            Notification::make()
                ->title('No QR Codes Generated')
                ->body('Unable to generate QR codes for this order.')
                ->danger()
                ->send();
            return;
        }

        $zipFilename = "order_{$this->record->id}_qr_codes_" . now()->format('Y-m-d_H-i-s') . ".zip";
        
        // Create ZIP file
        $zip = new \ZipArchive();
        $tempZipPath = storage_path("app/public/temp_downloads/{$zipFilename}");
        
        if (!file_exists(dirname($tempZipPath))) {
            mkdir(dirname($tempZipPath), 0755, true);
        }

        if ($zip->open($tempZipPath, \ZipArchive::CREATE) === TRUE) {
            foreach ($zipContent as $filename) {
                $filePath = storage_path("app/public/temp_qr/{$filename}");
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $filename);
                }
            }
            $zip->close();

            // Clean up temporary QR files
            foreach ($zipContent as $filename) {
                $filePath = storage_path("app/public/temp_qr/{$filename}");
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            Notification::make()
                ->title('QR Codes Ready')
                ->body('Your QR codes have been packaged and are ready for download.')
                ->success()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('download')
                        ->label('Download ZIP')
                        ->url(asset("storage/temp_downloads/{$zipFilename}"))
                        ->openUrlInNewTab()
                ])
                ->send();
        } else {
            Notification::make()
                ->title('Download Failed')
                ->body('Unable to create ZIP file for QR codes.')
                ->danger()
                ->send();
        }
    }
}
