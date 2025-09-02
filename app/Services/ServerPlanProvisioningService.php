<?php

namespace App\Services;

use App\Models\Server;
use App\Models\ServerPlan;
use App\Models\ServerCategory;
use App\Models\ServerBrand;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ServerPlanProvisioningService
{
    /**
     * Create or update plans for a server based on provided templates.
     * Returns an array of results for each plan.
     *
     * @param Server $server
     * @param array $plans Array of plan definitions
     * @param array $options Optional overrides: ['brand_id' => int, 'category_map' => ['slug' => id]]
     * @return array
     */
    public function createPlansForServer(Server $server, array $plans, array $options = []): array
    {
        $results = [];

        foreach ($plans as $plan) {
            try {
                $name = trim(Arr::get($plan, 'name', 'Unnamed Plan'));
                $slug = Str::slug($name);

                // Resolve category: accept category_slug, category_name or fallback to option mapping
                $categoryId = null;
                if ($cat = Arr::get($plan, 'server_category_slug')) {
                    $category = ServerCategory::where('slug', $cat)->first();
                    $categoryId = $category->id ?? null;
                }
                if (!$categoryId && $catName = Arr::get($plan, 'server_category_name')) {
                    $category = ServerCategory::where('name', $catName)->first();
                    $categoryId = $category->id ?? null;
                }
                if (!$categoryId && is_array($options['category_map'] ?? null)) {
                    $mapped = $options['category_map'][$plan['server_category_slug'] ?? ''] ?? null;
                    $categoryId = $mapped ?: $categoryId;
                }

                // Resolve brand id
                $brandId = Arr::get($options, 'brand_id') ?: Arr::get($plan, 'brand_id');
                if (!$brandId && $brandSlug = Arr::get($plan, 'brand_slug')) {
                    $brand = ServerBrand::where('slug', $brandSlug)->first();
                    $brandId = $brand->id ?? null;
                }

                $defaults = [
                    'server_id' => $server->id,
                    'server_category_id' => $categoryId,
                    'server_brand_id' => $brandId,
                    'name' => $name,
                    'slug' => $slug,
                    'description' => Arr::get($plan, 'description', null),
                    'price_monthly' => (float) Arr::get($plan, 'price_monthly', 0),
                    'max_concurrent_connections' => (int) Arr::get($plan, 'max_concurrent_connections', 1),
                    'bandwidth_limit_gb' => (int) Arr::get($plan, 'bandwidth_limit_gb', 0),
                    'auto_provision' => (bool) Arr::get($plan, 'auto_provision', false),
                    'provisioning_type' => Arr::get($plan, 'provisioning_type', 'shared'),
                    'is_active' => Arr::get($plan, 'is_active', true),
                ];

                // Use unique keys: server_id + slug
                $planModel = ServerPlan::updateOrCreate(
                    ['server_id' => $server->id, 'slug' => $slug],
                    $defaults
                );

                $results[] = ['slug' => $slug, 'status' => 'created_or_updated', 'id' => $planModel->id];
            } catch (\Throwable $e) {
                Log::error('ServerPlanProvisioningService::createPlansForServer error', ['error' => $e->getMessage(), 'plan' => $plan]);
                $results[] = ['slug' => $plan['name'] ?? null, 'status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return $results;
    }
}
