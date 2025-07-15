<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class QrCodeService
{
    /**
     * Default 1000 Proxies branding colors
     */
    private const BRAND_PRIMARY_COLOR = [59, 130, 246]; // Blue
    private const BRAND_SECONDARY_COLOR = [239, 68, 68]; // Red accent
    private const BRAND_BACKGROUND_COLOR = [255, 255, 255]; // White
    private const BRAND_DARK_COLOR = [17, 24, 39]; // Dark gray

    /**
     * Generate a branded dotted QR code for 1000 Proxies
     */
    public function generateBrandedQrCode(
        string $data,
        int $size = 300,
        string $format = 'png',
        array $options = []
    ): string {
        try {
            $style = $options['style'] ?? 'dot';
            $eye = $options['eye'] ?? 'circle';
            $colorScheme = $options['colorScheme'] ?? 'primary';
            $margin = $options['margin'] ?? 15;
            $errorCorrection = $options['errorCorrection'] ?? 'M';

            // Select color scheme
            $colors = $this->getColorScheme($colorScheme);

            $qrCode = QrCode::format($format)
                ->size($size)
                ->margin($margin)
                ->style($style)
                ->eye($eye)
                ->color($colors['foreground'][0], $colors['foreground'][1], $colors['foreground'][2])
                ->backgroundColor($colors['background'][0], $colors['background'][1], $colors['background'][2])
                ->errorCorrection($errorCorrection);

            // Add gradient effect if supported
            if (isset($options['gradient']) && $options['gradient']) {
                $qrCode->gradient(
                    $colors['gradient']['start'][0], $colors['gradient']['start'][1], $colors['gradient']['start'][2],
                    $colors['gradient']['end'][0], $colors['gradient']['end'][1], $colors['gradient']['end'][2],
                    $options['gradientType'] ?? 'diagonal'
                );
            }

            return $qrCode->generate($data);
        } catch (\Exception $e) {
            // Fallback to simple QR code without advanced styling if imagick fails
            return $this->generateSimpleQrCode($data, $size, $format, $options);
        }
    }

    /**
     * Generate QR code with base64 encoding
     */
    public function generateBase64QrCode(
        string $data,
        int $size = 300,
        array $options = []
    ): string {
        $qrCode = $this->generateBrandedQrCode($data, $size, 'png', $options);
        return 'data:image/png;base64,' . base64_encode($qrCode);
    }

    /**
     * Save QR code to storage and return path
     */
    public function saveQrCodeToStorage(
        string $data,
        string $filename,
        string $disk = 'public',
        int $size = 300,
        array $options = []
    ): string {
        $qrCode = $this->generateBrandedQrCode($data, $size, 'png', $options);

        $path = "qr-codes/{$filename}";
        Storage::disk($disk)->put($path, $qrCode);

        return $path;
    }

    /**
     * Generate client configuration QR code
     */
    public function generateClientQrCode(string $clientLink, array $options = []): string
    {
        $defaultOptions = [
            'style' => 'dot',
            'eye' => 'circle',
            'colorScheme' => 'primary',
            'margin' => 10,
            'errorCorrection' => 'M'
        ];

        $options = array_merge($defaultOptions, $options);

        return $this->generateBase64QrCode($clientLink, 200, $options);
    }

    /**
     * Generate subscription QR code
     */
    public function generateSubscriptionQrCode(string $subscriptionLink, array $options = []): string
    {
        $defaultOptions = [
            'style' => 'square',
            'eye' => 'square',
            'colorScheme' => 'secondary',
            'margin' => 15,
            'errorCorrection' => 'H'
        ];

        $options = array_merge($defaultOptions, $options);

        return $this->generateBase64QrCode($subscriptionLink, 250, $options);
    }

    /**
     * Generate download QR code for files
     */
    public function generateDownloadQrCode(string $downloadUrl, array $options = []): string
    {
        $defaultOptions = [
            'style' => 'round',
            'eye' => 'circle',
            'colorScheme' => 'dark',
            'margin' => 12,
            'errorCorrection' => 'M'
        ];

        $options = array_merge($defaultOptions, $options);

        return $this->generateBase64QrCode($downloadUrl, 180, $options);
    }

    /**
     * Generate branded QR code with logo overlay (future enhancement)
     */
    public function generateQrCodeWithLogo(
        string $data,
        string $logoPath,
        int $size = 300,
        array $options = []
    ): string {
        // Generate base QR code
        $qrCode = $this->generateBrandedQrCode($data, $size, 'png', $options);

        // TODO: Add logo overlay functionality
        // This would require image manipulation library like Intervention Image

        return $qrCode;
    }

    /**
     * Get color schemes for different use cases
     */
    private function getColorScheme(string $scheme): array
    {
        return match ($scheme) {
            'primary' => [
                'foreground' => self::BRAND_PRIMARY_COLOR,
                'background' => self::BRAND_BACKGROUND_COLOR,
                'gradient' => [
                    'start' => self::BRAND_PRIMARY_COLOR,
                    'end' => [99, 102, 241] // Indigo
                ]
            ],
            'secondary' => [
                'foreground' => self::BRAND_SECONDARY_COLOR,
                'background' => self::BRAND_BACKGROUND_COLOR,
                'gradient' => [
                    'start' => self::BRAND_SECONDARY_COLOR,
                    'end' => [249, 115, 22] // Orange
                ]
            ],
            'dark' => [
                'foreground' => self::BRAND_DARK_COLOR,
                'background' => self::BRAND_BACKGROUND_COLOR,
                'gradient' => [
                    'start' => self::BRAND_DARK_COLOR,
                    'end' => [75, 85, 99] // Gray
                ]
            ],
            'inverse' => [
                'foreground' => self::BRAND_BACKGROUND_COLOR,
                'background' => self::BRAND_DARK_COLOR,
                'gradient' => [
                    'start' => self::BRAND_BACKGROUND_COLOR,
                    'end' => [229, 231, 235] // Light gray
                ]
            ],
            default => [
                'foreground' => self::BRAND_PRIMARY_COLOR,
                'background' => self::BRAND_BACKGROUND_COLOR,
                'gradient' => [
                    'start' => self::BRAND_PRIMARY_COLOR,
                    'end' => [99, 102, 241]
                ]
            ]
        };
    }

    /**
     * Generate multiple QR codes for a client configuration
     */
    public function generateClientQrCodeSet(array $links): array
    {
        $qrCodes = [];

        if (isset($links['client_link'])) {
            $qrCodes['client'] = $this->generateClientQrCode($links['client_link'], [
                'colorScheme' => 'primary'
            ]);
        }

        if (isset($links['subscription_link'])) {
            $qrCodes['subscription'] = $this->generateSubscriptionQrCode($links['subscription_link'], [
                'colorScheme' => 'secondary'
            ]);
        }

        if (isset($links['json_subscription_link'])) {
            $qrCodes['json_subscription'] = $this->generateSubscriptionQrCode($links['json_subscription_link'], [
                'colorScheme' => 'dark'
            ]);
        }

        return $qrCodes;
    }

    /**
     * Generate QR code for mobile app deep linking
     */
    public function generateMobileAppQrCode(string $deepLink, array $options = []): string
    {
        $defaultOptions = [
            'style' => 'dot',
            'eye' => 'circle',
            'colorScheme' => 'primary',
            'margin' => 20,
            'errorCorrection' => 'H', // High error correction for mobile scanning
        ];

        $options = array_merge($defaultOptions, $options);

        return $this->generateBase64QrCode($deepLink, 280, $options);
    }

    /**
     * Fallback method for simple QR code generation without advanced styling
     */
    private function generateSimpleQrCode(
        string $data,
        int $size = 300,
        string $format = 'png',
        array $options = []
    ): string {
        try {
            // Use basic QR code generation without advanced styling
            return QrCode::format($format)
                ->size($size)
                ->margin(15)
                ->generate($data);
        } catch (\Exception $e) {
            // Last resort: even simpler QR code
            return QrCode::size($size)->generate($data);
        }
    }

    /**
     * Validate QR code data before generation
     */
    public function validateQrData(string $data): bool
    {
        // Check if data is not empty
        if (empty(trim($data))) {
            return false;
        }

        // Check maximum data length for QR codes
        if (strlen($data) > 4296) { // Maximum for QR code
            return false;
        }

        // Validate URL format if it's a URL
        if (filter_var($data, FILTER_VALIDATE_URL) === false &&
            !preg_match('/^[a-z]+:\/\//', $data)) {
            // Not a URL, check if it's valid text
            return true;
        }

        return true;
    }

    /**
     * Get optimal QR code size based on data length
     */
    public function getOptimalSize(string $data): int
    {
        $length = strlen($data);

        return match (true) {
            $length <= 100 => 150,
            $length <= 300 => 200,
            $length <= 800 => 250,
            $length <= 1500 => 300,
            default => 350
        };
    }
}
