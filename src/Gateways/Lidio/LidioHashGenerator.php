<?php

declare(strict_types=1);

namespace TruePos\Gateways\Lidio;

use TruePos\Contracts\HashGeneratorInterface;

/**
 * Lidio 3DS callback hash verification:
 *
 * SHA256+Base64(OrderId + ":" + MerchantKey + ":" + TotalAmount + ":" + Result + ":" + Email/CustomerID)
 *
 * TotalAmount formatted in en-us culture with 2 decimal points.
 *
 * Lidio uses API Key in Authorization header for request auth (no per-request hash).
 */
final class LidioHashGenerator implements HashGeneratorInterface
{
    public function generate(array $parameters, array $credentials): string
    {
        // Lidio authenticates via API Key in Authorization header.
        // No per-request hash generation needed.
        return '';
    }

    public function verify(string $expected, array $parameters, array $credentials): bool
    {
        $merchantKey = $credentials['merchantKey'] ?? '';
        $orderId = $parameters['OrderId'] ?? $parameters['orderId'] ?? '';
        $totalAmount = $parameters['TotalAmount'] ?? $parameters['totalAmount'] ?? '';
        $result = $parameters['Result'] ?? $parameters['result'] ?? '';
        $customerIdentifier = $parameters['Email'] ?? $parameters['email']
            ?? $parameters['CustomerID'] ?? $parameters['customerId'] ?? '';

        // Format amount: en-us culture, 2 decimal points
        if (is_numeric($totalAmount)) {
            $totalAmount = number_format((float) $totalAmount, 2, '.', '');
        }

        $hashData = $orderId . ':' . $merchantKey . ':' . $totalAmount . ':' . $result . ':' . $customerIdentifier;

        $calculated = base64_encode(hash('sha256', $hashData, true));

        return hash_equals($calculated, $expected);
    }
}
