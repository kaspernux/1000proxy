<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ServerClient;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PartnershipService
{
    private array $partnerships = [];
    private array $affiliatePrograms = [];
    private array $resellerPrograms = [];
    
    public function __construct()
    {
        $this->initializePartnerships();
        $this->initializeAffiliatePrograms();
        $this->initializeResellerPrograms();
    }
    
    /**
     * Initialize available partnerships
     */
    private function initializePartnerships(): void
    {
        $this->partnerships = [
            'cloudflare' => [
                'name' => 'Cloudflare',
                'type' => 'infrastructure',
                'api_endpoint' => 'https://api.cloudflare.com/client/v4',
                'features' => ['dns', 'ssl', 'ddos_protection', 'caching'],
                'integration_status' => 'active',
                'webhook_url' => config('app.url') . '/webhooks/cloudflare',
                'commission_rate' => 0.15
            ],
            'maxmind' => [
                'name' => 'MaxMind',
                'type' => 'geolocation',
                'api_endpoint' => 'https://geoip.maxmind.com/geoip/v2.1',
                'features' => ['geolocation', 'fraud_detection', 'ip_intelligence'],
                'integration_status' => 'active',
                'webhook_url' => config('app.url') . '/webhooks/maxmind',
                'commission_rate' => 0.10
            ],
            'digital_ocean' => [
                'name' => 'DigitalOcean',
                'type' => 'hosting',
                'api_endpoint' => 'https://api.digitalocean.com/v2',
                'features' => ['droplets', 'kubernetes', 'databases', 'networking'],
                'integration_status' => 'active',
                'webhook_url' => config('app.url') . '/webhooks/digitalocean',
                'commission_rate' => 0.20
            ],
            'vultr' => [
                'name' => 'Vultr',
                'type' => 'hosting',
                'api_endpoint' => 'https://api.vultr.com/v2',
                'features' => ['compute', 'kubernetes', 'object_storage', 'networking'],
                'integration_status' => 'active',
                'webhook_url' => config('app.url') . '/webhooks/vultr',
                'commission_rate' => 0.25
            ],
            'aws' => [
                'name' => 'Amazon Web Services',
                'type' => 'cloud',
                'api_endpoint' => 'https://aws.amazon.com/api',
                'features' => ['ec2', 'lambda', 'rds', 'cloudfront', 's3'],
                'integration_status' => 'planned',
                'webhook_url' => config('app.url') . '/webhooks/aws',
                'commission_rate' => 0.12
            ]
        ];
    }
    
    /**
     * Initialize affiliate programs
     */
    private function initializeAffiliatePrograms(): void
    {
        $this->affiliatePrograms = [
            'basic' => [
                'name' => 'Basic Affiliate',
                'commission_rate' => 0.10,
                'min_referrals' => 1,
                'payment_threshold' => 50.00,
                'cookie_duration' => 30, // days
                'features' => ['referral_links', 'basic_analytics', 'monthly_payouts']
            ],
            'premium' => [
                'name' => 'Premium Affiliate',
                'commission_rate' => 0.15,
                'min_referrals' => 10,
                'payment_threshold' => 100.00,
                'cookie_duration' => 60, // days
                'features' => ['referral_links', 'advanced_analytics', 'weekly_payouts', 'custom_codes']
            ],
            'enterprise' => [
                'name' => 'Enterprise Partner',
                'commission_rate' => 0.25,
                'min_referrals' => 100,
                'payment_threshold' => 500.00,
                'cookie_duration' => 90, // days
                'features' => ['white_label', 'api_access', 'dedicated_support', 'real_time_payouts']
            ]
        ];
    }
    
    /**
     * Initialize reseller programs
     */
    private function initializeResellerPrograms(): void
    {
        $this->resellerPrograms = [
            'bronze' => [
                'name' => 'Bronze Reseller',
                'discount_rate' => 0.20,
                'min_monthly_volume' => 1000.00,
                'features' => ['bulk_pricing', 'basic_support', 'monthly_reports']
            ],
            'silver' => [
                'name' => 'Silver Reseller',
                'discount_rate' => 0.30,
                'min_monthly_volume' => 5000.00,
                'features' => ['bulk_pricing', 'priority_support', 'weekly_reports', 'custom_branding']
            ],
            'gold' => [
                'name' => 'Gold Reseller',
                'discount_rate' => 0.40,
                'min_monthly_volume' => 10000.00,
                'features' => ['bulk_pricing', 'dedicated_support', 'real_time_reports', 'white_label', 'api_access']
            ]
        ];
    }
    
    /**
     * Get available partnerships
     */
    public function getAvailablePartnerships(): array
    {
        return $this->partnerships;
    }
    
    /**
     * Get partnership by name
     */
    public function getPartnership(string $name): ?array
    {
        return $this->partnerships[$name] ?? null;
    }
    
    /**
     * Integrate with external service
     */
    public function integrateWithService(string $service, array $credentials): bool
    {
        try {
            $partnership = $this->getPartnership($service);
            
            if (!$partnership) {
                Log::error("Partnership not found: {$service}");
                return false;
            }
            
            // Test API connection
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $credentials['api_key'],
                'Content-Type' => 'application/json'
            ])->get($partnership['api_endpoint'] . '/health');
            
            if ($response->successful()) {
                // Store credentials securely
                Cache::put("partnership.{$service}.credentials", $credentials, now()->addDays(30));
                
                // Update integration status
                $this->partnerships[$service]['integration_status'] = 'active';
                $this->partnerships[$service]['integrated_at'] = now();
                
                Log::info("Successfully integrated with {$service}");
                return true;
            }
            
            Log::error("Failed to integrate with {$service}: " . $response->body());
            return false;
            
        } catch (\Exception $e) {
            Log::error("Partnership integration error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process affiliate referral
     */
    public function processAffiliateReferral(string $referralCode, Customer $newCustomer): bool
    {
        try {
            $affiliate = Customer::where('affiliate_code', $referralCode)->first();
            
            if (!$affiliate) {
                Log::warning("Invalid affiliate code: {$referralCode}");
                return false;
            }
            
            // Track referral
            DB::table('affiliate_referrals')->insert([
                'affiliate_id' => $affiliate->id,
                'referred_user_id' => $newCustomer->id,
                'referral_code' => $referralCode,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            Log::info("Affiliate referral tracked: {$referralCode} -> {$newCustomer->email}");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Affiliate referral error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculate affiliate commission
     */
    public function calculateAffiliateCommission(Customer $affiliate, Order $order): float
    {
        $program = $this->getAffiliateProgram($affiliate);
        
        if (!$program) {
            return 0.0;
        }
        
        return $order->total * $program['commission_rate'];
    }
    
    /**
     * Get affiliate program for customer
     */
    public function getAffiliateProgram(Customer $customer): ?array
    {
        $referralCount = DB::table('affiliate_referrals')
            ->where('affiliate_id', $customer->id)
            ->where('status', 'confirmed')
            ->count();
        
        if ($referralCount >= 100) {
            return $this->affiliatePrograms['enterprise'];
        } elseif ($referralCount >= 10) {
            return $this->affiliatePrograms['premium'];
        } elseif ($referralCount >= 1) {
            return $this->affiliatePrograms['basic'];
        }
        
        return null;
    }
    
    /**
     * Process reseller order
     */
    public function processResellerOrder(Customer $reseller, array $orderData): array
    {
        $program = $this->getResellerProgram($reseller);
        
        if (!$program) {
            return ['error' => 'Not eligible for reseller program'];
        }
        
        // Apply reseller discount
        $discountAmount = $orderData['total'] * $program['discount_rate'];
        $finalTotal = $orderData['total'] - $discountAmount;
        
        return [
            'original_total' => $orderData['total'],
            'discount_amount' => $discountAmount,
            'final_total' => $finalTotal,
            'program' => $program['name'],
            'discount_rate' => $program['discount_rate']
        ];
    }
    
    /**
     * Get reseller program for customer
     */
    public function getResellerProgram(Customer $customer): ?array
    {
        // Users (staff) don't place orders; approximate reseller eligibility via recent total volume
        $monthlyVolume = Order::query()
            ->whereBetween('created_at', [now()->subMonth(), now()])
            ->sum('total');
        
        if ($monthlyVolume >= 10000) {
            return $this->resellerPrograms['gold'];
        } elseif ($monthlyVolume >= 5000) {
            return $this->resellerPrograms['silver'];
        } elseif ($monthlyVolume >= 1000) {
            return $this->resellerPrograms['bronze'];
        }
        
        return null;
    }
    
    /**
     * Generate partnership report
     */
    public function generatePartnershipReport(string $period = 'monthly'): array
    {
        $startDate = $period === 'weekly' ? now()->subWeek() : now()->subMonth();
        
        return [
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'end_date' => now()->toDateString(),
            'affiliate_stats' => $this->getAffiliateStats($startDate),
            'reseller_stats' => $this->getResellerStats($startDate),
            'partnership_revenue' => $this->getPartnershipRevenue($startDate),
            'top_performers' => $this->getTopPerformers($startDate)
        ];
    }
    
    /**
     * Get affiliate statistics
     */
    private function getAffiliateStats(Carbon $startDate): array
    {
        $referrals = DB::table('affiliate_referrals')
            ->where('created_at', '>=', $startDate)
            ->count();
        
        $commissions = DB::table('affiliate_commissions')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');
        
        return [
            'total_referrals' => $referrals,
            'total_commissions' => $commissions,
            'active_affiliates' => DB::table('affiliate_referrals')
                ->where('created_at', '>=', $startDate)
                ->distinct('affiliate_id')
                ->count()
        ];
    }
    
    /**
     * Get reseller statistics
     */
    private function getResellerStats(Carbon $startDate): array
    {
        // Legacy reseller concept referenced users; align to customer-owned orders aggregate only
        $resellerOrders = Order::where('created_at', '>=', $startDate);
        
        return [
            'total_orders' => $resellerOrders->count(),
            'total_revenue' => $resellerOrders->sum('total'),
            'active_resellers' => 0
        ];
    }
    
    /**
     * Get partnership revenue
     */
    private function getPartnershipRevenue(Carbon $startDate): array
    {
        $revenue = [];
        
        foreach ($this->partnerships as $name => $partnership) {
            $revenue[$name] = [
                'name' => $partnership['name'],
                'commission_rate' => $partnership['commission_rate'],
                'estimated_revenue' => 0 // This would be calculated based on actual usage
            ];
        }
        
        return $revenue;
    }
    
    /**
     * Get top performers
     */
    private function getTopPerformers(Carbon $startDate): array
    {
        $topAffiliates = DB::table('affiliate_referrals')
            ->select('affiliate_id', DB::raw('COUNT(*) as referral_count'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('affiliate_id')
            ->orderBy('referral_count', 'desc')
            ->limit(10)
            ->get();
        
        // No customer ownership of orders; return top customers by revenue instead
        $topResellers = Order::where('created_at', '>=', $startDate)
            ->select('customer_id', DB::raw('SUM(total) as total_revenue'))
            ->groupBy('customer_id')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get();
        
        return [
            'top_affiliates' => $topAffiliates,
            'top_resellers' => $topResellers
        ];
    }
    
    /**
     * Send partnership notification
     */
    public function sendPartnershipNotification(string $type, array $data): bool
    {
        try {
            // This would integrate with notification services
            // For now, we'll log the notification
            Log::info("Partnership notification sent", [
                'type' => $type,
                'data' => $data,
                'timestamp' => now()
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Partnership notification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sync partnership data
     */
    public function syncPartnershipData(string $service): bool
    {
        try {
            $partnership = $this->getPartnership($service);
            
            if (!$partnership) {
                return false;
            }
            
            $credentials = Cache::get("partnership.{$service}.credentials");
            
            if (!$credentials) {
                Log::error("No credentials found for {$service}");
                return false;
            }
            
            // Sync data with external service
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $credentials['api_key'],
                'Content-Type' => 'application/json'
            ])->get($partnership['api_endpoint'] . '/sync');
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Process and store sync data
                Cache::put("partnership.{$service}.sync_data", $data, now()->addHours(6));
                
                Log::info("Successfully synced data with {$service}");
                return true;
            }
            
            Log::error("Failed to sync with {$service}: " . $response->body());
            return false;
            
        } catch (\Exception $e) {
            Log::error("Partnership sync error: " . $e->getMessage());
            return false;
        }
    }
}
