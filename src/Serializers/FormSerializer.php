<?php

declare(strict_types=1);

namespace TruePos\Serializers;

use TruePos\Contracts\SerializerInterface;
use TruePos\Exceptions\GatewayException;

final class FormSerializer implements SerializerInterface
{
    public function __construct(
        private readonly string $gatewayName = 'Unknown',
    ) {}

    public function serialize(array $data): string
    {
        return http_build_query($data);
    }

    public function deserialize(string $payload): array
    {
        $decoded = json_decode($payload, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            parse_str($payload, $decoded);
            if (empty($decoded)) {
                throw GatewayException::unexpectedResponse($this->gatewayName, $payload);
            }
        }

        return $decoded;
    }

    public function contentType(): string
    {
        return 'application/x-www-form-urlencoded';
    }
}
