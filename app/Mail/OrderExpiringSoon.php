<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderExpiringSoon extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $daysUntilExpiry;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, int $daysUntilExpiry = 7)
    {
        $this->order = $order;
        $this->daysUntilExpiry = $daysUntilExpiry;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ Your 1000 PROXIES service expires in {$this->daysUntilExpiry} days",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.expiring-soon',
            with: [
                'order' => $this->order,
                'daysUntilExpiry' => $this->daysUntilExpiry,
                'renewUrl' => route('my-orders.renew', $this->order->id, default: '#'),
                'viewOrderUrl' => route('my-orders.show', $this->order->id, default: '#'),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
