<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Redirect;
use PrevailExcel\Nowpayments\Facades\Nowpayments;

class PaymentController extends Controller
{
    /**
     * Create a crypto payment
     * @param Order $order
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function createCryptoPayment(Order $order)
    {
        try {
            $data = [
                'price_amount' => $order->grand_amount,
                'price_currency' => 'usd',
                'order_id' => (string) $order->id,
                'order_description' => 'Order #' . $order->id,
                'pay_currency' => 'btc',
                'ipn_callback_url' => 'https://1000proxybot/webhook',
                'success_url' => route('success', ['order' => $order->id]),
                'cancel_url' => route('cancel', ['order' => $order->id]),
                'partially_paid_url' => route('cancel', ['order' => $order->id]),
                'is_fixed_rate' => true,
                'is_fee_paid_by_user' => true,
            ];

            $paymentDetails = Nowpayments::createPayment($data);

            return response()->json($paymentDetails, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => "Error creating payment: " . $e->getMessage()], 400);
        }
    }

    /**
     * Create an invoice
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function createInvoice(Order $order)
    {
        try {
            $data = [
                'price_amount' => $order->grand_amount,
                'price_currency' => 'usd',
                'order_id' => (string) $order->id,
                'order_description' => 'Order #' . $order->id,
                'pay_currency' => 'btc',
                'ipn_callback_url' => 'https://1000proxybot/webhook',
                'success_url' => route('success', ['order' => $order->id]),
                'cancel_url' => route('cancel', ['order' => $order->id]),
                'partially_paid_url' => route('cancel', ['order' => $order->id]),
                'is_fixed_rate' => true,
                'is_fee_paid_by_user' => true,
            ];

            $invoice = Nowpayments::createInvoice($data);
            return response()->json($invoice);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get the status of a payment by Order ID
     * @param string $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentStatusByOrder($order_id)
    {
        try {
            $order = Order::findOrFail($order_id);
            $paymentId = $order->invoice->payment_id; // Assuming you store payment_id in orders table

            $status = Nowpayments::getPaymentStatus($paymentId);
            return response()->json($status);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    /**
     * Get the status of a payment
     * @param string $paymentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentStatus($payment_id)
    {
        try {
            $status = Nowpayments::getPaymentStatus($payment_id);
            return response()->json($status);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get available currencies
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrencies()
    {
        try {
            $currencies = Nowpayments::getCurrencies();
            return response()->json($currencies);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get the minimum payment amount for a specific pair
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMinimumPaymentAmount($fromCurrency, $toCurrency)
    {
        try {
            $minimumAmount = Nowpayments::getMinimumPaymentAmount($fromCurrency, $toCurrency);
            return response()->json($minimumAmount);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get estimate price for an amount in different pairs
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEstimatePrice()
    {
        try {
            $data = [
                'amount' => request()->amount ?? 100,
                'currency_from' => request()->currency_from ?? 'usd',
                'currency_to' => request()->currency_to ?? 'btc',
            ];

            $estimate = Nowpayments::getEstimatePrice($data);
            return response()->json($estimate);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get the list of all transactions
     * @return \Illuminate\Http\JsonResponse
     */
    public function getListOfPayments()
    {
        try {
            $payments = Nowpayments::getListOfPayments();
            return response()->json($payments);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get payment plan details
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPlans()
    {
        try {
            $plans = Nowpayments::getPlans();
            return response()->json($plans);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get subscription details
     * @param string $subscriptionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubscription($subscriptionId)
    {
        try {
            $subscription = Nowpayments::getSubscription($subscriptionId);
            return response()->json($subscription);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Email subscription details
     * @param string $subscriptionId
     * @param string $email
     * @return \Illuminate\Http\JsonResponse
     */
    public function emailSubscription($subscriptionId, $email)
    {
        try {
            $response = Nowpayments::emailSubscription($subscriptionId, $email);
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete subscription
     * @param string $subscriptionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteSubscription($subscriptionId)
    {
        try {
            $response = Nowpayments::deleteSubscription($subscriptionId);
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Handle Nowpayments webhook
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleWebhookNowPayments(Request $request)
    {
        try {
            // Handle Nowpayments webhook logic here
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Display invoice details (NowPayments)
     * @param string $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function showInvoice($order)
    {
        try {
            // Fetch invoice details from NowPayments or your application's database
            $invoice = Nowpayments::getInvoice($order); // Adjust as per NowPayments API

            // If using Eloquent ORM for local invoices, you might do:
            // $invoice = Invoice::where('order_id', $order)->first();

            if (!$invoice) {
                return response()->json(['error' => 'Invoice not found'], 404);
            }

            return response()->json($invoice);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public static function getPaymentMethods()
    {
        return PaymentMethod::all();
    }
}