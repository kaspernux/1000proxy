<?php

use App\Livewire\{
    CartPage,
    HomePage,
    CancelPage,
    SuccessPage,
    CheckoutPage,
    MyOrdersPage,
    ProductsPage,
    Auth\LoginPage,
    CategoriesPage,
    Auth\ForgotPage,
    Auth\RegisterPage,
    MyOrderDetailPage,
    ProductDetailPage,
    Auth\ResetPasswordPage
};

use App\Http\Controllers\{
    PaymentController,
    PaymentMethodController,
    WalletController,
    WalletTransactionController,
    Webhook\NowPaymentsWebhookController,
    Webhook\StripeWebhookController
};

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RedirectIfCustomer;
use Laravel\Horizon\Horizon;

// Public Routes
Route::get('/', HomePage::class);
Route::get('/categories', CategoriesPage::class);
Route::get('/servers', ProductsPage::class);
Route::get('/cart', CartPage::class);
Route::get('/servers/{slug}', ProductDetailPage::class);

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', LoginPage::class)->name('login');
    Route::get('/register', RegisterPage::class);
    Route::get('/reset-password/{token}', ResetPasswordPage::class)->name('password.reset');
    Route::get('/forgot', ForgotPage::class)->name('password.request');
});

// Authenticated User Routes
Route::middleware(['auth:web,customer'])->group(function () {

    Route::get('/logout', function () {
        auth()->logout();
        return redirect('/');
    })->name('logout');

    Route::get('/checkout', CheckoutPage::class)->name('checkout');
    Route::get('/my-orders', MyOrdersPage::class)->name('my.orders');
    Route::get('/my-orders/{order_id}', MyOrderDetailPage::class)->name('my-orders.show');
    Route::get('/success', SuccessPage::class)->name('success');
    Route::get('/cancel', CancelPage::class)->name('cancel');

    // NowPayments Routes
    Route::get('/payment-status/{orderId}', [PaymentController::class, 'getPaymentStatusByOrder'])->name('payment.status');
    Route::get('/payments', [PaymentController::class, 'listPayments'])->name('payments');
    Route::get('/invoice/{order}', [PaymentController::class, 'showInvoice'])->name('invoice');
    Route::get('/currencies', [PaymentController::class, 'getCurrencies'])->name('currencies');
    Route::post('/create-invoice/stripe/{order}', [PaymentMethodController::class, 'createInvoice'])->name('create.invoice.stripe');
    Route::get('/partial/{order}', [PaymentController::class, 'orderPartial'])->name('order.partial');

    Route::post('/webhook/stripe', [StripeWebhookController::class, 'handle'])->name('webhook.stripe');
    Route::post('/webhook/nowpayments', [NowPaymentsWebhookController::class, 'handle'])->name('webhook.nowpay');

    // Wallet Routes
    Route::prefix('wallet')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('wallet.index');
        Route::get('/{currency}', [WalletController::class, 'show'])->name('wallet.show');
        
        // Top-up Routes
        Route::get('/{currency}/top-up', [WalletController::class, 'topUpForm'])->name('wallet.topup');
        Route::post('/{currency}/top-up', [WalletController::class, 'topUp'])->name('wallet.topup.submit');

        // Insufficient balance redirect
        Route::get('/{currency}/insufficient', [WalletController::class, 'insufficient'])->name('wallet.insufficient');

        // Transactions
        Route::get('/transactions', [WalletTransactionController::class, 'index'])->name('wallet.transactions.index');
        Route::get('/transactions/{id}', [WalletTransactionController::class, 'show'])->name('wallet.transactions.show');
    });
});

// Webhook Routes (outside auth middleware)

// Laravel Horizon setup (consider placing in a service provider instead of routes)
Horizon::routeMailNotificationsTo('you@example.com');
Horizon::routeSlackNotificationsTo('your-slack-webhook');
Horizon::auth(function ($request) {
    return true; // Adjust for your authentication needs
});

// Admin Routes (with customer redirect)
Route::middleware(['redirect.customer', RedirectIfCustomer::class])->prefix('admin')->group(function () {
    Route::get('/', function () {
        // Admin logic here
    });

    Route::get('/{any}', function ($any) {
        // Admin logic here
    })->where('any', '.*');
});
