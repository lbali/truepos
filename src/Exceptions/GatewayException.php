<?php

declare(strict_types=1);

namespace TruePos\Exceptions;

class GatewayException extends TruePosException
{
    public static function connectionFailed(string $gateway, ?\Throwable $previous = null): self
    {
        return new self(
            message: "Failed to connect to {$gateway} gateway.",
            previous: $previous,
        );
    }

    public static function unexpectedResponse(string $gateway, string $rawResponse): self
    {
        $truncated = strlen($rawResponse) > 200
            ? substr($rawResponse, 0, 200) . '... [truncated]'
            : $rawResponse;

        return new self(
            message: "Unexpected response from {$gateway}: {$truncated}",
        );
    }
}
