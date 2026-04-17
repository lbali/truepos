<?php

declare(strict_types=1);

namespace TruePos\Exceptions;

use TruePos\Enums\TransactionStatus;

class InvalidStateTransitionException extends TruePosException
{
    public static function cannot(TransactionStatus $from, TransactionStatus $to): self
    {
        return new self(
            message: "Cannot transition from [{$from->value}] to [{$to->value}].",
        );
    }
}
