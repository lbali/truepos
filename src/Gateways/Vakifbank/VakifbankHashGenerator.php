<?php

declare(strict_types=1);

namespace TruePos\Gateways\Vakifbank;

use TruePos\Contracts\HashGeneratorInterface;

/**
 * Vakıfbank VPOS 7/24 hash algorithm:
 * SHA-512 of (MerchantPassword + TerminalNo)
 * Then SHA-512 of (SecurityData + fields...)
 *
 * For 3DS: SHA-512(MerchantId + TerminalNo + Amount + OkUrl + FailUrl + TxnType + InstallmentCount + Rnd + SubMerchantId + MerchantPass)
 */
final class VakifbankHashGenerator implements HashGeneratorInterface
{
    public function generate(array $parameters, array $credentials): string
    {
        $merchantPass = $credentials['merchantPass'] ?? '';
        $terminalNo = $credentials['terminalNo'] ?? '';

        $securityData = strtoupper(hash('sha512', $merchantPass . $terminalNo));

        // 3DS form hash
        if (isset($parameters['SuccessUrl'])) {
            $hashStr = $terminalNo
                . ($parameters['MerchantId'] ?? $credentials['merchantId'] ?? '')
                . ($parameters['TransactionId'] ?? '')
                . ($parameters['CurrencyAmount'] ?? '')
                . $securityData;

            return strtoupper(hash('sha512', $hashStr));
        }

        // API hash
        $hashStr = $terminalNo
            . ($parameters['MerchantId'] ?? $credentials['merchantId'] ?? '')
            . ($parameters['TransactionId'] ?? '')
            . ($parameters['CurrencyAmount'] ?? '')
            . $securityData;

        return strtoupper(hash('sha512', $hashStr));
    }

    public function verify(string $expected, array $parameters, array $credentials): bool
    {
        $calculated = $this->generate($parameters, $credentials);

        return hash_equals($expected, $calculated);
    }
}
