<?php

declare(strict_types=1);

namespace TruePos\Gateways\KuveytTurk;

use TruePos\DataTransferObjects\CancelRequest;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\RefundRequest;
use TruePos\DataTransferObjects\StatusRequest;
use TruePos\Enums\Gateway;
use TruePos\Enums\PaymentModel;
use TruePos\Gateways\AbstractGateway;
use TruePos\ValueObjects\Money;

/**
 * Kuveyt Türk gateway.
 *
 * Uses MerchantId, CustomerId, UserName, Password.
 * Amount is in kuruş (integer, e.g., 10050 for 100.50 TL).
 * 3DS is mandatory for this gateway.
 */
final class KuveytTurkGateway extends AbstractGateway
{
    public function gateway(): Gateway
    {
        return Gateway::KuveytTurk;
    }

    public function supportsInstallment(): bool
    {
        return true;
    }

    public function supportedPaymentModels(): array
    {
        return [
            PaymentModel::ThreeD,
        ];
    }

    protected function buildPurchaseParameters(PaymentRequest $request): array
    {
        return $this->buildBaseParameters($request, 'Sale');
    }

    protected function buildPreAuthParameters(PaymentRequest $request): array
    {
        return $this->buildBaseParameters($request, 'Auth');
    }

    protected function buildPostAuthParameters(string $transactionId, Money $amount): array
    {
        return [
            'MerchantId' => $this->config['merchant_id'],
            'CustomerId' => $this->config['customer_id'],
            'UserName' => $this->config['username'],
            'TransactionType' => 'SaleCompletion',
            'MerchantOrderId' => $transactionId,
            'Amount' => (string) $amount->toMinor(),
            'CurrencyCode' => $amount->currency->value,
        ];
    }

    protected function buildRefundParameters(RefundRequest $request): array
    {
        return [
            'MerchantId' => $this->config['merchant_id'],
            'CustomerId' => $this->config['customer_id'],
            'UserName' => $this->config['username'],
            'TransactionType' => 'Drawback',
            'MerchantOrderId' => $request->orderId,
            'Amount' => (string) $request->amount->toMinor(),
            'CurrencyCode' => $request->amount->currency->value,
        ];
    }

    protected function buildCancelParameters(CancelRequest $request): array
    {
        return [
            'MerchantId' => $this->config['merchant_id'],
            'CustomerId' => $this->config['customer_id'],
            'UserName' => $this->config['username'],
            'TransactionType' => 'SaleReversal',
            'MerchantOrderId' => $request->orderId,
        ];
    }

    protected function buildStatusParameters(StatusRequest $request): array
    {
        return [
            'MerchantId' => $this->config['merchant_id'],
            'CustomerId' => $this->config['customer_id'],
            'UserName' => $this->config['username'],
            'TransactionType' => 'GetMerchantOrderDetail',
            'MerchantOrderId' => $request->orderId,
        ];
    }

    protected function buildThreeDFormParameters(PaymentRequest $request): array
    {
        return [
            'MerchantId' => $this->config['merchant_id'],
            'CustomerId' => $this->config['customer_id'],
            'UserName' => $this->config['username'],
            'TransactionType' => $request->type->value === 'pre_auth' ? 'Auth' : 'Sale',
            'MerchantOrderId' => $request->orderId,
            'Amount' => (string) $request->amount->toMinor(),
            'CurrencyCode' => $request->amount->currency->value,
            'InstallmentCount' => $request->hasInstallment() ? (string) $request->installment : '0',
            'CardNumber' => $request->card?->number ?? '',
            'CardExpireDateMonth' => $request->card ? str_pad($request->card->expiryMonth, 2, '0', STR_PAD_LEFT) : '',
            'CardExpireDateYear' => $request->card ? substr($request->card->expiryYear, -2) : '',
            'CardCVV2' => $request->card?->cvv ?? '',
            'CardHolderName' => $request->card?->holderName ?? '',
            'OkUrl' => $request->callbackUrl ?? $this->config['callback_url'] ?? '',
            'FailUrl' => $request->callbackUrl ?? $this->config['callback_url'] ?? '',
        ];
    }

    protected function buildThreeDProvisionParameters(array $callbackData): array
    {
        return [
            'MerchantId' => $this->config['merchant_id'],
            'CustomerId' => $this->config['customer_id'],
            'UserName' => $this->config['username'],
            'TransactionType' => 'Sale',
            'MerchantOrderId' => $callbackData['MerchantOrderId'] ?? '',
            'Amount' => $callbackData['Amount'] ?? '',
            'MD' => $callbackData['MD'] ?? '',
        ];
    }

    protected function applyHash(array $parameters, string $hash): array
    {
        $parameters['HashData'] = $hash;

        return $parameters;
    }

    protected function credentials(): array
    {
        return [
            'merchantId' => $this->config['merchant_id'],
            'customerId' => $this->config['customer_id'],
            'username' => $this->config['username'],
            'password' => $this->config['password'],
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
        return $callbackData['ResponseCode'] ?? null;
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
        $hash = $callbackData['HashData'] ?? '';
        if (empty($hash)) {
            return false;
        }

        return $this->hashGenerator->verify($hash, $callbackData, $this->credentials());
    }

    private function buildBaseParameters(PaymentRequest $request, string $txnType): array
    {
        return [
            'MerchantId' => $this->config['merchant_id'],
            'CustomerId' => $this->config['customer_id'],
            'UserName' => $this->config['username'],
            'TransactionType' => $txnType,
            'MerchantOrderId' => $request->orderId,
            'Amount' => (string) $request->amount->toMinor(),
            'CurrencyCode' => $request->amount->currency->value,
            'InstallmentCount' => $request->hasInstallment() ? (string) $request->installment : '0',
            'CardNumber' => $request->card?->number ?? '',
            'CardExpireDateMonth' => $request->card ? str_pad($request->card->expiryMonth, 2, '0', STR_PAD_LEFT) : '',
            'CardExpireDateYear' => $request->card ? substr($request->card->expiryYear, -2) : '',
            'CardCVV2' => $request->card?->cvv ?? '',
            'CardHolderName' => $request->card?->holderName ?? '',
        ];
    }
}
