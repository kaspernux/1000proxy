<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\HomePage;
use App\Livewire\CategoriesPage;
use App\Livewire\ProductsPage;
use App\Livewire\CartPage;
use App\Livewire\ProductDetailPage;
use App\Livewire\CheckoutPage;
use App\Livewire\MyOrdersPage;
use App\Livewire\MyOrderDetailPage;

use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\RegisterPage;
use App\Livewire\Auth\ResetPasswordPage;
use App\Livewire\Auth\ForgotPage;

use App\Livewire\SuccessPage;
use App\Livewire\CancelPage;

Route::get('/', HomePage::class);
Route::get('/categories', CategoriesPage::class);
Route::get('/servers', ProductsPage::class);
Route::get('/cart', CartPage::class);
Route::get('/servers/{slug}', ProductDetailPage::class);

Route::middleware('guest')->group(function(){
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
    Route::get('/my-orders/{order}', MyOrderDetailPage::class)->name('my.order.detail');
    Route::get('/success', SuccessPage::class)->name('success');
    Route::get('/cancel', CancelPage::class)->name('cancel');
});