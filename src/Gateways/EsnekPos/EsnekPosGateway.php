<?php

declare(strict_types=1);

namespace TruePos\Gateways\EsnekPos;

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
 * EsnekPOS payment gateway.
 *
 * REST JSON API.
 * Amount: comma-separated decimal ("150,00")
 * Currency: text codes (TRY, USD, EUR, GBP)
 * Expiry: month 2-digit, year 4-digit
 * ORDER_REF_NUMBER max 24 chars
 *
 * Endpoints:
 * - 3D Payment:    POST /api/pay/EYV3DPay
 * - Common Page:   POST /api/pay/CommonPaymentDealer
 * - Recurring:     POST /api/pay/RecurringPayment
 * - Query:         POST /api/services/RecurringPaymentQuery
 * - Cancel:        POST /api/services/OrderReturnPhysical
 * - Payment Link:  POST /api/services/SendPaymentRequest
 *
 * Test: https://posservicetest.esnekpos.com
 * Prod: https://posservice.esnekpos.com
 */
final class EsnekPosGateway extends AbstractGateway
{
    public function gateway(): Gateway
    {
        return Gateway::EsnekPos;
    }

    public function supportsInstallment(): bool
    {
        return true;
    }

    public function supportedPaymentModels(): array
    {
        return [
            PaymentModel::ThreeD,
            PaymentModel::ThreeDHost,
        ];
    }

    // ─── 3D Payment: POST /api/pay/EYV3DPay ─────────────────

    protected function buildPurchaseParameters(PaymentRequest $request): array
    {
        return $this->buildPaymentRequest($request);
    }

    protected function buildPreAuthParameters(PaymentRequest $request): array
    {
        return $this->buildPaymentRequest($request);
    }

    protected function buildPostAuthParameters(string $transactionId, Money $amount): array
    {
        return [
            'MERCHANT' => $this->config['merchant'],
            'MERCHANT_KEY' => $this->config['merchant_key'],
            'REFNO' => $transactionId,
            'ORDER_AMOUNT' => $this->formatAmount($amount),
        ];
    }

    protected function buildRefundParameters(RefundRequest $request): array
    {
        return [
            'MERCHANT' => $this->config['merchant'],
            'MERCHANT_KEY' => $this->config['merchant_key'],
            'ORDER_REF_NUMBER' => $request->orderId,
            'ORDER_AMOUNT' => $this->formatAmount($request->amount),
            'REFNO' => $request->transactionId ?? '',
        ];
    }

    protected function buildCancelParameters(CancelRequest $request): array
    {
        return [
            'MERCHANT' => $this->config['merchant'],
            'MERCHANT_KEY' => $this->config['merchant_key'],
            'ORDER_REF_NUMBER' => $request->orderId,
            'REFNO' => $request->transactionId ?? '',
        ];
    }

    protected function buildStatusParameters(StatusRequest $request): array
    {
        return [
            'MERCHANT' => $this->config['merchant'],
            'MERCHANT_KEY' => $this->config['merchant_key'],
            'ORDER_REF_NUMBER' => $request->orderId,
        ];
    }

    protected function buildThreeDFormParameters(PaymentRequest $request): array
    {
        return $this->buildPaymentRequest($request);
    }

    protected function buildThreeDProvisionParameters(array $callbackData): array
    {
        // EsnekPOS 3D: payment completes during redirect, callback contains result
        return $callbackData;
    }

    protected function applyHash(array $parameters, string $hash): array
    {
        $parameters['AUTH_HASH'] = $hash;

        return $parameters;
    }

    protected function credentials(): array
    {
        return [
            'merchant' => $this->config['merchant'],
            'merchantKey' => $this->config['merchant_key'],
        ];
    }

    protected function endpoint(): string
    {
        return rtrim($this->config['base_url'], '/') . '/api/pay/EYV3DPay';
    }

    protected function threeDGatewayUrl(): string
    {
        return rtrim($this->config['base_url'], '/') . '/api/pay/EYV3DPay';
    }

    protected function extractMdStatus(array $callbackData): ?string
    {
        return $callbackData['RETURN_CODE'] ?? null;
    }

    protected function isThreeDAuthSuccessful(?string $mdStatus): bool
    {
        return $mdStatus === '0';
    }

    protected function requiresProvisionAfterThreeD(): bool
    {
        return false;
    }

    public function verifyThreeDCallback(array $callbackData): bool
    {
        $hash = $callbackData['HASH'] ?? $callbackData['AUTH_HASH'] ?? '';

        if (empty($hash)) {
            // Hash field is required for callback verification.
            // Without it, we cannot prove the callback is genuine.
            return false;
        }

        return $this->hashGenerator->verify($hash, $callbackData, $this->credentials());
    }

    // ─── Private helpers ─────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function buildPaymentRequest(PaymentRequest $request): array
    {
        $params = [
            'MERCHANT' => $this->config['merchant'],
            'MERCHANT_KEY' => $this->config['merchant_key'],
            'BACK_URL' => $request->callbackUrl ?? $this->config['callback_url'] ?? '',
            'PRICES_CURRENCY' => $this->currencyCode($request->amount),
            'ORDER_REF_NUMBER' => substr($request->orderId, 0, 24),
            'ORDER_AMOUNT' => $this->formatAmount($request->amount),
            'INSTALLMENT_NUMBER' => $request->hasInstallment() ? (string) $request->installment : '1',
        ];

        // Card details for 3D payment (not needed for CommonPaymentDealer)
        if ($request->card !== null) {
            $params['CC_NUMBER'] = $request->card->number;
            $params['EXP_MONTH'] = str_pad($request->card->expiryMonth, 2, '0', STR_PAD_LEFT);
            $params['EXP_YEAR'] = strlen($request->card->expiryYear) === 2
                ? '20' . $request->card->expiryYear
                : $request->card->expiryYear;
            $params['CC_CVV'] = $request->card->cvv;
            $params['CC_OWNER'] = $request->card->holderName ?? '';
        }

        // Customer info
        $params['FIRST_NAME'] = $this->extractFirstName($request->customer?->name);
        $params['LAST_NAME'] = $this->extractLastName($request->customer?->name);
        $params['MAIL'] = $request->customer?->email ?? '';
        $params['PHONE'] = $request->customer?->phone ?? '';
        $params['CLIENT_IP'] = $request->customer?->ip ?? '';
        $params['CITY'] = 'Istanbul';
        $params['STATE'] = 'Istanbul';
        $params['ADDRESS'] = 'N/A';

        // Product info
        $params['PRODUCTS'] = [
            [
                'PRODUCT_ID' => $request->orderId,
                'PRODUCT_NAME' => $request->metadata['productName'] ?? 'Ödeme',
                'PRODUCT_CATEGORY' => $request->metadata['category'] ?? 'Genel',
                'PRODUCT_DESCRIPTION' => $request->metadata['description'] ?? '',
                'PRODUCT_AMOUNT' => $this->formatAmount($request->amount),
            ],
        ];

        return $params;
    }

    /**
     * EsnekPOS uses comma as decimal separator: "150,00"
     */
    private function formatAmount(Money $money): string
    {
        return number_format($money->amount / 100, 2, ',', '');
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

    private function extractFirstName(?string $fullName): string
    {
        if ($fullName === null || $fullName === '') {
            return 'N/A';
        }

        return explode(' ', trim($fullName))[0];
    }

    private function extractLastName(?string $fullName): string
    {
        if ($fullName === null || $fullName === '') {
            return 'N/A';
        }

        $parts = explode(' ', trim($fullName));

        return count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : $parts[0];
    }
}
