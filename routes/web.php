<?php

use App\Models\Invoice;
use App\Livewire\CartPage;
use App\Livewire\HomePage;
use App\Livewire\CancelPage;
use App\Livewire\SuccessPage;
use App\Livewire\CheckoutPage;
use App\Livewire\MyOrdersPage;
use App\Livewire\ProductsPage;
use App\Livewire\Auth\LoginPage;
use App\Livewire\CategoriesPage;
use App\Livewire\Auth\ForgotPage;
use App\Livewire\Auth\RegisterPage;
use App\Livewire\MyOrderDetailPage;
use App\Livewire\ProductDetailPage;
use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\ResetPasswordPage;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Middleware\RedirectIfCustomer;
use App\Http\Controllers\PaymentMethodController;

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

    // PaymentController routes for Nowpayments
    Route::post('/create-invoice/nowpayments/{order}', [PaymentController::class, 'createCryptoPayment'])->name('create.invoice.nowpay');
    Route::get('/payment-status/{orderId}', [PaymentController::class, 'getPaymentStatusByOrder'])->name('payment.status');
    Route::get('/payments', [PaymentController::class, 'listPayments'])->name('payments');
    Route::get('/invoice/{order}', [PaymentController::class, 'showInvoice'])->name('invoice');

    // Additional Nowpayments routes in PaymentController
    Route::get('/currencies', [PaymentController::class, 'getCurrencies'])->name('currencies');
    Route::post('/create-invoice/stripe/{order}', [PaymentMethodController::class, 'createInvoice'])->name('create.invoice.stripe');
    Route::post('/webhook/nowpayments', [PaymentController::class, 'handleWebhookNowPayments'])->name('webhook.nowpay');
    Route::get('/partial/{order}', [PaymentController::class, 'orderPartial'])->name('order.partial');
});

Route::middleware(['redirect.customer'])->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('/admin', function () {
            // Admin route logic here
        });

        Route::get('/admin/{any}', function ($any) {
            // Admin route logic here
        })->where('any', '.*');
    });
})->middleware(RedirectIfCustomer::class);