<?php

declare(strict_types=1);

namespace TruePos\Gateways\Paratika;

use TruePos\Contracts\SerializerInterface;
use TruePos\Exceptions\GatewayException;

/**
 * Paratika uses form-encoded POST requests, JSON responses.
 */
final class ParatikaSerializer implements SerializerInterface
{
    public function serialize(array $data): string
    {
        return http_build_query($data);
    }

    public function deserialize(string $payload): array
    {
        $decoded = json_decode($payload, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            // Try parsing as query string (some Paratika responses are form-encoded)
            parse_str($payload, $decoded);
            if (empty($decoded)) {
                throw GatewayException::unexpectedResponse('Paratika', $payload);
            }
        }

        return $decoded;
    }

    public function contentType(): string
    {
        return 'application/x-www-form-urlencoded';
    }
}
