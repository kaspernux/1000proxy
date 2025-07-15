<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Services\ProgressiveWebAppService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

/**
 * PWA Installation Widget
 *
 * Filament widget for displaying PWA installation status and quick actions.
 */
class PWAInstallationWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.pwa-installation-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 5;

    protected static bool $isLazy = false;

    /**
     * Get widget data
     */
    protected function getViewData(): array
    {
        $pwaService = app(ProgressiveWebAppService::class);
        $stats = $pwaService->getInstallationStats();

        // Calculate installation percentage
        $requirements = [
            'manifest_exists',
            'service_worker_exists',
            'offline_page_exists',
            'icons_directory_exists'
        ];

        $completed = 0;
        foreach ($requirements as $requirement) {
            if ($stats[$requirement] ?? false) {
                $completed++;
            }
        }

        $installationPercentage = round(($completed / count($requirements)) * 100);

        // Get feature support count
        $supportedFeatures = count(array_filter($stats['supported_features'] ?? []));
        $totalFeatures = count($stats['supported_features'] ?? []);

        return [
            'stats' => $stats,
            'installationPercentage' => $installationPercentage,
            'completedRequirements' => $completed,
            'totalRequirements' => count($requirements),
            'supportedFeatures' => $supportedFeatures,
            'totalFeatures' => $totalFeatures,
            'isFullyInstalled' => $installationPercentage === 100,
            'lastUpdated' => $stats['last_updated'] ?? now()->toISOString(),
            'cacheVersion' => $stats['cache_version'] ?? 'Unknown'
        ];
    }

    /**
     * Get installation status color
     */
    public static function getInstallationStatusColor(int $percentage): string
    {
        return match(true) {
            $percentage === 100 => 'success',
            $percentage >= 75 => 'warning',
            $percentage >= 50 => 'info',
            default => 'danger'
        };
    }

    /**
     * Get installation status icon
     */
    public static function getInstallationStatusIcon(int $percentage): string
    {
        return match(true) {
            $percentage === 100 => 'heroicon-o-check-circle',
            $percentage >= 75 => 'heroicon-o-exclamation-triangle',
            $percentage >= 50 => 'heroicon-o-information-circle',
            default => 'heroicon-o-x-circle'
        };
    }

    /**
     * Can view widget
     */
    public static function canView(): bool
    {
        return Auth::check() &&
               (optional(Auth::user())->hasRole('admin') || optional(Auth::user())->hasPermission('view-pwa-status'));
    }
}
