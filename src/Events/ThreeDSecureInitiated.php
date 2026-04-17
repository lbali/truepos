<?php

declare(strict_types=1);

namespace TruePos\Events;

use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\ThreeDSecureData;

final readonly class ThreeDSecureInitiated
{

    public function __construct(
        public PaymentRequest $request,
        public ThreeDSecureData $threeDSecureData,
    ) {}
}
