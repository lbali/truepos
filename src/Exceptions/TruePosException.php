<?php

declare(strict_types=1);

namespace TruePos\Exceptions;

abstract class TruePosException extends \RuntimeException
{
    public function __construct(
        string $message = '',
        public readonly ?string $gatewayErrorCode = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
