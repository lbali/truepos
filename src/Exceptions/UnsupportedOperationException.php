<?php

declare(strict_types=1);

namespace TruePos\Exceptions;

class UnsupportedOperationException extends TruePosException
{
    public static function paymentModel(string $gateway, string $model): self
    {
        return new self(
            message: "Gateway [{$gateway}] does not support payment model [{$model}].",
        );
    }

    public static function transactionType(string $gateway, string $type): self
    {
        return new self(
            message: "Gateway [{$gateway}] does not support transaction type [{$type}].",
        );
    }
}
