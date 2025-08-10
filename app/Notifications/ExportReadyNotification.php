<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Storage;

class ExportReadyNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    public function __construct(public string $path) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function broadcastOn(): array
    {
        return [new \Illuminate\Broadcasting\Channel('exports')];
    }

    public function broadcastAs(): string
    {
        return 'export.ready';
    }

    public function toBroadcast(object $notifiable): array
    {
        return [
            'path' => $this->path,
            'download_url' => route('admin.download-export', ['path' => base64_encode($this->path)]),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('admin.download-export', ['path' => base64_encode($this->path)]);
        return (new MailMessage)
            ->subject('Your export is ready')
            ->line('An export/report you requested has finished processing.')
            ->action('Download', $url)
            ->line('File: ' . $this->path);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'path' => $this->path,
            'size' => Storage::disk('local')->size($this->path) ?? null,
        ];
    }
}
