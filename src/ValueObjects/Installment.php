<?php

declare(strict_types=1);

namespace TruePos\ValueObjects;

final readonly class Installment
{
    public function __construct(
        public int $count,
        public ?float $commissionRate = null,
        public ?Money $totalAmount = null,
    ) {
        if ($this->count < 0 || $this->count > 12) {
            throw new \InvalidArgumentException('Installment count must be between 0 and 12.');
        }
    }

    public function isSinglePayment(): bool
    {
        return $this->count <= 1;
    }
}
