<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Create a new payment
     */
    public function createPayment(array $paymentData): array;
    
    /**
     * Verify a payment
     */
    public function verifyPayment(string $paymentId): array;
    
    /**
     * Process webhook from payment gateway
     */
    public function processWebhook(array $webhookData): array;
    
    /**
     * Get supported currencies
     */
    public function getSupportedCurrencies(): array;
    
    /**
     * Get supported payment methods
     */
    public function getPaymentMethods(): array;
    
    /**
     * Refund a payment
     */
    public function refundPayment(string $paymentId, float $amount = null): array;
    
    /**
     * Get gateway information
     */
    public function getGatewayInfo(): array;
}
