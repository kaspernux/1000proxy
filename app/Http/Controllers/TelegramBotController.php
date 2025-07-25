<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use App\Jobs\ProcessTelegramMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TelegramBotController extends Controller
{
    protected $telegramBotService;

    public function __construct(TelegramBotService $telegramBotService)
    {
        $this->telegramBotService = $telegramBotService;
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
                'total_linked_users' => \App\Models\User::whereNotNull('telegram_chat_id')->count(),
                'total_users' => \App\Models\User::count(),
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

        $telegramToken = $request->header('X-Telegram-Bot-Api-Secret-Token');

        return hash_equals($secretToken, $telegramToken ?? '');
    }
}
