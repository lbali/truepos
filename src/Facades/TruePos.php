<?php

declare(strict_types=1);

namespace TruePos\Facades;

use Illuminate\Support\Facades\Facade;
use TruePos\Contracts\GatewayInterface;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\TruePosManager;

/**
 * @method static GatewayInterface gateway(?string $name = null)
 * @method static void registerThreeDMapping(string $orderId, string $gatewayName)
 *
 * @see TruePosManager
 */
final class TruePos extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TruePosManager::class;
    }
}
