<?php

declare(strict_types=1);

namespace TruePos\Gateways\PayTR;

use TruePos\Contracts\HashGeneratorInterface;

/**
 * PayTR HMAC token generation:
 * HMAC-SHA256(merchant_id + user_ip + merchant_oid + email + payment_amount + ... + merchant_salt, merchant_key)
 */
final class PayTRHashGenerator implements HashGeneratorInterface
{
    public function generate(array $parameters, array $credentials): string
    {
        $merchantKey = $credentials['merchantKey'] ?? '';
        $merchantSalt = $credentials['merchantSalt'] ?? '';

        $hashStr = ($parameters['merchant_id'] ?? $credentials['merchantId'] ?? '')
            . ($parameters['user_ip'] ?? '')
            . ($parameters['merchant_oid'] ?? '')
            . ($parameters['email'] ?? '')
            . ($parameters['payment_amount'] ?? '')
            . ($parameters['payment_type'] ?? 'card')
            . ($parameters['installment_count'] ?? '0')
            . ($parameters['currency'] ?? 'TL')
            . ($parameters['test_mode'] ?? '0')
            . ($parameters['non_3d'] ?? '0')
            . $merchantSalt;

        return base64_encode(hash_hmac('sha256', $hashStr, $merchantKey, true));
    }

    public function verify(string $expected, array $parameters, array $credentials): bool
    {
        $merchantKey = $credentials['merchantKey'] ?? '';
        $merchantSalt = $credentials['merchantSalt'] ?? '';

        // PayTR callback verification
        $hashStr = ($parameters['merchant_oid'] ?? '')
            . $merchantSalt
            . ($parameters['status'] ?? '')
            . ($parameters['total_amount'] ?? '');

        $calculated = base64_encode(hash_hmac('sha256', $hashStr, $merchantKey, true));

        return hash_equals($calculated, $expected);
    }
}
