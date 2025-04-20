<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\XUIService;
use App\Models\ServerInbound;
use App\Models\ServerClient;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ProcessXuiOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle(): void
    {
        Log::info("🚀 Starting XUI processing for Order #{$this->order->id}");

        foreach ($this->order->items as $item) {
            $plan = $item->serverPlan;
            $xuiService = new XUIService($plan->server_id);
            $inbound_id = $xuiService->getDefaultInboundId();

            $clientData = $xuiService->addInboundAccount(
                $plan->server_id,
                $xuiService->generateUID(),
                $inbound_id,
                now()->addDays($plan->days)->timestamp * 1000,
                "{$plan->name} - Client ID {$this->order->customer_id}",
                $plan->volume,
                1,
                $plan->id
            );

            $localInbound = ServerInbound::updateOrCreate(
                ['server_id' => $plan->server_id, 'port' => $clientData['port']],
                [
                    'protocol' => $clientData['protocol'] ?? 'vless',
                    'remark' => $clientData['remark'] ?? '',
                    'enable' => $clientData['enable'] ?? true,
                    'settings' => $clientData['settings'] ?? [],
                    'streamSettings' => $clientData['streamSettings'] ?? [],
                    'sniffing' => $clientData['sniffing'] ?? [],
                ]
            );

            ServerClient::fromRemoteClient(
                $clientData,
                $localInbound->id,
                $clientData['link']
            )->update(['plan_id' => $plan->id]);

            Log::info("✅ ServerClient created for Order #{$this->order->id}");
        }

        $this->order->markAsCompleted();
        Log::info("✅ Order #{$this->order->id} completed.");
    }


    public function failed(\Throwable $exception): void
    {
        Log::error("\u{26D4} ProcessXuiOrder failed", [
            'order_id' => $this->order->id,
            'exception' => $exception->getMessage(),
        ]);
    }

    // At the bottom of the ProcessXuiOrder class (above the last closing bracket)
    public static function dispatchWithDependencies(Order $order): void
    {
        // Eager load items + nested serverPlan to avoid nulls in the job
        $order->loadMissing('items.serverPlan');

        // Dispatch job
        dispatch(new self($order));
    }

}

