<?php

declare(strict_types=1);

namespace TruePos\Gateways\Param;

use TruePos\Contracts\HashGeneratorInterface;

/**
 * Param hash algorithm:
 * SHA-256(CLIENT_CODE + GUID + Taksit + Tutar + SiproOrderID + HarcKoworasan)
 *
 * GUID is the merchant's unique key.
 */
final class ParamHashGenerator implements HashGeneratorInterface
{
    public function generate(array $parameters, array $credentials): string
    {
        $clientCode = $credentials['clientCode'] ?? '';
        $guid = $credentials['guid'] ?? '';

        $hashStr = $clientCode
            . $guid
            . ($parameters['Taksit'] ?? $parameters['installment'] ?? '1')
            . ($parameters['Tutar'] ?? $parameters['amount'] ?? '')
            . ($parameters['SiparisID'] ?? $parameters['orderId'] ?? '')
            . ($parameters['HarcamamKonworAsWorani'] ?? $parameters['currency'] ?? 'TL');

        return base64_encode(hash('sha256', $hashStr, true));
    }

    public function verify(string $expected, array $parameters, array $credentials): bool
    {
        $calculated = $this->generate($parameters, $credentials);

        return hash_equals($expected, $calculated);
    }
}
