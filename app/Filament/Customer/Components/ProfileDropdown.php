<?php

namespace App\Filament\Customer\Components;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;


class ProfileDropdown extends Widget
{
    protected static string $view = 'filament.components.profile-dropdown';

    public function getActiveClientsCount(): int
    {
        return Auth::user()->clients()->where('enable', true)->count();
    }

    public function getWalletBalance(): float
    {
        return Auth::user()->getWallet()->balance;
    }

    public function getOrderCounts(): array
    {
        return [
            'new'        => Order::where('customer_id', Auth::id())->where('order_status', 'new')->count(),
            'processing' => Order::where('customer_id', Auth::id())->where('order_status', 'processing')->count(),
            'failed'     => Order::where('customer_id', Auth::id())->where('order_status', 'failed')->count(),
        ];
    }
}
