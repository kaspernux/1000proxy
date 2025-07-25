<?php

namespace App\Livewire\Components;

use Livewire\Component;
use App\Services\ProgressiveWebAppService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

/**
 * PWA Status Component
 *
 * Livewire component for displaying PWA installation status,
 * managing notifications, and providing PWA controls.
 */
class PWAStatus extends Component
{
    public array $stats = [];
    public bool $isInstalled = false;
    public bool $isOnline = true;
    public string $installationStatus = 'unknown';
    public array $notifications = [];
    public bool $showNotifications = false;
    public string $notificationTitle = '';
    public string $notificationBody = '';
    public string $notificationIcon = '';
    public string $notificationUrl = '';

    protected $listeners = [
        'pwa-status-updated' => 'refreshStatus',
        'pwa-notification-sent' => 'refreshNotifications'
    ];

    public function mount()
    {
        $this->refreshStatus();
        $this->refreshNotifications();
    }

    public function render()
    {
        return view('livewire.components.pwa-status');
    }

    /**
     * Refresh PWA status
     */
    public function refreshStatus()
    {
        try {
            $pwaService = app(ProgressiveWebAppService::class);
            $this->stats = $pwaService->getInstallationStats();

            // Determine installation status
            if ($this->stats['manifest_exists'] &&
                $this->stats['service_worker_exists'] &&
                $this->stats['offline_page_exists']) {
                $this->installationStatus = 'complete';
            } elseif ($this->stats['manifest_exists'] || $this->stats['service_worker_exists']) {
                $this->installationStatus = 'partial';
            } else {
                $this->installationStatus = 'none';
            }

        } catch (\Exception $e) {
            $this->installationStatus = 'error';
            session()->flash('error', 'Failed to load PWA status: ' . $e->getMessage());
        }
    }

    /**
     * Refresh notifications
     */
    public function refreshNotifications()
    {
        try {
            $pwaService = app(ProgressiveWebAppService::class);
            $this->notifications = $pwaService->getCachedNotifications();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load notifications: ' . $e->getMessage());
        }
    }

    /**
     * Install PWA
     */
    public function installPWA()
    {
        try {
            $pwaService = app(ProgressiveWebAppService::class);
            $results = $pwaService->installPWAFiles();

            $this->refreshStatus();

            session()->flash('success', 'PWA installed successfully!');
            $this->dispatch('pwa-installed', $results);

        } catch (\Exception $e) {
            session()->flash('error', 'PWA installation failed: ' . $e->getMessage());
        }
    }

    /**
     * Update PWA cache
     */
    public function updateCache()
    {
        try {
            $pwaService = app(ProgressiveWebAppService::class);
            $newVersion = $pwaService->updateCacheVersion();

            session()->flash('success', "PWA cache updated to version: {$newVersion}");
            $this->dispatch('pwa-cache-updated', $newVersion);

        } catch (\Exception $e) {
            session()->flash('error', 'Cache update failed: ' . $e->getMessage());
        }
    }

    /**
     * Send test notification
     */
    public function sendTestNotification()
    {
        $this->validate([
            'notificationTitle' => 'required|string|max:255',
            'notificationBody' => 'required|string|max:500',
            'notificationIcon' => 'nullable|url',
            'notificationUrl' => 'nullable|url'
        ]);

        try {
            $pwaService = app(ProgressiveWebAppService::class);

            $notificationData = [
                'title' => $this->notificationTitle,
                'body' => $this->notificationBody,
                'icon' => $this->notificationIcon ?: '/images/icons/icon-192x192.png',
                'url' => $this->notificationUrl ?: '/',
                'data' => [
                    'test' => true,
                    'timestamp' => now()->toISOString()
                ]
            ];

            $success = $pwaService->sendPushNotification($notificationData);

            if ($success) {
                $this->refreshNotifications();
                $this->resetNotificationForm();

                session()->flash('success', 'Test notification sent successfully!');
                $this->dispatch('pwa-notification-sent', $notificationData);
            } else {
                session()->flash('error', 'Failed to send notification');
            }

        } catch (\Exception $e) {
            session()->flash('error', 'Notification sending failed: ' . $e->getMessage());
        }
    }

    /**
     * Clear all notifications
     */
    public function clearNotifications()
    {
        try {
            $pwaService = app(ProgressiveWebAppService::class);
            $pwaService->clearCachedNotifications();

            $this->notifications = [];

            session()->flash('success', 'All notifications cleared');
            $this->dispatch('pwa-notifications-cleared');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to clear notifications: ' . $e->getMessage());
        }
    }

    /**
     * Toggle notifications panel
     */
    public function toggleNotifications()
    {
        $this->showNotifications = !$this->showNotifications;

        if ($this->showNotifications) {
            $this->refreshNotifications();
        }
    }

    /**
     * Reset notification form
     */
    public function resetNotificationForm()
    {
        $this->notificationTitle = '';
        $this->notificationBody = '';
        $this->notificationIcon = '';
        $this->notificationUrl = '';
    }

    /**
     * Get installation status color
     */
    public function getStatusColor(): string
    {
        return match($this->installationStatus) {
            'complete' => 'text-green-600',
            'partial' => 'text-yellow-600',
            'none' => 'text-red-600',
            'error' => 'text-red-600',
            default => 'text-gray-600'
        };
    }

    /**
     * Get installation status icon
     */
    public function getStatusIcon(): string
    {
        return match($this->installationStatus) {
            'complete' => '✅',
            'partial' => '⚠️',
            'none' => '❌',
            'error' => '🚫',
            default => '❓'
        };
    }

    /**
     * Get installation status text
     */
    public function getStatusText(): string
    {
        return match($this->installationStatus) {
            'complete' => 'Fully Installed',
            'partial' => 'Partially Installed',
            'none' => 'Not Installed',
            'error' => 'Installation Error',
            default => 'Unknown Status'
        };
    }

    /**
     * Get feature status
     */
    public function getFeatureStatus(string $feature): array
    {
        $isSupported = $this->stats['supported_features'][$feature] ?? false;

        return [
            'supported' => $isSupported,
            'icon' => $isSupported ? '✅' : '❌',
            'color' => $isSupported ? 'text-green-600' : 'text-red-600'
        ];
    }

    /**
     * Generate test data for demo
     */
    public function generateTestData()
    {
        $this->notificationTitle = 'Test Notification';
        $this->notificationBody = 'This is a test notification from your PWA dashboard.';
        $this->notificationIcon = '/images/icons/icon-192x192.png';
        $this->notificationUrl = '/dashboard';
    }

    /**
     * Check if admin user
     */
    public function getIsAdminProperty(): bool
    {
        return Auth::check() && optional(Auth::user())->hasRole('admin');
    }

    /**
     * Get component statistics
     */
    public function getComponentStats(): array
    {
        return [
            'total_notifications' => count($this->notifications),
            'installation_percentage' => $this->calculateInstallationPercentage(),
            'supported_features' => count(array_filter($this->stats['supported_features'] ?? [])),
            'total_features' => count($this->stats['supported_features'] ?? [])
        ];
    }

    /**
     * Calculate installation percentage
     */
    private function calculateInstallationPercentage(): int
    {
        $requirements = [
            'manifest_exists',
            'service_worker_exists',
            'offline_page_exists',
            'icons_directory_exists'
        ];

        $completed = 0;
        foreach ($requirements as $requirement) {
            if ($this->stats[$requirement] ?? false) {
                $completed++;
            }
        }

        return round(($completed / count($requirements)) * 100);
    }
}
