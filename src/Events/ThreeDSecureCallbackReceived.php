<?php

declare(strict_types=1);

namespace TruePos\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class ThreeDSecureCallbackReceived
{
    use Dispatchable;

    public function __construct(
        public array $callbackData,
    ) {}
}
