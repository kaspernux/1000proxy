<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Server;
use App\Models\ServerPlan;
use App\Jobs\ProcessXuiOrder;
use App\Http\Requests\Api\CreateOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Get user orders
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['server', 'orderItems.server'])
            ->where('user_id', $request->user()->id);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Sort by date
        $query->orderBy('created_at', 'desc');

        $orders = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]
        ]);
    }

    /**
     * Get order by ID
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $order = Order::with(['server', 'orderItems.server', 'serverClients'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Create new order
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $server = Server::findOrFail($validated['server_id']);

        if (!$server->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Server is not available'
            ], 400);
        }

        // Calculate price
        $plan = null;
        if (isset($validated['plan_id'])) {
            $plan = ServerPlan::where('server_id', $server->id)
                ->findOrFail($validated['plan_id']);
            $price = $plan->price;
        } else {
            $price = $server->price;
        }

        $quantity = $validated['quantity'] ?? 1;
        $duration = $validated['duration'] ?? 1;
        $totalPrice = $price * $quantity * $duration;

        // Check wallet balance
        if (!$user->wallet || $user->wallet->balance < $totalPrice) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance',
                'data' => [
                    'required_amount' => $totalPrice,
                    'current_balance' => $user->wallet->balance ?? 0
                ]
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'server_id' => $server->id,
                'total_amount' => $totalPrice,
                'currency' => 'USD',
                'status' => 'pending',
                'payment_method' => 'wallet',
                'payment_status' => 'pending',
            ]);

            // Create order item
            $order->orderItems()->create([
                'server_id' => $server->id,
                'server_plan_id' => $plan?->id,
                'quantity' => $quantity,
                'duration' => $duration,
                'price' => $price,
                'total_price' => $totalPrice,
            ]);

            // Deduct from wallet
            $user->wallet->decrement('balance', $totalPrice);

            // Create wallet transaction
            $user->wallet->transactions()->create([
                'type' => 'debit',
                'amount' => $totalPrice,
                'description' => "Order #{$order->id} - {$server->name}",
                'reference' => "order_{$order->id}",
            ]);

            // Update order status
            $order->update([
                'status' => 'paid',
                'payment_status' => 'completed',
                'paid_at' => now(),
            ]);

            // Dispatch job to process order
            ProcessXuiOrder::dispatch($order);

            DB::commit();

            Log::info('Order created successfully', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'server_id' => $server->id,
                'amount' => $totalPrice
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order->load(['server', 'orderItems'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Order creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'server_id' => $server->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Order creation failed'
            ], 500);
        }
    }

    /**
     * Cancel order
     */
    public function cancel(int $id, Request $request): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)
            ->findOrFail($id);

        if (!in_array($order->status, ['pending', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be cancelled'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Refund to wallet if payment was completed
            if ($order->payment_status === 'completed') {
                $order->user->wallet->increment('balance', $order->total_amount);
                
                // Create refund transaction
                $order->user->wallet->transactions()->create([
                    'type' => 'credit',
                    'amount' => $order->total_amount,
                    'description' => "Refund for Order #{$order->id}",
                    'reference' => "refund_{$order->id}",
                ]);
            }

            // Update order status
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data' => $order
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Order cancellation failed', [
                'error' => $e->getMessage(),
                'order_id' => $order->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Order cancellation failed'
            ], 500);
        }
    }

    /**
     * Get order statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $stats = [
            'total_orders' => $user->orders()->count(),
            'completed_orders' => $user->orders()->where('status', 'completed')->count(),
            'pending_orders' => $user->orders()->where('status', 'pending')->count(),
            'cancelled_orders' => $user->orders()->where('status', 'cancelled')->count(),
            'total_spent' => $user->orders()->where('payment_status', 'completed')->sum('total_amount'),
            'active_subscriptions' => $user->serverClients()->where('is_active', true)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get order configuration (for completed orders)
     */
    public function configuration(int $id, Request $request): JsonResponse
    {
        $order = Order::with(['serverClients'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        if ($order->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Order is not completed yet'
            ], 400);
        }

        $configurations = $order->serverClients->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
                'config_link' => $client->config_link,
                'subscription_link' => $client->subscription_link,
                'qr_code' => $client->qr_code,
                'protocol' => $client->protocol,
                'is_active' => $client->is_active,
                'expires_at' => $client->expires_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order->only(['id', 'status', 'created_at']),
                'configurations' => $configurations
            ]
        ]);
    }
}
