<?php

declare(strict_types=1);

namespace TruePos\Gateways\Garanti;

use TruePos\DataTransferObjects\CancelRequest;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\RefundRequest;
use TruePos\DataTransferObjects\StatusRequest;
use TruePos\Enums\Gateway;
use TruePos\Enums\PaymentModel;
use TruePos\Gateways\AbstractGateway;
use TruePos\ValueObjects\Money;

/**
 * Garanti BBVA GVP gateway.
 *
 * Completely different XML structure from NestPay — nested elements,
 * different hash algorithm, different 3DS flow.
 */
final class GarantiGateway extends AbstractGateway
{
    public function gateway(): Gateway
    {
        return Gateway::Garanti;
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
        return $this->buildGvpRequest($request, 'sales');
    }

    protected function buildPreAuthParameters(PaymentRequest $request): array
    {
        return $this->buildGvpRequest($request, 'preauth');
    }

    protected function buildPostAuthParameters(string $transactionId, Money $amount): array
    {
        return [
            'Mode' => $this->mode(),
            'Version' => 'v0.01',
            'Terminal' => $this->terminalBlock(),
            'Customer' => ['IPAddress' => '', 'EmailAddress' => ''],
            'Card' => ['Number' => '', 'ExpireDate' => '', 'CVV2' => ''],
            'Order' => ['OrderID' => $transactionId],
            'Transaction' => [
                'Type' => 'postauth',
                'Amount' => $amount->toMinor(),
                'CurrencyCode' => $amount->currency->value,
                'InstallmentCnt' => '',
                'CardholderPresentCode' => '0',
                'MotoInd' => 'N',
            ],
        ];
    }

    protected function buildRefundParameters(RefundRequest $request): array
    {
        return [
            'Mode' => $this->mode(),
            'Version' => 'v0.01',
            'Terminal' => $this->terminalBlock(),
            'Customer' => ['IPAddress' => '', 'EmailAddress' => ''],
            'Card' => ['Number' => '', 'ExpireDate' => '', 'CVV2' => ''],
            'Order' => ['OrderID' => $request->orderId],
            'Transaction' => [
                'Type' => 'refund',
                'Amount' => $request->amount->toMinor(),
                'CurrencyCode' => $request->amount->currency->value,
                'InstallmentCnt' => '',
            ],
        ];
    }

    protected function buildCancelParameters(CancelRequest $request): array
    {
        return [
            'Mode' => $this->mode(),
            'Version' => 'v0.01',
            'Terminal' => $this->terminalBlock(),
            'Customer' => ['IPAddress' => '', 'EmailAddress' => ''],
            'Card' => ['Number' => '', 'ExpireDate' => '', 'CVV2' => ''],
            'Order' => ['OrderID' => $request->orderId],
            'Transaction' => [
                'Type' => 'void',
                'Amount' => '1',
                'CurrencyCode' => '949',
                'InstallmentCnt' => '',
            ],
        ];
    }

    protected function buildStatusParameters(StatusRequest $request): array
    {
        return [
            'Mode' => $this->mode(),
            'Version' => 'v0.01',
            'Terminal' => $this->terminalBlock(),
            'Customer' => ['IPAddress' => '', 'EmailAddress' => ''],
            'Card' => ['Number' => '', 'ExpireDate' => '', 'CVV2' => ''],
            'Order' => ['OrderID' => $request->orderId],
            'Transaction' => [
                'Type' => 'orderinq',
                'Amount' => '1',
                'CurrencyCode' => '949',
                'InstallmentCnt' => '',
            ],
        ];
    }

    protected function buildThreeDFormParameters(PaymentRequest $request): array
    {
        return [
            'mode' => $this->mode(),
            'apiversion' => 'v0.01',
            'terminalprovuserid' => $this->config['provision_user'] ?? 'PROVAUT',
            'terminaluserid' => $this->config['terminal_user'] ?? $this->config['provision_user'] ?? 'PROVAUT',
            'terminalmerchantid' => $this->config['merchant_id'],
            'terminalid' => $this->config['terminal_id'],
            'txntype' => $request->type->value === 'pre_auth' ? 'preauth' : 'sales',
            'txnamount' => (string) $request->amount->toMinor(),
            'txncurrencycode' => $request->amount->currency->value,
            'txninstallmentcount' => $request->installment > 1 ? str_pad((string) $request->installment, 2, '0', STR_PAD_LEFT) : '',
            'orderid' => $request->orderId,
            'successurl' => $request->callbackUrl ?? $this->config['callback_url'] ?? '',
            'errorurl' => $request->callbackUrl ?? $this->config['callback_url'] ?? '',
            'customeripaddress' => $request->customer?->ip ?? '',
            'customeremailaddress' => $request->customer?->email ?? '',
            'secure3dsecuritylevel' => '3D',
            'cardnumber' => $request->card?->number ?? '',
            'cardexpiredatemonth' => $request->card ? str_pad($request->card->expiryMonth, 2, '0', STR_PAD_LEFT) : '',
            'cardexpiredateyear' => $request->card ? substr($request->card->expiryYear, -2) : '',
            'cardcvv2' => $request->card?->cvv ?? '',
        ];
    }

    protected function buildThreeDProvisionParameters(array $callbackData): array
    {
        return [
            'Mode' => $this->mode(),
            'Version' => 'v0.01',
            'Terminal' => $this->terminalBlock(),
            'Customer' => [
                'IPAddress' => $callbackData['customeripaddress'] ?? '',
                'EmailAddress' => $callbackData['customeremailaddress'] ?? '',
            ],
            'Card' => [
                'Number' => $callbackData['md'] ?? '',
                'ExpireDate' => '',
                'CVV2' => '',
            ],
            'Order' => [
                'OrderID' => $callbackData['orderid'] ?? '',
            ],
            'Transaction' => [
                'Type' => $callbackData['txntype'] ?? 'sales',
                'Amount' => $callbackData['txnamount'] ?? '',
                'CurrencyCode' => $callbackData['txncurrencycode'] ?? '949',
                'InstallmentCnt' => $callbackData['txninstallmentcount'] ?? '',
                'CardholderPresentCode' => '13',
                'MotoInd' => 'N',
                'Secure3D' => [
                    'AuthenticationCode' => $callbackData['cavv'] ?? '',
                    'SecurityLevel' => $callbackData['eci'] ?? '',
                    'TxnID' => $callbackData['xid'] ?? '',
                    'Md' => $callbackData['md'] ?? '',
                ],
            ],
        ];
    }

    protected function applyHash(array $parameters, string $hash): array
    {
        // For 3DS form, hash goes as 'secure3dhash'
        if (isset($parameters['secure3dsecuritylevel'])) {
            $parameters['secure3dhash'] = $hash;

            return $parameters;
        }

        // For API calls, hash goes into Terminal block
        $parameters['Terminal']['HashData'] = $hash;

        return $parameters;
    }

    protected function credentials(): array
    {
        return [
            'terminalId' => $this->config['terminal_id'],
            'merchantId' => $this->config['merchant_id'],
            'provisionPassword' => $this->config['provision_password'],
            'storeKey' => $this->config['store_key'] ?? '',
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
        return $callbackData['mdstatus'] ?? null;
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
        $hash = $callbackData['secure3dhash'] ?? '';
        if (empty($hash)) {
            return false;
        }

        $terminalId = str_pad($this->config['terminal_id'], 9, '0', STR_PAD_LEFT);
        $storeKey = $this->config['store_key'] ?? '';
        $password = $this->config['provision_password'] ?? '';

        $securityData = strtoupper(hash('sha512', $password . $terminalId));

        $hashStr = $terminalId
            . ($callbackData['orderid'] ?? '')
            . ($callbackData['txnamount'] ?? '')
            . ($callbackData['successurl'] ?? '')
            . ($callbackData['errorurl'] ?? '')
            . ($callbackData['txntype'] ?? '')
            . ($callbackData['txninstallmentcount'] ?? '')
            . $storeKey
            . $securityData;

        $calculated = strtoupper(hash('sha512', $hashStr));

        return hash_equals($calculated, $hash);
    }

    // ─── Private helpers ─────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function terminalBlock(): array
    {
        return [
            'ProvUserID' => $this->config['provision_user'] ?? 'PROVAUT',
            'UserID' => $this->config['terminal_user'] ?? $this->config['provision_user'] ?? 'PROVAUT',
            'ID' => $this->config['terminal_id'],
            'MerchantID' => $this->config['merchant_id'],
        ];
    }

    private function mode(): string
    {
        return ($this->config['test_mode'] ?? false) ? 'TEST' : 'PROD';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildGvpRequest(PaymentRequest $request, string $txnType): array
    {
        return [
            'Mode' => $this->mode(),
            'Version' => 'v0.01',
            'Terminal' => $this->terminalBlock(),
            'Customer' => [
                'IPAddress' => $request->customer?->ip ?? '',
                'EmailAddress' => $request->customer?->email ?? '',
            ],
            'Card' => [
                'Number' => $request->card?->number ?? '',
                'ExpireDate' => $request->card?->expiryFormatMMYY() ?? '',
                'CVV2' => $request->card?->cvv ?? '',
            ],
            'Order' => [
                'OrderID' => $request->orderId,
            ],
            'Transaction' => [
                'Type' => $txnType,
                'Amount' => (string) $request->amount->toMinor(),
                'CurrencyCode' => $request->amount->currency->value,
                'InstallmentCnt' => $request->hasInstallment() ? (string) $request->installment : '',
                'CardholderPresentCode' => '0',
                'MotoInd' => 'N',
            ],
        ];
    }
}
