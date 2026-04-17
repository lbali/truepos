<?php

declare(strict_types=1);

namespace TruePos\Exceptions;

class ValidationException extends TruePosException
{
    /** @var array<string, string[]> */
    private array $errors;

    /**
     * @param  array<string, string[]>  $errors
     */
    public static function withErrors(array $errors): self
    {
        $instance = new self(
            message: 'Payment request validation failed: ' . self::summarize($errors),
        );
        $instance->errors = $errors;

        return $instance;
    }

    /**
     * @return array<string, string[]>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @param  array<string, string[]>  $errors
     */
    private static function summarize(array $errors): string
    {
        $messages = [];
        foreach ($errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $messages[] = "[{$field}] {$error}";
            }
        }

        return implode('; ', $messages);
    }
}
