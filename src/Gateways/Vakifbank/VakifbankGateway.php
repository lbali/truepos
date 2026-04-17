<?php

declare(strict_types=1);

namespace TruePos\Gateways\Vakifbank;

use TruePos\DataTransferObjects\CancelRequest;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\RefundRequest;
use TruePos\DataTransferObjects\StatusRequest;
use TruePos\Enums\Gateway;
use TruePos\Enums\PaymentModel;
use TruePos\Gateways\AbstractGateway;
use TruePos\ValueObjects\Money;

/**
 * Vakıfbank VPOS 7/24 gateway.
 *
 * Uses MerchantId, MerchantPassword, TerminalNo.
 * Amount is decimal (e.g., "100.00").
 * 3DS flow uses SuccessUrl/FailUrl with auto-POST back.
 */
final class VakifbankGateway extends AbstractGateway
{
    public function gateway(): Gateway
    {
        return Gateway::Vakifbank;
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
            'Password' => $this->config['merchant_pass'],
            'TerminalNo' => $this->config['terminal_no'],
            'TransactionType' => 'Capture',
            'ReferenceTransactionId' => $transactionId,
            'CurrencyAmount' => $amount->toDecimal(),
            'CurrencyCode' => $amount->currency->value,
        ];
    }

    protected function buildRefundParameters(RefundRequest $request): array
    {
        return [
            'MerchantId' => $this->config['merchant_id'],
            'Password' => $this->config['merchant_pass'],
            'TerminalNo' => $this->config['terminal_no'],
            'TransactionType' => 'Refund',
            'ReferenceTransactionId' => $request->transactionId ?? '',
            'ClientIp' => '127.0.0.1',
            'CurrencyAmount' => $request->amount->toDecimal(),
            'CurrencyCode' => $request->amount->currency->value,
        ];
    }

    protected function buildCancelParameters(CancelRequest $request): array
    {
        return [
            'MerchantId' => $this->config['merchant_id'],
            'Password' => $this->config['merchant_pass'],
            'TerminalNo' => $this->config['terminal_no'],
            'TransactionType' => 'Cancel',
            'ReferenceTransactionId' => $request->transactionId ?? '',
            'ClientIp' => '127.0.0.1',
        ];
    }

    protected function buildStatusParameters(StatusRequest $request): array
    {
        return [
            'MerchantId' => $this->config['merchant_id'],
            'Password' => $this->config['merchant_pass'],
            'TerminalNo' => $this->config['terminal_no'],
            'TransactionType' => 'OrderInquiry',
            'MerchantOrderId' => $request->orderId,
        ];
    }

    protected function buildThreeDFormParameters(PaymentRequest $request): array
    {
        return [
            'MerchantId' => $this->config['merchant_id'],
            'MerchantPassword' => $this->config['merchant_pass'],
            'TerminalNo' => $this->config['terminal_no'],
            'TransactionType' => $request->type->value === 'pre_auth' ? 'Auth' : 'Sale',
            'TransactionId' => $request->orderId,
            'CurrencyAmount' => $request->amount->toDecimal(),
            'CurrencyCode' => $request->amount->currency->value,
            'Pan' => $request->card?->number ?? '',
            'Expiry' => $request->card?->expiryFormatYYMM() ?? '',
            'Cvv' => $request->card?->cvv ?? '',
            'CardHoldersName' => $request->card?->holderName ?? '',
            'InstallmentCount' => $request->hasInstallment() ? (string) $request->installment : '0',
            'ClientIp' => $request->customer?->ip ?? '',
            'SuccessUrl' => $request->callbackUrl ?? $this->config['callback_url'] ?? '',
            'FailUrl' => $request->callbackUrl ?? $this->config['callback_url'] ?? '',
        ];
    }

    protected function buildThreeDProvisionParameters(array $callbackData): array
    {
        return [
            'MerchantId' => $this->config['merchant_id'],
            'Password' => $this->config['merchant_pass'],
            'TerminalNo' => $this->config['terminal_no'],
            'TransactionType' => 'Sale',
            'TransactionId' => $callbackData['TransactionId'] ?? '',
            'CurrencyAmount' => $callbackData['CurrencyAmount'] ?? '',
            'CurrencyCode' => $callbackData['CurrencyCode'] ?? '949',
            'ECI' => $callbackData['Eci'] ?? '',
            'CAVV' => $callbackData['Cavv'] ?? '',
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
            'merchantPass' => $this->config['merchant_pass'],
            'terminalNo' => $this->config['terminal_no'],
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
        return $callbackData['MdStatus'] ?? null;
    }

    protected function isThreeDAuthSuccessful(?string $mdStatus): bool
    {
        return in_array($mdStatus, ['1', '2', '3', '4'], true);
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
        $params = [
            'MerchantId' => $this->config['merchant_id'],
            'Password' => $this->config['merchant_pass'],
            'TerminalNo' => $this->config['terminal_no'],
            'TransactionType' => $txnType,
            'TransactionId' => $request->orderId,
            'CurrencyAmount' => $request->amount->toDecimal(),
            'CurrencyCode' => $request->amount->currency->value,
            'Pan' => $request->card?->number ?? '',
            'Expiry' => $request->card?->expiryFormatYYMM() ?? '',
            'Cvv' => $request->card?->cvv ?? '',
            'ClientIp' => $request->customer?->ip ?? '',
        ];

        if ($request->hasInstallment()) {
            $params['NumberOfInstallments'] = (string) $request->installment;
        }

        return $params;
    }
}
