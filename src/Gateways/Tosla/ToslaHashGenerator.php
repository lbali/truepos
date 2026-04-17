<?php

declare(strict_types=1);

namespace TruePos\Gateways\Tosla;

use TruePos\Contracts\HashGeneratorInterface;

/**
 * Tosla hash algorithm (from Postman pre-request script):
 *
 * hash = Base64(SHA-512(apiPass + clientId + apiUser + rnd + timeSpan))
 *
 * Where:
 * - apiPass: merchant API password
 * - clientId: merchant client ID
 * - apiUser: merchant API username
 * - rnd: random string provided by merchant
 * - timeSpan: YYYYMMDDHHmmss format timestamp
 */
final class ToslaHashGenerator implements HashGeneratorInterface
{
    public function generate(array $parameters, array $credentials): string
    {
        $apiPass = $credentials['apiPass'] ?? '';
        $clientId = $parameters['clientId'] ?? $credentials['clientId'] ?? '';
        $apiUser = $parameters['apiUser'] ?? $credentials['apiUser'] ?? '';
        $rnd = $parameters['rnd'] ?? '';
        $timeSpan = $parameters['timeSpan'] ?? '';

        $payload = $apiPass . $clientId . $apiUser . $rnd . $timeSpan;

        return base64_encode(hash('sha512', $payload, true));
    }

    public function verify(string $expected, array $parameters, array $credentials): bool
    {
        $calculated = $this->generate($parameters, $credentials);

        return hash_equals($expected, $calculated);
    }
}
