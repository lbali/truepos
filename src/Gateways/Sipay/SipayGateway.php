<?php

declare(strict_types=1);

namespace TruePos\Gateways\Sipay;

use TruePos\DataTransferObjects\CancelRequest;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\RefundRequest;
use TruePos\DataTransferObjects\StatusRequest;
use TruePos\Enums\Gateway;
use TruePos\Enums\PaymentModel;
use TruePos\Gateways\AbstractGateway;
use TruePos\ValueObjects\Money;

/**
 * Sipay payment facilitator gateway.
 *
 * REST JSON API with HMAC-SHA256 token authentication.
 * Amount is decimal string (e.g., "100.50").
 * Uses merchant_key + app_key + app_secret for auth.
 */
final class SipayGateway extends AbstractGateway
{
    public function gateway(): Gateway
    {
        return Gateway::Sipay;
    }

    public function supportsInstallment(): bool
    {
        return true;
    }

    public function supportedPaymentModels(): array
    {
        return [
            PaymentModel::Regular,
            PaymentModel::ThreeD,
        ];
    }

    protected function buildPurchaseParameters(PaymentRequest $request): array
    {
        return [
            'merchant_key' => $this->config['merchant_key'],
            'cc_holder_name' => $request->card?->holderName ?? '',
            'cc_no' => $request->card?->number ?? '',
            'expiry_month' => $request->card ? str_pad($request->card->expiryMonth, 2, '0', STR_PAD_LEFT) : '',
            'expiry_year' => $request->card ? substr($request->card->expiryYear, -2) : '',
            'cvv' => $request->card?->cvv ?? '',
            'total' => $request->amount->toDecimal(),
            'currency_code' => 'TRY',
            'installments_number' => $request->hasInstallment() ? $request->installment : 1,
            'invoice_id' => $request->orderId,
            'invoice_description' => $request->metadata['description'] ?? 'Ödeme',
            'name' => $request->customer?->name ?? '',
            'email' => $request->customer?->email ?? '',
            'ip' => $request->customer?->ip ?? '',
            'is_non_3d' => '1',
        ];
    }

    protected function buildPreAuthParameters(PaymentRequest $request): array
    {
        $params = $this->buildPurchaseParameters($request);
        $params['transaction_type'] = 'Auth';

        return $params;
    }

    protected function buildPostAuthParameters(string $transactionId, Money $amount): array
    {
        return [
            'merchant_key' => $this->config['merchant_key'],
            'order_id' => $transactionId,
            'total' => $amount->toDecimal(),
        ];
    }

    protected function buildRefundParameters(RefundRequest $request): array
    {
        return [
            'merchant_key' => $this->config['merchant_key'],
            'invoice_id' => $request->orderId,
            'amount' => $request->amount->toDecimal(),
            'refund_transaction_id' => $request->transactionId ?? '',
        ];
    }

    protected function buildCancelParameters(CancelRequest $request): array
    {
        return [
            'merchant_key' => $this->config['merchant_key'],
            'invoice_id' => $request->orderId,
            'order_id' => $request->transactionId ?? '',
        ];
    }

    protected function buildStatusParameters(StatusRequest $request): array
    {
        return [
            'merchant_key' => $this->config['merchant_key'],
            'invoice_id' => $request->orderId,
        ];
    }

    protected function buildThreeDFormParameters(PaymentRequest $request): array
    {
        $params = $this->buildPurchaseParameters($request);
        $params['is_non_3d'] = '0';
        $params['return_url'] = $request->callbackUrl ?? $this->config['callback_url'] ?? '';
        $params['cancel_url'] = $request->callbackUrl ?? $this->config['callback_url'] ?? '';

        return $params;
    }

    protected function buildThreeDProvisionParameters(array $callbackData): array
    {
        return $callbackData;
    }

    protected function applyHash(array $parameters, string $hash): array
    {
        $parameters['hash_key'] = $hash;

        return $parameters;
    }

    protected function credentials(): array
    {
        return [
            'merchantKey' => $this->config['merchant_key'],
            'appKey' => $this->config['app_key'],
            'appSecret' => $this->config['app_secret'],
        ];
    }

    protected function endpoint(): string
    {
        return $this->config['payment_url'];
    }

    protected function threeDGatewayUrl(): string
    {
        return $this->config['threed_gateway_url'];
    }

    protected function extractMdStatus(array $callbackData): ?string
    {
        $statusCode = $callbackData['status_code'] ?? $callbackData['SipayStatus'] ?? '';

        return (string) $statusCode;
    }

    protected function isThreeDAuthSuccessful(?string $mdStatus): bool
    {
        return $mdStatus === '100';
    }

    protected function requiresProvisionAfterThreeD(): bool
    {
        return false;
    }

    public function verifyThreeDCallback(array $callbackData): bool
    {
        $hash = $callbackData['hash_key'] ?? '';
        if (empty($hash)) {
            return ! empty($callbackData['order_id'] ?? $callbackData['SipayOrderId'] ?? '');
        }

        return $this->hashGenerator->verify($hash, $callbackData, $this->credentials());
    }
}
