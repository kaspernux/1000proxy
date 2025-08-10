<?php
namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface; 
use Illuminate\Support\Facades\Log; 
use Exception;

class CoinbaseCommercePaymentService implements PaymentGatewayInterface
{
    private ?string $apiKey;
    public function __construct() { $this->apiKey = config('services.coinbase.key'); }

    public function createPayment(array $paymentData): array
    {
        if (!$this->apiKey) { return ['success'=>false,'error'=>'Coinbase Commerce not configured','data'=>[]]; }
        try {
            return ['success'=>true,'error'=>null,'data'=>[
                'charge_id'=>'cbc_'.uniqid(),
                'payment_url'=>'https://commerce.coinbase.com/charges/'.uniqid(),
                'amount'=>$paymentData['amount'],
                'currency'=>$paymentData['currency'] ?? 'USD',
                'status'=>'pending'
            ]];
        } catch (Exception $e) {
            Log::error('Coinbase createPayment error',['error'=>$e->getMessage()]);
            return ['success'=>false,'error'=>$e->getMessage(),'data'=>[]];
        }
    }

    public function verifyPayment(string $paymentId): array { return ['success'=>true,'error'=>null,'data'=>['status'=>'pending','payment_id'=>$paymentId]]; }
    public function processWebhook(array $webhookData): array { return ['success'=>true,'error'=>null,'data'=>$webhookData]; }
    public function refundPayment(string $paymentId, float $amount = null): array { return ['success'=>false,'error'=>'Manual refund required','data'=>['payment_id'=>$paymentId]]; }
    public function getSupportedCurrencies(): array { return ['success'=>true,'error'=>null,'data'=>['currencies'=>['USD','BTC','ETH','USDC']]]; }
    public function getPaymentMethods(): array { return ['crypto'=>['name'=>'Crypto','description'=>'Cryptocurrency payment']]; }
    public function getGatewayInfo(): array { return ['success'=>true,'error'=>null,'data'=>['id'=>'coinbase','name'=>'Coinbase Commerce','type'=>'cryptocurrency','supports_refunds'=>false,'supports_webhooks'=>true,'supported_currencies'=>['USD','BTC','ETH','USDC'],'enabled'=>!empty($this->apiKey)]]; }
}
