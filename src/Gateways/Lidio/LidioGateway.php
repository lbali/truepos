<?php

declare(strict_types=1);

namespace TruePos\Gateways\Lidio;

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
 * Lidio (Mobilexpress) payment gateway.
 *
 * REST JSON API with API Key auth in Authorization header.
 * Headers: "Authorization: MxS2S {APIKey}", "MerchantCode: {code}"
 * Amount: decimal dot-separated, max 2 digits (e.g., 100.50)
 * Currency: text codes (TRY, USD, EUR, GBP)
 *
 * Endpoints:
 * - Payment:        POST /api/ProcessPayment
 * - Finish 3DS:     POST /api/FinishPaymentProcess
 * - PostAuth:       POST /api/PostAuth
 * - Refund:         POST /api/Refund
 * - Cancel:         POST /api/Cancel
 * - Inquiry:        POST /api/PaymentInquiry
 *
 * 3DS Flow:
 * 1. ProcessPayment → result=RedirectFormCreated → write RedirectForm to page
 * 2. User completes 3DS on hosted page
 * 3. Redirect to ReturnURL with OrderId, SystemTransId, Result, Hash
 * 4. Verify Hash: Base64(SHA-256(OrderId:MerchantKey:TotalAmount:Result:Email))
 * 5. Call FinishPaymentProcess to finalize
 *
 * Test: https://test.lidio.com/api
 * Prod: https://lidio.com/api
 */
final class LidioGateway extends AbstractGateway
{
    public function gateway(): Gateway
    {
        return Gateway::Lidio;
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

    // ─── POST /api/ProcessPayment ────────────────────────────

    protected function buildPurchaseParameters(PaymentRequest $request): array
    {
        return [
            'orderId' => $request->orderId,
            'totalAmount' => (float) $request->amount->toDecimal(),
            'currency' => $this->currencyCode($request->amount),
            'customerInfo' => [
                'email' => $request->customer?->email ?? '',
                'name' => $request->customer?->name ?? '',
                'phone' => $request->customer?->phone ?? '',
                'customerId' => $request->customer?->identity ?? '',
            ],
            'paymentInstrument' => 'newCard',
            'paymentInstrumentInfo' => [
                'newCard' => [
                    'processType' => 'sales',
                    'cardInfo' => [
                        'cardHolderName' => $request->card?->holderName ?? '',
                        'cardNumber' => $request->card?->number ?? '',
                        'lastMonth' => $request->card ? (int) $request->card->expiryMonth : 0,
                        'lastYear' => $request->card ? (int) (strlen($request->card->expiryYear) === 2 ? '20' . $request->card->expiryYear : $request->card->expiryYear) : 0,
                    ],
                    'cvv' => $request->card?->cvv ?? '',
                    'use3DSecure' => $request->isThreeD(),
                    'saveAfterSuccess' => false,
                    'installmentCount' => $request->hasInstallment() ? $request->installment : 1,
                    'extraInstallment' => 0,
                    'amountDetail' => [
                        'baseAmount' => 0,
                        'interestAmount' => 0,
                    ],
                    'loyaltyPointUsage' => 'none',
                    'loyaltyPointAmount' => 0,
                    'posAccount' => [
                        'id' => (int) ($this->config['pos_account_id'] ?? 1),
                    ],
                ],
            ],
            'returnUrl' => $request->callbackUrl ?? $this->config['callback_url'] ?? '',
            'notificationUrl' => $this->config['notification_url'] ?? '',
            'basketItems' => [
                [
                    'name' => $request->metadata['itemName'] ?? 'Ödeme',
                    'quantity' => 1,
                    'unitPrice' => (float) $request->amount->toDecimal(),
                    'itemType' => 'Virtual',
                ],
            ],
            'clientType' => 'Web',
            'clientIp' => $request->customer?->ip ?? '',
            'merchantProcessId' => $request->metadata['merchantProcessId'] ?? '',
        ];
    }

    protected function buildPreAuthParameters(PaymentRequest $request): array
    {
        $params = $this->buildPurchaseParameters($request);
        $params['paymentInstrumentInfo']['newCard']['processType'] = 'preAuth';

        return $params;
    }

    // ─── POST /api/PostAuth ──────────────────────────────────

    protected function buildPostAuthParameters(string $transactionId, Money $amount): array
    {
        return [
            'orderId' => $transactionId,
            'totalAmount' => (float) $amount->toDecimal(),
            'currency' => $this->currencyCode($amount),
            'clientIp' => '127.0.0.1',
        ];
    }

    // ─── POST /api/Refund ────────────────────────────────────

    protected function buildRefundParameters(RefundRequest $request): array
    {
        return [
            'orderId' => $request->orderId,
            'totalAmount' => (float) $request->amount->toDecimal(),
            'currency' => $this->currencyCode($request->amount),
            'refundTransId' => $request->transactionId ?? '',
            'clientIp' => '127.0.0.1',
        ];
    }

    // ─── POST /api/Cancel ────────────────────────────────────

    protected function buildCancelParameters(CancelRequest $request): array
    {
        return [
            'orderId' => $request->orderId,
        ];
    }

    // ─── POST /api/PaymentInquiry ────────────────────────────

    protected function buildStatusParameters(StatusRequest $request): array
    {
        return [
            'orderId' => $request->orderId,
            'totalAmount' => 0,
            'paymentInstrument' => 'newCard',
            'paymentInquiryInstrumentInfo' => [
                'posAccount' => [
                    'id' => (int) ($this->config['pos_account_id'] ?? 0),
                ],
            ],
        ];
    }

    // ─── 3DS: same as purchase with use3DSecure=true ─────────

    protected function buildThreeDFormParameters(PaymentRequest $request): array
    {
        $params = $this->buildPurchaseParameters($request);
        $params['paymentInstrumentInfo']['newCard']['use3DSecure'] = true;

        return $params;
    }

    // ─── POST /api/FinishPaymentProcess ──────────────────────

    protected function buildThreeDProvisionParameters(array $callbackData): array
    {
        return [
            'orderId' => $callbackData['OrderId'] ?? $callbackData['orderId'] ?? '',
            'systemTransId' => $callbackData['SystemTransId'] ?? $callbackData['systemTransId'] ?? '',
            'totalAmount' => (float) ($callbackData['TotalAmount'] ?? $callbackData['totalAmount'] ?? 0),
            'currency' => $callbackData['Currency'] ?? 'TRY',
            'paymentInstrument' => 'newCard',
            'paymentInstrumentInfo' => [
                'newCard' => [],
            ],
            'clientType' => 'Web',
            'clientIp' => '127.0.0.1',
        ];
    }

    protected function applyHash(array $parameters, string $hash): array
    {
        // Lidio uses API Key in Authorization header, no per-request hash in body
        return $parameters;
    }

    protected function credentials(): array
    {
        return [
            'apiKey' => $this->config['api_key'],
            'merchantCode' => $this->config['merchant_code'],
            'merchantKey' => $this->config['merchant_key'],
        ];
    }

    protected function endpoint(): string
    {
        return rtrim($this->config['base_url'], '/') . '/api/ProcessPayment';
    }

    protected function threeDGatewayUrl(): string
    {
        return rtrim($this->config['base_url'], '/') . '/api/ProcessPayment';
    }

    protected function extractMdStatus(array $callbackData): ?string
    {
        return $callbackData['Result'] ?? $callbackData['result'] ?? null;
    }

    protected function isThreeDAuthSuccessful(?string $mdStatus): bool
    {
        return $mdStatus === '3DSuccess';
    }

    protected function requiresProvisionAfterThreeD(): bool
    {
        return true;
    }

    public function verifyThreeDCallback(array $callbackData): bool
    {
        $hash = $callbackData['Hash'] ?? $callbackData['hash'] ?? '';

        if (empty($hash)) {
            return false;
        }

        return $this->hashGenerator->verify($hash, $callbackData, $this->credentials());
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
