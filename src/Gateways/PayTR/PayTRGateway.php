<?php

declare(strict_types=1);

namespace TruePos\Gateways\PayTR;

use TruePos\DataTransferObjects\CancelRequest;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\RefundRequest;
use TruePos\DataTransferObjects\StatusRequest;
use TruePos\Enums\Currency;
use TruePos\Enums\Gateway;
use TruePos\Enums\PaymentModel;
use TruePos\Gateways\AbstractGateway;
use TruePos\ValueObjects\Money;

/**
 * PayTR payment facilitator gateway.
 *
 * Unlike bank gateways, PayTR uses:
 * - JSON/form-encoded API (not XML)
 * - HMAC-SHA256 tokens (not hash of concatenated fields)
 * - iframe-based payment flow for 3DS
 * - Amount in kuruş (integer)
 * - Text currency codes (TL, USD, EUR)
 */
final class PayTRGateway extends AbstractGateway
{
    public function gateway(): Gateway
    {
        return Gateway::PayTR;
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
            'merchant_id' => $this->config['merchant_id'],
            'user_ip' => $request->customer?->ip ?? '',
            'merchant_oid' => $request->orderId,
            'email' => $request->customer?->email ?? '',
            'payment_amount' => (string) $request->amount->toMinor(),
            'payment_type' => 'card',
            'installment_count' => $request->hasInstallment() ? (string) $request->installment : '0',
            'currency' => $this->currencyCode($request->amount),
            'test_mode' => ($this->config['test_mode'] ?? false) ? '1' : '0',
            'non_3d' => '1',
            'cc_owner' => $request->card?->holderName ?? '',
            'card_number' => $request->card?->number ?? '',
            'expiry_month' => $request->card ? str_pad($request->card->expiryMonth, 2, '0', STR_PAD_LEFT) : '',
            'expiry_year' => $request->card ? substr($request->card->expiryYear, -2) : '',
            'cvv' => $request->card?->cvv ?? '',
            'user_name' => $request->customer?->name ?? '',
            'user_phone' => $request->customer?->phone ?? '',
            'merchant_ok_url' => $request->callbackUrl ?? $this->config['callback_url'] ?? '',
            'merchant_fail_url' => $request->callbackUrl ?? $this->config['callback_url'] ?? '',
            'user_basket' => base64_encode((string) json_encode([
                [$request->orderId, $request->amount->toDecimal(), 1],
            ])),
            'debug_on' => ($this->config['debug'] ?? false) ? '1' : '0',
            'lang' => $this->config['lang'] ?? 'tr',
        ];
    }

    protected function buildPreAuthParameters(PaymentRequest $request): array
    {
        $params = $this->buildPurchaseParameters($request);
        $params['payment_type'] = 'card';

        return $params;
    }

    protected function buildPostAuthParameters(string $transactionId, Money $amount): array
    {
        return [
            'merchant_id' => $this->config['merchant_id'],
            'merchant_oid' => $transactionId,
            'capture_amount' => (string) $amount->toMinor(),
        ];
    }

    protected function buildRefundParameters(RefundRequest $request): array
    {
        return [
            'merchant_id' => $this->config['merchant_id'],
            'merchant_oid' => $request->orderId,
            'return_amount' => (string) $request->amount->toMinor(),
            'reference_no' => $request->transactionId ?? '',
        ];
    }

    protected function buildCancelParameters(CancelRequest $request): array
    {
        return [
            'merchant_id' => $this->config['merchant_id'],
            'merchant_oid' => $request->orderId,
        ];
    }

    protected function buildStatusParameters(StatusRequest $request): array
    {
        return [
            'merchant_id' => $this->config['merchant_id'],
            'merchant_oid' => $request->orderId,
        ];
    }

    protected function buildThreeDFormParameters(PaymentRequest $request): array
    {
        $params = $this->buildPurchaseParameters($request);
        $params['non_3d'] = '0';

        return $params;
    }

    protected function buildThreeDProvisionParameters(array $callbackData): array
    {
        // PayTR handles 3DS internally — no second provision call needed
        return $callbackData;
    }

    protected function applyHash(array $parameters, string $hash): array
    {
        $parameters['paytr_token'] = $hash;

        return $parameters;
    }

    protected function credentials(): array
    {
        return [
            'merchantId' => $this->config['merchant_id'],
            'merchantKey' => $this->config['merchant_key'],
            'merchantSalt' => $this->config['merchant_salt'],
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
        return $callbackData['status'] ?? null;
    }

    protected function isThreeDAuthSuccessful(?string $mdStatus): bool
    {
        return $mdStatus === 'success';
    }

    protected function requiresProvisionAfterThreeD(): bool
    {
        return false;
    }

    public function validateThreeDCallbackPayload(array $callbackData): bool
    {
        $hash = $callbackData['hash'] ?? '';
        if (empty($hash)) {
            return false;
        }

        return $this->hashGenerator->verify($hash, $callbackData, $this->credentials());
    }

    private function currencyCode(Money $money): string
    {
        return match ($money->currency) {
            Currency::TRY => 'TL',
            Currency::USD => 'USD',
            Currency::EUR => 'EUR',
            Currency::GBP => 'GBP',
        };
    }
}
