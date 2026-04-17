<?php

declare(strict_types=1);

namespace TruePos\Gateways\Moka;

use TruePos\DataTransferObjects\CancelRequest;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\RefundRequest;
use TruePos\DataTransferObjects\StatusRequest;
use TruePos\Enums\Gateway;
use TruePos\Enums\PaymentModel;
use TruePos\Gateways\AbstractGateway;
use TruePos\ValueObjects\Money;

/**
 * Moka payment gateway.
 *
 * REST JSON API. Uses DealerCode + Username + Password authentication.
 * CheckKey = SHA-256(DealerCode + "MK" + Username + "PD" + Password)
 * Amount is decimal string (e.g., "100.50").
 */
final class MokaGateway extends AbstractGateway
{
    public function gateway(): Gateway
    {
        return Gateway::Moka;
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
            'PaymentDealerAuthentication' => $this->authBlock(),
            'PaymentDealerRequest' => [
                'CardHolderFullName' => $request->card?->holderName ?? '',
                'CardNumber' => $request->card?->number ?? '',
                'ExpMonth' => $request->card ? str_pad($request->card->expiryMonth, 2, '0', STR_PAD_LEFT) : '',
                'ExpYear' => $request->card ? (strlen($request->card->expiryYear) === 2 ? '20' . $request->card->expiryYear : $request->card->expiryYear) : '',
                'CvcNumber' => $request->card?->cvv ?? '',
                'Amount' => $request->amount->toDecimal(),
                'Currency' => 'TL',
                'InstallmentNumber' => $request->hasInstallment() ? $request->installment : 1,
                'ClientIP' => $request->customer?->ip ?? '',
                'OtherTrxCode' => $request->orderId,
                'IsPreAuth' => 0,
                'IsPoolPayment' => 0,
                'Software' => 'TruePos',
            ],
        ];
    }

    protected function buildPreAuthParameters(PaymentRequest $request): array
    {
        $params = $this->buildPurchaseParameters($request);
        $params['PaymentDealerRequest']['IsPreAuth'] = 1;

        return $params;
    }

    protected function buildPostAuthParameters(string $transactionId, Money $amount): array
    {
        return [
            'PaymentDealerAuthentication' => $this->authBlock(),
            'PaymentDealerRequest' => [
                'VirtualPosOrderId' => $transactionId,
                'Amount' => $amount->toDecimal(),
            ],
        ];
    }

    protected function buildRefundParameters(RefundRequest $request): array
    {
        return [
            'PaymentDealerAuthentication' => $this->authBlock(),
            'PaymentDealerRequest' => [
                'VirtualPosOrderId' => $request->transactionId ?? '',
                'Amount' => $request->amount->toDecimal(),
            ],
        ];
    }

    protected function buildCancelParameters(CancelRequest $request): array
    {
        return [
            'PaymentDealerAuthentication' => $this->authBlock(),
            'PaymentDealerRequest' => [
                'VirtualPosOrderId' => $request->transactionId ?? '',
                'VoidRefundReason' => 'OTHER',
            ],
        ];
    }

    protected function buildStatusParameters(StatusRequest $request): array
    {
        return [
            'PaymentDealerAuthentication' => $this->authBlock(),
            'PaymentDealerRequest' => [
                'OtherTrxCode' => $request->orderId,
            ],
        ];
    }

    protected function buildThreeDFormParameters(PaymentRequest $request): array
    {
        return [
            'PaymentDealerAuthentication' => $this->authBlock(),
            'PaymentDealerRequest' => [
                'CardHolderFullName' => $request->card?->holderName ?? '',
                'CardNumber' => $request->card?->number ?? '',
                'ExpMonth' => $request->card ? str_pad($request->card->expiryMonth, 2, '0', STR_PAD_LEFT) : '',
                'ExpYear' => $request->card ? (strlen($request->card->expiryYear) === 2 ? '20' . $request->card->expiryYear : $request->card->expiryYear) : '',
                'CvcNumber' => $request->card?->cvv ?? '',
                'Amount' => $request->amount->toDecimal(),
                'Currency' => 'TL',
                'InstallmentNumber' => $request->hasInstallment() ? $request->installment : 1,
                'ClientIP' => $request->customer?->ip ?? '',
                'OtherTrxCode' => $request->orderId,
                'IsPreAuth' => 0,
                'IsPoolPayment' => 0,
                'Software' => 'TruePos',
                'RedirectUrl' => $request->callbackUrl ?? $this->config['callback_url'] ?? '',
                'RedirectType' => 1,
            ],
        ];
    }

    protected function buildThreeDProvisionParameters(array $callbackData): array
    {
        return $callbackData;
    }

    protected function applyHash(array $parameters, string $hash): array
    {
        if (isset($parameters['PaymentDealerAuthentication'])) {
            $parameters['PaymentDealerAuthentication']['CheckKey'] = $hash;
        }

        return $parameters;
    }

    protected function credentials(): array
    {
        return [
            'dealerCode' => $this->config['dealer_code'],
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

    protected function extractMdStatus(array $callbackData): string
    {
        return ($callbackData['isSuccessful'] ?? '') === '1' ? 'success' : 'failed';
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
        $hash = $callbackData['hashValue'] ?? '';

        if (empty($hash)) {
            // Hash field is required for callback verification.
            // Without it, we cannot prove the callback is genuine.
            return false;
        }

        return $this->hashGenerator->verify($hash, $callbackData, $this->credentials());
    }

    /**
     * @return array<string, mixed>
     */
    private function authBlock(): array
    {
        return [
            'DealerCode' => $this->config['dealer_code'],
            'Username' => $this->config['username'],
            'Password' => $this->config['password'],
        ];
    }
}
