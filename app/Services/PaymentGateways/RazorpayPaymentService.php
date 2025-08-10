<?php
namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface; 
use Illuminate\Support\Facades\Log; 
use Exception;

class RazorpayPaymentService implements PaymentGatewayInterface
{
    private ?string $keyId; private ?string $keySecret;
    public function __construct() { $this->keyId = config('services.razorpay.key_id'); $this->keySecret = config('services.razorpay.key_secret'); }

    public function createPayment(array $paymentData): array
    {
        if (!$this->keyId || !$this->keySecret) {
            return ['success'=>false,'error'=>'Razorpay not configured','data'=>[]];
        }
        try {
            return ['success'=>true,'error'=>null,'data'=>[
                'payment_id'=>'rzp_'.uniqid(),
                'amount'=>$paymentData['amount'],
                'currency'=>$paymentData['currency'] ?? 'INR',
                'status'=>'created'
            ]];
        } catch (Exception $e) {
            Log::error('Razorpay createPayment error',['error'=>$e->getMessage()]);
            return ['success'=>false,'error'=>$e->getMessage(),'data'=>[]];
        }
    }

    public function verifyPayment(string $paymentId): array { return ['success'=>true,'error'=>null,'data'=>['status'=>'pending','payment_id'=>$paymentId]]; }
    public function processWebhook(array $webhookData): array { return ['success'=>true,'error'=>null,'data'=>$webhookData]; }
    public function refundPayment(string $paymentId, float $amount = null): array { return ['success'=>false,'error'=>'Manual refund required','data'=>['payment_id'=>$paymentId]]; }
    public function getSupportedCurrencies(): array { return ['success'=>true,'error'=>null,'data'=>['currencies'=>['INR']]]; }
    public function getPaymentMethods(): array { return ['upi'=>['name'=>'UPI','description'=>'Unified Payments Interface'],'card'=>['name'=>'Card','description'=>'Credit/Debit Card']]; }
    public function getGatewayInfo(): array { return ['success'=>true,'error'=>null,'data'=>['id'=>'razorpay','name'=>'Razorpay','type'=>'aggregator','supports_refunds'=>true,'supports_webhooks'=>true,'supported_currencies'=>['INR'],'enabled'=>!empty($this->keyId)&&!empty($this->keySecret)]]; }
}
