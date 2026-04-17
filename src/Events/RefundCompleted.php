<?php

declare(strict_types=1);

namespace TruePos\Events;

use TruePos\DataTransferObjects\PaymentResponse;

final readonly class RefundCompleted
{

    public function __construct(
        public PaymentResponse $response,
    ) {}
}
