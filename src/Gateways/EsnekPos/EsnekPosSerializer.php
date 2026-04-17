<?php

declare(strict_types=1);

namespace TruePos\Gateways\EsnekPos;

use TruePos\Contracts\SerializerInterface;
use TruePos\Exceptions\GatewayException;

/**
 * EsnekPOS uses JSON REST API.
 * Response can be JSON or HTML form POST (for 3DS callback).
 */
final class EsnekPosSerializer implements SerializerInterface
{
    public function serialize(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    public function deserialize(string $payload): array
    {
        $decoded = json_decode($payload, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw GatewayException::unexpectedResponse('EsnekPOS', $payload);
        }

        return $decoded;
    }

    public function contentType(): string
    {
        return 'application/json';
    }
}
