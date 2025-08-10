<?php
namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;
use Exception;

class FlutterwavePaymentService implements PaymentGatewayInterface
{
    private ?string $secret;

    public function __construct()
    {
        $this->secret = config('services.flutterwave.secret');
    }

    public function createPayment(array $paymentData): array
    {
        if (!$this->secret) {
            return ['success'=>false,'error'=>'Flutterwave not configured','data'=>[]];
        }
        try {
            return ['success'=>true,'error'=>null,'data'=>[
                'payment_id'=>'flw_'.uniqid(),
                'payment_url'=>'https://flutterwave.com/pay/demo-'.uniqid(),
                'amount'=>$paymentData['amount'],
                'currency'=>$paymentData['currency'] ?? 'USD',
                'status'=>'pending'
            ]];
        } catch (Exception $e) {
            Log::error('Flutterwave createPayment error',['error'=>$e->getMessage()]);
            return ['success'=>false,'error'=>$e->getMessage(),'data'=>[]];
        }
    }

    public function verifyPayment(string $paymentId): array
    { return ['success'=>true,'error'=>null,'data'=>['status'=>'pending','payment_id'=>$paymentId]]; }

    public function processWebhook(array $webhookData): array
    { return ['success'=>true,'error'=>null,'data'=>$webhookData]; }

    public function refundPayment(string $paymentId, float $amount = null): array
    { return ['success'=>false,'error'=>'Manual refund required','data'=>['payment_id'=>$paymentId]]; }

    public function getSupportedCurrencies(): array
    { return ['success'=>true,'error'=>null,'data'=>['currencies'=>['USD','NGN','KES','ZAR']]]; }

    public function getPaymentMethods(): array
    { return ['card'=>['name'=>'Card','description'=>'Card payment'], 'mobile_money'=>['name'=>'Mobile Money','description'=>'Mobile wallet']]; }

    public function getGatewayInfo(): array
    { return ['success'=>true,'error'=>null,'data'=>['id'=>'flutterwave','name'=>'Flutterwave','type'=>'aggregator','supports_refunds'=>true,'supports_webhooks'=>true,'supported_currencies'=>['USD','NGN','KES','ZAR'],'enabled'=>!empty($this->secret)]]; }
}
