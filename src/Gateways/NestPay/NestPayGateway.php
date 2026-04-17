<?php

declare(strict_types=1);

namespace TruePos\Gateways\NestPay;

use TruePos\DataTransferObjects\CancelRequest;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\RefundRequest;
use TruePos\DataTransferObjects\StatusRequest;
use TruePos\Enums\Gateway;
use TruePos\Enums\PaymentModel;
use TruePos\Gateways\AbstractGateway;
use TruePos\ValueObjects\Money;

/**
 * NestPay (EST) gateway — used by İş Bankası, Akbank, Halkbank,
 * Ziraat Bankası, TEB, Denizbank, Anadolubank, ING.
 *
 * All these banks share the same XML protocol; only the endpoints
 * and credentials differ (configured via truepos.php).
 */
final class NestPayGateway extends AbstractGateway
{
    public function gateway(): Gateway
    {
        return Gateway::NestPay;
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

    // ─── Abstract hook implementations ───────────────────────

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
            'Name' => $this->config['username'],
            'Password' => $this->config['password'],
            'ClientId' => $this->config['client_id'],
            'Type' => 'PostAuth',
            'TransId' => $transactionId,
            'Total' => $amount->toDecimal(),
            'Currency' => $amount->currency->value,
        ];
    }

    protected function buildRefundParameters(RefundRequest $request): array
    {
        return [
            'Name' => $this->config['username'],
            'Password' => $this->config['password'],
            'ClientId' => $this->config['client_id'],
            'Type' => 'Credit',
            'OrderId' => $request->orderId,
            'Total' => $request->amount->toDecimal(),
            'Currency' => $request->amount->currency->value,
        ];
    }

    protected function buildCancelParameters(CancelRequest $request): array
    {
        return [
            'Name' => $this->config['username'],
            'Password' => $this->config['password'],
            'ClientId' => $this->config['client_id'],
            'Type' => 'Void',
            'OrderId' => $request->orderId,
        ];
    }

    protected function buildStatusParameters(StatusRequest $request): array
    {
        return [
            'Name' => $this->config['username'],
            'Password' => $this->config['password'],
            'ClientId' => $this->config['client_id'],
            'OrderId' => $request->orderId,
            'Extra' => [
                'ORDERDETAIL' => 'QUERY',
            ],
        ];
    }

    protected function buildThreeDFormParameters(PaymentRequest $request): array
    {
        $rnd = microtime();

        return [
            'clientid' => $this->config['client_id'],
            'storetype' => $this->resolveStoreType($request->paymentModel),
            'amount' => $request->amount->toDecimal(),
            'currency' => $request->amount->currency->value,
            'oid' => $request->orderId,
            'okUrl' => $request->callbackUrl ?? $this->config['callback_url'] ?? '',
            'failUrl' => $request->callbackUrl ?? $this->config['callback_url'] ?? '',
            'TranType' => $request->type->value === 'pre_auth' ? 'PreAuth' : 'Auth',
            'Instalment' => $request->installment > 1 ? (string) $request->installment : '',
            'rnd' => $rnd,
            'lang' => $this->config['lang'] ?? 'tr',
            'cardnumber' => $request->card?->number ?? '',
            'cardexpiredatemonth' => $request->card?->expiryMonth ?? '',
            'cardexpiredateyear' => $request->card?->expiryYear ?? '',
            'cardcvv2' => $request->card?->cvv ?? '',
        ];
    }

    protected function buildThreeDProvisionParameters(array $callbackData): array
    {
        return [
            'Name' => $this->config['username'],
            'Password' => $this->config['password'],
            'ClientId' => $this->config['client_id'],
            'Type' => 'Auth',
            'OrderId' => $callbackData['oid'] ?? '',
            'Total' => $callbackData['amount'] ?? '',
            'Currency' => $callbackData['currency'] ?? '',
            'PayerTxnId' => $callbackData['PayerTxnId'] ?? '',
            'PayerSecurityLevel' => $callbackData['PayerSecurityLevel'] ?? '',
            'PayerAuthenticationCode' => $callbackData['PayerAuthenticationCode'] ?? '',
            'CardholderPresentCode' => $callbackData['CardholderPresentCode'] ?? '13',
            'Number' => $callbackData['md'] ?? '',
        ];
    }

    protected function applyHash(array $parameters, string $hash): array
    {
        $parameters['hash'] = $hash;
        $parameters['encoding'] = 'utf-8';

        return $parameters;
    }

    protected function credentials(): array
    {
        return [
            'storeKey' => $this->config['store_key'],
            'clientId' => $this->config['client_id'],
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
        return $callbackData['mdStatus'] ?? null;
    }

    protected function isThreeDAuthSuccessful(?string $mdStatus): bool
    {
        // mdStatus values: 1 = full auth, 2 = card not enrolled (attempt),
        // 3 = bank not participating, 4 = attempt, 5-9 = failures
        return in_array($mdStatus, ['1', '2', '3', '4'], true);
    }

    protected function requiresProvisionAfterThreeD(): bool
    {
        // In NestPay 3D model, after bank auth, we must call the API to charge.
        // In 3D Pay model, the charge happens during redirect, no second call needed.
        $storeType = $this->config['store_type'] ?? '3d';

        return $storeType === '3d';
    }

    // ─── Private helpers ─────────────────────────────────────

    public function verifyThreeDCallback(array $callbackData): bool
    {
        $hashParams = $callbackData['HASHPARAMS'] ?? '';
        $hashParamsVal = $callbackData['HASHPARAMSVAL'] ?? '';
        $hash = $callbackData['HASH'] ?? '';

        if (empty($hash) || empty($hashParams)) {
            return false;
        }

        // Build the hash string from the specified parameters
        $params = explode(':', $hashParams);
        $hashStr = '';
        foreach ($params as $param) {
            $hashStr .= $callbackData[$param] ?? '';
        }
        $hashStr .= $this->config['store_key'];

        $calculated = base64_encode(hash('sha512', $hashStr, true));

        return hash_equals($calculated, $hash);
    }

    private function buildBaseParameters(PaymentRequest $request, string $type): array
    {
        $params = [
            'Name' => $this->config['username'],
            'Password' => $this->config['password'],
            'ClientId' => $this->config['client_id'],
            'Type' => $type,
            'IPAddress' => $request->customer?->ip ?? '',
            'Email' => $request->customer?->email ?? '',
            'OrderId' => $request->orderId,
            'Total' => $request->amount->toDecimal(),
            'Currency' => $request->amount->currency->value,
            'Number' => $request->card?->number ?? '',
            'Expires' => $request->card?->expiryFormatMMYY() ?? '',
            'Cvv2Val' => $request->card?->cvv ?? '',
        ];

        if ($request->hasInstallment()) {
            $params['Taksit'] = (string) $request->installment;
        }

        return $params;
    }

    private function resolveStoreType(PaymentModel $model): string
    {
        return match ($model) {
            PaymentModel::ThreeD => '3d',
            PaymentModel::ThreeDPay => '3d_pay',
            PaymentModel::ThreeDHost => '3d_pay_hosting',
            default => '3d',
        };
    }
}
