<?php

declare(strict_types=1);

namespace TruePos\Gateways\Moka;

use TruePos\Contracts\HashGeneratorInterface;

/**
 * Moka hash algorithm:
 * SHA-256(DealerCode + Username + Password + CheckKey)
 * CheckKey = SHA-256(DealerCode + "MK" + Username + "PD" + Password)
 */
final class MokaHashGenerator implements HashGeneratorInterface
{
    public function generate(array $parameters, array $credentials): string
    {
        $dealerCode = $credentials['dealerCode'] ?? '';
        $username = $credentials['username'] ?? '';
        $password = $credentials['password'] ?? '';

        return hash('sha256', $dealerCode . 'MK' . $username . 'PD' . $password);
    }

    public function verify(string $expected, array $parameters, array $credentials): bool
    {
        $calculated = $this->generate($parameters, $credentials);

        return hash_equals($expected, $calculated);
    }
}
