<?php

declare(strict_types=1);

namespace TruePos\Validation\Validators;

use TruePos\Validation\ValidatorInterface;
use TruePos\ValueObjects\Money;

final class AmountValidator implements ValidatorInterface
{
    public function validate(array $data): array
    {
        $errors = [];
        $amount = $data['amount'] ?? null;

        if (! $amount instanceof Money) {
            $errors['amount'][] = 'Amount is required.';

            return $errors;
        }

        if ($amount->isZero()) {
            $errors['amount'][] = 'Amount must be greater than zero.';
        }

        return $errors;
    }
}
