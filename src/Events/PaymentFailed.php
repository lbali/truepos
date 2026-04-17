<?php

declare(strict_types=1);

namespace TruePos\Events;

use TruePos\DataTransferObjects\PaymentResponse;

final readonly class PaymentFailed
{

    public function __construct(
        public PaymentResponse $response,
        public ?\Throwable $exception = null,
    ) {}
}
