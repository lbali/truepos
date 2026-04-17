<?php

declare(strict_types=1);

namespace TruePos\Validation\Validators;

use TruePos\Validation\ValidatorInterface;

final class InstallmentValidator implements ValidatorInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function validate(array $data): array
    {
        $errors = [];
        $installment = $data['installment'] ?? 0;

        if (! is_int($installment)) {
            $errors['installment'][] = 'Installment must be an integer.';

            return $errors;
        }

        if ($installment < 0 || $installment > 12) {
            $errors['installment'][] = 'Installment must be between 0 and 12.';
        }

        return $errors;
    }
}
