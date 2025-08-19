<?php

namespace App\Services;

use App\Mail\AdminNotification;
use App\Mail\OrderExpiringSoon;
use App\Mail\OrderPlaced;
use App\Mail\PaymentFailed;
use App\Mail\PaymentReceived;
use App\Mail\ServiceActivated;
use App\Mail\WelcomeEmail;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

class EnhancedMailService
{
    /**
     * Send welcome email to new customer
     */
    public function sendWelcomeEmail(Customer $customer): bool
    {
        try {
            Mail::to($customer->email)->send(new WelcomeEmail($customer));

            Log::info('Welcome email sent successfully', [
                'customer_id' => $customer->id,
                'email' => $customer->email
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Welcome email failed', [
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send order placed confirmation
     */
    public function sendOrderPlacedEmail(Order $order): bool
    {
        try {
            $recipient = $order->customer?->email;
            if (!$recipient) {
                throw new \RuntimeException('Order has no associated customer email');
            }
            Mail::to($recipient)->send(new OrderPlaced($order));

            Log::info('Order placed email sent successfully', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'email' => $recipient
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Order placed email failed', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send payment received confirmation
     */
    public function sendPaymentReceivedEmail(Order $order, string $paymentMethod = 'Unknown', string $transactionId = null): bool
    {
        try {
            $recipient = $order->customer?->email;
            if (!$recipient) {
                throw new \RuntimeException('Order has no associated customer email');
            }
            Mail::to($recipient)->send(new PaymentReceived($order, $paymentMethod, $transactionId));

            Log::info('Payment received email sent successfully', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'payment_method' => $paymentMethod,
                'transaction_id' => $transactionId
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Payment received email failed', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send service activated notification
     */
    public function sendServiceActivatedEmail(Order $order, array $serverDetails = []): bool
    {
        try {
            $recipient = $order->customer?->email;
            if (!$recipient) {
                throw new \RuntimeException('Order has no associated customer email');
            }
            Mail::to($recipient)->send(new ServiceActivated($order, $serverDetails));

            Log::info('Service activated email sent successfully', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'server_count' => count($serverDetails)
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Service activated email failed', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send payment failed notification
     */
    public function sendPaymentFailedEmail(Customer $customer, int $orderId, float $amount, string $reason = 'Payment processing failed'): bool
    {
        try {
            Mail::to($customer->email)->send(new PaymentFailed($customer, $orderId, $amount, $reason));

            Log::info('Payment failed email sent successfully', [
                'customer_id' => $customer->id,
                'order_id' => $orderId,
                'amount' => $amount,
                'reason' => $reason
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Payment failed email failed', [
                'customer_id' => $customer->id,
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send service expiring soon notification
     */
    public function sendServiceExpiringEmail(Order $order, int $daysUntilExpiry = 7): bool
    {
        try {
            $recipient = $order->customer?->email;
            if (!$recipient) {
                throw new \RuntimeException('Order has no associated customer email');
            }
            Mail::to($recipient)->send(new OrderExpiringSoon($order, $daysUntilExpiry));

            Log::info('Service expiring email sent successfully', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'days_until_expiry' => $daysUntilExpiry
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Service expiring email failed', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send admin notification
     */
    public function sendAdminNotification(Customer $customer, string $subject, string $messageContent, string $type = 'info'): bool
    {
        try {
            Mail::to($customer->email)->send(new AdminNotification($customer, $subject, $messageContent, $type));

            Log::info('Admin notification sent successfully', [
                'customer_id' => $customer->id,
                'subject' => $subject,
                'type' => $type
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Admin notification failed', [
                'customer_id' => $customer->id,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send bulk notifications to multiple customers
     */
    public function sendBulkNotifications(array $customersIds, string $subject, string $messageContent, string $type = 'info'): array
    {
        $results = ['sent' => 0, 'failed' => 0, 'errors' => []];

        try {
            $customers = Customer::whereIn('id', $customersIds)->get();

            foreach ($customers as $customer) {
                if ($this->sendAdminNotification($customer, $subject, $messageContent, $type)) {
                    $results['sent']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to send to customer {$customer->id}";
                }
            }

            Log::info('Bulk notifications completed', [
                'total_customers' => count($customersIds),
                'sent' => $results['sent'],
                'failed' => $results['failed']
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk notifications failed', [
                'customer_ids' => $customersIds,
                'error' => $e->getMessage()
            ]);

            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Queue email for background processing
     */
    public function queueEmail(string $emailClass, array $data, string $recipient): bool
    {
        try {
            Queue::push(function ($job) use ($emailClass, $data, $recipient) {
                $emailInstance = new $emailClass(...$data);
                Mail::to($recipient)->send($emailInstance);
                $job->delete();
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Email queue failed', [
                'email_class' => $emailClass,
                'recipient' => $recipient,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send test email
     */
    public function sendTestEmail(string $email, string $type = 'welcome'): bool
    {
        try {
            $testUser = new Customer([
                'name' => 'Test Customer',
                'email' => $email,
                'id' => 999999,
                'created_at' => now()
            ]);

            switch ($type) {
                case 'welcome':
                    return $this->sendWelcomeEmail($testUser);

                case 'admin':
                    return $this->sendAdminNotification(
                        $testUser,
                        'Test Admin Notification',
                        'This is a test admin notification from 1000 PROXIES.',
                        'info'
                    );

                default:
                    Mail::raw("This is a test email from 1000 PROXIES mail system.\n\nMail functionality is working correctly!", function ($message) use ($email) {
                        $message->to($email)
                               ->subject('✅ 1000 PROXIES Mail Test - SUCCESS!');
                    });
                    return true;
            }
        } catch (\Exception $e) {
            Log::error('Test email failed', [
                'email' => $email,
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get email statistics
     */
    public function getEmailStats(): array
    {
        // This would typically query a mail_logs table
        // For now, we'll return mock data
        return [
            'total_sent_today' => 0,
            'total_sent_week' => 0,
            'total_sent_month' => 0,
            'failed_today' => 0,
            'queue_size' => 0,
            'last_sent' => null
        ];
    }

    /**
     * Check mail configuration
     */
    public function checkMailConfiguration(): array
    {
        $config = [
            'driver' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ];

        $status = 'healthy';
        $issues = [];

        // Basic configuration checks
        if ($config['driver'] === 'log') {
            $issues[] = 'Using log driver - emails will not be sent';
        }

        if ($config['from_address'] === 'hello@example.com') {
            $issues[] = 'Using default from address';
        }

        if (!empty($issues)) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'config' => $config,
            'issues' => $issues
        ];
    }
}
