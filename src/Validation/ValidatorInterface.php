<?php

declare(strict_types=1);

namespace TruePos\Validation;

interface ValidatorInterface
{
    /**
     * Validate the given data.
     *
     * @return array<string, string[]> Validation errors keyed by field name.
     * @param  array<string, mixed>  $data
     */
    public function validate(array $data): array;
}
