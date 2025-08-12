<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use App\Jobs\ProcessTelegramMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Services\QrCodeService;

class TelegramBotController extends Controller
{
    protected $telegramBotService;

    public function __construct(TelegramBotService $telegramBotService)
    {
        $this->telegramBotService = $telegramBotService;
        // Feature flag to toggle enhanced behavior without creating V2 service
        $enhanced = (bool) (config('services.telegram.enhanced') ?? env('TELEGRAM_BOT_ENHANCED', true));
        if (method_exists($this->telegramBotService, 'setEnhanced')) {
            $this->telegramBotService->setEnhanced($enhanced);
        }
    }

    /**
     * Handle webhook requests from Telegram
     */
    public function webhook(Request $request): Response
    {
        try {
            $update = $request->all();

            Log::info('Telegram webhook received', ['update' => $update]);

            // Verify webhook authenticity (optional but recommended)
            if (!$this->verifyWebhook($request)) {
                Log::warning('Invalid Telegram webhook request');
                return response('Unauthorized', 401);
            }

            // Process the update asynchronously via queue
            ProcessTelegramMessage::dispatch($update)
                ->onQueue('telegram')
                ->delay(now()->addSeconds(1)); // Small delay to ensure webhook responds quickly

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Telegram webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('Internal Server Error', 500);
        }
    }

    /**
     * Set webhook URL
     */
    public function setWebhook(): JsonResponse
    {
        try {
            $success = $this->telegramBotService->setWebhook();

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook set successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to set webhook'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Set webhook error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo(): JsonResponse
    {
        try {
            $info = $this->telegramBotService->getWebhookInfo();

            return response()->json([
                'success' => true,
                'data' => $info
            ]);
        } catch (\Exception $e) {
            Log::error('Get webhook info error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Admin: set bot branding (name/desc/short)
     */
    public function setBranding(Request $request): JsonResponse
    {
        $this->authorizeStaff();
        $ok = $this->telegramBotService->setBranding(
            $request->input('name'),
            $request->input('short_description'),
            $request->input('description'),
        );
        return response()->json(['success' => $ok]);
    }

    /**
     * Admin: set chat menu button
     */
    public function setMenu(Request $request): JsonResponse
    {
        $this->authorizeStaff();
        $ok = $this->telegramBotService->setMenuButton(
            $request->input('type', 'commands'),
            $request->input('text'),
            $request->input('url')
        );
        return response()->json(['success' => $ok]);
    }

    /**
     * Remove webhook
     */
    public function removeWebhook(): JsonResponse
    {
        try {
            $success = $this->telegramBotService->removeWebhook();

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook removed successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to remove webhook'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Remove webhook error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Test bot functionality
     */
    public function testBot(): JsonResponse
    {
        try {
            $result = $this->telegramBotService->testBot();

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Test bot error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Send test message to admin (for testing purposes)
     */
    public function sendTestMessage(Request $request): JsonResponse
    {
        try {
            $chatId = $request->input('chat_id');
            $message = $request->input('message', 'Test message from 1000proxy bot');

            if (!$chatId) {
                return response()->json([
                    'success' => false,
                    'message' => 'chat_id is required'
                ], 400);
            }

            $this->telegramBotService->sendDirectMessage($chatId, $message);

            return response()->json([
                'success' => true,
                'message' => 'Test message sent successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Send test message error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bot statistics
     */
    public function getBotStats(): JsonResponse
    {
        try {
            $stats = [
                'total_linked_users' => Customer::whereNotNull('telegram_chat_id')->count(),
                'total_users' => Customer::count(),
                'recent_interactions' => $this->getRecentInteractions(),
                'bot_info' => $this->telegramBotService->testBot()['bot_info'] ?? null,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Get bot stats error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get bot statistics'
            ], 500);
        }
    }

    /**
     * Generate a one-time Telegram linking code for a customer
     */
    public function generateLink(Request $request): JsonResponse
    {
        try {
            $customer = Auth::guard('customer')->user();

            // Allow staff to generate for a specific customer
            if (!$customer && Auth::check()) {
                $customerId = (int) $request->input('customer_id');
                if ($customerId) {
                    $customer = Customer::find($customerId);
                }
            }

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer context required to generate link'
                ], 422);
            }

            $code = strtoupper(Str::random(8));
            $cacheKey = "telegram_linking_{$code}";
            Cache::put($cacheKey, $customer->id, now()->addMinutes(10));

            // Generate QR that encodes the code text for easy scanning
            $qrService = app(QrCodeService::class);
            $qrBase64 = $qrService->generateBase64QrCode($code, 220, [
                'colorScheme' => 'primary',
                'style' => 'dot'
            ]);

            return response()->json([
                'success' => true,
                'code' => $code,
                'qr_url' => $qrBase64,
                'expires_at' => now()->addMinutes(10)->toIso8601String()
            ]);
        } catch (\Exception $e) {
            Log::error('Generate link error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate link'
            ], 500);
        }
    }

    /**
     * List customers with Telegram linked
     */
    public function linkedUsers(Request $request): JsonResponse
    {
        try {
            $query = Customer::query();

            if ($request->boolean('only_linked', true)) {
                $query->whereNotNull('telegram_chat_id');
            }

            if ($search = $request->input('q')) {
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('telegram_username', 'like', "%{$search}%");
                });
            }

            $users = $query->orderByDesc('last_login_at')
                ->limit(200)
                ->get(['id', 'name', 'email', 'telegram_username', 'telegram_chat_id', 'last_login_at', 'email_verified_at']);

            return response()->json([
                'success' => true,
                'users' => $users,
            ]);
        } catch (\Exception $e) {
            Log::error('Linked users fetch error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load linked users'
            ], 500);
        }
    }

    /**
     * Basic stats for Telegram linking
     */
    public function linkedUsersStats(): JsonResponse
    {
        try {
            $totalLinked = Customer::whereNotNull('telegram_chat_id')->count();
            $activeToday = Customer::whereNotNull('telegram_chat_id')
                ->where('last_login_at', '>=', now()->subDay())
                ->count();
            $pendingLinks = Customer::whereNull('telegram_chat_id')->count();

            return response()->json([
                'success' => true,
                'stats' => [
                    'totalLinked' => $totalLinked,
                    'activeToday' => $activeToday,
                    'pendingLinks' => $pendingLinks,
                    'averageResponseTime' => 0,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Linked users stats error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load stats'
            ], 500);
        }
    }

    /**
     * Unlink a customer's Telegram
     */
    public function unlinkUser(int $id): JsonResponse
    {
        try {
            $customer = Customer::findOrFail($id);
            $customer->unlinkTelegram();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Unlink user error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlink user'
            ], 500);
        }
    }

    /**
     * Placeholder: list of notifications (no persistence yet)
     */
    public function getNotifications(): JsonResponse
    {
        $this->authorizeStaff();
        $items = \App\Models\TelegramNotification::orderByDesc('created_at')->limit(200)->get();
        return response()->json([
            'success' => true,
            'notifications' => $items
        ]);
    }

    /**
     * Placeholder: list of templates (no persistence yet)
     */
    public function getTemplates(): JsonResponse
    {
        $this->authorizeStaff();
        $items = \App\Models\TelegramTemplate::orderBy('title')->get();
        return response()->json([
            'success' => true,
            'templates' => $items
        ]);
    }

    /**
     * Send a one-off notification (reuses broadcast)
     */
    public function sendNotification(Request $request): JsonResponse
    {
        $this->authorizeStaff();
        $message = trim((string) $request->input('message', ''));
        if ($message === '') {
            return response()->json([
                'success' => false,
                'message' => 'Message is required'
            ], 422);
        }

        // Persist notification record
        $notification = \App\Models\TelegramNotification::create([
            'title' => $request->input('title', ''),
            'message' => $message,
            'recipients' => $request->input('recipients', 'all'),
            'priority' => $request->input('priority', 'normal'),
            'status' => 'pending',
            'scheduled_at' => $request->input('schedule_at') ? now() : null,
            'created_by' => Auth::id(),
        ]);

        // Reuse broadcast logic to all linked customers (basic immediate send)
        $sent = 0; $failed = 0;
        $customers = Customer::whereNotNull('telegram_chat_id')->get();
        foreach ($customers as $c) {
            try {
                $this->telegramBotService->sendDirectMessage($c->telegram_chat_id, $message);
                usleep(100000); // 0.1s
                $sent++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        $notification->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [ 'sent' => $sent, 'failed' => $failed, 'total' => $customers->count() ]
        ]);
    }

    /**
     * Preview notification payload
     */
    public function previewNotification(Request $request): JsonResponse
    {
        $this->authorizeStaff();
        return response()->json([
            'success' => true,
            'preview' => $request->only(['title', 'message', 'recipients', 'priority'])
        ]);
    }

    /**
     * Placeholder delete notification endpoint
     */
    public function deleteNotification(int $id): JsonResponse
    {
        $this->authorizeStaff();
        \App\Models\TelegramNotification::where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Ensure the caller is an authenticated staff/admin user.
     */
    private function authorizeStaff(): void
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['admin', 'support_manager', 'sales_support'])) {
            abort(403, 'Forbidden');
        }
    }

    /**
     * Send broadcast message to all users
     */
    public function broadcastMessage(Request $request): JsonResponse
    {
        try {
            $message = $request->input('message');
            $adminUserId = $request->input('admin_user_id'); // For logging purposes

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message is required'
                ], 400);
            }

            // Get all users with linked Telegram accounts
            $users = \App\Models\User::whereNotNull('telegram_chat_id')->get();
            $sentCount = 0;
            $failedCount = 0;

            foreach ($users as $user) {
                try {
                    $this->telegramBotService->sendDirectMessage(
                        $user->telegram_chat_id,
                        "📢 System Announcement\n\n{$message}\n\n—\n1000proxy Team"
                    );
                    $sentCount++;
                    usleep(100000); // 0.1 second delay to avoid rate limiting
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::warning('Broadcast message failed for user', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Broadcast message sent', [
                'admin_user_id' => $adminUserId,
                'message' => $message,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Broadcast completed',
                'data' => [
                    'sent_count' => $sentCount,
                    'failed_count' => $failedCount,
                    'total_users' => $users->count()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Broadcast message error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send broadcast message'
            ], 500);
        }
    }

    /**
     * Get recent bot interactions from logs
     */
    private function getRecentInteractions(): array
    {
        try {
            // This would typically query a dedicated interactions table
            // For now, we'll return a placeholder
            return [
                'last_24h' => 0,
                'last_7d' => 0,
                'total' => 0
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get recent interactions', ['error' => $e->getMessage()]);
            return [
                'last_24h' => 'N/A',
                'last_7d' => 'N/A',
                'total' => 'N/A'
            ];
        }
    }

    /**
     * Verify webhook authenticity using Telegram's secret token
     */
    private function verifyWebhook(Request $request): bool
    {
        $secretToken = config('services.telegram.secret_token');

        if (!$secretToken) {
            return true; // Skip verification if no secret token is configured
        }

        $telegramToken = (string) ($request->header('X-Telegram-Bot-Api-Secret-Token') ?? '');
        $pathSecret = (string) ($request->route('secret') ?? '');

        // Accept either header-based secret or the optional path secret
        $ok = hash_equals($secretToken, $telegramToken) || (!empty($pathSecret) && hash_equals($secretToken, $pathSecret));

        if (!$ok) {
            \Log::warning('Telegram webhook secret validation failed', [
                'has_header' => !empty($telegramToken),
                'has_path_secret' => !empty($pathSecret),
            ]);
        }
        return $ok;
    }
}
