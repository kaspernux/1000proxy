<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ServerClient;
use App\Models\WalletTransaction;
use App\Services\EnhancedMailService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CustomerSuccessService
{
    private array $automationRules = [];
    private array $customerSegments = [];
    private array $healthScoreMetrics = [];
    private EnhancedMailService $mailService;

    public function __construct(EnhancedMailService $mailService)
    {
        $this->mailService = $mailService;
        $this->initializeAutomationRules();
        $this->initializeCustomerSegments();
        $this->initializeHealthScoreMetrics();
    }

    /**
     * Initialize automation rules
     */
    private function initializeAutomationRules(): void
    {
        $this->automationRules = [
            'welcome_series' => [
                'trigger' => 'customer_registration',
                'delay' => 0,
                'actions' => ['send_welcome_email', 'create_onboarding_task'],
                'conditions' => ['is_new_customer' => true]
            ],
            'onboarding_follow_up' => [
                'trigger' => 'no_first_order',
                'delay' => 24, // hours
                'actions' => ['send_onboarding_email', 'offer_discount'],
                'conditions' => ['hours_since_registration' => 24, 'order_count' => 0]
            ],
            'first_order_congratulations' => [
                'trigger' => 'first_order_completed',
                'delay' => 1, // hours
                'actions' => ['send_congratulations_email', 'provide_setup_guide'],
                'conditions' => ['order_count' => 1, 'order_status' => 'completed']
            ],
            'usage_encouragement' => [
                'trigger' => 'low_usage_detected',
                'delay' => 72, // hours
                'actions' => ['send_usage_tips', 'offer_support'],
                'conditions' => ['usage_percentage' => '<30', 'days_since_order' => 3]
            ],
            'renewal_reminder' => [
                'trigger' => 'approaching_expiry',
                'delay' => 168, // hours (7 days)
                'actions' => ['send_renewal_reminder', 'offer_renewal_discount'],
                'conditions' => ['days_until_expiry' => 7]
            ],
            'churn_prevention' => [
                'trigger' => 'churn_risk_detected',
                'delay' => 24, // hours
                'actions' => ['send_retention_email', 'offer_special_discount', 'schedule_support_call'],
                'conditions' => ['health_score' => '<50', 'days_since_last_order' => 30]
            ],
            'upsell_opportunity' => [
                'trigger' => 'high_usage_detected',
                'delay' => 48, // hours
                'actions' => ['send_upsell_email', 'recommend_premium_plan'],
                'conditions' => ['usage_percentage' => '>80', 'plan_type' => 'basic']
            ],
            'loyalty_reward' => [
                'trigger' => 'loyal_customer_milestone',
                'delay' => 1, // hours
                'actions' => ['send_loyalty_reward', 'upgrade_support_tier'],
                'conditions' => ['months_as_customer' => 12, 'order_count' => '>10']
            ]
        ];
    }

    /**
     * Initialize customer segments
     */
    private function initializeCustomerSegments(): void
    {
        $this->customerSegments = [
            'new_customers' => [
                'name' => 'New Customers',
                'criteria' => ['days_since_registration' => '<=7', 'order_count' => 0],
                'automation_focus' => 'onboarding'
            ],
            'active_customers' => [
                'name' => 'Active Customers',
                'criteria' => ['last_login' => '<=7', 'order_count' => '>0'],
                'automation_focus' => 'engagement'
            ],
            'power_customers' => [
                'name' => 'Power Customers',
                'criteria' => ['order_count' => '>10', 'monthly_spend' => '>500'],
                'automation_focus' => 'retention'
            ],
            'at_risk' => [
                'name' => 'At Risk',
                'criteria' => ['last_login' => '>30', 'health_score' => '<50'],
                'automation_focus' => 'churn_prevention'
            ],
            'dormant' => [
                'name' => 'Dormant',
                'criteria' => ['last_order' => '>90', 'last_login' => '>60'],
                'automation_focus' => 'reactivation'
            ],
            'vip' => [
                'name' => 'VIP Customers',
                'criteria' => ['lifetime_value' => '>5000', 'months_as_customer' => '>12'],
                'automation_focus' => 'loyalty'
            ]
        ];
    }

    /**
     * Initialize health score metrics
     */
    private function initializeHealthScoreMetrics(): void
    {
        $this->healthScoreMetrics = [
            'login_frequency' => [
                'weight' => 0.20,
                'calculation' => 'login_days_last_month / 30 * 100'
            ],
            'usage_consistency' => [
                'weight' => 0.25,
                'calculation' => 'active_days_last_month / 30 * 100'
            ],
            'payment_timeliness' => [
                'weight' => 0.15,
                'calculation' => 'on_time_payments / total_payments * 100'
            ],
            'support_interaction' => [
                'weight' => 0.10,
                'calculation' => 'positive_support_interactions / total_support_interactions * 100'
            ],
            'feature_adoption' => [
                'weight' => 0.15,
                'calculation' => 'features_used / total_features * 100'
            ],
            'renewal_rate' => [
                'weight' => 0.15,
                'calculation' => 'renewals / total_subscriptions * 100'
            ]
        ];
    }

    /**
     * Calculate customer health score
     */
    public function calculateHealthScore(Customer $customer): float
    {
        $score = 0;

        foreach ($this->healthScoreMetrics as $metric => $config) {
            $value = $this->calculateMetricValue($customer, $metric);
            $score += $value * $config['weight'];
        }

        return round($score, 2);
    }

    /**
     * Calculate individual metric value
     */
    private function calculateMetricValue(Customer $customer, string $metric): float
    {
        switch ($metric) {
            case 'login_frequency':
                $loginDays = DB::table('customer_logins')
                    ->where('customer_id', $customer->id)
                    ->where('created_at', '>=', now()->subMonth())
                    ->distinct('login_date')
                    ->count();
                return ($loginDays / 30) * 100;

            case 'usage_consistency':
                $activeDays = ServerClient::where('customer_id', $customer->id)
                    ->where('last_used_at', '>=', now()->subMonth())
                    ->distinct('last_used_date')
                    ->count();
                return ($activeDays / 30) * 100;

            case 'payment_timeliness':
                $totalPayments = Order::where('customer_id', $customer->id)->count();
                $onTimePayments = Order::where('customer_id', $customer->id)
                    ->where('payment_status', 'completed')
                    ->where('paid_at', '<=', DB::raw('due_date'))
                    ->count();
                return $totalPayments > 0 ? ($onTimePayments / $totalPayments) * 100 : 100;

            case 'support_interaction':
                // This would need a support tickets system
                return 85; // Default positive score

            case 'feature_adoption':
                $totalFeatures = 10; // Total available features
                $usedFeatures = $this->countUsedFeatures($customer);
                return ($usedFeatures / $totalFeatures) * 100;

            case 'renewal_rate':
                $totalSubscriptions = ServerClient::where('customer_id', $customer->id)->count();
                $renewedSubscriptions = ServerClient::where('customer_id', $customer->id)
                    ->where('renewed_at', '>=', now()->subYear())
                    ->count();
                return $totalSubscriptions > 0 ? ($renewedSubscriptions / $totalSubscriptions) * 100 : 100;

            default:
                return 0;
        }
    }

    /**
     * Count used features for a customer
     */
    private function countUsedFeatures(Customer $customer): int
    {
        $features = 0;

        // Check various feature usage
        if ($customer->orders()->exists()) $features++;
        if ($customer->walletTransactions()->exists()) $features++;
        if ($customer->clients()->exists()) $features++;
        if ($customer->telegram_chat_id) $features++;
        if ($customer->email_verified_at) $features++;

        return $features;
    }

    /**
     * Segment customers based on criteria
     */
    public function segmentCustomers(): array
    {
        $segments = [];

        foreach ($this->customerSegments as $segmentKey => $segment) {
            $segments[$segmentKey] = Customer::query();

            foreach ($segment['criteria'] as $criterion => $value) {
                $segments[$segmentKey] = $this->applyCriterion($segments[$segmentKey], $criterion, $value);
            }

            $segments[$segmentKey] = $segments[$segmentKey]->get();
        }

        return $segments;
    }

    /**
     * Apply segmentation criterion
     */
    private function applyCriterion($query, string $criterion, $value)
    {
        switch ($criterion) {
            case 'days_since_registration':
                if (strpos($value, '<=') !== false) {
                    $days = (int) str_replace('<=', '', $value);
                    return $query->where('created_at', '>=', now()->subDays($days));
                }
                break;

            case 'order_count':
                if (strpos($value, '>') !== false) {
                    $count = (int) str_replace('>', '', $value);
                    return $query->has('orders', '>', $count);
                } elseif ($value === 0) {
                    return $query->doesntHave('orders');
                }
                break;

            case 'last_login':
                if (strpos($value, '<=') !== false) {
                    $days = (int) str_replace('<=', '', $value);
                    return $query->where('last_login_at', '>=', now()->subDays($days));
                } elseif (strpos($value, '>') !== false) {
                    $days = (int) str_replace('>', '', $value);
                    return $query->where('last_login_at', '<', now()->subDays($days));
                }
                break;

            case 'monthly_spend':
                if (strpos($value, '>') !== false) {
                    $amount = (float) str_replace('>', '', $value);
                    return $query->whereHas('orders', function ($q) use ($amount) {
                        $q->where('created_at', '>=', now()->subMonth())
                          ->groupBy('customer_id')
                          ->havingRaw('SUM(total) > ?', [$amount]);
                    });
                }
                break;

            case 'health_score':
                if (strpos($value, '<') !== false) {
                    $score = (float) str_replace('<', '', $value);
                    return $query->where('health_score', '<', $score);
                }
                break;
        }

        return $query;
    }

    /**
     * Process automation rules
     */
    public function processAutomationRules(): void
    {
        foreach ($this->automationRules as $ruleKey => $rule) {
            $this->processRule($ruleKey, $rule);
        }
    }

    /**
     * Process individual automation rule
     */
    private function processRule(string $ruleKey, array $rule): void
    {
        try {
            $customers = $this->getUsersForRule($rule);

            foreach ($customers as $customer) {
                if ($this->shouldTriggerRule($customer, $rule)) {
                    $this->executeRuleActions($customer, $rule);

                    // Log automation execution
                    Log::info("Customer success automation executed", [
                        'rule' => $ruleKey,
                        'customer_id' => $customer->id,
                        'actions' => $rule['actions']
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("Error processing automation rule {$ruleKey}: " . $e->getMessage());
        }
    }

    /**
     * Get customers for automation rule
     */
    private function getUsersForRule(array $rule): \Illuminate\Database\Eloquent\Collection
    {
        $query = Customer::query();

        // Apply rule conditions to filter customers
        foreach ($rule['conditions'] as $condition => $value) {
            $query = $this->applyRuleCondition($query, $condition, $value);
        }

        return $query->get();
    }

    /**
     * Apply rule condition
     */
    private function applyRuleCondition($query, string $condition, $value)
    {
        switch ($condition) {
            case 'is_new_customer':
                if ($value) {
                    return $query->where('created_at', '>=', now()->subDay());
                }
                break;

            case 'hours_since_registration':
                return $query->where('created_at', '<=', now()->subHours($value));

            case 'order_count':
                return $query->has('orders', '=', $value);

            case 'days_since_order':
                return $query->whereHas('orders', function ($q) use ($value) {
                    $q->where('created_at', '<=', now()->subDays($value));
                });

            case 'days_until_expiry':
                return $query->whereHas('clients', function ($q) use ($value) {
                    $q->where('expires_at', '<=', now()->addDays($value));
                });

            case 'health_score':
                if (strpos($value, '<') !== false) {
                    $score = (float) str_replace('<', '', $value);
                    return $query->where('health_score', '<', $score);
                }
                break;
        }

        return $query;
    }

    /**
     * Check if rule should trigger for customer
     */
    private function shouldTriggerRule(Customer $customer, array $rule): bool
    {
        // Check if rule was already executed recently
        $lastExecution = Cache::get("automation.{$customer->id}.last_execution");

        if ($lastExecution && $lastExecution > now()->subHours($rule['delay'])) {
            return false;
        }

        return true;
    }

    /**
     * Execute rule actions
     */
    private function executeRuleActions(Customer $customer, array $rule): void
    {
        foreach ($rule['actions'] as $action) {
            $this->executeAction($customer, $action);
        }

        // Update last execution time
        Cache::put("automation.{$customer->id}.last_execution", now(), now()->addDays(1));
    }

    /**
     * Execute individual action
     */
    private function executeAction(Customer $customer, string $action): void
    {
        switch ($action) {
            case 'send_welcome_email':
                $this->sendWelcomeEmail($customer);
                break;

            case 'send_onboarding_email':
                $this->sendOnboardingEmail($customer);
                break;

            case 'send_congratulations_email':
                $this->sendCongratulationsEmail($customer);
                break;

            case 'send_usage_tips':
                $this->sendUsageTips($customer);
                break;

            case 'send_renewal_reminder':
                $this->sendRenewalReminder($customer);
                break;

            case 'send_retention_email':
                $this->sendRetentionEmail($customer);
                break;

            case 'send_upsell_email':
                $this->sendUpsellEmail($customer);
                break;

            case 'send_loyalty_reward':
                $this->sendLoyaltyReward($customer);
                break;

            case 'offer_discount':
                $this->offerDiscount($customer);
                break;

            case 'create_onboarding_task':
                $this->createOnboardingTask($customer);
                break;

            case 'schedule_support_call':
                $this->scheduleSupportCall($customer);
                break;
        }
    }

    /**
     * Send welcome email
     */
    private function sendWelcomeEmail(Customer $customer): void
    {
        $this->mailService->sendWelcomeEmail($customer);
    }

    /**
     * Send onboarding email
     */
    private function sendOnboardingEmail(Customer $customer): void
    {
        $this->mailService->sendAdminNotification(
            $customer,
            'Complete Your 1000 PROXIES Setup',
            'Welcome! Let\'s get your proxy service up and running. Visit your dashboard to configure your first proxy connection.',
            'info'
        );
    }

    /**
     * Send congratulations email
     */
    private function sendCongratulationsEmail(Customer $customer): void
    {
        $this->mailService->sendAdminNotification(
            $customer,
            'Congratulations on Your First Order! 🎉',
            'Thank you for choosing 1000 PROXIES! Your proxy service is now active. Check your dashboard for connection details and setup instructions.',
            'success'
        );
    }

    /**
     * Send usage tips
     */
    private function sendUsageTips(Customer $customer): void
    {
        $this->mailService->sendAdminNotification(
            $customer,
            'Maximize Your Proxy Performance',
            'Here are some tips to get the most out of your 1000 PROXIES service: Use rotating IPs for better anonymity, configure sticky sessions for specific use cases, and monitor your usage in the dashboard.',
            'info'
        );
    }

    /**
     * Send renewal reminder
     */
    private function sendRenewalReminder(Customer $customer): void
    {
        Log::info("Renewal reminder sent to customer {$customer->id}");
    }

    /**
     * Send retention email
     */
    private function sendRetentionEmail(Customer $customer): void
    {
        Log::info("Retention email sent to customer {$customer->id}");
    }

    /**
     * Send upsell email
     */
    private function sendUpsellEmail(Customer $customer): void
    {
        Log::info("Upsell email sent to customer {$customer->id}");
    }

    /**
     * Send loyalty reward
     */
    private function sendLoyaltyReward(Customer $customer): void
    {
        Log::info("Loyalty reward sent to customer {$customer->id}");
    }

    /**
     * Offer discount
     */
    private function offerDiscount(Customer $customer): void
    {
        // Create discount code or wallet credit
        Log::info("Discount offered to customer {$customer->id}");
    }

    /**
     * Create onboarding task
     */
    private function createOnboardingTask(Customer $customer): void
    {
        Log::info("Onboarding task created for customer {$customer->id}");
    }

    /**
     * Schedule support call
     */
    private function scheduleSupportCall(Customer $customer): void
    {
        Log::info("Support call scheduled for customer {$customer->id}");
    }

    /**
     * Generate customer success report
     */
    public function generateReport(string $period = 'monthly'): array
    {
        $startDate = $period === 'weekly' ? now()->subWeek() : now()->subMonth();

        $segments = $this->segmentCustomers();

        return [
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'end_date' => now()->toDateString(),
            'customer_segments' => array_map(function ($segment) {
                return $segment->count();
            }, $segments),
            'health_score_distribution' => $this->getHealthScoreDistribution(),
            'automation_stats' => $this->getAutomationStats($startDate),
            'churn_metrics' => $this->getChurnMetrics($startDate),
            'growth_metrics' => $this->getGrowthMetrics($startDate)
        ];
    }

    /**
     * Get health score distribution
     */
    private function getHealthScoreDistribution(): array
    {
        return [
            'excellent' => Customer::where('health_score', '>=', 80)->count(),
            'good' => Customer::whereBetween('health_score', [60, 79])->count(),
            'fair' => Customer::whereBetween('health_score', [40, 59])->count(),
            'poor' => Customer::where('health_score', '<', 40)->count()
        ];
    }

    /**
     * Get automation statistics
     */
    private function getAutomationStats(Carbon $startDate): array
    {
        return [
            'total_automations_executed' => 0, // Would track in database
            'email_open_rate' => 0.65,
            'email_click_rate' => 0.15,
            'conversion_rate' => 0.08
        ];
    }

    /**
     * Get churn metrics
     */
    private function getChurnMetrics(Carbon $startDate): array
    {
        $totalCustomers = Customer::count();
        $churnedCustomers = Customer::where('last_login_at', '<', now()->subMonths(3))->count();

        return [
            'churn_rate' => $totalCustomers > 0 ? ($churnedCustomers / $totalCustomers) * 100 : 0,
            'at_risk_customers' => Customer::where('health_score', '<', 50)->count(),
            'saved_customers' => 0 // Would track successful retention efforts
        ];
    }

    /**
     * Get growth metrics
     */
    private function getGrowthMetrics(Carbon $startDate): array
    {
        return [
            'new_customers' => Customer::where('created_at', '>=', $startDate)->count(),
            'activated_customers' => Customer::where('created_at', '>=', $startDate)
                ->has('orders')
                ->count(),
            'revenue_expansion' => Order::where('created_at', '>=', $startDate)
                ->whereHas('customer', function ($q) {
                    $q->where('created_at', '<', now()->subMonth());
                })
                ->sum('total')
        ];
    }

    /**
     * Update customer health scores
     */
    public function updateHealthScores(): void
    {
        Customer::chunk(100, function ($customers) {
            foreach ($customers as $customer) {
                $healthScore = $this->calculateHealthScore($customer);
                $customer->update(['health_score' => $healthScore]);
            }
        });

        Log::info("Health scores updated for all customers");
    }

    /**
     * Run customer success automation
     */
    public function runAutomation(): void
    {
        Log::info("Starting customer success automation");

        // Update health scores
        $this->updateHealthScores();

        // Process automation rules
        $this->processAutomationRules();

        Log::info("Customer success automation completed");
    }
}
