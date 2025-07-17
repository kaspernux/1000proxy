<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceActivated extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $serverDetails;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, array $serverDetails = [])
    {
        $this->order = $order;
        $this->serverDetails = $serverDetails;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🚀 Your 1000 PROXIES service is now active!",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.service.activated',
            with: [
                'order' => $this->order,
                'serverDetails' => $this->serverDetails,
                'downloadConfigUrl' => route('my-orders.download-config', $this->order->id, default: '#'),
                'supportUrl' => route('support.contact', default: '#'),
                'docsUrl' => route('docs', default: '#'),
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
