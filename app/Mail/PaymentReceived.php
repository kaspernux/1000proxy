<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceived extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $paymentMethod;
    public $transactionId;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, string $paymentMethod = 'Unknown', string $transactionId = null)
    {
        $this->order = $order;
        $this->paymentMethod = $paymentMethod;
        $this->transactionId = $transactionId;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "✅ Payment Received - Order #{$this->order->id}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.payment.received',
            with: [
                'order' => $this->order,
                'paymentMethod' => $this->paymentMethod,
                'transactionId' => $this->transactionId,
                'viewOrderUrl' => route('my-orders.show', $this->order->id, default: '#'),
                'downloadConfigUrl' => route('my-orders.download-config', $this->order->id, default: '#'),
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
