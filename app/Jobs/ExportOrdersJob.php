<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\Writer;
use SplTempFileObject;

class ExportOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ?int $userId = null, public array $filters = []) {}

    public function handle(): void
    {
        $query = Order::query();
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        if (!empty($this->filters['from'])) {
            $query->whereDate('created_at', '>=', $this->filters['from']);
        }
        if (!empty($this->filters['to'])) {
            $query->whereDate('created_at', '<=', $this->filters['to']);
        }

        $orders = $query->with(['customer:id,name,email'])->orderBy('id')->get();

        $csv = Writer::createFromFileObject(new SplTempFileObject());
        $csv->insertOne(['ID','Customer','Email','Amount','Currency','Status','Payment Status','Created']);
        foreach ($orders as $o) {
            $csv->insertOne([
                $o->id,
                $o->customer->name ?? '',
                $o->customer->email ?? '',
                $o->amount,
                $o->currency,
                $o->status,
                $o->payment_status,
                $o->created_at,
            ]);
        }

        $filename = 'exports/orders/orders_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.csv';
        Storage::disk('local')->put($filename, $csv->toString());

        if ($this->userId) {
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new \App\Notifications\ExportReadyNotification($filename));
            }
        }
    }
}
