<?php

declare(strict_types=1);

namespace TruePos\Events;


final readonly class ThreeDSecureCallbackReceived
{

    /**
     * @param  array<string, mixed>  $callbackData
     */
    public function __construct(
        public array $callbackData,
    ) {}
}
