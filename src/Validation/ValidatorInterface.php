<?php

declare(strict_types=1);

namespace TruePos\Validation;

interface ValidatorInterface
{
    /**
     * Validate the given data.
     *
     * @return array<string, string[]> Validation errors keyed by field name.
     */
    public function validate(array $data): array;
}
