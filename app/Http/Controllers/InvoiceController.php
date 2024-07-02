<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class InvoiceController extends Controller
{
    public function show(Order $order)
    {
        // Retrieve the invoice URL from the order
        $invoiceUrl = $order->invoice->invoice_url;

        if ($invoiceUrl) {
            return redirect()->away($invoiceUrl);
        }

        return redirect()->route('my.orders')->with('error', 'Invoice URL not found.');
    }
}