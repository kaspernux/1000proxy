<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServerPlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServerPlanFilterController extends Controller
{
    /**
     * Get server plans with advanced filtering
     * Implements location-first sorting as per TODO requirements
     */
    public function index(Request $request): JsonResponse
    {
        $query = ServerPlan::with(['brand', 'category', 'server'])
                           ->where('is_active', true)
                           ->where('server_status', 'online');

        // Apply filters
        $this->applyFilters($query, $request);

        // Apply sorting
        $this->applySorting($query, $request);

        // Paginate results
        $plans = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $plans,
            'filters' => $this->getAvailableFilters(),
        ]);
    }

    /**
     * Get available filter options
     */
    public function getFilters(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->getAvailableFilters(),
        ]);
    }

    /**
     * Apply filters based on request parameters
     */
    private function applyFilters($query, Request $request): void
    {
        // Location filtering (priority #1 as per TODO)
        if ($request->filled('country')) {
            $query->byLocation($request->get('country'));
        }

        if ($request->filled('region')) {
            $query->byLocation(null, $request->get('region'));
        }

        // Category filtering (priority #2)
        if ($request->filled('category')) {
            $query->byCategory($request->get('category'));
        }

        // Brand filtering (priority #3)
        if ($request->filled('brand')) {
            $query->byBrand($request->get('brand'));
        }

        // Protocol filtering
        if ($request->filled('protocol')) {
            $query->byProtocol($request->get('protocol'));
        }

        // Price range filtering
        if ($request->filled('min_price') || $request->filled('max_price')) {
            $query->byPriceRange(
                $request->get('min_price'),
                $request->get('max_price')
            );
        }

        // Bandwidth filtering
        if ($request->filled('min_bandwidth')) {
            $query->byBandwidth($request->get('min_bandwidth'));
        }

        // IPv6 support filtering
        if ($request->filled('ipv6_only') && $request->boolean('ipv6_only')) {
            $query->withIpv6(true);
        }

        // Server status filtering
        if ($request->filled('status')) {
            $query->byStatus($request->get('status'));
        }

        // Featured plans only
        if ($request->boolean('featured_only')) {
            $query->where('is_featured', true);
        }

        // In stock only
        if ($request->boolean('in_stock_only')) {
            $query->where('in_stock', true);
        }
    }

    /**
     * Apply sorting based on request parameters
     */
    private function applySorting($query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'location_first');
        $sortDirection = $request->get('sort_direction', 'asc');

        switch ($sortBy) {
            case 'location_first':
                // Location-first sorting as per TODO requirements
                $query->locationFirstSort();
                break;

            case 'popularity':
                $query->byPopularity($sortDirection);
                break;

            case 'price':
                $query->byPrice($sortDirection);
                break;

            case 'bandwidth':
                $query->orderBy('bandwidth_mbps', $sortDirection);
                break;

            case 'data_limit':
                $query->orderBy('data_limit_gb', $sortDirection);
                break;

            default:
                $query->locationFirstSort();
                break;
        }
    }

    /**
     * Get available filter options with counts
     */
    private function getAvailableFilters(): array
    {
        return [
            'countries' => $this->getCountryOptions(),
            'categories' => $this->getCategoryOptions(),
            'brands' => $this->getBrandOptions(),
            'protocols' => $this->getProtocolOptions(),
            'price_range' => $this->getPriceRange(),
            'bandwidth_options' => $this->getBandwidthOptions(),
            'sort_options' => $this->getSortOptions(),
        ];
    }

    /**
     * Get country options with flags and counts
     */
    private function getCountryOptions(): array
    {
        $countries = ServerPlan::getAvailableCountries();
        $countryNames = [
            'US' => ['name' => 'United States', 'flag' => '🇺🇸'],
            'GB' => ['name' => 'United Kingdom', 'flag' => '🇬🇧'],
            'DE' => ['name' => 'Germany', 'flag' => '🇩🇪'],
            'JP' => ['name' => 'Japan', 'flag' => '🇯🇵'],
            'CA' => ['name' => 'Canada', 'flag' => '🇨🇦'],
            'FR' => ['name' => 'France', 'flag' => '🇫🇷'],
            'AU' => ['name' => 'Australia', 'flag' => '🇦🇺'],
            'SG' => ['name' => 'Singapore', 'flag' => '🇸🇬'],
        ];

        return $countries->map(function ($country) use ($countryNames) {
            $info = $countryNames[$country->country_code] ?? ['name' => $country->country_code, 'flag' => '🌍'];
            return [
                'code' => $country->country_code,
                'name' => $info['name'],
                'flag' => $info['flag'],
                'plan_count' => $country->plan_count,
            ];
        })->toArray();
    }

    /**
     * Get category options with counts
     */
    private function getCategoryOptions(): array
    {
        return ServerPlan::getAvailableCategories()->map(function ($item) {
            return [
                'id' => $item->server_category_id,
                'name' => $item->category->name ?? 'Unknown',
                'slug' => $item->category->slug ?? 'unknown',
                'image' => $item->category->image ?? null,
                'plan_count' => $item->plan_count,
            ];
        })->toArray();
    }

    /**
     * Get brand options with counts
     */
    private function getBrandOptions(): array
    {
        return ServerPlan::getAvailableBrands()->map(function ($item) {
            return [
                'id' => $item->server_brand_id,
                'name' => $item->brand->name ?? 'Unknown',
                'slug' => $item->brand->slug ?? 'unknown',
                'image' => $item->brand->image ?? null,
                'plan_count' => $item->plan_count,
            ];
        })->toArray();
    }

    /**
     * Get protocol options
     */
    private function getProtocolOptions(): array
    {
        return [
            ['value' => 'vless', 'name' => 'VLESS', 'description' => 'Lightweight protocol with excellent performance'],
            ['value' => 'vmess', 'name' => 'VMess', 'description' => 'Versatile protocol with good compatibility'],
            ['value' => 'trojan', 'name' => 'Trojan', 'description' => 'Secure protocol that mimics HTTPS traffic'],
            ['value' => 'shadowsocks', 'name' => 'Shadowsocks', 'description' => 'Fast and reliable SOCKS5 proxy'],
            ['value' => 'mixed', 'name' => 'Mixed', 'description' => 'Supports multiple protocols'],
        ];
    }

    /**
     * Get price range information
     */
    private function getPriceRange(): array
    {
        $prices = ServerPlan::where('is_active', true)
                           ->where('server_status', 'online')
                           ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
                           ->first();

        return [
            'min' => (float) ($prices->min_price ?? 0),
            'max' => (float) ($prices->max_price ?? 100),
            'step' => 1.0,
        ];
    }

    /**
     * Get bandwidth filtering options
     */
    private function getBandwidthOptions(): array
    {
        return [
            ['value' => 100, 'label' => '100+ Mbps'],
            ['value' => 500, 'label' => '500+ Mbps'],
            ['value' => 1000, 'label' => '1+ Gbps'],
            ['value' => 2000, 'label' => '2+ Gbps'],
        ];
    }

    /**
     * Get sorting options
     */
    private function getSortOptions(): array
    {
        return [
            ['value' => 'location_first', 'label' => 'Location First (Default)'],
            ['value' => 'popularity', 'label' => 'Most Popular'],
            ['value' => 'price', 'label' => 'Price: Low to High'],
            ['value' => 'bandwidth', 'label' => 'Highest Bandwidth'],
            ['value' => 'data_limit', 'label' => 'Highest Data Limit'],
        ];
    }
}
