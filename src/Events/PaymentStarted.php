<?php

declare(strict_types=1);

namespace TruePos\Events;

use TruePos\DataTransferObjects\PaymentRequest;

final readonly class PaymentStarted
{

    public function __construct(
        public PaymentRequest $request,
    ) {}
}
