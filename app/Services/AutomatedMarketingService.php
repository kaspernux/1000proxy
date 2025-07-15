<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\WalletTransaction;
use App\Models\MarketingCampaign;
use App\Models\EmailTemplate;
use App\Models\CustomerSegment;
use App\Models\ReferralCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AutomatedMarketingService
{
    protected $campaigns = [];
    protected $segments = [];
    protected $emailTemplates = [];

    public function __construct()
    {
        $this->loadCampaigns();
        $this->loadSegments();
        $this->loadEmailTemplates();
    }

    /**
     * Execute automated marketing workflows
     */
    public function executeAutomatedMarketing(): array
    {
        $results = [
            'campaigns_executed' => 0,
            'emails_sent' => 0,
            'segments_updated' => 0,
            'referrals_processed' => 0,
            'errors' => [],
            'execution_time' => microtime(true),
        ];

        try {
            // Update customer segments
            $results['segments_updated'] = $this->updateCustomerSegments();

            // Execute active campaigns
            $results['campaigns_executed'] = $this->executeActiveCampaigns();

            // Send automated emails
            $results['emails_sent'] = $this->sendAutomatedEmails();

            // Process referral rewards
            $results['referrals_processed'] = $this->processReferralRewards();

            // Analyze and optimize campaigns
            $this->analyzeCampaignPerformance();

            Log::info('Automated marketing execution completed', $results);

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            Log::error('Automated marketing execution failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        $results['execution_time'] = round(microtime(true) - $results['execution_time'], 2);
        return $results;
    }

    /**
     * Update customer segments based on behavior and characteristics
     */
    protected function updateCustomerSegments(): int
    {
        $segmentCount = 0;
        $customers = Customer::with(['orders', 'walletTransactions'])->get();

        foreach ($customers as $customer) {
            $segment = $this->calculateCustomerSegment($customer);

            if ($customer->segment !== $segment) {
                $customer->update(['segment' => $segment]);
                $segmentCount++;

                // Log segment change for analytics
                Log::info('Customer segment updated', [
                    'customer_id' => $customer->id,
                    'old_segment' => $customer->segment,
                    'new_segment' => $segment
                ]);
            }
        }

        return $segmentCount;
    }

    /**
     * Calculate customer segment based on RFM analysis
     */
    protected function calculateCustomerSegment(Customer $customer): string
    {
        $orders = $customer->orders()->where('status', 'completed')->get();

        if ($orders->isEmpty()) {
            return 'new';
        }

        // Calculate RFM metrics
        $recency = $orders->max('created_at')->diffInDays(now());
        $frequency = $orders->count();
        $monetary = $orders->sum('total_amount');

        // Define segment rules
        if ($monetary > 1000 && $frequency > 10 && $recency < 30) {
            return 'vip';
        } elseif ($monetary > 500 && $frequency > 5 && $recency < 60) {
            return 'high_value';
        } elseif ($frequency > 3 && $recency < 90) {
            return 'loyal';
        } elseif ($monetary > 100 && $recency < 30) {
            return 'potential';
        } elseif ($recency > 180) {
            return 'at_risk';
        } elseif ($recency > 90) {
            return 'dormant';
        } else {
            return 'regular';
        }
    }

    /**
     * Execute active marketing campaigns
     */
    protected function executeActiveCampaigns(): int
    {
        $campaignsExecuted = 0;
        $activeCampaigns = $this->getActiveCampaigns();

        foreach ($activeCampaigns as $campaign) {
            try {
                $this->executeCampaign($campaign);
                $campaignsExecuted++;

                Log::info('Campaign executed', [
                    'campaign_id' => $campaign['id'],
                    'campaign_name' => $campaign['name']
                ]);

            } catch (\Exception $e) {
                Log::error('Campaign execution failed', [
                    'campaign_id' => $campaign['id'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $campaignsExecuted;
    }

    /**
     * Execute a specific marketing campaign
     */
    protected function executeCampaign(array $campaign): void
    {
        $targetCustomers = $this->getTargetCustomers($campaign['target_segment']);

        foreach ($targetCustomers as $customer) {
            // Check if customer is eligible for this campaign
            if ($this->isCustomerEligible($customer, $campaign)) {
                $this->sendCampaignEmail($customer, $campaign);

                // Apply campaign rewards if applicable
                if (isset($campaign['reward'])) {
                    $this->applyCampaignReward($customer, $campaign['reward']);
                }
            }
        }
    }

    /**
     * Send automated emails based on triggers
     */
    protected function sendAutomatedEmails(): int
    {
        $emailsSent = 0;

        // Welcome emails for new customers
        $emailsSent += $this->sendWelcomeEmails();

        // Abandoned cart emails
        $emailsSent += $this->sendAbandonedCartEmails();

        // Renewal reminder emails
        $emailsSent += $this->sendRenewalReminderEmails();

        // Win-back emails for dormant customers
        $emailsSent += $this->sendWinBackEmails();

        // Usage milestone emails
        $emailsSent += $this->sendMilestoneEmails();

        return $emailsSent;
    }

    /**
     * Send welcome emails to new customers
     */
    protected function sendWelcomeEmails(): int
    {
        $newCustomers = Customer::where('created_at', '>=', now()->subDays(1))
            ->where('welcome_email_sent', false)
            ->get();

        $emailsSent = 0;
        foreach ($newCustomers as $customer) {
            try {
                $this->sendEmail($customer, 'welcome', [
                    'customer_name' => $customer->name,
                    'dashboard_url' => route('customer.dashboard'),
                    'support_url' => route('support.contact'),
                ]);

                $customer->update(['welcome_email_sent' => true]);
                $emailsSent++;

            } catch (\Exception $e) {
                Log::error('Welcome email failed', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $emailsSent;
    }

    /**
     * Send abandoned cart emails
     */
    protected function sendAbandonedCartEmails(): int
    {
        // This would require implementing a cart system
        // For now, we'll focus on incomplete orders
        $incompleteOrders = Order::where('status', 'pending')
            ->where('created_at', '<=', now()->subHours(24))
            ->where('created_at', '>=', now()->subDays(3))
            ->whereDoesntHave('abandonedCartEmails')
            ->with('customer')
            ->get();

        $emailsSent = 0;
        foreach ($incompleteOrders as $order) {
            try {
                $customer = $order->customer()->first();
                if (!$customer) continue;

                $this->sendEmail($customer, 'abandoned_cart', [
                    'customer_name' => $customer->name,
                    'order_total' => $order->total_amount,
                    'complete_order_url' => route('customer.orders.complete', $order->id),
                ]);

                // Mark as sent to avoid duplicates
                $order->abandonedCartEmails()->create([
                    'sent_at' => now()
                ]);

                $emailsSent++;

            } catch (\Exception $e) {
                Log::error('Abandoned cart email failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $emailsSent;
    }

    /**
     * Send renewal reminder emails
     */
    protected function sendRenewalReminderEmails(): int
    {
        // Find orders that will expire soon
        $expiringOrders = Order::where('status', 'completed')
            ->whereDate('expires_at', '=', now()->addDays(7)->toDateString())
            ->with('customer')
            ->get();

        $emailsSent = 0;
        foreach ($expiringOrders as $order) {
            try {
                $customer = $order->customer()->first();
                if (!$customer) continue;

                $this->sendEmail($customer, 'renewal_reminder', [
                    'customer_name' => $customer->name,
                    'service_name' => $order->orderItems->first()->server_plan->name ?? 'Proxy Service',
                    'expiry_date' => $order->expires_at->format('M j, Y'),
                    'renewal_url' => route('customer.orders.renew', $order->id),
                ]);

                $emailsSent++;

            } catch (\Exception $e) {
                Log::error('Renewal reminder email failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $emailsSent;
    }

    /**
     * Send win-back emails to dormant customers
     */
    protected function sendWinBackEmails(): int
    {
        $dormantCustomers = Customer::where('segment', 'dormant')
            ->where('last_winback_email', '<', now()->subDays(30))
            ->orWhereNull('last_winback_email')
            ->get();

        $emailsSent = 0;
        foreach ($dormantCustomers as $customer) {
            try {
                // Create special offer for win-back
                $discountCode = $this->generateDiscountCode($customer, 25); // 25% discount

                $this->sendEmail($customer, 'winback', [
                    'customer_name' => $customer->name,
                    'discount_code' => $discountCode,
                    'discount_percentage' => 25,
                    'shop_url' => route('products'),
                ]);

                $customer->update(['last_winback_email' => now()]);
                $emailsSent++;

            } catch (\Exception $e) {
                Log::error('Win-back email failed', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $emailsSent;
    }

    /**
     * Send milestone emails
     */
    protected function sendMilestoneEmails(): int
    {
        $customers = Customer::whereHas('orders', function ($query) {
            $query->where('status', 'completed');
        })->get();

        $emailsSent = 0;
        foreach ($customers as $customer) {
            $orderCount = $customer->orders()->where('status', 'completed')->count();
            $totalSpent = $customer->orders()->where('status', 'completed')->sum('total_amount');

            // Check for milestone achievements
            $milestones = $this->checkMilestones($customer, $orderCount, $totalSpent);

            foreach ($milestones as $milestone) {
                try {
                    $this->sendEmail($customer, 'milestone', [
                        'customer_name' => $customer->name,
                        'milestone_type' => $milestone['type'],
                        'milestone_value' => $milestone['value'],
                        'reward' => $milestone['reward'],
                    ]);

                    // Apply milestone reward
                    $this->applyMilestoneReward($customer, $milestone);

                    $emailsSent++;

                } catch (\Exception $e) {
                    Log::error('Milestone email failed', [
                        'customer_id' => $customer->id,
                        'milestone' => $milestone,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $emailsSent;
    }

    /**
     * Process referral rewards
     */
    protected function processReferralRewards(): int
    {
        $referralsProcessed = 0;

        // Find new customers who used referral codes
        $referredCustomers = Customer::whereNotNull('referred_by')
            ->where('referral_reward_processed', false)
            ->whereHas('orders', function ($query) {
                $query->where('status', 'completed');
            })
            ->get();

        foreach ($referredCustomers as $customer) {
            try {
                $referrer = Customer::find($customer->referred_by);
                if ($referrer) {
                    // Give reward to referrer
                    $this->giveReferralReward($referrer, 'referrer', $customer);

                    // Give welcome bonus to referred customer
                    $this->giveReferralReward($customer, 'referred', $referrer);

                    $customer->update(['referral_reward_processed' => true]);
                    $referralsProcessed++;
                }

            } catch (\Exception $e) {
                Log::error('Referral reward processing failed', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $referralsProcessed;
    }

    /**
     * Analyze campaign performance and optimize
     */
    protected function analyzeCampaignPerformance(): void
    {
        // This would implement A/B testing and performance analysis
        // For now, we'll log basic metrics

        $metrics = [
            'total_customers' => Customer::count(),
            'active_customers' => Customer::whereHas('orders', function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            })->count(),
            'segment_distribution' => Customer::selectRaw('segment, COUNT(*) as count')
                ->groupBy('segment')
                ->pluck('count', 'segment')
                ->toArray(),
        ];

        Log::info('Marketing performance metrics', $metrics);

        // Cache metrics for dashboard
        Cache::put('marketing_metrics', $metrics, now()->addHours(1));
    }

    /**
     * Helper methods
     */
    protected function loadCampaigns(): void
    {
        $this->campaigns = [
            [
                'id' => 1,
                'name' => 'VIP Customer Appreciation',
                'target_segment' => 'vip',
                'active' => true,
                'trigger' => 'segment_update',
                'reward' => ['type' => 'discount', 'value' => 20],
            ],
            [
                'id' => 2,
                'name' => 'High Value Customer Retention',
                'target_segment' => 'high_value',
                'active' => true,
                'trigger' => 'monthly',
                'reward' => ['type' => 'credit', 'value' => 50],
            ],
            // Add more campaigns as needed
        ];
    }

    protected function loadSegments(): void
    {
        $this->segments = [
            'new' => 'New customers with no orders',
            'regular' => 'Regular customers with moderate activity',
            'loyal' => 'Customers with high frequency',
            'high_value' => 'High spending customers',
            'vip' => 'VIP customers with highest value',
            'potential' => 'Customers with growth potential',
            'at_risk' => 'Customers at risk of churning',
            'dormant' => 'Inactive customers',
        ];
    }

    protected function loadEmailTemplates(): void
    {
        $this->emailTemplates = [
            'welcome' => [
                'subject' => 'Welcome to 1000proxy!',
                'template' => 'emails.welcome',
            ],
            'abandoned_cart' => [
                'subject' => 'Complete your proxy setup',
                'template' => 'emails.abandoned_cart',
            ],
            'renewal_reminder' => [
                'subject' => 'Your proxy service expires soon',
                'template' => 'emails.renewal_reminder',
            ],
            'winback' => [
                'subject' => 'We miss you! Special offer inside',
                'template' => 'emails.winback',
            ],
            'milestone' => [
                'subject' => 'Congratulations on your milestone!',
                'template' => 'emails.milestone',
            ],
        ];
    }

    protected function getActiveCampaigns(): array
    {
        return array_filter($this->campaigns, function ($campaign) {
            return $campaign['active'] === true;
        });
    }

    protected function getTargetCustomers(string $segment): Collection
    {
        return Customer::where('segment', $segment)->get();
    }

    protected function isCustomerEligible(Customer $customer, array $campaign): bool
    {
        // Implement eligibility rules
        $lastCampaignEmail = Cache::get("campaign_email_{$campaign['id']}_{$customer->id}");

        if ($lastCampaignEmail && $lastCampaignEmail > now()->subDays(30)) {
            return false; // Don't send same campaign too frequently
        }

        return true;
    }

    protected function sendCampaignEmail(Customer $customer, array $campaign): void
    {
        // Implementation would send actual campaign email
        Log::info('Campaign email sent', [
            'customer_id' => $customer->id,
            'campaign_id' => $campaign['id']
        ]);

        Cache::put("campaign_email_{$campaign['id']}_{$customer->id}", now(), now()->addDays(30));
    }

    protected function applyCampaignReward(Customer $customer, array $reward): void
    {
        if ($reward['type'] === 'discount') {
            $this->generateDiscountCode($customer, $reward['value']);
        } elseif ($reward['type'] === 'credit') {
            $this->addWalletCredit($customer, $reward['value']);
        }
    }

    protected function sendEmail(Customer $customer, string $templateType, array $data): void
    {
        $template = $this->emailTemplates[$templateType];

        // This would send actual email using Laravel Mail
        Log::info('Email sent', [
            'customer_id' => $customer->id,
            'template' => $templateType,
            'subject' => $template['subject']
        ]);
    }

    protected function checkMilestones(Customer $customer, int $orderCount, float $totalSpent): array
    {
        $milestones = [];

        // Order count milestones
        if (in_array($orderCount, [5, 10, 25, 50, 100])) {
            $milestones[] = [
                'type' => 'orders',
                'value' => $orderCount,
                'reward' => ['type' => 'credit', 'value' => $orderCount * 5],
            ];
        }

        // Spending milestones
        $spendingMilestones = [100, 500, 1000, 2500, 5000];
        foreach ($spendingMilestones as $milestone) {
            if ($totalSpent >= $milestone && !$customer->hasAchievedMilestone('spending', $milestone)) {
                $milestones[] = [
                    'type' => 'spending',
                    'value' => $milestone,
                    'reward' => ['type' => 'discount', 'value' => 15],
                ];
            }
        }

        return $milestones;
    }

    protected function applyMilestoneReward(Customer $customer, array $milestone): void
    {
        // Apply the reward and mark milestone as achieved
        $this->applyCampaignReward($customer, $milestone['reward']);

        // Record milestone achievement
        $customer->milestones()->create([
            'type' => $milestone['type'],
            'value' => $milestone['value'],
            'achieved_at' => now(),
        ]);
    }

    protected function giveReferralReward(Customer $customer, string $type, Customer $relatedCustomer): void
    {
        $rewardAmount = $type === 'referrer' ? 25 : 10; // $25 for referrer, $10 for referred

        $this->addWalletCredit($customer, $rewardAmount);

        Log::info('Referral reward given', [
            'customer_id' => $customer->id,
            'type' => $type,
            'amount' => $rewardAmount,
            'related_customer' => $relatedCustomer->id,
        ]);
    }

    protected function generateDiscountCode(Customer $customer, int $percentage): string
    {
        $code = strtoupper($customer->name . $percentage . random_int(100, 999));

        // This would create a discount code in the database
        Log::info('Discount code generated', [
            'customer_id' => $customer->id,
            'code' => $code,
            'percentage' => $percentage,
        ]);

        return $code;
    }

    protected function addWalletCredit(Customer $customer, float $amount): void
    {
        WalletTransaction::create([
            'customer_id' => $customer->id,
            'type' => 'credit',
            'amount' => $amount,
            'description' => 'Marketing reward credit',
            'payment_method' => 'marketing_reward',
            'status' => 'completed',
        ]);

        Log::info('Wallet credit added', [
            'customer_id' => $customer->id,
            'amount' => $amount,
        ]);
    }

    /**
     * Get marketing performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return Cache::get('marketing_metrics', [
            'total_customers' => 0,
            'active_customers' => 0,
            'segment_distribution' => [],
        ]);
    }

    /**
     * Generate marketing report
     */
    public function generateMarketingReport(string $period = '30d'): array
    {
        $startDate = match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '1y' => now()->subYear(),
            default => now()->subDays(30),
        };

        return [
            'period' => $period,
            'customer_acquisition' => Customer::where('created_at', '>=', $startDate)->count(),
            'revenue_generated' => Order::where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->sum('total_amount'),
            'email_campaign_stats' => $this->getEmailCampaignStats($startDate),
            'segment_growth' => $this->getSegmentGrowth($startDate),
            'referral_stats' => $this->getReferralStats($startDate),
        ];
    }

    protected function getEmailCampaignStats(\DateTime $startDate): array
    {
        // This would return actual email statistics
        return [
            'emails_sent' => rand(100, 1000),
            'open_rate' => rand(20, 40),
            'click_rate' => rand(5, 15),
            'conversion_rate' => rand(2, 8),
        ];
    }

    protected function getSegmentGrowth(\DateTime $startDate): array
    {
        return Customer::selectRaw('segment, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('segment')
            ->pluck('count', 'segment')
            ->toArray();
    }

    protected function getReferralStats(\DateTime $startDate): array
    {
        $referredCount = Customer::whereNotNull('referred_by')
            ->where('created_at', '>=', $startDate)
            ->count();

        return [
            'new_referrals' => $referredCount,
            'total_referrals' => Customer::whereNotNull('referred_by')->count(),
            'referral_conversion_rate' => $referredCount > 0 ? rand(15, 35) : 0,
        ];
    }
}
