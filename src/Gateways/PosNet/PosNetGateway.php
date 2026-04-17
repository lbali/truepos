<?php

declare(strict_types=1);

namespace TruePos\Gateways\PosNet;

use TruePos\DataTransferObjects\CancelRequest;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\RefundRequest;
use TruePos\DataTransferObjects\StatusRequest;
use TruePos\Enums\Gateway;
use TruePos\Enums\PaymentModel;
use TruePos\Gateways\AbstractGateway;
use TruePos\ValueObjects\Money;

/**
 * Yapı Kredi PosNet gateway.
 *
 * Key differences from NestPay/Garanti:
 * - Amount is in minor units (kuruş) as string, no decimal point
 * - Expiry format is YYMM (not MMYY)
 * - OrderID must be exactly 20 chars (XXXXXXXXXXYYYYYYYYYY format)
 * - Uses <sale>, <capt>, <return>, <reverse> XML elements instead of Type field
 * - Transaction ID is called "hostlogkey"
 * - Currency codes: "TL", "US", "EU" (not ISO 4217 numeric)
 */
final class PosNetGateway extends AbstractGateway
{
    public function gateway(): Gateway
    {
        return Gateway::PosNet;
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
            'mid' => $this->config['merchant_id'],
            'tid' => $this->config['terminal_id'],
            'sale' => [
                'orderID' => $this->formatOrderId($request->orderId),
                'amount' => $request->amount->toMinorString(),
                'currencyCode' => $this->currencyCode($request->amount),
                'ccno' => $request->card?->number ?? '',
                'expDate' => $request->card?->expiryFormatYYMM() ?? '',
                'cvc' => $request->card?->cvv ?? '',
                'installment' => $request->hasInstallment() ? str_pad((string) $request->installment, 2, '0', STR_PAD_LEFT) : '00',
            ],
        ];
    }

    protected function buildPreAuthParameters(PaymentRequest $request): array
    {
        return [
            'mid' => $this->config['merchant_id'],
            'tid' => $this->config['terminal_id'],
            'auth' => [
                'orderID' => $this->formatOrderId($request->orderId),
                'amount' => $request->amount->toMinorString(),
                'currencyCode' => $this->currencyCode($request->amount),
                'ccno' => $request->card?->number ?? '',
                'expDate' => $request->card?->expiryFormatYYMM() ?? '',
                'cvc' => $request->card?->cvv ?? '',
                'installment' => $request->hasInstallment() ? str_pad((string) $request->installment, 2, '0', STR_PAD_LEFT) : '00',
            ],
        ];
    }

    protected function buildPostAuthParameters(string $transactionId, Money $amount): array
    {
        return [
            'mid' => $this->config['merchant_id'],
            'tid' => $this->config['terminal_id'],
            'capt' => [
                'hostLogKey' => $transactionId,
                'amount' => $amount->toMinorString(),
                'currencyCode' => $this->currencyCode($amount),
                'installment' => '00',
            ],
        ];
    }

    protected function buildRefundParameters(RefundRequest $request): array
    {
        return [
            'mid' => $this->config['merchant_id'],
            'tid' => $this->config['terminal_id'],
            'return' => [
                'hostLogKey' => $request->transactionId ?? '',
                'amount' => $request->amount->toMinorString(),
                'currencyCode' => $this->currencyCode($request->amount),
            ],
        ];
    }

    protected function buildCancelParameters(CancelRequest $request): array
    {
        return [
            'mid' => $this->config['merchant_id'],
            'tid' => $this->config['terminal_id'],
            'reverse' => [
                'transaction' => 'sale',
                'hostLogKey' => $request->transactionId ?? '',
            ],
        ];
    }

    protected function buildStatusParameters(StatusRequest $request): array
    {
        return [
            'mid' => $this->config['merchant_id'],
            'tid' => $this->config['terminal_id'],
            'agreement' => [
                'orderID' => $this->formatOrderId($request->orderId),
            ],
        ];
    }

    protected function buildThreeDFormParameters(PaymentRequest $request): array
    {
        $xid = $this->formatOrderId($request->orderId);

        return [
            'mid' => $this->config['merchant_id'],
            'posnetID' => $this->config['posnet_id'],
            'posnetData' => [
                'XID' => $xid,
                'Amount' => $request->amount->toMinorString(),
                'CurrencyCode' => $this->currencyCode($request->amount),
                'InstallmentCount' => $request->hasInstallment() ? str_pad((string) $request->installment, 2, '0', STR_PAD_LEFT) : '00',
                'TranType' => $request->type->value === 'pre_auth' ? 'Auth' : 'Sale',
                'CardNo' => $request->card?->number ?? '',
                'ExpiredDate' => $request->card?->expiryFormatYYMM() ?? '',
                'Cvv' => $request->card?->cvv ?? '',
                'CardHolderName' => $request->card?->holderName ?? '',
            ],
            'merchantReturnURL' => $request->callbackUrl ?? $this->config['callback_url'] ?? '',
            'openANewWindow' => '0',
        ];
    }

    protected function buildThreeDProvisionParameters(array $callbackData): array
    {
        return [
            'mid' => $this->config['merchant_id'],
            'tid' => $this->config['terminal_id'],
            'oosTranData' => [
                'bankData' => $callbackData['BankPacket'] ?? '',
                'merchantData' => $callbackData['MerchantPacket'] ?? '',
                'sign' => $callbackData['Sign'] ?? '',
                'wpAmount' => '0',
                'mac' => $this->generateOosMac($callbackData),
            ],
        ];
    }

    protected function applyHash(array $parameters, string $hash): array
    {
        // PosNet hash is applied differently based on context
        if (isset($parameters['posnetData'])) {
            $parameters['posnetData']['MacCode'] = $hash;
        } else {
            $parameters['mac'] = $hash;
        }

        return $parameters;
    }

    protected function credentials(): array
    {
        return [
            'merchantId' => $this->config['merchant_id'],
            'terminalId' => $this->config['terminal_id'],
            'posnetId' => $this->config['posnet_id'],
            'encKey' => $this->config['enc_key'],
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
        return $callbackData['mdStatus'] ?? $callbackData['MdStatus'] ?? null;
    }

    protected function isThreeDAuthSuccessful(?string $mdStatus): bool
    {
        return in_array($mdStatus, ['1', '2', '3', '4'], true);
    }

    protected function requiresProvisionAfterThreeD(): bool
    {
        return true;
    }

    /**
     * Callback payload sanity check (NOT cryptographic verification).
     *
     * PosNet's 3DS callback contains encrypted MerchantPacket, BankPacket, and Sign
     * fields that can only be decrypted/verified by the PosNet API itself during the
     * provision step (oosTranData). This method validates that all required fields
     * are present. The actual cryptographic verification happens server-to-server
     * when completeThreeD() calls buildThreeDProvisionParameters() → executeTransaction(),
     * because requiresProvisionAfterThreeD() returns true.
     *
     * @param  array<string, mixed>  $callbackData
     */
    public function verifyThreeDCallback(array $callbackData): bool
    {
        $merchantPacket = $callbackData['MerchantPacket'] ?? '';
        $bankPacket = $callbackData['BankPacket'] ?? '';
        $sign = $callbackData['Sign'] ?? '';

        if ($merchantPacket === '' || $bankPacket === '' || $sign === '') {
            return false;
        }

        return true;
    }

    // ─── Private helpers ─────────────────────────────────────

    /**
     * PosNet requires OrderID to be exactly 20 characters.
     * Pad with zeros on the left if shorter.
     */
    private function formatOrderId(string $orderId): string
    {
        // Remove non-alphanumeric chars
        $clean = preg_replace('/[^a-zA-Z0-9]/', '', $orderId) ?? $orderId;

        if (strlen($clean) > 20) {
            return substr($clean, 0, 20);
        }

        return str_pad($clean, 20, '0', STR_PAD_LEFT);
    }

    /**
     * PosNet uses text currency codes, not ISO 4217 numeric.
     */
    private function currencyCode(Money $money): string
    {
        return match ($money->currency->value) {
            '949' => 'TL',
            '840' => 'US',
            '978' => 'EU',
            '826' => 'GB',
        };
    }

    /**
     * @param  array<string, mixed>  $callbackData
     */
    private function generateOosMac(array $callbackData): string
    {
        $encKey = $this->config['enc_key'] ?? '';

        $hashStr = ($callbackData['MerchantPacket'] ?? '')
            . ($callbackData['BankPacket'] ?? '')
            . ($callbackData['Sign'] ?? '')
            . $encKey;

        return base64_encode(hash('sha256', $hashStr, true));
    }
}
