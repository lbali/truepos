<?php

declare(strict_types=1);

namespace TruePos\Gateways\KuveytTurk;

use TruePos\Contracts\HashGeneratorInterface;

/**
 * KuveytTürk hash algorithm:
 * SHA-1 of (MerchantId + MerchantOrderId + Amount + OkUrl + FailUrl + UserName + HashedPassword)
 * Where HashedPassword = SHA-1(Password)
 */
final class KuveytTurkHashGenerator implements HashGeneratorInterface
{
    public function generate(array $parameters, array $credentials): string
    {
        $password = $credentials['password'] ?? '';
        $hashedPassword = base64_encode(hash('sha1', $password, true));

        $hashStr = ($parameters['MerchantId'] ?? $credentials['merchantId'] ?? '')
            . ($parameters['MerchantOrderId'] ?? '')
            . ($parameters['Amount'] ?? '')
            . ($parameters['OkUrl'] ?? $parameters['SuccessUrl'] ?? '')
            . ($parameters['FailUrl'] ?? $parameters['ErrorUrl'] ?? '')
            . ($parameters['UserName'] ?? $credentials['username'] ?? '')
            . $hashedPassword;

        return base64_encode(hash('sha1', $hashStr, true));
    }

    public function verify(string $expected, array $parameters, array $credentials): bool
    {
        $calculated = $this->generate($parameters, $credentials);

        return hash_equals($expected, $calculated);
    }
}
