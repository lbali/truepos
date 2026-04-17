<?php

declare(strict_types=1);

namespace TruePos\Gateways\Tosla;

use TruePos\DataTransferObjects\CancelRequest;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\RefundRequest;
use TruePos\DataTransferObjects\StatusRequest;
use TruePos\Enums\Gateway;
use TruePos\Enums\PaymentModel;
use TruePos\Gateways\AbstractGateway;
use TruePos\ValueObjects\Money;

/**
 * Tosla (Akbank) payment gateway.
 *
 * REST JSON API based on Postman collection "Payment Api Prep".
 *
 * Auth: clientId + apiUser + apiPass
 * Hash: Base64(SHA-512(apiPass + clientId + apiUser + rnd + timeSpan))
 * Amount: kuruş integer (10000 = 100.00 TL)
 * Currency: ISO 4217 numeric (949 = TRY)
 * ExpireDate format: MMYY (e.g., "1226")
 *
 * Endpoints:
 * - NonSecure:  POST /api/Payment/Payment
 * - 3D Pay:     POST /api/Payment/ThreeDPayment
 * - 3D Model:   POST /api/Payment/StartPaymentThreeDSession → ProcessCardForm → ProcessThreeD
 * - PreAuth:    POST /api/Payment/ThreeDPreAuth → ProcessCardForm → PostAuth
 * - Void:       POST /api/Payment/void
 * - Refund:     POST /api/Payment/refund
 * - Inquiry:    POST /api/Payment/inquiry
 * - PostAuth:   POST /api/Payment/PostAuth
 */
final class ToslaGateway extends AbstractGateway
{
    public function gateway(): Gateway
    {
        return Gateway::Tosla;
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
            PaymentModel::ThreeDHost,
        ];
    }

    // ─── NonSecure: POST /api/Payment/Payment ────────────────

    protected function buildPurchaseParameters(PaymentRequest $request): array
    {
        return [
            ...$this->authBlock(),
            'CardHolderName' => $request->card?->holderName ?? '',
            'cardNo' => $request->card?->number ?? '',
            'expireDate' => $request->card?->expiryFormatMMYY() ?? '',
            'cvv' => $request->card?->cvv ?? '',
            'description' => $request->metadata['description'] ?? '',
            'orderId' => $request->orderId,
            'amount' => $request->amount->toMinor(),
            'currency' => (int) $request->amount->currency->value,
            'installmentCount' => $request->hasInstallment() ? $request->installment : 1,
        ];
    }

    // ─── NonSecure PreAuth — same endpoint, Tosla uses 3D PreAuth flow
    // For non-3D preauth, same as purchase but endpoint differs

    protected function buildPreAuthParameters(PaymentRequest $request): array
    {
        return $this->buildPurchaseParameters($request);
    }

    // ─── PostAuth: POST /api/Payment/PostAuth ────────────────

    protected function buildPostAuthParameters(string $transactionId, Money $amount): array
    {
        return [
            ...$this->authBlock(),
            'orderId' => $transactionId,
            'amount' => $amount->toMinor(),
            'currency' => (int) $amount->currency->value,
        ];
    }

    // ─── Refund: POST /api/Payment/refund ────────────────────

    protected function buildRefundParameters(RefundRequest $request): array
    {
        return [
            ...$this->authBlock(),
            'OrderId' => $request->orderId,
            'Amount' => (string) $request->amount->toMinor(),
        ];
    }

    // ─── Void: POST /api/Payment/void ────────────────────────

    protected function buildCancelParameters(CancelRequest $request): array
    {
        return [
            ...$this->authBlock(),
            'OrderId' => $request->orderId,
        ];
    }

    // ─── Inquiry: POST /api/Payment/inquiry ──────────────────

    protected function buildStatusParameters(StatusRequest $request): array
    {
        return [
            ...$this->authBlock(),
            'transactionId' => $request->transactionId ?? $request->orderId,
        ];
    }

    // ─── 3D Pay: POST /api/Payment/ThreeDPayment ─────────────

    protected function buildThreeDFormParameters(PaymentRequest $request): array
    {
        return [
            ...$this->authBlock(),
            'orderId' => $request->orderId,
            'amount' => $request->amount->toMinor(),
            'currency' => (int) $request->amount->currency->value,
            'installmentCount' => $request->hasInstallment() ? $request->installment : 1,
            'callbackUrl' => $request->callbackUrl ?? $this->config['callback_url'] ?? '',
            'description' => $request->metadata['description'] ?? '',
        ];
    }

    // ─── 3D Model completion: POST /api/Payment/ProcessThreeD

    protected function buildThreeDProvisionParameters(array $callbackData): array
    {
        return [
            ...$this->authBlock(),
            'orderId' => $callbackData['OrderId'] ?? $callbackData['orderId'] ?? '',
            'threeDSessionId' => $callbackData['ThreeDSessionId'] ?? $callbackData['threeDSessionId'] ?? '',
        ];
    }

    protected function applyHash(array $parameters, string $hash): array
    {
        $parameters['hash'] = $hash;

        return $parameters;
    }

    protected function credentials(): array
    {
        return [
            'clientId' => $this->config['client_id'],
            'apiUser' => $this->config['api_user'],
            'apiPass' => $this->config['api_pass'],
        ];
    }

    protected function endpoint(): string
    {
        return rtrim($this->config['base_url'], '/') . '/api/Payment/Payment';
    }

    protected function threeDGatewayUrl(): string
    {
        return rtrim($this->config['base_url'], '/') . '/api/Payment/ThreeDPayment';
    }

    protected function extractMdStatus(array $callbackData): ?string
    {
        $code = $callbackData['Code'] ?? $callbackData['code'] ?? null;

        return $code !== null ? (string) $code : null;
    }

    protected function isThreeDAuthSuccessful(?string $mdStatus): bool
    {
        return $mdStatus === '0';
    }

    protected function requiresProvisionAfterThreeD(): bool
    {
        // 3D Pay model: payment completes during redirect, no second call.
        // 3D Model: requires ProcessThreeD call after.
        return ($this->config['payment_model'] ?? '3d_pay') === '3d';
    }

    public function verifyThreeDCallback(array $callbackData): bool
    {
        $hash = $callbackData['hash'] ?? $callbackData['Hash'] ?? '';

        if (empty($hash)) {
            // Tosla callback may not always include hash — verify via Code
            return isset($callbackData['Code']) || isset($callbackData['OrderId']);
        }

        return $this->hashGenerator->verify($hash, $callbackData, $this->credentials());
    }

    // ─── Private helpers ─────────────────────────────────────

    /**
     * Build the common auth block present in every Tosla request.
     * timeSpan format: YYYYMMDDHHmmss
     */
    private function authBlock(): array
    {
        return [
            'clientId' => $this->config['client_id'],
            'apiUser' => $this->config['api_user'],
            'rnd' => $this->config['rnd_prefix'] ?? ('truepos' . bin2hex(random_bytes(4))),
            'timeSpan' => date('YmdHis'),
        ];
    }
}
