<?php

declare(strict_types=1);

namespace TruePos\Gateways\Iyzico;

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
 * iyzico payment facilitator gateway.
 *
 * REST JSON API with PKI-based authorization headers.
 * Amount is decimal string (e.g., "1.0", "100.50").
 * Requires basket items in every payment request.
 * 3DS uses iyzico's initialize/callback flow.
 */
final class IyzicoGateway extends AbstractGateway
{
    public function gateway(): Gateway
    {
        return Gateway::Iyzico;
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
            'locale' => $this->config['locale'] ?? 'tr',
            'conversationId' => $request->orderId,
            'price' => $request->amount->toDecimal(),
            'paidPrice' => $request->amount->toDecimal(),
            'currency' => $this->currencyCode($request->amount),
            'installment' => $request->hasInstallment() ? $request->installment : 1,
            'basketId' => $request->orderId,
            'paymentChannel' => 'WEB',
            'paymentGroup' => 'PRODUCT',
            'paymentCard' => [
                'cardHolderName' => $request->card?->holderName ?? '',
                'cardNumber' => $request->card?->number ?? '',
                'expireMonth' => $request->card ? str_pad($request->card->expiryMonth, 2, '0', STR_PAD_LEFT) : '',
                'expireYear' => $request->card ? (strlen($request->card->expiryYear) === 2 ? '20' . $request->card->expiryYear : $request->card->expiryYear) : '',
                'cvc' => $request->card?->cvv ?? '',
                'registerCard' => '0',
            ],
            'buyer' => [
                'id' => $request->customer?->identity ?? 'BUYER_' . $request->orderId,
                'name' => $this->extractFirstName($request->customer?->name),
                'surname' => $this->extractLastName($request->customer?->name),
                'email' => $request->customer?->email ?? 'noemail@example.com',
                'ip' => $request->customer?->ip ?? '',
                'identityNumber' => $request->customer?->identity ?? '11111111111',
                'registrationAddress' => 'N/A',
                'city' => 'Istanbul',
                'country' => 'Turkey',
            ],
            'shippingAddress' => [
                'contactName' => $request->customer?->name ?? 'N/A',
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'address' => 'N/A',
            ],
            'billingAddress' => [
                'contactName' => $request->customer?->name ?? 'N/A',
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'address' => 'N/A',
            ],
            'basketItems' => [
                [
                    'id' => $request->orderId,
                    'name' => $request->metadata['itemName'] ?? 'Ödeme',
                    'category1' => $request->metadata['category'] ?? 'Default',
                    'itemType' => 'PHYSICAL',
                    'price' => $request->amount->toDecimal(),
                ],
            ],
        ];
    }

    protected function buildPreAuthParameters(PaymentRequest $request): array
    {
        $params = $this->buildPurchaseParameters($request);
        $params['paymentGroup'] = 'PRODUCT';

        return $params;
    }

    protected function buildPostAuthParameters(string $transactionId, Money $amount): array
    {
        return [
            'locale' => $this->config['locale'] ?? 'tr',
            'paymentTransactionId' => $transactionId,
            'paidPrice' => $amount->toDecimal(),
            'currency' => $this->currencyCode($amount),
        ];
    }

    protected function buildRefundParameters(RefundRequest $request): array
    {
        return [
            'locale' => $this->config['locale'] ?? 'tr',
            'conversationId' => $request->orderId,
            'paymentTransactionId' => $request->transactionId ?? '',
            'price' => $request->amount->toDecimal(),
            'currency' => $this->currencyCode($request->amount),
            'ip' => '127.0.0.1',
        ];
    }

    protected function buildCancelParameters(CancelRequest $request): array
    {
        return [
            'locale' => $this->config['locale'] ?? 'tr',
            'conversationId' => $request->orderId,
            'paymentId' => $request->transactionId ?? '',
            'ip' => '127.0.0.1',
        ];
    }

    protected function buildStatusParameters(StatusRequest $request): array
    {
        return [
            'locale' => $this->config['locale'] ?? 'tr',
            'conversationId' => $request->orderId,
            'paymentId' => $request->transactionId ?? '',
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
            'locale' => $this->config['locale'] ?? 'tr',
            'conversationId' => $callbackData['conversationId'] ?? '',
            'paymentId' => $callbackData['paymentId'] ?? '',
        ];
    }

    protected function applyHash(array $parameters, string $hash): array
    {
        $parameters['_authorization'] = $hash;
        $parameters['_random'] = $parameters['_random'] ?? bin2hex(random_bytes(8));

        return $parameters;
    }

    protected function credentials(): array
    {
        return [
            'apiKey' => $this->config['api_key'],
            'secretKey' => $this->config['secret_key'],
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
        return true;
    }

    public function verifyThreeDCallback(array $callbackData): bool
    {
        $token = $callbackData['token'] ?? '';

        if ($token === '') {
            return false;
        }

        // Verify token has valid format (iyzico tokens are non-trivial strings)
        if (strlen($token) < 16) {
            return false;
        }

        return true;
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

        $parts = explode(' ', trim($fullName));

        return $parts[0];
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
