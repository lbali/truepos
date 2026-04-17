<?php

declare(strict_types=1);

namespace TruePos\Validation;

use TruePos\Exceptions\ValidationException;
use TruePos\Validation\Validators\AmountValidator;
use TruePos\Validation\Validators\CreditCardValidator;
use TruePos\Validation\Validators\InstallmentValidator;
use TruePos\Validation\Validators\ThreeDCallbackValidator;

final class ValidationPipeline
{
    /** @var ValidatorInterface[] */
    private array $validators;

    public function __construct(ValidatorInterface ...$validators)
    {
        $this->validators = $validators;
    }

    public static function default(): self
    {
        return new self(
            new CreditCardValidator(),
            new AmountValidator(),
            new InstallmentValidator(),
            new ThreeDCallbackValidator(),
        );
    }

    /**
     * Run all validators and throw if any errors are found.
     * All validators run — errors are collected, not short-circuited.
     *
     * @throws ValidationException
     */
    public function validate(array $data): void
    {
        $allErrors = [];

        foreach ($this->validators as $validator) {
            $errors = $validator->validate($data);
            if ($errors !== []) {
                $allErrors = array_merge_recursive($allErrors, $errors);
            }
        }

        if ($allErrors !== []) {
            throw ValidationException::withErrors($allErrors);
        }
    }
}
