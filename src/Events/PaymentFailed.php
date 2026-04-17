<?php

declare(strict_types=1);

namespace TruePos\Events;

use Illuminate\Foundation\Events\Dispatchable;
use TruePos\DataTransferObjects\PaymentResponse;

final readonly class PaymentFailed
{
    use Dispatchable;

    public function __construct(
        public PaymentResponse $response,
        public ?\Throwable $exception = null,
    ) {}
}
