<?php

declare(strict_types=1);

namespace TruePos\Gateways\NestPay;

use TruePos\Contracts\HashGeneratorInterface;

final class NestPayHashGenerator implements HashGeneratorInterface
{
    /**
     * NestPay hash algorithm (v3):
     * 1. Escape special chars: \ → \\ and | → \|
     * 2. Concatenate specific fields with | separator
     * 3. SHA-512 hash with storeKey appended
     * 4. Base64 encode the binary hash
     */
    public function generate(array $parameters, array $credentials): string
    {
        $storeKey = $credentials['storeKey'] ?? '';

        $hashFields = [
            $parameters['clientid'] ?? $parameters['clientId'] ?? $parameters['ClientId'] ?? '',
            $parameters['oid'] ?? $parameters['OrderId'] ?? '',
            $parameters['amount'] ?? $parameters['Total'] ?? '',
            $parameters['okUrl'] ?? $parameters['SuccessUrl'] ?? '',
            $parameters['failUrl'] ?? $parameters['FailUrl'] ?? '',
            $parameters['TranType'] ?? $parameters['Type'] ?? '',
            $parameters['Instalment'] ?? $parameters['Taksit'] ?? '',
            $parameters['rnd'] ?? '',
            '', // callbackUrl (reserved)
            $storeKey,
        ];

        $hashStr = implode('|', array_map(
            fn (string $value) => str_replace(['\\', '|'], ['\\\\', '\\|'], $value),
            $hashFields,
        ));

        return base64_encode(hash('sha512', $hashStr, true));
    }

    public function verify(string $expected, array $parameters, array $credentials): bool
    {
        $calculated = $this->generate($parameters, $credentials);

        return hash_equals($expected, $calculated);
    }
}
