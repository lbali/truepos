<?php

declare(strict_types=1);

namespace TruePos\Events;

use Illuminate\Foundation\Events\Dispatchable;
use TruePos\DataTransferObjects\PaymentRequest;

final readonly class PaymentStarted
{
    use Dispatchable;

    public function __construct(
        public PaymentRequest $request,
    ) {}
}
