<?php

declare(strict_types=1);

namespace TruePos\Gateways\Sipay;

use TruePos\Contracts\HashGeneratorInterface;

/**
 * Sipay hash algorithm:
 * HMAC-SHA256(merchantKey + invoiceId + amount + merchantSecret)
 * using app_secret as the key
 */
final class SipayHashGenerator implements HashGeneratorInterface
{
    public function generate(array $parameters, array $credentials): string
    {
        $appKey = $credentials['appKey'] ?? '';
        $appSecret = $credentials['appSecret'] ?? '';

        $hashStr = $appKey
            . ($parameters['invoice_id'] ?? $parameters['merchant_key'] ?? '')
            . ($parameters['total'] ?? $parameters['amount'] ?? '')
            . $appSecret;

        return base64_encode(hash_hmac('sha256', $hashStr, $appSecret, true));
    }

    public function verify(string $expected, array $parameters, array $credentials): bool
    {
        $calculated = $this->generate($parameters, $credentials);

        return hash_equals($expected, $calculated);
    }
}
