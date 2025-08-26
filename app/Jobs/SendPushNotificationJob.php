<?php

namespace App\Jobs;

use App\Models\PushNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public int $pushId) {}

    public function handle(): void
    {
        $push = PushNotification::find($this->pushId);
        if (!$push) { return; }

        try {
            // TODO: integrate with your push gateway provider (FCM/APNs/OneSignal)
            // Placeholder: mark as sent
            $push->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
            Log::info('Push sent', ['id' => $push->id]);
        } catch (\Throwable $e) {
            $push->markAsFailed($e->getMessage());
            Log::error('Push failed', ['id' => $push->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
