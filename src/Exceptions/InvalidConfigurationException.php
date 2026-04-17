<?php

declare(strict_types=1);

namespace TruePos\Exceptions;

class InvalidConfigurationException extends TruePosException
{
    public static function gatewayNotConfigured(string $name): self
    {
        return new self(
            message: "Gateway [{$name}] is not configured. Check your truepos.php config.",
        );
    }

    public static function noDefault(): self
    {
        return new self(
            message: 'No default gateway configured. Set TRUEPOS_GATEWAY in your .env file.',
        );
    }

    public static function missingKey(string $gateway, string $key): self
    {
        return new self(
            message: "Missing required config key [{$key}] for gateway [{$gateway}].",
        );
    }
}
