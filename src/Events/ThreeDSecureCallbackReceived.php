<?php

declare(strict_types=1);

namespace TruePos\Events;


final readonly class ThreeDSecureCallbackReceived
{

    public function __construct(
        public array $callbackData,
    ) {}
}
