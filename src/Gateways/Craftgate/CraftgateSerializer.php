<?php

declare(strict_types=1);

namespace TruePos\Gateways\Craftgate;

use TruePos\Contracts\SerializerInterface;
use TruePos\Exceptions\GatewayException;

final class CraftgateSerializer implements SerializerInterface
{
    public function serialize(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function deserialize(string $payload): array
    {
        $decoded = json_decode($payload, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw GatewayException::unexpectedResponse('Craftgate', $payload);
        }

        return $decoded;
    }

    public function contentType(): string
    {
        return 'application/json';
    }
}
