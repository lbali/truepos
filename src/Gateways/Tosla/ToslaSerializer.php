<?php

declare(strict_types=1);

namespace TruePos\Gateways\Tosla;

use TruePos\Contracts\SerializerInterface;
use TruePos\Exceptions\GatewayException;

/**
 * Tosla (Akbank) uses JSON REST API.
 * Content-Type: application/json-patch+json
 */
final class ToslaSerializer implements SerializerInterface
{
    public function serialize(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    public function deserialize(string $payload): array
    {
        $decoded = json_decode($payload, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw GatewayException::unexpectedResponse('Tosla', $payload);
        }

        return $decoded;
    }

    public function contentType(): string
    {
        return 'application/json-patch+json';
    }
}
