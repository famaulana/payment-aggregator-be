<?php

namespace App\Services\Gateway\Contracts;

use App\DTOs\CreatePaymentDTO;
use App\DTOs\PaymentResultDTO;
use App\DTOs\RefundDTO;
use App\DTOs\RefundResultDTO;
use App\DTOs\WebhookResultDTO;

interface PaymentGatewayInterface
{
    /**
     * Create a new payment at the Payment Gateway
     */
    public function createPayment(CreatePaymentDTO $dto): PaymentResultDTO;

    /**
     * Get payment status from the Payment Gateway (polling fallback)
     */
    public function getPaymentStatus(string $pgReferenceId): PaymentResultDTO;

    /**
     * Cancel a pending payment at the Payment Gateway
     */
    public function cancelPayment(string $pgReferenceId): bool;

    /**
     * Process a refund at the Payment Gateway
     */
    public function refund(RefundDTO $dto): RefundResultDTO;

    /**
     * Parse inbound webhook payload into internal WebhookResultDTO
     */
    public function parseWebhook(array $payload, array $headers): WebhookResultDTO;

    /**
     * Validate inbound webhook signature from the Payment Gateway
     */
    public function validateWebhookSignature(array $payload, array $headers): bool;

    /**
     * Return list of method_code values supported by this gateway
     */
    public function getSupportedMethods(): array;
}
