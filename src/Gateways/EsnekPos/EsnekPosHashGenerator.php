<?php

declare(strict_types=1);

namespace TruePos\Gateways\EsnekPos;

use TruePos\Contracts\HashGeneratorInterface;

/**
 * EsnekPOS AUTH_HASH generation.
 *
 * Based on documentation: MERCHANT + MERCHANT_KEY + ORDER_REF_NUMBER + ORDER_AMOUNT
 * Algorithm: Base64(SHA-256(concatenated string))
 *
 * Note: exact algorithm may need refinement based on EsnekPOS support feedback.
 */
final class EsnekPosHashGenerator implements HashGeneratorInterface
{
    public function generate(array $parameters, array $credentials): string
    {
        $merchant = $credentials['merchant'] ?? '';
        $merchantKey = $credentials['merchantKey'] ?? '';

        $hashStr = $merchant
            . $merchantKey
            . ($parameters['ORDER_REF_NUMBER'] ?? '')
            . ($parameters['ORDER_AMOUNT'] ?? '');

        return base64_encode(hash('sha256', $hashStr, true));
    }

    public function verify(string $expected, array $parameters, array $credentials): bool
    {
        $calculated = $this->generate($parameters, $credentials);

        return hash_equals($expected, $calculated);
    }
}
