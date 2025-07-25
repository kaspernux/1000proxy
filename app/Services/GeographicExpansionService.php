<?php

namespace App\Services;

use App\Models\User;
use App\Models\Server;
use App\Models\ServerLocation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeographicExpansionService
{
    protected $supportedRegions = [
        'NA' => ['US', 'CA', 'MX'],
        'EU' => ['DE', 'FR', 'GB', 'NL', 'ES', 'IT', 'SE', 'NO', 'DK', 'FI'],
        'AS' => ['JP', 'SG', 'HK', 'KR', 'IN', 'TH', 'MY', 'ID', 'PH', 'VN'],
        'OC' => ['AU', 'NZ'],
        'SA' => ['BR', 'AR', 'CL', 'CO', 'PE'],
        'AF' => ['ZA', 'EG', 'NG', 'KE', 'MA'],
        'ME' => ['AE', 'SA', 'IL', 'TR', 'IR'],
    ];

    protected $currencyMapping = [
        'US' => 'USD', 'CA' => 'CAD', 'GB' => 'GBP', 'EU' => 'EUR',
        'JP' => 'JPY', 'AU' => 'AUD', 'SG' => 'SGD', 'HK' => 'HKD',
        'IN' => 'INR', 'BR' => 'BRL', 'ZA' => 'ZAR', 'AE' => 'AED',
    ];

    protected $languageMapping = [
        'US' => 'en', 'CA' => 'en', 'GB' => 'en', 'AU' => 'en',
        'DE' => 'de', 'FR' => 'fr', 'ES' => 'es', 'IT' => 'it',
        'BR' => 'pt', 'JP' => 'ja', 'KR' => 'ko', 'CN' => 'zh',
        'RU' => 'ru', 'AR' => 'ar', 'HI' => 'hi', 'TH' => 'th',
    ];

    /**
     * Get user's geographic location
     */
    public function getUserLocation(string $ipAddress): array
    {
        $cacheKey = "geo_location_{$ipAddress}";
        
        return Cache::remember($cacheKey, 3600, function () use ($ipAddress) {
            try {
                // Use multiple IP geolocation services for accuracy
                $location = $this->getLocationFromIpInfo($ipAddress);
                
                if (!$location) {
                    $location = $this->getLocationFromMaxMind($ipAddress);
                }
                
                return $location ?: [
                    'country' => 'US',
                    'region' => 'NA',
                    'city' => 'Unknown',
                    'currency' => 'USD',
                    'language' => 'en',
                    'timezone' => 'UTC',
                ];
            } catch (\Exception $e) {
                Log::error('Failed to get user location', [
                    'ip' => $ipAddress,
                    'error' => $e->getMessage()
                ]);
                
                return [
                    'country' => 'US',
                    'region' => 'NA',
                    'city' => 'Unknown',
                    'currency' => 'USD',
                    'language' => 'en',
                    'timezone' => 'UTC',
                ];
            }
        });
    }

    /**
     * Get localized content for user
     */
    public function getLocalizedContent(User $user): array
    {
        $location = $this->getUserLocation($user->ip_address ?? request()->ip());
        
        return [
            'currency' => $this->getLocalCurrency($location['country']),
            'language' => $this->getLocalLanguage($location['country']),
            'timezone' => $location['timezone'],
            'servers' => $this->getRegionalServers($location['region']),
            'pricing' => $this->getLocalizedPricing($location['currency']),
            'payment_methods' => $this->getLocalPaymentMethods($location['country']),
            'legal_compliance' => $this->getLegalCompliance($location['country']),
        ];
    }

    /**
     * Get servers optimized for user's region
     */
    public function getRegionalServers(string $region): array
    {
        return Server::whereHas('serverLocation', function ($query) use ($region) {
            $query->where('region', $region);
        })
        ->where('is_active', true)
        ->orderBy('ping_latency', 'asc')
        ->get()
        ->map(function ($server) {
            return [
                'id' => $server->id,
                'name' => $server->name,
                'location' => $server->location,
                'latency' => $server->ping_latency,
                'load' => $server->load_percentage,
                'price' => $server->price,
                'protocols' => $server->supported_protocols,
            ];
        })
        ->toArray();
    }

    /**
     * Get localized pricing
     */
    public function getLocalizedPricing(string $currency): array
    {
        $exchangeRate = $this->getExchangeRate('USD', $currency);
        
        $basePricing = [
            'basic' => 9.99,
            'premium' => 19.99,
            'enterprise' => 49.99,
        ];
        
        $localizedPricing = [];
        
        foreach ($basePricing as $plan => $price) {
            $localizedPricing[$plan] = [
                'amount' => round($price * $exchangeRate, 2),
                'currency' => $currency,
                'formatted' => $this->formatCurrency($price * $exchangeRate, $currency),
            ];
        }
        
        return $localizedPricing;
    }

    /**
     * Get local payment methods
     */
    public function getLocalPaymentMethods(string $country): array
    {
        $globalMethods = ['stripe', 'paypal', 'nowpayments'];
        
        $localMethods = [
            'US' => ['stripe', 'paypal', 'apple_pay', 'google_pay'],
            'GB' => ['stripe', 'paypal', 'apple_pay', 'google_pay'],
            'DE' => ['stripe', 'paypal', 'sofort', 'giropay'],
            'FR' => ['stripe', 'paypal', 'sofort', 'bancontact'],
            'NL' => ['stripe', 'paypal', 'ideal', 'sofort'],
            'IN' => ['razorpay', 'paytm', 'upi'],
            'BR' => ['stripe', 'paypal', 'pix', 'boleto'],
            'JP' => ['stripe', 'paypal', 'konbini', 'bank_transfer'],
            'CN' => ['alipay', 'wechat_pay', 'unionpay'],
            'RU' => ['yandex_money', 'qiwi', 'webmoney'],
        ];
        
        return $localMethods[$country] ?? $globalMethods;
    }

    /**
     * Get legal compliance requirements
     */
    public function getLegalCompliance(string $country): array
    {
        $compliance = [
            'data_protection' => $this->getDataProtectionLaws($country),
            'payment_regulations' => $this->getPaymentRegulations($country),
            'tax_requirements' => $this->getTaxRequirements($country),
            'terms_of_service' => $this->getLocalizedTerms($country),
            'privacy_policy' => $this->getLocalizedPrivacyPolicy($country),
        ];
        
        return $compliance;
    }

    /**
     * Expand to new market
     */
    public function expandToMarket(string $country, array $expansionData): array
    {
        try {
            // Create server locations for the new market
            $locations = $this->createServerLocations($country, $expansionData['locations']);
            
            // Set up local payment methods
            $paymentMethods = $this->setupLocalPaymentMethods($country);
            
            // Create localized content
            $localizedContent = $this->createLocalizedContent($country, $expansionData['content']);
            
            // Set up legal compliance
            $legalCompliance = $this->setupLegalCompliance($country, $expansionData['legal']);
            
            // Configure local partnerships
            $partnerships = $this->setupLocalPartnerships($country, $expansionData['partnerships']);
            
            Log::info('Market expansion completed', [
                'country' => $country,
                'locations' => count($locations),
                'payment_methods' => count($paymentMethods),
                'partnerships' => count($partnerships),
            ]);
            
            return [
                'success' => true,
                'country' => $country,
                'locations' => $locations,
                'payment_methods' => $paymentMethods,
                'localized_content' => $localizedContent,
                'legal_compliance' => $legalCompliance,
                'partnerships' => $partnerships,
            ];
            
        } catch (\Exception $e) {
            Log::error('Market expansion failed', [
                'country' => $country,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get market analytics
     */
    public function getMarketAnalytics(string $country = null): array
    {
        $query = User::query();
        
        if ($country) {
            $query->where('country', $country);
        }
        
        $userStats = $query->selectRaw('
            country,
            COUNT(*) as user_count,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
            AVG(CASE WHEN wallet_balance > 0 THEN wallet_balance ELSE 0 END) as avg_balance
        ')
        ->groupBy('country')
        ->get();
        
        $serverStats = Server::selectRaw('
            location,
            COUNT(*) as server_count,
            AVG(ping_latency) as avg_latency,
            AVG(load_percentage) as avg_load
        ')
        ->groupBy('location')
        ->get();
        
        return [
            'user_distribution' => $userStats->toArray(),
            'server_distribution' => $serverStats->toArray(),
            'revenue_by_country' => $this->getRevenueByCountry($country),
            'growth_trends' => $this->getGrowthTrends($country),
            'market_opportunities' => $this->getMarketOpportunities(),
        ];
    }

    /**
     * Get exchange rate
     */
    protected function getExchangeRate(string $from, string $to): float
    {
        if ($from === $to) {
            return 1.0;
        }
        
        $cacheKey = "exchange_rate_{$from}_{$to}";
        
        return Cache::remember($cacheKey, 3600, function () use ($from, $to) {
            try {
                $response = Http::get("https://api.exchangerate-api.com/v4/latest/{$from}");
                $data = $response->json();
                
                return $data['rates'][$to] ?? 1.0;
            } catch (\Exception $e) {
                Log::error('Failed to get exchange rate', [
                    'from' => $from,
                    'to' => $to,
                    'error' => $e->getMessage()
                ]);
                
                return 1.0;
            }
        });
    }

    /**
     * Format currency
     */
    protected function formatCurrency(float $amount, string $currency): string
    {
        $symbols = [
            'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'JPY' => '¥',
            'CAD' => 'C$', 'AUD' => 'A$', 'CHF' => 'CHF', 'CNY' => '¥',
            'INR' => '₹', 'BRL' => 'R$', 'KRW' => '₩', 'SGD' => 'S$',
        ];
        
        $symbol = $symbols[$currency] ?? $currency;
        
        return $symbol . number_format($amount, 2);
    }

    /**
     * Get location from IP Info service
     */
    protected function getLocationFromIpInfo(string $ip): ?array
    {
        try {
            $response = Http::get("https://ipinfo.io/{$ip}/json", [
                'token' => config('services.ipinfo.token'),
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'country' => $data['country'] ?? 'US',
                    'region' => $this->getRegionFromCountry($data['country'] ?? 'US'),
                    'city' => $data['city'] ?? 'Unknown',
                    'currency' => $this->getLocalCurrency($data['country'] ?? 'US'),
                    'language' => $this->getLocalLanguage($data['country'] ?? 'US'),
                    'timezone' => $data['timezone'] ?? 'UTC',
                ];
            }
        } catch (\Exception $e) {
            Log::error('IP Info service failed', ['error' => $e->getMessage()]);
        }
        
        return null;
    }

    /**
     * Get region from country
     */
    protected function getRegionFromCountry(string $country): string
    {
        foreach ($this->supportedRegions as $region => $countries) {
            if (in_array($country, $countries)) {
                return $region;
            }
        }
        
        return 'NA'; // Default to North America
    }

    /**
     * Get local currency
     */
    protected function getLocalCurrency(string $country): string
    {
        return $this->currencyMapping[$country] ?? 'USD';
    }

    /**
     * Get local language
     */
    protected function getLocalLanguage(string $country): string
    {
        return $this->languageMapping[$country] ?? 'en';
    }

    // Additional helper methods would be implemented here...
}
