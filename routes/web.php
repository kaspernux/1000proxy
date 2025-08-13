<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Download route for generated exports (ensures notifications marked read)
Route::middleware(['web','auth'])->get('/admin/exports/download', function() {
    $path = base64_decode(request('path'));
    abort_unless($path && Storage::disk('local')->exists($path), 404);
    $user = auth()->user();
    if ($user) {
        $user->unreadNotifications()
            ->where('type', \App\Notifications\ExportReadyNotification::class)
            ->whereJsonContains('data->path', $path)
            ->get()
            ->each(function($n){ $n->markAsRead(); });
    }
    return Storage::disk('local')->download($path);
})->name('admin.download-export');

use App\Livewire\{
    CartPage,
    HomePage,
    CancelPage,
    SuccessPage,
    MyOrdersPage,
    ProductsPage,
    Auth\LoginPage,
    Auth\NewLoginPage,
    CategoriesPage,
    Auth\ForgotPage,
    Auth\RegisterPage,
    MyOrderDetailPage,
    ProductDetailPage,
    Auth\ResetPasswordPage,
    AccountSettings
};

use App\Http\Controllers\{
    Auth\CustomerLoginController,
    PaymentController,
    PaymentMethodController,
    WalletController,
    WalletTransactionController,
    TelegramBotController,
    Admin\BusinessGrowthController,
    Admin\ThirdPartyIntegrationController,
    Admin\MarketingAutomationController,
    CheckoutController,
    MagicLoginController
};
// Magic login for customers (signed URL)
Route::get('/auth/magic', MagicLoginController::class)->name('magic.login');

// (Route facade already imported at top)
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\RedirectIfCustomer;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessXuiOrder;
use App\Http\Controllers\Webhook\NowPaymentsWebhookController;
use App\Http\Controllers\Webhook\StripeWebhookController;
use Laravel\Horizon\Horizon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\DepositWebhookController;

use App\Livewire\Components\PaymentProcessor;


Route::get('/', HomePage::class);
Route::get('/categories', CategoriesPage::class);
Route::get('/servers', ProductsPage::class)->name('servers.index');
// Legacy/alternate products route name used by tests & services
Route::get('/products', ProductsPage::class)->name('products');
Route::get('/cart', CartPage::class);
Route::get('/servers/{slug}', ProductDetailPage::class);

Route::middleware('guest')->group(function () {
    // Test with fresh new component to avoid caching issues
    Route::match(['GET', 'POST'], '/login', LoginPage::class)->name('login');
    Route::get('/register', RegisterPage::class)->name('register'); // <-- Add ->name('register')
    Route::get('/reset-password/{token}', ResetPasswordPage::class)->name('password.reset');
    Route::get('/forgot', ForgotPage::class)->name('password.request');
});

Route::middleware(['auth:customer'])->group(function () {

    Route::get('/logout', function () {
        Auth::guard('customer')->logout();
        return redirect('/');
    })->name('logout');

    Route::get('/checkout', \App\Livewire\CheckoutPage::class)->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])
        ->name('checkout.success')
        ->where('order', '[0-9]+');
    Route::get('/checkout/cancel/{order}', [CheckoutController::class, 'cancel'])
        ->name('checkout.cancel')
        ->where('order', '[0-9]+');
    Route::get('/my-orders', MyOrdersPage::class)->name('my.orders');
    Route::get('/my-orders/{order_id}', MyOrderDetailPage::class)->name('my-orders.show');
    Route::get('/success', SuccessPage::class)->name('success');
    Route::get('/cancel', CancelPage::class)->name('cancel');
    Route::get('/account-settings', AccountSettings::class)->name('account.settings');
    // Explicit redirect for /account root to Filament customer dashboard page
    Route::get('/account', function () {
        return redirect()->route('filament.customer.pages.dashboard');
    })->name('customer.account.redirect');
    // Redirect /account to Filament customer panel dashboard instead of account settings
    // Removed explicit /account route so Filament customer panel root can serve the dashboard
    Route::get('/telegram-link', \App\Livewire\Auth\TelegramLink::class)->name('telegram.link');

    // Payment routes for web UI (session/customer only)
    Route::prefix('payment')->group(function () {
        // Use Livewire component for payment processor UI
        Route::get('/processor', PaymentProcessor::class)->name('payment.processor');
        Route::get('/invoice/{order}', [PaymentController::class, 'showInvoice'])->name('payment.invoice');
    // Session (customer) authenticated JSON gateway list for Livewire (avoids Sanctum token requirement on /api route)
    Route::get('/gateways', [PaymentController::class, 'getAvailableGateways'])->name('payment.gateways');
    });

    // Invoice routes
    // Route::get('/invoice/{order}', [PaymentController::class, 'showInvoice'])->name('payment.invoice');
    // Remove duplicate route name if present
    // Route::post('/create-invoice/nowpayments/{order}', [PaymentController::class, 'createPayment'])->name('payment.create.invoice.nowpay');
    // Route::post('/create-invoice/stripe/{order}', [PaymentController::class, 'createPayment'])->name('payment.create.invoice.stripe');


    // Wallet routes (use PaymentController for top-up)
    Route::prefix('wallet')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('wallet.index');
        Route::get('/{currency}', [WalletController::class, 'show'])->name('wallet.show');
        Route::get('/{currency}/top-up', [WalletController::class, 'topUpForm'])->name('wallet.topup');
        Route::post('/{currency}/top-up', [WalletController::class, 'topUp'])->name('wallet.topup.submit');
        Route::get('/{currency}/insufficient', [WalletController::class, 'insufficient'])->name('wallet.insufficient');
    });


    // Transaction routes
    Route::get('/transactions', \App\Livewire\Transactions::class)->name('transactions.index');
    Route::get('/transactions/{transaction}', [WalletTransactionController::class, 'show'])->name('wallet.transactions.show');

    // Webhook routes (outside auth middleware)
    Route::post('/webhook/stripe', [StripeWebhookController::class, '__invoke'])->name('webhook.stripe');
    Route::post('/webhook/nowpayments', [NowPaymentsWebhookController::class, '__invoke'])->name('webhook.nowpay');
    Route::post('/webhook/btc', [DepositWebhookController::class, 'handleBtc']);
    Route::post('/webhook/xmr', [DepositWebhookController::class, 'handleXmr']);
    Route::post('/webhook/sol', [DepositWebhookController::class, 'handleSol']);

    // Payment Method routes
    Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('payment.methods.index');
    Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('payment.methods.store');
    Route::put('/payment-methods/{method}', [PaymentMethodController::class, 'update'])->name('payment.methods.update');
    Route::delete('/payment-methods/{method}', [PaymentMethodController::class, 'destroy'])->name('payment.methods.destroy');
});


// Horizon routes
Route::get('/horizon', function () {
    return view('horizon');
})->middleware(['auth:sanctum', 'can:viewHorizon']);

// Horizon Jobs
Horizon::routeMailNotificationsTo('you@example.com');
Horizon::routeSlackNotificationsTo('your-slack-webhook');
Horizon::auth(function ($request) {
    return true; // 🔒 you can secure with Gate, e.g., auth()->user()->isAdmin()
});

// Telegram Bot Routes
Route::prefix('telegram')->group(function () {
    Route::post('/webhook/{secret?}', [TelegramBotController::class, 'webhook'])
        ->withoutMiddleware([
            \App\Http\Middleware\EnhancedCsrfProtection::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        ])
        ->middleware('telegram.rate')
        ->name('telegram.webhook');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/set-webhook', [TelegramBotController::class, 'setWebhook'])
            ->name('telegram.set-webhook');
        Route::get('/webhook-info', [TelegramBotController::class, 'getWebhookInfo'])
            ->name('telegram.webhook-info');
        Route::delete('/webhook', [TelegramBotController::class, 'removeWebhook'])
            ->name('telegram.remove-webhook');
        Route::get('/test', [TelegramBotController::class, 'testBot'])
            ->name('telegram.test');
        Route::post('/send-test-message', [TelegramBotController::class, 'sendTestMessage'])
            ->name('telegram.send-test-message');
        Route::get('/bot-stats', [TelegramBotController::class, 'getBotStats'])
            ->name('telegram.bot-stats');
        Route::post('/broadcast', [TelegramBotController::class, 'broadcastMessage'])
            ->name('telegram.broadcast');

        // Admin UI: Linking and Notifications
        Route::post('/generate-link', [TelegramBotController::class, 'generateLink'])
            ->name('telegram.generate-link');
        Route::get('/linked-users', [TelegramBotController::class, 'linkedUsers'])
            ->name('telegram.linked-users');
        Route::get('/stats', [TelegramBotController::class, 'linkedUsersStats'])
            ->name('telegram.linked-users-stats');
        Route::delete('/unlink-user/{id}', [TelegramBotController::class, 'unlinkUser'])
            ->name('telegram.unlink-user');
        Route::get('/notifications', [TelegramBotController::class, 'getNotifications'])
            ->name('telegram.notifications');
        Route::get('/templates', [TelegramBotController::class, 'getTemplates'])
            ->name('telegram.templates');
        Route::post('/send-notification', [TelegramBotController::class, 'sendNotification'])
            ->name('telegram.send-notification');
        Route::post('/preview-notification', [TelegramBotController::class, 'previewNotification'])
            ->name('telegram.preview-notification');
        Route::delete('/notifications/{id}', [TelegramBotController::class, 'deleteNotification'])
            ->name('telegram.delete-notification');

        // Admin bot UX controls
        Route::post('/set-branding', [TelegramBotController::class, 'setBranding'])
            ->name('telegram.set-branding');
        Route::post('/set-menu', [TelegramBotController::class, 'setMenu'])
            ->name('telegram.set-menu');
    });
});

Route::middleware(['redirect.customer', RedirectIfCustomer::class])->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('/admin', function () {
            // Admin route logic here
        });

        Route::get('/admin/{any}', function ($any) {
            // Admin route logic here
        })->where('any', '.*');

        // Business Growth Routes
        Route::prefix('business-growth')->group(function () {
            Route::get('/dashboard', [BusinessGrowthController::class, 'dashboard'])
                ->name('admin.business-growth.dashboard');

            // Payment Gateway Management
            Route::get('/payment-gateways', [BusinessGrowthController::class, 'paymentGateways'])
                ->name('admin.business-growth.payment-gateways');
            Route::post('/payment-gateways/{gateway}/configure', [BusinessGrowthController::class, 'configurePaymentGateway'])
                ->name('admin.business-growth.payment-gateways.configure');

            // Geographic Expansion Management
            Route::get('/geographic-expansion', [BusinessGrowthController::class, 'geographicExpansion'])
                ->name('admin.business-growth.geographic-expansion');
            Route::post('/geographic-expansion/pricing', [BusinessGrowthController::class, 'updateRegionalPricing'])
                ->name('admin.business-growth.geographic-expansion.pricing');
            Route::post('/geographic-expansion/restrictions', [BusinessGrowthController::class, 'updateGeographicRestrictions'])
                ->name('admin.business-growth.geographic-expansion.restrictions');

            // Partnership Management
            Route::get('/partnerships', [BusinessGrowthController::class, 'partnerships'])
                ->name('admin.business-growth.partnerships');
            Route::post('/partnerships/{service}/integrate', [BusinessGrowthController::class, 'integratePartnership'])
                ->name('admin.business-growth.partnerships.integrate');
            Route::get('/partnerships/affiliate', [BusinessGrowthController::class, 'affiliateProgram'])
                ->name('admin.business-growth.partnerships.affiliate');
            Route::get('/partnerships/reseller', [BusinessGrowthController::class, 'resellerProgram'])
                ->name('admin.business-growth.partnerships.reseller');

            // Customer Success Management
            Route::get('/customer-success', [BusinessGrowthController::class, 'customerSuccess'])
                ->name('admin.business-growth.customer-success');
            Route::post('/customer-success/run-automation', [BusinessGrowthController::class, 'runAutomation'])
                ->name('admin.business-growth.customer-success.run-automation');
            Route::post('/customer-success/update-health-scores', [BusinessGrowthController::class, 'updateHealthScores'])
                ->name('admin.business-growth.customer-success.update-health-scores');

            // Analytics and Reporting
            Route::get('/analytics', [BusinessGrowthController::class, 'analytics'])
                ->name('admin.business-growth.analytics');
            Route::get('/analytics/dashboard', function () {
                return view('admin.analytics.dashboard');
            })->name('admin.analytics.dashboard');
            Route::post('/export-report', [BusinessGrowthController::class, 'exportReport'])
                ->name('admin.business-growth.export-report');
        });

        // Advanced Proxy Management Routes
        Route::prefix('proxy-management')->group(function () {
            Route::get('/advanced', function () {
                return view('admin.advanced-proxy-management');
            })->name('admin.proxy-management.advanced');
        });

        // Third-Party Integrations Routes
        Route::prefix('integrations')->group(function () {
            Route::get('/management', function () {
                return view('admin.third-party-integration-management');
            })->name('admin.integrations.management');

            Route::get('/dashboard', [ThirdPartyIntegrationController::class, 'dashboard'])
                ->name('admin.integrations.dashboard');

            Route::post('/initialize', [ThirdPartyIntegrationController::class, 'initializeIntegrations'])
                ->name('admin.integrations.initialize');

            Route::post('/billing/setup', [ThirdPartyIntegrationController::class, 'setupBillingIntegration'])
                ->name('admin.integrations.billing.setup');

            Route::post('/crm/setup', [ThirdPartyIntegrationController::class, 'setupCRMIntegration'])
                ->name('admin.integrations.crm.setup');

            Route::post('/analytics/setup', [ThirdPartyIntegrationController::class, 'setupAnalyticsIntegration'])
                ->name('admin.integrations.analytics.setup');

            Route::post('/support/setup', [ThirdPartyIntegrationController::class, 'setupSupportIntegration'])
                ->name('admin.integrations.support.setup');

            Route::post('/partner-api/setup', [ThirdPartyIntegrationController::class, 'setupPartnerAPI'])
                ->name('admin.integrations.partner-api.setup');

            Route::post('/webhook', [ThirdPartyIntegrationController::class, 'handleWebhook'])
                ->name('admin.integrations.webhook');

            Route::post('/test/{service}', [ThirdPartyIntegrationController::class, 'testIntegration'])
                ->name('admin.integrations.test');

            Route::post('/sync/{service}', [ThirdPartyIntegrationController::class, 'syncData'])
                ->name('admin.integrations.sync');

            Route::get('/export', [ThirdPartyIntegrationController::class, 'exportConfiguration'])
                ->name('admin.integrations.export');

            Route::post('/import', [ThirdPartyIntegrationController::class, 'importConfiguration'])
                ->name('admin.integrations.import');
        });

        // Marketing Automation Routes
        Route::prefix('marketing')->group(function () {
            Route::get('/automation', function () {
                return view('admin.marketing-automation-management');
            })->name('admin.marketing.automation');

            Route::get('/dashboard', [MarketingAutomationController::class, 'dashboard'])
                ->name('admin.marketing.dashboard');

            Route::post('/initialize', [MarketingAutomationController::class, 'initializeAutomation'])
                ->name('admin.marketing.initialize');

            Route::post('/campaigns/create', [MarketingAutomationController::class, 'createCampaign'])
                ->name('admin.marketing.campaigns.create');

            Route::post('/campaigns/execute', [MarketingAutomationController::class, 'executeCampaign'])
                ->name('admin.marketing.campaigns.execute');

            Route::post('/workflows/setup', [MarketingAutomationController::class, 'setupWorkflows'])
                ->name('admin.marketing.workflows.setup');

            Route::post('/lead-nurturing/process', [MarketingAutomationController::class, 'processLeadNurturing'])
                ->name('admin.marketing.lead-nurturing.process');

            Route::post('/abandoned-cart/process', [MarketingAutomationController::class, 'processAbandonedCart'])
                ->name('admin.marketing.abandoned-cart.process');

            Route::get('/segments', [MarketingAutomationController::class, 'getCustomerSegments'])
                ->name('admin.marketing.segments');

            Route::get('/performance', [MarketingAutomationController::class, 'getCampaignPerformance'])
                ->name('admin.marketing.performance');

            Route::get('/email-metrics', [MarketingAutomationController::class, 'getEmailMetrics'])
                ->name('admin.marketing.email-metrics');

            Route::post('/settings/update', [MarketingAutomationController::class, 'updateAutomationSettings'])
                ->name('admin.marketing.settings.update');

            Route::get('/analytics/generate', [MarketingAutomationController::class, 'generateAnalytics'])
                ->name('admin.marketing.analytics.generate');

            Route::post('/email/test', [MarketingAutomationController::class, 'testEmailDelivery'])
                ->name('admin.marketing.email.test');

            Route::get('/export', [MarketingAutomationController::class, 'exportCampaignData'])
                ->name('admin.marketing.export');
        });
    });
});

// PWA Routes
require __DIR__.'/pwa.php';

// Test Routes
require __DIR__.'/test.php';


