<?php

declare(strict_types=1);

namespace TruePos\Events;

use Illuminate\Foundation\Events\Dispatchable;
use TruePos\DataTransferObjects\PaymentResponse;

final readonly class RefundCompleted
{
    use Dispatchable;

    public function __construct(
        public PaymentResponse $response,
    ) {}
}
