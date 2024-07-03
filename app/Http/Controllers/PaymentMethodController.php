<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PaymentMethodController extends Controller
{
    protected $ipn_secret;
    protected $api_nowPay;

    public function __construct()
    {
        $this->ipn_secret = env('NOWPAYMENTS_IPN_SECRET');
        $this->api_nowPay = env('NOWPAYMENTS_API_KEY');
    }

    public static function getPaymentMethods()
    {
        return PaymentMethod::all();
    }

    public function createInvoiceNowPayments(Order $order)
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->api_nowPay,
            'Content-Type' => 'application/json',
        ])->post('https://api.nowpayments.io/v1/invoice', [
            'price_amount' => $order->grand_amount,
            'price_currency' => 'usd',
            'order_id' => (string) $order->id,
            'order_description' => $order->notes,
            'ipn_callback_url' => route('webhook.nowpay'),
            'success_url' => route('success', ['order' => $order->id]),
            'cancel_url' => route('cancel', ['order' => $order->id]),
        ]);

        if ($response->successful()) {
            return [
                'status' => 'success',
                'data' => $response->json()
            ];
        } else {
            Log::error('NowPayments response: ' . $response->status() . ' - ' . $response->body());
            return [
                'status' => 'error',
                'message' => 'Failed to create invoice: ' . $response->body()
            ];
        }

        Log::info('NowPayments Request Payload:', [
            'price_amount' => $order->grand_amount,
            'price_currency' => 'usd',
            'order_id' => $order->id,
            'order_description' => $order->notes,
            'ipn_callback_url' => route('webhook.nowpay'),
            'success_url' => route('success', ['order' => $order->id]),
            'cancel_url' => route('cancel', ['order' => $order->id]),
        ]);

    }

    public function handleWebhookNowPayments(Request $request)
    {
        Log::info('Webhook received:', $request->all());
        if (!$request->hasHeader('x-nowpayments-sig')) {
            Log::error('No HMAC signature sent.');
            return response()->json(['error' => 'No HMAC signature sent.'], 400);
        }

        $received_hmac = $request->header('x-nowpayments-sig');
        $request_data = $request->all();
        $this->tksort($request_data);
        $sorted_request_json = json_encode($request_data, JSON_UNESCAPED_SLASHES);
        $calc_hmac = hash_hmac("sha512", $sorted_request_json, $this->ipn_secret);

        if ($calc_hmac !== $received_hmac) {
            Log::error('Invalid HMAC signature.');
            return response()->json(['error' => 'Invalid HMAC signature.'], 400);
        }

        $this->processWebhookData($request_data);

        return response()->json(['message' => 'Webhook processed successfully.'], 200);
    }

    private function tksort(&$array)
    {
        if (!is_array($array)) {
            return false;
        }
        ksort($array);
        foreach ($array as $k => $v) {
            $this->tksort($array[$k]);
        }
    }

    private function processWebhookData($data)
    {
        $order_id = $data['order_id'] ?? null;
        $status = $data['payment_status'] ?? null;

        if ($order_id && $status) {
            $order = Order::find($order_id);
            if ($order) {
                switch ($status) {
                    case 'finished':
                        $order->update(['payment_status' => 'completed', 'order_status' => 'processed']);
                        break;
                    case 'failed':
                        $order->update(['payment_status' => 'failed', 'order_status' => 'canceled']);
                        break;
                    case 'partially_paid':
                        $order->update(['payment_status' => 'partially_paid', 'order_status' => 'pending']);
                        break;
                    default:
                        Log::info('Unhandled payment status: ' . $status);
                        break;
                }
                Log::info('Order updated:', $order->toArray());
            } else {
                Log::error('Order not found: ' . $order_id);
            }
        } else {
            Log::error('Invalid webhook data: ', $data);
        }
    }

    public function getAvailableCurrenciesNowPayments()
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->api_nowPay,
        ])->get('https://api.nowpayments.io/v1/currencies');

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => $response->body()], 500);
    }

    public function getPaymentStatus(Order $order)
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->api_nowPay,
        ])->get("https://api.nowpayments.io/v1/payment/{$order->payment_id}");

        if ($response->successful()) {
            $paymentStatus = $response->json();
            Log::info('Payment status:', $paymentStatus);

            switch ($paymentStatus['payment_status']) {
                case 'finished':
                    return $this->orderSuccess($order);
                case 'failed':
                case 'expired':
                case 'refunded':
                    return $this->orderCancel($order);
                case 'partially_paid':
                    return $this->orderPartial($order);
                default:
                    return response()->json(['status' => $paymentStatus['payment_status']], 200);
            }
        }

        Log::error('cURL error: ' . $response->body());
        return response()->json(['error' => $response->body()], 500);
    }

    public function orderSuccess(Order $order)
    {
        Log::info('Order successful:', $order->toArray());
        $order->update(['status' => 'successful']);
        return view('order.success', compact('order'));
    }

    public function orderCancel(Order $order)
    {
        Log::info('Order canceled:', $order->toArray());
        $order->update(['status' => 'canceled']);
        return view('order.cancel', compact('order'));
    }

    public function orderPartial(Order $order)
    {
        Log::info('Order partially paid:', $order->toArray());
        $order->update(['status' => 'partially_paid']);
        return view('order.partial', compact('order'));
    }

    public function updateMerchantEstimate($payment_id)
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->api_nowPay,
            'Content-Type' => 'application/json',
        ])->post("https://api.nowpayments.io/v1/payment/{$payment_id}/update-merchant-estimate");

        if ($response->successful()) {
            Log::info('Estimate response:', $response->json());
            return response()->json($response->json());
        }

        Log::error('cURL error: ' . $response->body());
        return response()->json(['error' => $response->body()], 500);
    }

    public function getEstimatedPrice(Request $request)
    {
        $amount = $request->input('amount');
        $currency_from = $request->input('currency_from');
        $currency_to = $request->input('currency_to');

        $response = Http::withHeaders([
            'x-api-key' => $this->api_nowPay,
        ])->get("https://api.nowpayments.io/v1/estimate", [
            'amount' => $amount,
            'currency_from' => $currency_from,
            'currency_to' => $currency_to
        ]);

        if ($response->successful()) {
            Log::info('Estimated price:', $response->json());
            return response()->json($response->json());
        }

        Log::error('cURL error: ' . $response->body());
        return response()->json(['error' => $response->body()], 500);
    }

    public function listPayments(Request $request)
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->api_nowPay,
        ])->get('https://api.nowpayments.io/v1/payment', [
            'limit' => $request->input('limit', 10),
            'page' => $request->input('page', 0),
            'sortBy' => $request->input('sortBy', 'created_at'),
            'orderBy' => $request->input('orderBy', 'asc'),
            'dateFrom' => $request->input('dateFrom'),
            'dateTo' => $request->input('dateTo'),
            'invoiceId' => $request->input('invoiceId'),
        ]);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        Log::error('cURL error: ' . $response->body());
        return response()->json(['error' => $response->body()], 500);
    }
}