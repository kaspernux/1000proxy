<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $subject;
    public $messageContent;
    public $type;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $subject, string $messageContent, string $type = 'info')
    {
        $this->user = $user;
        $this->subject = $subject;
        $this->messageContent = $messageContent;
        $this->type = $type;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "📢 {$this->subject} - 1000 PROXIES",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admin.notification',
            with: [
                'user' => $this->user,
                'subject' => $this->subject,
                'messageContent' => $this->messageContent,
                'type' => $this->type,
                'dashboardUrl' => route('dashboard', default: '#'),
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
