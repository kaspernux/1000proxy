<?php

namespace App\Services;

use App\Models\Server;
use App\Models\ServerPlan;
use App\Models\User;
use App\Models\Order;
use App\Models\ServerClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PricingEngineService
{
    /**
     * Calculate dynamic pricing for a server plan
     */
    public function calculateDynamicPrice(ServerPlan $plan, ?User $user = null): array
    {
        $basePrice = $plan->price;
        $adjustments = [];

        // Demand-based pricing
        $demandMultiplier = $this->calculateDemandMultiplier($plan);
        $adjustments['demand'] = ($demandMultiplier - 1) * $basePrice;

        // Capacity-based pricing
        $capacityMultiplier = $this->calculateCapacityMultiplier($plan->server);
        $adjustments['capacity'] = ($capacityMultiplier - 1) * $basePrice;

        // Time-based pricing
        $timeMultiplier = $this->calculateTimeMultiplier();
        $adjustments['time'] = ($timeMultiplier - 1) * $basePrice;

        // User-based pricing
        $userMultiplier = $this->calculateUserMultiplier($user);
        $adjustments['user'] = ($userMultiplier - 1) * $basePrice;

        // Geographic pricing
        $geoMultiplier = $this->calculateGeographicMultiplier($plan->server, $user);
        $adjustments['geographic'] = ($geoMultiplier - 1) * $basePrice;

        // Seasonal pricing
        $seasonalMultiplier = $this->calculateSeasonalMultiplier($plan);
        $adjustments['seasonal'] = ($seasonalMultiplier - 1) * $basePrice;

        // Competition-based pricing
        $competitionMultiplier = $this->calculateCompetitionMultiplier($plan);
        $adjustments['competition'] = ($competitionMultiplier - 1) * $basePrice;

        $totalAdjustment = array_sum($adjustments);
        $finalPrice = max($basePrice * 0.5, $basePrice + $totalAdjustment); // Never go below 50% of base price

        return [
            'base_price' => $basePrice,
            'adjustments' => $adjustments,
            'total_adjustment' => $totalAdjustment,
            'final_price' => round($finalPrice, 2),
            'discount_percentage' => $finalPrice < $basePrice ? round((($basePrice - $finalPrice) / $basePrice) * 100, 2) : 0,
            'markup_percentage' => $finalPrice > $basePrice ? round((($finalPrice - $basePrice) / $basePrice) * 100, 2) : 0,
        ];
    }

    /**
     * Generate personalized pricing for a user
     */
    public function generatePersonalizedPricing(User $user): array
    {
        $userProfile = $this->buildUserProfile($user);
        $personalizedPrices = [];

        $plans = ServerPlan::where('is_active', true)->get();

        foreach ($plans as $plan) {
            $pricing = $this->calculateDynamicPrice($plan, $user);
            $personalizedPrices[] = [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'server_name' => $plan->server->name,
                'pricing' => $pricing,
                'recommendations' => $this->generateRecommendations($plan, $user, $pricing),
            ];
        }

        return [
            'user_profile' => $userProfile,
            'personalized_prices' => $personalizedPrices,
            'best_deals' => $this->findBestDeals($personalizedPrices),
            'loyalty_benefits' => $this->calculateLoyaltyBenefits($user),
        ];
    }

    /**
     * Calculate bulk pricing discounts
     */
    public function calculateBulkDiscount(array $items, ?User $user = null): array
    {
        $totalValue = 0;
        $totalQuantity = 0;
        $itemBreakdown = [];

        foreach ($items as $item) {
            $plan = ServerPlan::find($item['plan_id']);
            $quantity = $item['quantity'];
            $duration = $item['duration'] ?? 1;

            $pricing = $this->calculateDynamicPrice($plan, $user);
            $itemTotal = $pricing['final_price'] * $quantity * $duration;

            $totalValue += $itemTotal;
            $totalQuantity += $quantity;

            $itemBreakdown[] = [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'quantity' => $quantity,
                'duration' => $duration,
                'unit_price' => $pricing['final_price'],
                'item_total' => $itemTotal,
            ];
        }

        // Calculate bulk discount tiers
        $bulkDiscountRate = $this->calculateBulkDiscountRate($totalQuantity, $totalValue);
        $bulkDiscount = $totalValue * $bulkDiscountRate;

        // Volume bonuses
        $volumeBonus = $this->calculateVolumeBonus($totalQuantity, $totalValue);

        // Loyalty discount
        $loyaltyDiscount = $this->calculateLoyaltyDiscount($user, $totalValue);

        $totalDiscount = $bulkDiscount + $volumeBonus + $loyaltyDiscount;
        $finalTotal = $totalValue - $totalDiscount;

        return [
            'subtotal' => $totalValue,
            'item_breakdown' => $itemBreakdown,
            'discounts' => [
                'bulk_discount' => $bulkDiscount,
                'volume_bonus' => $volumeBonus,
                'loyalty_discount' => $loyaltyDiscount,
            ],
            'total_discount' => $totalDiscount,
            'final_total' => $finalTotal,
            'savings_percentage' => $totalValue > 0 ? round(($totalDiscount / $totalValue) * 100, 2) : 0,
        ];
    }

    /**
     * Calculate subscription pricing
     */
    public function calculateSubscriptionPricing(ServerPlan $plan, int $months, ?User $user = null): array
    {
        $monthlyPricing = $this->calculateDynamicPrice($plan, $user);
        $monthlyPrice = $monthlyPricing['final_price'];

        // Subscription discounts based on duration
        $subscriptionDiscount = $this->getSubscriptionDiscount($months);
        $discountedMonthlyPrice = $monthlyPrice * (1 - $subscriptionDiscount);

        $totalPrice = $discountedMonthlyPrice * $months;
        $totalSavings = ($monthlyPrice * $months) - $totalPrice;

        return [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'months' => $months,
            'monthly_price' => $monthlyPrice,
            'subscription_discount' => $subscriptionDiscount,
            'discounted_monthly_price' => $discountedMonthlyPrice,
            'total_price' => $totalPrice,
            'total_savings' => $totalSavings,
            'savings_percentage' => round(($totalSavings / ($monthlyPrice * $months)) * 100, 2),
        ];
    }

    /**
     * Generate promotional pricing
     */
    public function generatePromotionalPricing(string $promoCode, array $items, ?User $user = null): array
    {
        $promotion = $this->validatePromoCode($promoCode, $user);
        
        if (!$promotion['valid']) {
            return [
                'valid' => false,
                'message' => $promotion['message'],
            ];
        }

        $regularPricing = $this->calculateBulkDiscount($items, $user);
        $promoDiscount = $this->calculatePromoDiscount($promotion, $regularPricing['subtotal']);
        
        $finalTotal = $regularPricing['final_total'] - $promoDiscount;

        return [
            'valid' => true,
            'promo_code' => $promoCode,
            'promotion' => $promotion,
            'regular_pricing' => $regularPricing,
            'promo_discount' => $promoDiscount,
            'final_total' => $finalTotal,
            'additional_savings' => $promoDiscount,
            'total_savings_percentage' => round((($regularPricing['subtotal'] - $finalTotal) / $regularPricing['subtotal']) * 100, 2),
        ];
    }

    /**
     * Calculate demand multiplier
     */
    private function calculateDemandMultiplier(ServerPlan $plan): float
    {
        $cacheKey = "demand_multiplier:{$plan->id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($plan) {
            $recentOrders = Order::whereHas('orderItems', function ($query) use ($plan) {
                $query->where('server_plan_id', $plan->id);
            })->where('created_at', '>=', now()->subDays(7))->count();

            $averageOrders = Order::whereHas('orderItems', function ($query) use ($plan) {
                $query->where('server_plan_id', $plan->id);
            })->where('created_at', '>=', now()->subDays(30))->count() / 4; // Weekly average

            if ($averageOrders == 0) {
                return 1.0;
            }

            $demandRatio = $recentOrders / $averageOrders;
            
            // Scale demand multiplier between 0.8 and 1.3
            return min(1.3, max(0.8, 1 + ($demandRatio - 1) * 0.2));
        });
    }

    /**
     * Calculate capacity multiplier
     */
    private function calculateCapacityMultiplier(Server $server): float
    {
        $activeClients = ServerClient::where('server_id', $server->id)
            ->where('is_active', true)
            ->count();

        $maxCapacity = $server->max_clients ?? 1000;
        $utilizationRate = $activeClients / $maxCapacity;

        // Higher utilization = higher prices
        if ($utilizationRate > 0.9) {
            return 1.2;
        } elseif ($utilizationRate > 0.7) {
            return 1.1;
        } elseif ($utilizationRate < 0.3) {
            return 0.9;
        }

        return 1.0;
    }

    /**
     * Calculate time-based multiplier
     */
    private function calculateTimeMultiplier(): float
    {
        $hour = now()->hour;
        $dayOfWeek = now()->dayOfWeek;

        // Peak hours (9 AM - 5 PM) on weekdays
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $hour >= 9 && $hour <= 17) {
            return 1.1;
        }

        // Off-peak hours
        if ($hour >= 2 && $hour <= 6) {
            return 0.95;
        }

        return 1.0;
    }

    /**
     * Calculate user-based multiplier
     */
    private function calculateUserMultiplier(?User $user): float
    {
        if (!$user) {
            return 1.0;
        }

        $userProfile = $this->buildUserProfile($user);

        // Loyalty discount
        if ($userProfile['loyalty_tier'] === 'gold') {
            return 0.9;
        } elseif ($userProfile['loyalty_tier'] === 'silver') {
            return 0.95;
        }

        // New customer discount
        if ($userProfile['is_new_customer']) {
            return 0.85;
        }

        return 1.0;
    }

    /**
     * Calculate geographic multiplier
     */
    private function calculateGeographicMultiplier(Server $server, ?User $user): float
    {
        if (!$user) {
            return 1.0;
        }

        // This would typically use IP geolocation or user's saved location
        // For now, we'll use a simple country-based multiplier
        $userCountry = $user->country ?? 'US';
        $serverCountry = $server->country ?? 'US';

        // Same country = small discount
        if ($userCountry === $serverCountry) {
            return 0.98;
        }

        // Different regions might have different pricing
        $highValueCountries = ['US', 'CA', 'GB', 'DE', 'FR', 'AU'];
        if (in_array($userCountry, $highValueCountries)) {
            return 1.05;
        }

        return 0.95;
    }

    /**
     * Calculate seasonal multiplier
     */
    private function calculateSeasonalMultiplier(ServerPlan $plan): float
    {
        $month = now()->month;
        
        // Holiday season (November-December)
        if ($month >= 11) {
            return 1.1;
        }

        // Summer season (June-August)
        if ($month >= 6 && $month <= 8) {
            return 1.05;
        }

        // Back-to-school season (September)
        if ($month === 9) {
            return 1.08;
        }

        return 1.0;
    }

    /**
     * Calculate competition multiplier
     */
    private function calculateCompetitionMultiplier(ServerPlan $plan): float
    {
        // This would typically analyze competitor pricing
        // For now, we'll use a simple market position multiplier
        $cacheKey = "competition_multiplier:{$plan->id}";
        
        return Cache::remember($cacheKey, 86400, function () use ($plan) {
            // Simulate competitive analysis
            $marketPosition = $this->getMarketPosition($plan);
            
            switch ($marketPosition) {
                case 'premium':
                    return 1.15;
                case 'competitive':
                    return 1.0;
                case 'budget':
                    return 0.9;
                default:
                    return 1.0;
            }
        });
    }

    /**
     * Build user profile
     */
    private function buildUserProfile(User $user): array
    {
        $totalOrders = Order::where('user_id', $user->id)->count();
        $totalSpent = Order::where('user_id', $user->id)
            ->where('payment_status', 'paid')
            ->sum('grand_amount');

        $loyaltyTier = $this->calculateLoyaltyTier($totalSpent);
        $isNewCustomer = $totalOrders === 0;

        return [
            'user_id' => $user->id,
            'total_orders' => $totalOrders,
            'total_spent' => $totalSpent,
            'loyalty_tier' => $loyaltyTier,
            'is_new_customer' => $isNewCustomer,
            'account_age_days' => $user->created_at->diffInDays(now()),
            'average_order_value' => $totalOrders > 0 ? $totalSpent / $totalOrders : 0,
            'last_order_date' => $user->orders()->latest()->first()?->created_at,
        ];
    }

    /**
     * Calculate loyalty tier
     */
    private function calculateLoyaltyTier(float $totalSpent): string
    {
        if ($totalSpent >= 1000) {
            return 'gold';
        } elseif ($totalSpent >= 500) {
            return 'silver';
        } elseif ($totalSpent >= 100) {
            return 'bronze';
        }

        return 'basic';
    }

    /**
     * Calculate bulk discount rate
     */
    private function calculateBulkDiscountRate(int $quantity, float $value): float
    {
        if ($quantity >= 50 || $value >= 1000) {
            return 0.15; // 15% discount
        } elseif ($quantity >= 20 || $value >= 500) {
            return 0.10; // 10% discount
        } elseif ($quantity >= 10 || $value >= 250) {
            return 0.05; // 5% discount
        }

        return 0.0;
    }

    /**
     * Calculate volume bonus
     */
    private function calculateVolumeBonus(int $quantity, float $value): float
    {
        if ($quantity >= 100) {
            return $value * 0.05; // 5% bonus
        } elseif ($quantity >= 50) {
            return $value * 0.025; // 2.5% bonus
        }

        return 0.0;
    }

    /**
     * Calculate loyalty discount
     */
    private function calculateLoyaltyDiscount(?User $user, float $value): float
    {
        if (!$user) {
            return 0.0;
        }

        $userProfile = $this->buildUserProfile($user);
        
        switch ($userProfile['loyalty_tier']) {
            case 'gold':
                return $value * 0.1; // 10% discount
            case 'silver':
                return $value * 0.05; // 5% discount
            case 'bronze':
                return $value * 0.025; // 2.5% discount
            default:
                return 0.0;
        }
    }

    /**
     * Get subscription discount
     */
    private function getSubscriptionDiscount(int $months): float
    {
        if ($months >= 12) {
            return 0.2; // 20% discount
        } elseif ($months >= 6) {
            return 0.15; // 15% discount
        } elseif ($months >= 3) {
            return 0.1; // 10% discount
        }

        return 0.0;
    }

    /**
     * Validate promo code
     */
    private function validatePromoCode(string $code, ?User $user): array
    {
        // This would typically check against a promotions database
        // For now, we'll use some hardcoded examples
        $validCodes = [
            'WELCOME10' => ['type' => 'percentage', 'value' => 0.1, 'min_order' => 50],
            'SAVE20' => ['type' => 'percentage', 'value' => 0.2, 'min_order' => 100],
            'FLAT50' => ['type' => 'fixed', 'value' => 50, 'min_order' => 200],
        ];

        if (!isset($validCodes[$code])) {
            return ['valid' => false, 'message' => 'Invalid promo code'];
        }

        return [
            'valid' => true,
            'code' => $code,
            'type' => $validCodes[$code]['type'],
            'value' => $validCodes[$code]['value'],
            'min_order' => $validCodes[$code]['min_order'],
        ];
    }

    /**
     * Calculate promo discount
     */
    private function calculatePromoDiscount(array $promotion, float $subtotal): float
    {
        if ($subtotal < $promotion['min_order']) {
            return 0.0;
        }

        if ($promotion['type'] === 'percentage') {
            return $subtotal * $promotion['value'];
        } elseif ($promotion['type'] === 'fixed') {
            return min($promotion['value'], $subtotal);
        }

        return 0.0;
    }

    /**
     * Generate recommendations
     */
    private function generateRecommendations(ServerPlan $plan, User $user, array $pricing): array
    {
        $recommendations = [];

        if ($pricing['discount_percentage'] > 10) {
            $recommendations[] = "Great deal! You're saving {$pricing['discount_percentage']}% on this plan.";
        }

        if ($pricing['markup_percentage'] > 20) {
            $recommendations[] = "High demand plan. Consider booking early to avoid further price increases.";
        }

        $userProfile = $this->buildUserProfile($user);
        if ($userProfile['loyalty_tier'] === 'gold') {
            $recommendations[] = "As a Gold member, you get additional priority support with this plan.";
        }

        return $recommendations;
    }

    /**
     * Find best deals
     */
    private function findBestDeals(array $personalizedPrices): array
    {
        $bestDeals = [];

        foreach ($personalizedPrices as $price) {
            if ($price['pricing']['discount_percentage'] > 15) {
                $bestDeals[] = $price;
            }
        }

        // Sort by discount percentage
        usort($bestDeals, function ($a, $b) {
            return $b['pricing']['discount_percentage'] <=> $a['pricing']['discount_percentage'];
        });

        return array_slice($bestDeals, 0, 3); // Return top 3 deals
    }

    /**
     * Calculate loyalty benefits
     */
    private function calculateLoyaltyBenefits(User $user): array
    {
        $userProfile = $this->buildUserProfile($user);
        
        return [
            'current_tier' => $userProfile['loyalty_tier'],
            'total_spent' => $userProfile['total_spent'],
            'discount_rate' => $this->getLoyaltyDiscountRate($userProfile['loyalty_tier']),
            'next_tier_requirement' => $this->getNextTierRequirement($userProfile['loyalty_tier']),
            'benefits' => $this->getLoyaltyBenefits($userProfile['loyalty_tier']),
        ];
    }

    /**
     * Get loyalty discount rate
     */
    private function getLoyaltyDiscountRate(string $tier): float
    {
        return match ($tier) {
            'gold' => 0.1,
            'silver' => 0.05,
            'bronze' => 0.025,
            default => 0.0,
        };
    }

    /**
     * Get next tier requirement
     */
    private function getNextTierRequirement(string $tier): ?float
    {
        return match ($tier) {
            'basic' => 100,
            'bronze' => 500,
            'silver' => 1000,
            default => null,
        };
    }

    /**
     * Get loyalty benefits
     */
    private function getLoyaltyBenefits(string $tier): array
    {
        return match ($tier) {
            'gold' => ['10% discount', 'Priority support', 'Early access to new features'],
            'silver' => ['5% discount', 'Extended support hours'],
            'bronze' => ['2.5% discount', 'Quarterly promotions'],
            default => ['Welcome bonus eligibility'],
        };
    }

    /**
     * Get market position
     */
    private function getMarketPosition(ServerPlan $plan): string
    {
        // This would typically analyze competitor pricing
        // For now, we'll use a simple price-based categorization
        if ($plan->price >= 100) {
            return 'premium';
        } elseif ($plan->price >= 50) {
            return 'competitive';
        } else {
            return 'budget';
        }
    }
}
