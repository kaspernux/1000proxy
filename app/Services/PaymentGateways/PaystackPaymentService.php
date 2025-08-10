<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;
use Exception;

class PaystackPaymentService implements PaymentGatewayInterface
{
    private ?string $secret;
    private string $apiBase;

    public function __construct()
    {
        $this->secret = config('services.paystack.secret');
        $this->apiBase = 'https://api.paystack.co';
    }

    public function createPayment(array $paymentData): array
    {
        if (!$this->secret) {
            return ['success'=>false,'error'=>'Paystack not configured','data'=>[]];
        }
        try {
            // Placeholder (implement API call)
            return ['success'=>true,'error'=>null,'data'=>[
                'reference' => 'pst_'.uniqid(),
                'payment_url' => 'https://paystack.com/pay/demo-'.uniqid(),
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'] ?? 'NGN',
                'status' => 'pending'
            ]];
        } catch (Exception $e) {
            Log::error('Paystack createPayment error',['error'=>$e->getMessage()]);
            return ['success'=>false,'error'=>$e->getMessage(),'data'=>[]];
        }
    }

    public function verifyPayment(string $paymentId): array
    {
        return ['success'=>true,'error'=>null,'data'=>['status'=>'pending','payment_id'=>$paymentId]];
    }

    public function processWebhook(array $webhookData): array
    {
        return ['success'=>true,'error'=>null,'data'=>$webhookData];
    }

    public function refundPayment(string $paymentId, float $amount = null): array
    {
        return ['success'=>false,'error'=>'Manual refund required','data'=>['payment_id'=>$paymentId]];
    }

    public function getSupportedCurrencies(): array
    {
        return ['success'=>true,'error'=>null,'data'=>['currencies'=>['NGN']]];
    }

    public function getPaymentMethods(): array
    {
        return ['card'=>['name'=>'Card','description'=>'Paystack card payment']];
    }

    public function getGatewayInfo(): array
    {
        return ['success'=>true,'error'=>null,'data'=>[
            'id'=>'paystack','name'=>'Paystack','type'=>'aggregator','supports_refunds'=>false,'supports_webhooks'=>true,
            'supported_currencies'=>['NGN'],'enabled'=>!empty($this->secret)
        ]];
    }
}
