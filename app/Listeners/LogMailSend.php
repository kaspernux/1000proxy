<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;

class LogMailSend
{
    public function handle(MessageSending $event): void
    {
        // Lightweight visibility: who/subject
        $to = collect($event->message->getTo() ?? [])->map(fn($a) => $a->getAddress())->all();
        $subject = $event->message->getSubject();
        logger()->channel('security')->info('Mail sending', [
            'to' => $to,
            'subject' => $subject,
        ]);
    }
}
