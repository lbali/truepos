<?php

declare(strict_types=1);

namespace TruePos\Gateways\Paratika;

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
 * Paratika (Asseco) payment gateway.
 *
 * Session-token based flow:
 * 1. Create SESSIONTOKEN via API
 * 2. Use token for payment (HPP redirect or Direct POST)
 * 3. Result POSTed to RETURNURL
 *
 * All requests go to: POST /paratika/api/v2
 * Differentiated by ACTION parameter.
 *
 * Integration: https://entegrasyon.paratika.com.tr/paratika/api/v2
 * Production:  https://vpos.paratika.com.tr/paratika/api/v2
 */
final class ParatikaGateway extends AbstractGateway
{
    public function gateway(): Gateway
    {
        return Gateway::Paratika;
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
            PaymentModel::ThreeDHost,
        ];
    }

    protected function buildPurchaseParameters(PaymentRequest $request): array
    {
        return [
            ...$this->authBlock(),
            'ACTION' => 'SALE',
            'MERCHANTPAYMENTID' => $request->orderId,
            'AMOUNT' => $request->amount->toDecimal(),
            'CURRENCY' => $this->currencyCode($request->amount),
            'INSTALLMENTS' => $request->hasInstallment() ? (string) $request->installment : '1',
            'CARDPAN' => $request->card?->number ?? '',
            'CARDEXPIRY' => $request->card ? ($request->card->expiryMonth . '.' . (strlen($request->card->expiryYear) === 2 ? '20' . $request->card->expiryYear : $request->card->expiryYear)) : '',
            'CARDCVV' => $request->card?->cvv ?? '',
            'CARDHOLDER' => $request->card?->holderName ?? '',
            'CUSTOMER' => $request->customer?->email ?? $request->orderId,
            'CUSTOMERNAME' => $request->customer?->name ?? '',
            'CUSTOMEREMAIL' => $request->customer?->email ?? '',
            'CUSTOMERPHONE' => $request->customer?->phone ?? '',
            'CUSTOMERIP' => $request->customer?->ip ?? '',
        ];
    }

    protected function buildPreAuthParameters(PaymentRequest $request): array
    {
        $params = $this->buildPurchaseParameters($request);
        $params['ACTION'] = 'PREAUTH';

        return $params;
    }

    protected function buildPostAuthParameters(string $transactionId, Money $amount): array
    {
        return [
            ...$this->authBlock(),
            'ACTION' => 'POSTAUTH',
            'PGTRANID' => $transactionId,
            'AMOUNT' => $amount->toDecimal(),
            'CURRENCY' => $this->currencyCode($amount),
        ];
    }

    protected function buildRefundParameters(RefundRequest $request): array
    {
        return [
            ...$this->authBlock(),
            'ACTION' => 'REFUND',
            'PGTRANID' => $request->transactionId ?? '',
            'AMOUNT' => $request->amount->toDecimal(),
            'CURRENCY' => $this->currencyCode($request->amount),
        ];
    }

    protected function buildCancelParameters(CancelRequest $request): array
    {
        return [
            ...$this->authBlock(),
            'ACTION' => 'VOID',
            'PGTRANID' => $request->transactionId ?? '',
        ];
    }

    protected function buildStatusParameters(StatusRequest $request): array
    {
        return [
            ...$this->authBlock(),
            'ACTION' => 'QUERYTRANSACTION',
            'MERCHANTPAYMENTID' => $request->orderId,
        ];
    }

    protected function buildThreeDFormParameters(PaymentRequest $request): array
    {
        // Step 1: Create session token
        return [
            ...$this->authBlock(),
            'ACTION' => 'SESSIONTOKEN',
            'SESSIONTYPE' => 'PAYMENTSESSION',
            'MERCHANTPAYMENTID' => $request->orderId,
            'AMOUNT' => $request->amount->toDecimal(),
            'CURRENCY' => $this->currencyCode($request->amount),
            'INSTALLMENTS' => $request->hasInstallment() ? (string) $request->installment : '1',
            'RETURNURL' => $request->callbackUrl ?? $this->config['callback_url'] ?? '',
            'CUSTOMER' => $request->customer?->email ?? $request->orderId,
            'CUSTOMERNAME' => $request->customer?->name ?? '',
            'CUSTOMEREMAIL' => $request->customer?->email ?? '',
            'CUSTOMERPHONE' => $request->customer?->phone ?? '',
            'CUSTOMERIP' => $request->customer?->ip ?? '',
            'ORDERITEMS' => json_encode([[
                'productCode' => $request->orderId,
                'name' => $request->metadata['itemName'] ?? 'Ödeme',
                'description' => $request->metadata['description'] ?? '',
                'quantity' => 1,
                'amount' => $request->amount->toDecimal(),
            ]]),
        ];
    }

    protected function buildThreeDProvisionParameters(array $callbackData): array
    {
        // Server-to-server verification: query the transaction status from Paratika
        return [
            ...$this->authBlock(),
            'ACTION' => 'QUERYTRANSACTION',
            'PGTRANID' => $callbackData['pgTranId'] ?? '',
            'MERCHANTPAYMENTID' => $callbackData['merchantPaymentId'] ?? '',
        ];
    }

    protected function applyHash(array $parameters, string $hash): array
    {
        // Paratika uses credential-based auth, no hash needed
        return $parameters;
    }

    protected function credentials(): array
    {
        return [
            'merchant' => $this->config['merchant'],
            'merchantUser' => $this->config['merchant_user'],
            'merchantPassword' => $this->config['merchant_password'],
        ];
    }

    protected function endpoint(): string
    {
        return rtrim($this->config['base_url'], '/') . '/paratika/api/v2';
    }

    protected function threeDGatewayUrl(): string
    {
        return rtrim($this->config['base_url'], '/') . '/paratika/api/v2';
    }

    protected function extractMdStatus(array $callbackData): ?string
    {
        return $callbackData['responseCode'] ?? null;
    }

    protected function isThreeDAuthSuccessful(?string $mdStatus): bool
    {
        return $mdStatus === '00';
    }

    protected function requiresProvisionAfterThreeD(): bool
    {
        return true;
    }

    public function verifyThreeDCallback(array $callbackData): bool
    {
        $responseCode = $callbackData['responseCode'] ?? '';

        if (empty($responseCode)) {
            return false;
        }

        // Paratika callback must have a valid pgTranId from the gateway
        // to prove it's genuine, not forged.
        $pgTranId = $callbackData['pgTranId'] ?? '';

        if (empty($pgTranId)) {
            return false;
        }

        return $responseCode === '00';
    }

    /**
     * @return array<string, mixed>
     */
    private function authBlock(): array
    {
        return [
            'MERCHANT' => $this->config['merchant'],
            'MERCHANTUSER' => $this->config['merchant_user'],
            'MERCHANTPASSWORD' => $this->config['merchant_password'],
        ];
    }

    private function currencyCode(Money $money): string
    {
        return match ($money->currency) {
            Currency::TRY => 'TRY',
            Currency::USD => 'USD',
            Currency::EUR => 'EUR',
            Currency::GBP => 'GBP',
        };
    }
}
