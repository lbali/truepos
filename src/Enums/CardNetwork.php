<?php

declare(strict_types=1);

namespace TruePos\Enums;

enum CardNetwork: string
{
    case Visa = 'visa';
    case Mastercard = 'mastercard';
    case Amex = 'amex';
    case Troy = 'troy';
    case Unknown = 'unknown';

    public static function fromBin(string $bin): self
    {
        $bin = preg_replace('/\D/', '', $bin);

        if ($bin === '' || $bin === null) {
            return self::Unknown;
        }

        $first = (int) $bin[0];
        $firstTwo = (int) substr($bin, 0, 2);
        $firstFour = strlen($bin) >= 4 ? (int) substr($bin, 0, 4) : 0;

        // Troy: 9792 prefix
        if ($firstFour >= 9792 && $firstFour <= 9792) {
            return self::Troy;
        }

        // Amex: 34, 37
        if ($firstTwo === 34 || $firstTwo === 37) {
            return self::Amex;
        }

        // Visa: starts with 4
        if ($first === 4) {
            return self::Visa;
        }

        // Mastercard: 51-55, 2221-2720
        if ($firstTwo >= 51 && $firstTwo <= 55) {
            return self::Mastercard;
        }
        if ($firstFour >= 2221 && $firstFour <= 2720) {
            return self::Mastercard;
        }

        return self::Unknown;
    }
}
