<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class PaymentMethodController extends Controller
{
    protected $ipn_secret;
    protected $api_nowPay;

    public function __construct()
    {
        $this->ipn_secret = env('NOWPAYMENTS_IPN_SECRET');
        $this->api_nowPay = env('NOWPAYMENTS_API_KEY');
    }


    public function getAvailableCurrencies()
    {
        // Set the API endpoint
        $url = 'https://api.nowpayments.io/v1/currencies';

        // Initiate cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key:'.env('NOWPAYMENTS_API_KEY')
        ]);

        // Execute cURL request and get the response
        $response = curl_exec($ch);

        // Check for cURL errors
        if(curl_errno($ch)){
            $error_msg = curl_error($ch);
        }

        // Close cURL session
        curl_close($ch);

        // If there was an error, return it
        if (isset($error_msg)){
            return response()->json(['error' => $error_msg], 500);
        }

        // Decode the JSON response
        $currencies = json_decode($response, true);

        // Return the currencies as JSON response
        return response()->json($currencies);
    }

    public function createInvoice(Request $request)
    {
        // Set the API endpoint
        $url = 'https://api.nowpayments.io/v1/invoice';

        // Get data from the request
        $data = [
            'price_amount' => $request->input('price_amount'),
            'price_currency' => $request->input('price_currency'),
            'order_id' => $request->input('order_id'),
            'order_description' =>$request->input('order_description'),
            'ipn_callback_url' => $request->input('ipn_callback_url'),
            'success_url' => $request->input('success_url'),
            'cancel_url' => $request->input('cancel_url'),
            'partially_paid_url' => $request->input('partially_paid_url'),
            'is_fixed_rate' => $request->input('is_fixed_rate'),
            'is_fee_paid_by_user' => $request->input('is_fee_paid_by_user'),
        ];

        // Initiate cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key:'.env('NOWPAYMENTS_API_KEY'),
            'Content-Type: application/json'
        ]);

        // Execute cURL request and get the response
        $response = curl_exec($ch);

        // Check for cURL errors
        if(curl_errno($ch)){
            $error_msg = curl_error($ch);
        }

        // Close cURL session
        curl_close($ch);

        // If there was an error, return it
        if (isset($error_msg)){
            return response()->json(['error' => $error_msg], 500);
        }

        // Decode the JSON response
        $invoiceData = json_decode($response, true);

        // Check if the necessary keys exist in the response
        if (!isset($invoiceData['id'])) {
            return response()->json(['error' => 'Invoice ID not found in response'], 500);
        }

        // Log the raw response for debugging
        Log::info('NowPayments response: ' . $response);

        // Store the invoice in the database
        $invoice = new Invoice();
        $invoice->order_id = $invoiceData['id'] ?? null;
        $invoice->order_description = $invoiceData['order_description'] ?? '';
        $invoice->price_amount = $invoiceData['price_amount'] ?? 0;
        $invoice->price_currency = $invoiceData['price_currency'] ?? '';
        $invoice->pay_currency = $invoiceData['pay_currency'] ?? '';
        $invoice->ipn_callback_url = $invoiceData['ipn_callback_url'] ?? '';
        $invoice->invoice_url = $invoiceData['invoice_url'] ?? '';
        $invoice->success_url = $invoiceData['success_url'] ?? '';
        $invoice->cancel_url = $invoiceData['cancel_url'] ?? '';
        $invoice->save();

        // Return the invoice data as JSON response
        return response()->json($invoiceData);
    }

    public function getPaymentStatus(Order $order)
    {
        // Set the API endpoint
        $url = "https://api.nowpayments.io/v1/payment/{$order->payment_id}";

        // Initiate cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . env('NOWPAYMENTS_API_KEY'),
        ]);

        // Execute cURL request and get the response
        $response = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }

        // Close cURL session
        curl_close($ch);

        // If there was an error, log and return it
        if (isset($error_msg)) {
            Log::error('cURL error: ' . $error_msg);
            return response()->json(['error' => $error_msg], 500);
        }

        // Decode the JSON response
        $paymentStatus = json_decode($response, true);

        // Log the payment status
        Log::info('Payment status:', $paymentStatus);

        // Determine which view to return based on the payment status
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

    public function orderSuccess(Order $order)
    {
        // Handle order success logic here
        Log::info('Order successful:', $order->toArray());

        // Update order status
        $order->status = 'successful';
        $order->save();

        // Additional logic for successful orders can be added here

        return view('order.success', compact('order'));
    }

    public function orderCancel(Order $order)
    {
        // Handle order cancel logic here
        Log::info('Order canceled:', $order->toArray());

        // Update order status
        $order->status = 'canceled';
        $order->save();

        // Additional logic for canceled orders can be added here

        return view('order.cancel', compact('order'));
    }

    public function orderPartial(Order $order)
    {
        // Handle order partial payment logic here
        Log::info('Order partially paid:', $order->toArray());

        // Update order status
        $order->status = 'partially_paid';
        $order->save();

        // Additional logic for partially paid orders can be added here

        return view('order.partial', compact('order'));
    }

    public function updateMerchantEstimate($payment_id)
    {
        // Set the API endpoint
        $url = "https://api.nowpayments.io/v1/payment/{$payment_id}/update-merchant-estimate";

        // Initiate cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . env('NOWPAYMENTS_API_KEY'),
            'Content-Type: application/json',
        ]);

        // Execute cURL request and get the response
        $response = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }

        // Close cURL session
        curl_close($ch);

        // If there was an error, log and return it
        if (isset($error_msg)) {
            Log::error('cURL error: ' . $error_msg);
            return response()->json(['error' => $error_msg], 500);
        }

        // Decode the JSON response
        $estimateResponse = json_decode($response, true);

        // Log the estimate response
        Log::info('Estimate response:', $estimateResponse);

        // Return the estimate response
        return response()->json($estimateResponse);
    }

    public function getEstimatedPrice(Request $request)
    {
        // Get request parameters
        $amount = $request->input('amount');
        $currency_from = $request->input('currency_from');
        $currency_to = $request->input('currency_to');

        // Set the API endpoint
        $url = "https://api.nowpayments.io/v1/estimate?amount={$amount}&currency_from={$currency_from}&currency_to={$currency_to}";

        // Initiate cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . env('NOWPAYMENTS_API_KEY'),
        ]);

        // Execute cURL request and get the response
        $response = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }

        // Close cURL session
        curl_close($ch);

        // If there was an error, log and return it
        if (isset($error_msg)) {
            Log::error('cURL error: ' . $error_msg);
            return response()->json(['error' => $error_msg], 500);
        }

        // Decode the JSON response
        $estimatePrice = json_decode($response, true);

        // Log the estimated price
        Log::info('Estimated price:', $estimatePrice);

        // Return the estimated price response
        return response()->json($estimatePrice);
    }


    public function listPayments(Request $request)
    {
        // Set the API endpoint
        $url = 'https://api.nowpayments.io/v1/payment/';

        // Prepare query parameters
        $queryParams = http_build_query([
            'limit' => $request->input('limit', 10),
            'page' => $request->input('page', 0),
            'sortBy' => $request->input('sortBy', 'created_at'),
            'orderBy' => $request->input('orderBy', 'asc'),
            'dateFrom' => $request->input('dateFrom'),
            'dateTo' => $request->input('dateTo'),
            'invoiceId' => $request->input('invoiceId'),
        ]);

        // Append query parameters to the URL
        $url .= '?' . $queryParams;

        // Initiate cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . env('NOWPAYMENTS_API_KEY'),
        ]);

        // Execute cURL request and get the response
        $response = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }

        // Close cURL session
        curl_close($ch);

        // If there was an error, return it
        if (isset($error_msg)) {
            return response()->json(['error' => $error_msg], 500);
        }

        // Decode the JSON response
        $payments = json_decode($response, true);

        // Return the payments as JSON response
        return response()->json($payments);
    }

    public function handleWebhook(Request $request)
    {
        // Log the incoming request for debugging
        Log::info('Webhook received:', $request->all());

        // Check if the x-nowpayments-sig header is present
        if (!$request->hasHeader('x-nowpayments-sig')) {
            Log::error('No HMAC signature sent.');
            return response()->json(['error' => 'No HMAC signature sent.'], 400);
        }

        $recived_hmac = $request->header('x-nowpayments-sig');
        $request_data = $request->all();

        // Sort the request data by keys and convert it to a JSON string
        $this->tksort($request_data);
        $sorted_request_json = json_encode($request_data, JSON_UNESCAPED_SLASHES);

        // Generate HMAC signature using the IPN secret key
        $hmac = hash_hmac("sha512", $sorted_request_json, trim($this->ipn_secret));

        // Verify the HMAC signature
        if ($hmac !== $recived_hmac) {
            Log::error('HMAC signature does not match.');
            return response()->json(['error' => 'HMAC signature does not match.'], 400);
        }

        // Process the webhook request data
        $this->processWebhookData($request_data);

        return response()->json(['status' => 'success']);
    }

    private function tksort(&$array)
    {
        ksort($array);
        foreach (array_keys($array) as $k) {
            if (is_array($array[$k])) {
                $this->tksort($array[$k]);
            }
        }
    }

    private function processWebhookData($data)
    {
        // Example: Update the order status based on the webhook data
        $order_id = $data['order_id'] ?? null;
        $status = $data['payment_status'] ?? null;

        if ($order_id && $status) {
            $order = Order::find($order_id);
            if ($order) {
                switch ($status) {
                    case 'finished':
                        $order->payment_status = 'completed';
                        $order->order_status = 'processed';
                        break;
                    case 'failed':
                        $order->payment_status = 'failed';
                        $order->order_status = 'canceled';
                        break;
                    case 'partially_paid':
                        $order->payment_status = 'partially_paid';
                        $order->order_status = 'pending';
                        break;
                    default:
                        Log::info('Unhandled payment status: ' . $status);
                        break;
                }
                $order->save();
                Log::info('Order updated:', $order->toArray());
            } else {
                Log::error('Order not found: ' . $order_id);
            }
        } else {
            Log::error('Invalid webhook data: ', $data);
        }
    }


}