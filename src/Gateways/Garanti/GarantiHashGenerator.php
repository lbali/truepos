<?php

declare(strict_types=1);

namespace TruePos\Gateways\Garanti;

use TruePos\Contracts\HashGeneratorInterface;

/**
 * Garanti GVP hash algorithm:
 * 1. SecurityData = SHA-512(password + terminalId(zero-padded to 9 digits))
 * 2. HashData = SHA-512(orderId + terminalId + cardNumber + amount + securityData)
 *
 * For 3DS:
 * HashData = SHA-512(terminalId + orderId + amount + successUrl + failUrl + type + installment + storeKey + securityData)
 */
final class GarantiHashGenerator implements HashGeneratorInterface
{
    public function generate(array $parameters, array $credentials): string
    {
        $terminalId = str_pad($credentials['terminalId'] ?? '', 9, '0', STR_PAD_LEFT);
        $password = $credentials['provisionPassword'] ?? '';
        $storeKey = $credentials['storeKey'] ?? '';

        $securityData = strtoupper(hash('sha512', $password . $terminalId));

        // 3DS form hash
        if (isset($parameters['secure3dsecuritylevel'])) {
            $hashStr = $terminalId
                . ($parameters['orderid'] ?? '')
                . ($parameters['txnamount'] ?? '')
                . ($parameters['successurl'] ?? '')
                . ($parameters['errorurl'] ?? '')
                . ($parameters['txntype'] ?? '')
                . ($parameters['txninstallmentcount'] ?? '')
                . $storeKey
                . $securityData;

            return strtoupper(hash('sha512', $hashStr));
        }

        // API transaction hash
        $hashStr = ($parameters['Order']['OrderID'] ?? '')
            . $terminalId
            . ($parameters['Card']['Number'] ?? '')
            . ($parameters['Transaction']['Amount'] ?? '')
            . $securityData;

        return strtoupper(hash('sha512', $hashStr));
    }

    public function verify(string $expected, array $parameters, array $credentials): bool
    {
        $calculated = $this->generate($parameters, $credentials);

        return hash_equals($expected, $calculated);
    }
}
