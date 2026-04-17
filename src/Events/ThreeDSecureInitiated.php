<?php

declare(strict_types=1);

namespace TruePos\Events;

use Illuminate\Foundation\Events\Dispatchable;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\ThreeDSecureData;

final readonly class ThreeDSecureInitiated
{
    use Dispatchable;

    public function __construct(
        public PaymentRequest $request,
        public ThreeDSecureData $threeDSecureData,
    ) {}
}
