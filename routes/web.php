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
Route::get('/servers/{server}', ProductDetailPage::class);

Route::get('/checkout', CheckoutPage::class);
Route::get('/my-orders', MyOrdersPage::class);
Route::get('/my-orders/{order}', MyOrderDetailPage::class);

Route::get('/login', LoginPage::class);
Route::get('/register', RegisterPage::class);
Route::get('/reset-password', ResetPasswordPage::class);
Route::get('/forgot', ForgotPage::class);

Route::get('/success', SuccessPage::class);
Route::get('/cancel', CancelPage::class);