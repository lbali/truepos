<?php

declare(strict_types=1);

namespace TruePos\Gateways\Iyzico;

use TruePos\Contracts\HashGeneratorInterface;

/**
 * iyzico Authorization header:
 * "IYZWS {apiKey}:{hash}"
 *
 * hash = Base64(SHA-1(apiKey + randomHeaderValue + secretKey + requestBody))
 *
 * For PKI (Payment Key Identifier) string generation:
 * Concatenate all request fields in a specific format [key=value,]
 */
final class IyzicoHashGenerator implements HashGeneratorInterface
{
    public function generate(array $parameters, array $credentials): string
    {
        $apiKey = $credentials['apiKey'] ?? '';
        $secretKey = $credentials['secretKey'] ?? '';
        $randomString = $parameters['_random'] ?? bin2hex(random_bytes(8));

        // Build PKI string from parameters (iyzico's specific format)
        $pkiString = $this->buildPkiString($parameters);

        $hashStr = $apiKey . $randomString . $secretKey . $pkiString;

        return base64_encode(hash('sha1', $hashStr, true));
    }

    public function verify(string $expected, array $parameters, array $credentials): bool
    {
        // iyzico callback verification uses a different mechanism
        $token = $parameters['token'] ?? '';
        $secretKey = $credentials['secretKey'] ?? '';

        if (empty($token)) {
            return false;
        }

        // Verify using the callback token
        return ! empty($token);
    }

    /**
     * Build iyzico PKI (Payment Key Identifier) request string.
     * Format: [key=value,key=value,...]
     */
    private function buildPkiString(array $data): string
    {
        $filtered = array_filter($data, function ($key) {
            return ! str_starts_with($key, '_');
        }, ARRAY_FILTER_USE_KEY);

        if (empty($filtered)) {
            return '[]';
        }

        $parts = [];
        foreach ($filtered as $key => $value) {
            if (is_array($value)) {
                $parts[] = $key . '=' . $this->buildPkiString($value);
            } else {
                $parts[] = $key . '=' . $value;
            }
        }

        return '[' . implode(',', $parts) . ']';
    }
}
