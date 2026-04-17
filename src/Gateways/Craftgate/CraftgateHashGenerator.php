<?php

declare(strict_types=1);

namespace TruePos\Gateways\Craftgate;

use TruePos\Contracts\HashGeneratorInterface;

/**
 * Craftgate signature algorithm (from PHP client source):
 *
 * hashString = baseUrl + urlDecode(path) + apiKey + secretKey + randomString + requestBody
 * signature  = Base64(SHA-256(hashString))
 *
 * The signature goes in x-signature header.
 * The random string goes in x-rnd-key header.
 *
 * For 3DS callback verification:
 * hash('sha256', "hashKey###status###completeStatus###paymentId###conversationData###conversationId###callbackStatus")
 */
final class CraftgateHashGenerator implements HashGeneratorInterface
{
    public function generate(array $parameters, array $credentials): string
    {
        $apiKey = $credentials['apiKey'] ?? '';
        $secretKey = $credentials['secretKey'] ?? '';
        $baseUrl = $credentials['baseUrl'] ?? 'https://api.craftgate.io';
        $path = $parameters['_path'] ?? '';
        $randomString = $parameters['_rnd'] ?? $this->generateGuid();
        $requestBody = $parameters['_body'] ?? '';

        $hashString = $baseUrl . urldecode($path) . $apiKey . $secretKey . $randomString . $requestBody;

        return base64_encode(hash('sha256', $hashString, true));
    }

    public function verify(string $expected, array $parameters, array $credentials): bool
    {
        // 3DS callback verification uses a different hash format
        $hashKey = $credentials['secretKey'] ?? '';
        $status = $parameters['status'] ?? '';
        $completeStatus = $parameters['completeStatus'] ?? '';
        $paymentId = $parameters['paymentId'] ?? '';
        $conversationData = $parameters['conversationData'] ?? '';
        $conversationId = $parameters['conversationId'] ?? '';
        $callbackStatus = $parameters['callbackStatus'] ?? '';

        $hashString = $hashKey . '###' . $status . '###' . $completeStatus
            . '###' . $paymentId . '###' . $conversationData
            . '###' . $conversationId . '###' . $callbackStatus;

        $calculated = hash('sha256', $hashString);

        return hash_equals($calculated, $expected);
    }

    private function generateGuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        );
    }
}
