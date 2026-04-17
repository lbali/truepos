<?php

declare(strict_types=1);

namespace TruePos\DataTransferObjects;

final readonly class StatusRequest
{
    public function __construct(
        public string $orderId,
        public ?string $transactionId = null,
    ) {}
}
