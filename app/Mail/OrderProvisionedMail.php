<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderProvisionedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public array $results = []) {}

    public function build(): self
    {
        return $this->subject('Your proxies are ready (Order #' . $this->order->id . ')')
            ->view('mail.orders.provisioned')
            ->with([
                'order' => $this->order,
                'results' => $this->results,
                'clients' => $this->order->getAllClients(),
            ]);
    }
}
