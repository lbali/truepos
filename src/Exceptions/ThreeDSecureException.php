<?php

declare(strict_types=1);

namespace TruePos\Exceptions;

class ThreeDSecureException extends TruePosException
{
    public static function authenticationFailed(?string $mdStatus = null): self
    {
        return new self(
            message: "3D Secure authentication failed. mdStatus: {$mdStatus}",
        );
    }

    public static function callbackVerificationFailed(): self
    {
        return new self(
            message: '3D Secure callback hash verification failed.',
        );
    }

    public static function initializationFailed(?string $message = null): self
    {
        return new self(
            message: '3D Secure initialization failed.'.($message !== null && $message !== '' ? " {$message}" : ''),
        );
    }

    public static function gatewayNotResolved(): self
    {
        return new self(
            message: 'Cannot determine gateway for 3D Secure callback.',
        );
    }
}
