<?php

declare(strict_types=1);

namespace TruePos\Gateways\PayTR;

use TruePos\Contracts\SerializerInterface;
use TruePos\Exceptions\GatewayException;

/**
 * PayTR uses JSON API (unlike bank gateways which use XML).
 * Requests are form-encoded, responses are JSON.
 */
final class PayTRSerializer implements SerializerInterface
{
    public function serialize(array $data): string
    {
        return http_build_query($data);
    }

    public function deserialize(string $payload): array
    {
        $decoded = json_decode($payload, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw GatewayException::unexpectedResponse('PayTR', $payload);
        }

        return $decoded;
    }

    public function contentType(): string
    {
        return 'application/x-www-form-urlencoded';
    }
}
