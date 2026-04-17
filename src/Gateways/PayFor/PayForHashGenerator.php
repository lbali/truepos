<?php

declare(strict_types=1);

namespace TruePos\Gateways\PayFor;

use TruePos\Contracts\HashGeneratorInterface;

/**
 * PayFor (QNB Finansbank) hash algorithm:
 * SHA-256 of MbrId + MerchantId + MerchantPass + TerminalNo + fields
 *
 * For 3DS: SHA-1(MbrId + OrderId + Amount + OkUrl + FailUrl + TxnType + InstallmentCount + Rnd + MerchantPass)
 */
final class PayForHashGenerator implements HashGeneratorInterface
{
    public function generate(array $parameters, array $credentials): string
    {
        $merchantPass = $credentials['merchantPass'] ?? '';

        // 3DS form hash
        if (isset($parameters['OkUrl'])) {
            $hashStr = ($parameters['MbrId'] ?? '5')
                . ($parameters['OrderId'] ?? '')
                . ($parameters['PurchAmount'] ?? '')
                . ($parameters['OkUrl'] ?? '')
                . ($parameters['FailUrl'] ?? '')
                . ($parameters['TxnType'] ?? '')
                . ($parameters['InstallmentCount'] ?? '')
                . ($parameters['Rnd'] ?? '')
                . $merchantPass;

            return base64_encode(hash('sha1', $hashStr, true));
        }

        // API transaction hash
        $hashStr = ($parameters['MbrId'] ?? '5')
            . ($parameters['MerchantId'] ?? $credentials['merchantId'] ?? '')
            . ($parameters['OrderId'] ?? '')
            . ($parameters['Amount'] ?? '')
            . $merchantPass;

        return base64_encode(hash('sha256', $hashStr, true));
    }

    public function verify(string $expected, array $parameters, array $credentials): bool
    {
        $calculated = $this->generate($parameters, $credentials);

        return hash_equals($expected, $calculated);
    }
}
