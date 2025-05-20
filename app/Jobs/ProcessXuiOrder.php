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
            $plan       = $item->serverPlan;
            $xuiService = new XUIService($plan->server_id);
            $inbound_id = $xuiService->getDefaultInboundId();

            // 1) Create the remote client
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

            // 2) Re-fetch the inbound so we can grab its port & settings
            $remoteInbound = collect($xuiService->getInbounds($plan->server_id))
                ->firstWhere('id', $inbound_id);

            if (! $remoteInbound) {
                throw new \Exception("Remote inbound #{$inbound_id} not found after client creation.");
            }

            // 3) Upsert our local inbound record
            $localInbound = ServerInbound::updateOrCreate(
                [
                    'server_id' => $plan->server_id,
                    'port'      => $remoteInbound->port,
                ],
                [
                    'protocol'       => $remoteInbound->protocol       ?? 'vless',
                    'remark'         => $remoteInbound->remark         ?? '',
                    'enable'         => $remoteInbound->enable         ?? true,
                    'settings'       => $remoteInbound->settings       ?? [],
                    'streamSettings' => $remoteInbound->streamSettings ?? [],
                    'sniffing'       => $remoteInbound->sniffing       ?? [],
                    'up'             => $remoteInbound->up             ?? 0,
                    'down'           => $remoteInbound->down           ?? 0,
                    'total'          => $remoteInbound->total          ?? 0,
                    'expiryTime'     => isset($remoteInbound->expiryTime)
                                        ? now()->createFromTimestampMs($remoteInbound->expiryTime)
                                        : null,
                ]
            );

            // 4) Create the ServerClient from the remote client data
            ServerClient::fromRemoteClient(
                $clientData,
                $localInbound->id,
                $clientData['link'] ?? null
            )->update(['plan_id' => $plan->id]);

            Log::info("✅ ServerClient created for Order #{$this->order->id}");
        }

        // 5) Finally mark the order complete
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

