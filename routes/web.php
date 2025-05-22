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
    WalletTransactionController
};

use Illuminate\Support\Facades\Route;
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


Route::get('/', HomePage::class);
Route::get('/categories', CategoriesPage::class);
Route::get('/servers', ProductsPage::class);
Route::get('/cart', CartPage::class);
Route::get('/servers/{slug}', ProductDetailPage::class);

Route::middleware('guest')->group(function () {
    Route::get('/login', LoginPage::class)->name('login');
    Route::get('/register', RegisterPage::class);
    Route::get('/reset-password/{token}', ResetPasswordPage::class)->name('password.reset');
    Route::get('/forgot', ForgotPage::class)->name('password.request');
});

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

    // Nowpayments Routes
    Route::post('/create-invoice/nowpayments/{order}', [PaymentController::class, 'createCryptoPayment'])->name('create.invoice.nowpay');
    Route::get('/payment-status/{orderId}', [PaymentController::class, 'getPaymentStatusByOrder'])->name('payment.status');
    Route::get('/payments', [PaymentController::class, 'listPayments'])->name('payments');
    Route::get('/invoice/{order}', [PaymentController::class, 'showInvoice'])->name('invoice');
    Route::get('/currencies', [PaymentController::class, 'getCurrencies'])->name('currencies');
    Route::post('/create-invoice/stripe/{order}', [PaymentMethodController::class, 'createInvoice'])->name('create.invoice.stripe');
    Route::get('/partial/{order}', [PaymentController::class, 'orderPartial'])->name('order.partial');

    // Wallet Routes
    Route::prefix('wallet')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('wallet.index');
        Route::get('/{currency}', [WalletController::class, 'show'])->name('wallet.show');
        // Top-up Routes
        Route::get('/{currency}/top-up', [WalletController::class, 'topUpForm'])->name('wallet.topup'); // view form
        Route::post('/{currency}/top-up', [WalletController::class, 'topUp'])->name('wallet.topup.submit'); // submit form

        // Insufficient balance redirect
        Route::get('/{currency}/insufficient', [WalletController::class, 'insufficient'])->name('wallet.insufficient');

        // Transactions
        Route::get('/transactions', [WalletTransactionController::class, 'index'])->name('wallet.transactions.index');
    Route::get('/transactions/{transaction}', [WalletTransactionController::class, 'show'])->name('wallet.transactions.show');
    Route::get('/transactions/{transaction}/download', [WalletTransactionController::class, 'download'])->name('wallet.transactions.download');

    });

    // Horizon Jobs
    Horizon::routeMailNotificationsTo('you@example.com');
        Horizon::routeSlackNotificationsTo('your-slack-webhook');
        Horizon::auth(function ($request) {
            return true; // 🔒 you can secure with Gate, e.g., auth()->user()->isAdmin()
        });


    // Payment Methods Webhook
    Route::post('/webhook/stripe', [StripeWebhookController::class, 'handle'])->name('webhook.stripe');
    Route::post('/webhook/nowpayments', [NowPaymentsWebhookController::class, 'handle'])->name('webhook.nowpay');

    Route::get('/account/orders/{order}/invoice', function (Order $order) {
        $invoice = $order->invoice;
        if (!$invoice) {
            abort(404, 'Invoice not found.');
        }
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'order' => $order,
            'customer' => $order->customer,
        ]);
        return $pdf->download('Invoice-' . $invoice->id . '.pdf');
    })->name('customer.order.invoice.download');

    Route::post('/webhook/btc', [DepositWebhookController::class, 'handleBtc']);
    Route::post('/webhook/xmr', [DepositWebhookController::class, 'handleXmr']);
    Route::post('/webhook/sol', [DepositWebhookController::class, 'handleSol']);
});

Route::middleware(['redirect.customer', RedirectIfCustomer::class])->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('/admin', function () {
            // Admin route logic here
        });

        Route::get('/admin/{any}', function ($any) {
            // Admin route logic here
        })->where('any', '.*');
    });
});
