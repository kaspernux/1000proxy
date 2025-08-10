<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Central lightweight state manager for analytics filters (time range & segment filters)
 * shared between Filament Page (AnalyticsDashboard) and Chart Widgets.
 *
 * Stores per-admin-user selections in cache for quick retrieval by widgets
 * without tight coupling to page Livewire component instances.
 */
class AnalyticsFilterState
{
    protected function cacheKey(int $userId): string
    {
        return "analytics_filters_user_{$userId}";
    }

    public function get(int $userId): array
    {
        return Cache::remember($this->cacheKey($userId), 3600, function () {
            return [
                'time_range' => '30d',
                'payment_method' => null,
                'plan' => null,
            ];
        });
    }

    public function setTimeRange(int $userId, string $range): void
    {
        $state = $this->get($userId);
        $state['time_range'] = $range;
        Cache::put($this->cacheKey($userId), $state, 3600);
    }

    public function setFilters(int $userId, array $filters): void
    {
        $state = $this->get($userId);
        foreach (['payment_method','plan'] as $key) {
            if (array_key_exists($key, $filters)) {
                $state[$key] = $filters[$key] ?: null;
            }
        }
        Cache::put($this->cacheKey($userId), $state, 3600);
    }

    /** Map UI time ranges to BusinessIntelligenceService expected tokens */
    public function mapToServiceRange(string $uiRange): string
    {
        return match($uiRange) {
            '24h' => '24_hours',
            '7d' => '7_days',
            '30d' => '30_days',
            '90d' => '90_days',
            '1y' => '1_year',
            default => '30_days',
        };
    }
}
