<?php

declare(strict_types=1);

namespace TruePos\ValueObjects;

final readonly class Customer
{
    public function __construct(
        public string $ip,
        public ?string $email = null,
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $identity = null,
    ) {}
}
