<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Route;

class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    /**
     * Create a new message instance.
     *
     * @param Order $order The order instance to be passed to the Mailable.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the message envelope.
     */
    public function build()
    {
        return $this
            ->subject('Order Placed - 1000 PROXIES')
            ->markdown('mail.orders.placed')
            ->with('url', route('my-orders.show', $this->order));
    }
}