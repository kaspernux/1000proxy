<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Create a new payment.
     * Must return standardized structure:
     * [ 'success' => bool, 'error' => string|null, 'data' => array ]
     */
    public function createPayment(array $paymentData): array;
    
    /**
     * Verify a payment.
     * Return structure same as createPayment.
     */
    public function verifyPayment(string $paymentId): array;
    
    /**
     * Process webhook from payment gateway.
     * Return structure same as createPayment.
     */
    public function processWebhook(array $webhookData): array;
    
    /**
     * Get supported currencies.
     * Return structure same as createPayment OR raw list; Prefer standardized.
     */
    public function getSupportedCurrencies(): array;
    
    /**
     * Get supported payment methods (standardized return recommended).
     */
    public function getPaymentMethods(): array;
    
    /**
     * Refund a payment (standardized return required).
     */
    public function refundPayment(string $paymentId, float $amount = null): array;
    
    /**
     * Get gateway information (standardized return recommended).
     */
    public function getGatewayInfo(): array;
}
