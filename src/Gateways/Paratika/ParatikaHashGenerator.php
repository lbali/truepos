<?php

declare(strict_types=1);

namespace TruePos\Gateways\Paratika;

use TruePos\Contracts\HashGeneratorInterface;

/**
 * Paratika uses MERCHANT + MERCHANTUSER + MERCHANTPASSWORD for auth.
 * Session-token based flow — no traditional hash signing per request.
 * The session token acts as the auth mechanism after initial creation.
 */
final class ParatikaHashGenerator implements HashGeneratorInterface
{
    public function generate(array $parameters, array $credentials): string
    {
        // Paratika authenticates via credentials in each request body,
        // not via a computed hash. Return empty — credentials go directly in params.
        return '';
    }

    public function verify(string $expected, array $parameters, array $credentials): bool
    {
        // Paratika callback verification via responseCode check
        return ($parameters['responseCode'] ?? '') === '00';
    }
}
