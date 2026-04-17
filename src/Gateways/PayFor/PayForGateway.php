<?php

declare(strict_types=1);

namespace TruePos\Gateways\PayFor;

use TruePos\DataTransferObjects\CancelRequest;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\RefundRequest;
use TruePos\DataTransferObjects\StatusRequest;
use TruePos\Enums\Gateway;
use TruePos\Enums\PaymentModel;
use TruePos\Gateways\AbstractGateway;
use TruePos\ValueObjects\Money;

/**
 * QNB Finansbank PayFor gateway.
 *
 * Uses MbrId (member ID, usually "5"), MerchantId, MerchantPass, UserCode, UserPass.
 * Amount format: decimal with dot separator (e.g., "100.00").
 */
final class PayForGateway extends AbstractGateway
{
    public function gateway(): Gateway
    {
        return Gateway::PayFor;
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
            PaymentModel::ThreeDPay,
        ];
    }

    protected function buildPurchaseParameters(PaymentRequest $request): array
    {
        return $this->buildBaseParameters($request, 'Auth');
    }

    protected function buildPreAuthParameters(PaymentRequest $request): array
    {
        return $this->buildBaseParameters($request, 'PreAuth');
    }

    protected function buildPostAuthParameters(string $transactionId, Money $amount): array
    {
        return [
            'MbrId' => $this->config['mbr_id'] ?? '5',
            'MerchantId' => $this->config['merchant_id'],
            'UserCode' => $this->config['user_code'],
            'UserPass' => $this->config['user_pass'],
            'TxnType' => 'PostAuth',
            'OrderId' => $transactionId,
            'Amount' => $amount->toDecimal(),
            'Currency' => $amount->currency->value,
        ];
    }

    protected function buildRefundParameters(RefundRequest $request): array
    {
        return [
            'MbrId' => $this->config['mbr_id'] ?? '5',
            'MerchantId' => $this->config['merchant_id'],
            'UserCode' => $this->config['user_code'],
            'UserPass' => $this->config['user_pass'],
            'TxnType' => 'Refund',
            'OrderId' => $request->orderId,
            'Amount' => $request->amount->toDecimal(),
            'Currency' => $request->amount->currency->value,
        ];
    }

    protected function buildCancelParameters(CancelRequest $request): array
    {
        return [
            'MbrId' => $this->config['mbr_id'] ?? '5',
            'MerchantId' => $this->config['merchant_id'],
            'UserCode' => $this->config['user_code'],
            'UserPass' => $this->config['user_pass'],
            'TxnType' => 'Void',
            'OrderId' => $request->orderId,
        ];
    }

    protected function buildStatusParameters(StatusRequest $request): array
    {
        return [
            'MbrId' => $this->config['mbr_id'] ?? '5',
            'MerchantId' => $this->config['merchant_id'],
            'UserCode' => $this->config['user_code'],
            'UserPass' => $this->config['user_pass'],
            'TxnType' => 'OrderInquiry',
            'OrderId' => $request->orderId,
        ];
    }

    protected function buildThreeDFormParameters(PaymentRequest $request): array
    {
        $rnd = microtime();

        return [
            'MbrId' => $this->config['mbr_id'] ?? '5',
            'MerchantId' => $this->config['merchant_id'],
            'UserCode' => $this->config['user_code'],
            'OrderId' => $request->orderId,
            'PurchAmount' => $request->amount->toDecimal(),
            'Currency' => $request->amount->currency->value,
            'TxnType' => $request->type->value === 'pre_auth' ? 'PreAuth' : 'Auth',
            'InstallmentCount' => $request->hasInstallment() ? (string) $request->installment : '0',
            'OkUrl' => $request->callbackUrl ?? $this->config['callback_url'] ?? '',
            'FailUrl' => $request->callbackUrl ?? $this->config['callback_url'] ?? '',
            'Rnd' => $rnd,
            'CardHolderName' => $request->card?->holderName ?? '',
            'Pan' => $request->card?->number ?? '',
            'Expiry' => $request->card?->expiryFormatMMYY() ?? '',
            'Cvv2' => $request->card?->cvv ?? '',
            'Lang' => $this->config['lang'] ?? 'TR',
        ];
    }

    protected function buildThreeDProvisionParameters(array $callbackData): array
    {
        return [
            'MbrId' => $this->config['mbr_id'] ?? '5',
            'MerchantId' => $this->config['merchant_id'],
            'UserCode' => $this->config['user_code'],
            'UserPass' => $this->config['user_pass'],
            'TxnType' => $callbackData['TxnType'] ?? 'Auth',
            'OrderId' => $callbackData['OrderId'] ?? '',
            'Amount' => $callbackData['PurchAmount'] ?? '',
            'Currency' => $callbackData['Currency'] ?? '949',
            'SecureType' => 'NonSecure',
            'Eci' => $callbackData['Eci'] ?? '',
            'Cavv' => $callbackData['Cavv'] ?? '',
            'MdStatus' => $callbackData['3DStatus'] ?? '',
        ];
    }

    protected function applyHash(array $parameters, string $hash): array
    {
        $parameters['Hash'] = $hash;

        return $parameters;
    }

    protected function credentials(): array
    {
        return [
            'merchantId' => $this->config['merchant_id'],
            'merchantPass' => $this->config['merchant_pass'],
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
        return $callbackData['3DStatus'] ?? null;
    }

    protected function isThreeDAuthSuccessful(?string $mdStatus): bool
    {
        return $mdStatus === '1';
    }

    protected function requiresProvisionAfterThreeD(): bool
    {
        return true;
    }

    public function verifyThreeDCallback(array $callbackData): bool
    {
        $hash = $callbackData['Hash'] ?? '';
        if (empty($hash)) {
            return false;
        }

        return $this->hashGenerator->verify($hash, $callbackData, $this->credentials());
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBaseParameters(PaymentRequest $request, string $type): array
    {
        $params = [
            'MbrId' => $this->config['mbr_id'] ?? '5',
            'MerchantId' => $this->config['merchant_id'],
            'UserCode' => $this->config['user_code'],
            'UserPass' => $this->config['user_pass'],
            'TxnType' => $type,
            'OrderId' => $request->orderId,
            'Amount' => $request->amount->toDecimal(),
            'Currency' => $request->amount->currency->value,
            'SecureType' => 'NonSecure',
            'Pan' => $request->card?->number ?? '',
            'Expiry' => $request->card?->expiryFormatMMYY() ?? '',
            'Cvv2' => $request->card?->cvv ?? '',
            'IPAddress' => $request->customer?->ip ?? '',
        ];

        if ($request->hasInstallment()) {
            $params['InstallmentCount'] = (string) $request->installment;
        }

        return $params;
    }
}
