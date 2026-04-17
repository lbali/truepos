<?php

declare(strict_types=1);

namespace TruePos\Enums;

enum PaymentModel: string
{
    case Regular = 'regular';
    case ThreeD = '3d';
    case ThreeDPay = '3d_pay';
    case ThreeDHost = '3d_host';

    public function isThreeD(): bool
    {
        return $this !== self::Regular;
    }

    public function requiresCard(): bool
    {
        return $this !== self::ThreeDHost;
    }
}
