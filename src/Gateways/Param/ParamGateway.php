<?php

declare(strict_types=1);

namespace TruePos\Gateways\Param;

use TruePos\DataTransferObjects\CancelRequest;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\RefundRequest;
use TruePos\DataTransferObjects\StatusRequest;
use TruePos\Enums\Gateway;
use TruePos\Enums\PaymentModel;
use TruePos\Gateways\AbstractGateway;
use TruePos\ValueObjects\Money;

/**
 * Param (eski Türkpos) payment facilitator gateway.
 *
 * REST JSON API. Uses CLIENT_CODE + CLIENT_USERNAME + CLIENT_PASSWORD + GUID.
 * Amount is decimal comma-separated in legacy API ("100,50") but dot in new API ("100.50").
 * We use the new REST API with dot-separated amounts.
 */
final class ParamGateway extends AbstractGateway
{
    public function gateway(): Gateway
    {
        return Gateway::Param;
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
            'CLIENT_CODE' => $this->config['client_code'],
            'CLIENT_USERNAME' => $this->config['client_username'],
            'CLIENT_PASSWORD' => $this->config['client_password'],
            'GUID' => $this->config['guid'],
            'KK_Sahibi' => $request->card?->holderName ?? '',
            'KK_No' => $request->card?->number ?? '',
            'KK_SK_Ay' => $request->card ? str_pad($request->card->expiryMonth, 2, '0', STR_PAD_LEFT) : '',
            'KK_SK_Yil' => $request->card ? (strlen($request->card->expiryYear) === 2 ? '20' . $request->card->expiryYear : $request->card->expiryYear) : '',
            'KK_CVC' => $request->card?->cvv ?? '',
            'Tutar' => $request->amount->toDecimal(),
            'Taksit' => $request->hasInstallment() ? (string) $request->installment : '1',
            'SiparisID' => $request->orderId,
            'IPAdr' => $request->customer?->ip ?? '',
            'KullaniciAdi' => $request->customer?->email ?? '',
            'Islem_Guvenlik_Tip' => 'NS', // NonSecure
        ];
    }

    protected function buildPreAuthParameters(PaymentRequest $request): array
    {
        $params = $this->buildPurchaseParameters($request);
        $params['Islem_Tip'] = 'PREAUTH';

        return $params;
    }

    protected function buildPostAuthParameters(string $transactionId, Money $amount): array
    {
        return [
            'CLIENT_CODE' => $this->config['client_code'],
            'CLIENT_USERNAME' => $this->config['client_username'],
            'CLIENT_PASSWORD' => $this->config['client_password'],
            'GUID' => $this->config['guid'],
            'Dekont_ID' => $transactionId,
            'Tutar' => $amount->toDecimal(),
        ];
    }

    protected function buildRefundParameters(RefundRequest $request): array
    {
        return [
            'CLIENT_CODE' => $this->config['client_code'],
            'CLIENT_USERNAME' => $this->config['client_username'],
            'CLIENT_PASSWORD' => $this->config['client_password'],
            'GUID' => $this->config['guid'],
            'Dekont_ID' => $request->transactionId ?? '',
            'Tutar' => $request->amount->toDecimal(),
        ];
    }

    protected function buildCancelParameters(CancelRequest $request): array
    {
        return [
            'CLIENT_CODE' => $this->config['client_code'],
            'CLIENT_USERNAME' => $this->config['client_username'],
            'CLIENT_PASSWORD' => $this->config['client_password'],
            'GUID' => $this->config['guid'],
            'Dekont_ID' => $request->transactionId ?? '',
        ];
    }

    protected function buildStatusParameters(StatusRequest $request): array
    {
        return [
            'CLIENT_CODE' => $this->config['client_code'],
            'CLIENT_USERNAME' => $this->config['client_username'],
            'CLIENT_PASSWORD' => $this->config['client_password'],
            'GUID' => $this->config['guid'],
            'SiparisID' => $request->orderId,
        ];
    }

    protected function buildThreeDFormParameters(PaymentRequest $request): array
    {
        $params = $this->buildPurchaseParameters($request);
        $params['Islem_Guvenlik_Tip'] = '3D';
        $params['Basarili_URL'] = $request->callbackUrl ?? $this->config['callback_url'] ?? '';
        $params['Hata_URL'] = $request->callbackUrl ?? $this->config['callback_url'] ?? '';

        return $params;
    }

    protected function buildThreeDProvisionParameters(array $callbackData): array
    {
        return [
            'CLIENT_CODE' => $this->config['client_code'],
            'CLIENT_USERNAME' => $this->config['client_username'],
            'CLIENT_PASSWORD' => $this->config['client_password'],
            'GUID' => $this->config['guid'],
            'Dekont_ID' => $callbackData['Dekont_ID'] ?? $callbackData['TURKPOS_RETVAL_Dekont_ID'] ?? '',
            'Siparis_ID' => $callbackData['Siparis_ID'] ?? $callbackData['TURKPOS_RETVAL_Siparis_ID'] ?? '',
            'MD' => $callbackData['MD'] ?? $callbackData['md'] ?? '',
        ];
    }

    protected function applyHash(array $parameters, string $hash): array
    {
        $parameters['Islem_Hash'] = $hash;

        return $parameters;
    }

    protected function credentials(): array
    {
        return [
            'clientCode' => $this->config['client_code'],
            'guid' => $this->config['guid'],
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
        $result = $callbackData['Sonuc'] ?? $callbackData['TURKPOS_RETVAL_Sonuc'] ?? '0';

        return (string) $result;
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
        $hash = $callbackData['Islem_Hash'] ?? '';
        if (empty($hash)) {
            return ! empty($callbackData['Dekont_ID'] ?? $callbackData['TURKPOS_RETVAL_Dekont_ID'] ?? '');
        }

        return $this->hashGenerator->verify($hash, $callbackData, $this->credentials());
    }
}
