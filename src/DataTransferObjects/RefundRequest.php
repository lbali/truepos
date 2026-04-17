<?php

declare(strict_types=1);

namespace TruePos\DataTransferObjects;

use TruePos\ValueObjects\Money;

final readonly class RefundRequest
{
    public function __construct(
        public string $orderId,
        public Money $amount,
        public ?string $transactionId = null,
    ) {}
}
