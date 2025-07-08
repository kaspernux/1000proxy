<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
            
            // Process the update
            $this->telegramBotService->processUpdate($update);
            
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
    public function setWebhook(): Response
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
    public function getWebhookInfo(): Response
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
    public function removeWebhook(): Response
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
    public function testBot(): Response
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
