<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailed extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $orderId;
    public $amount;
    public $reason;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, int $orderId, float $amount, string $reason = 'Payment processing failed')
    {
        $this->user = $user;
        $this->orderId = $orderId;
        $this->amount = $amount;
        $this->reason = $reason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "❌ Payment Failed - Order #{$this->orderId}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.payment.failed',
            with: [
                'user' => $this->user,
                'orderId' => $this->orderId,
                'amount' => $this->amount,
                'reason' => $this->reason,
                'retryPaymentUrl' => route('payment.retry', ['order' => $this->orderId], default: '#'),
                'supportUrl' => route('support.contact', default: '#'),
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
