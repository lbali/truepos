<?php

declare(strict_types=1);

namespace TruePos\Gateways\Craftgate;

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
 * Craftgate payment orchestrator gateway.
 *
 * REST JSON API with header-based SHA-256 signature.
 * Amount is decimal (e.g., 100.50).
 * Currency: TRY, USD, EUR, GBP (text codes).
 *
 * Endpoints:
 * - Payment:     POST /payment/v1/card-payments
 * - 3DS Init:    POST /payment/v1/card-payments/3ds-init
 * - 3DS Complete:POST /payment/v1/card-payments/3ds-complete
 * - PostAuth:    POST /payment/v1/card-payments/{id}/post-auth
 * - Refund:      POST /payment/v1/refunds
 * - Refund Txn:  POST /payment/v1/refund-transactions
 * - Retrieve:    GET  /payment/v1/card-payments/{id}
 *
 * Signature: Base64(SHA-256(baseUrl + path + apiKey + secretKey + rnd + body))
 */
final class CraftgateGateway extends AbstractGateway
{
    public function gateway(): Gateway
    {
        return Gateway::Craftgate;
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
            'price' => (float) $request->amount->toDecimal(),
            'paidPrice' => (float) $request->amount->toDecimal(),
            'installment' => $request->hasInstallment() ? $request->installment : 1,
            'currency' => $this->currencyCode($request->amount),
            'paymentGroup' => 'PRODUCT',
            'conversationId' => $request->orderId,
            'card' => [
                'cardHolderName' => $request->card?->holderName ?? '',
                'cardNumber' => $request->card?->number ?? '',
                'expireYear' => $request->card ? (strlen($request->card->expiryYear) === 2 ? '20' . $request->card->expiryYear : $request->card->expiryYear) : '',
                'expireMonth' => $request->card ? str_pad($request->card->expiryMonth, 2, '0', STR_PAD_LEFT) : '',
                'cvc' => $request->card?->cvv ?? '',
            ],
            'items' => [
                [
                    'externalId' => $request->orderId,
                    'name' => $request->metadata['itemName'] ?? 'Ödeme',
                    'price' => (float) $request->amount->toDecimal(),
                ],
            ],
        ];
    }

    protected function buildPreAuthParameters(PaymentRequest $request): array
    {
        $params = $this->buildPurchaseParameters($request);
        $params['paymentPhase'] = 'PRE_AUTH';

        return $params;
    }

    protected function buildPostAuthParameters(string $transactionId, Money $amount): array
    {
        return [
            '_paymentId' => $transactionId,
            'paidPrice' => (float) $amount->toDecimal(),
        ];
    }

    protected function buildRefundParameters(RefundRequest $request): array
    {
        return [
            'paymentId' => (int) ($request->transactionId ?? 0),
            'conversationId' => $request->orderId,
            'refundPrice' => (float) $request->amount->toDecimal(),
            'refundDestinationType' => 'PROVIDER',
        ];
    }

    protected function buildCancelParameters(CancelRequest $request): array
    {
        return [
            'paymentId' => (int) ($request->transactionId ?? 0),
            'refundDestinationType' => 'PROVIDER',
        ];
    }

    protected function buildStatusParameters(StatusRequest $request): array
    {
        return [
            '_paymentId' => $request->transactionId ?? $request->orderId,
        ];
    }

    protected function buildThreeDFormParameters(PaymentRequest $request): array
    {
        $params = $this->buildPurchaseParameters($request);
        $params['callbackUrl'] = $request->callbackUrl ?? $this->config['callback_url'] ?? '';

        return $params;
    }

    protected function buildThreeDProvisionParameters(array $callbackData): array
    {
        return [
            'paymentId' => (int) ($callbackData['paymentId'] ?? 0),
        ];
    }

    protected function applyHash(array $parameters, string $hash): array
    {
        // Craftgate hash goes in HTTP headers, not in body.
        // We store it in a special key that sendRequest will extract.
        $parameters['_signature'] = $hash;

        return $parameters;
    }

    protected function credentials(): array
    {
        return [
            'apiKey' => $this->config['api_key'],
            'secretKey' => $this->config['secret_key'],
            'baseUrl' => rtrim($this->config['base_url'] ?? 'https://api.craftgate.io', '/'),
        ];
    }

    protected function endpoint(): string
    {
        return rtrim($this->config['base_url'] ?? 'https://api.craftgate.io', '/') . '/payment/v1/card-payments';
    }

    protected function threeDGatewayUrl(): string
    {
        return rtrim($this->config['base_url'] ?? 'https://api.craftgate.io', '/') . '/payment/v1/card-payments/3ds-init';
    }

    protected function extractMdStatus(array $callbackData): ?string
    {
        return $callbackData['completeStatus'] ?? $callbackData['status'] ?? null;
    }

    protected function isThreeDAuthSuccessful(?string $mdStatus): bool
    {
        return $mdStatus === 'SUCCESS';
    }

    protected function requiresProvisionAfterThreeD(): bool
    {
        return true;
    }

    public function verifyThreeDCallback(array $callbackData): bool
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
            Currency::TRY => 'TRY',
            Currency::USD => 'USD',
            Currency::EUR => 'EUR',
            Currency::GBP => 'GBP',
        };
    }
}
