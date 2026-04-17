<?php

declare(strict_types=1);

namespace TruePos\Exceptions;

class HashMismatchException extends TruePosException
{
    public static function forCallback(string $gateway): self
    {
        return new self(
            message: "Hash verification failed for {$gateway} callback.",
        );
    }
}
