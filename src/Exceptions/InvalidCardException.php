<?php

declare(strict_types=1);

namespace TruePos\Exceptions;

class InvalidCardException extends TruePosException
{
    public static function invalidNumber(): self
    {
        return new self(message: 'Invalid credit card number.');
    }

    public static function invalidExpiry(): self
    {
        return new self(message: 'Credit card has expired or expiry date is invalid.');
    }

    public static function invalidCvv(): self
    {
        return new self(message: 'Invalid CVV.');
    }
}
