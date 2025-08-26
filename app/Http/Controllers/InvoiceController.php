<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function download(Request $request, Order $order)
    {
        // Authorize: ensure the authenticated customer owns this order
        $customer = auth('customer')->user();
        abort_unless($customer && (int) $order->customer_id === (int) $customer->id, 403);

        // Ensure invoice exists and eager-load needed relations
        $order->ensureInvoice();
        $order->loadMissing([
            'customer',
            'invoice',
            'paymentMethod',
            // OrderItem doesn't have a direct `server` relation; it references Server via ServerPlan
            'items.serverPlan.server',
        ]);

        $invoice = $order->invoice;

        // Render PDF using existing invoice Blade (expects $invoice, $customer, $order)
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'customer' => $order->customer,
            'order' => $order,
        ])->setPaper('a4');

        $filename = sprintf('invoice-%s.pdf', $order->order_number ?: ($invoice?->iid ?: $order->id));
        return $pdf->download($filename);
    }
}