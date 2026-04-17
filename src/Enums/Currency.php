<?php

declare(strict_types=1);

namespace TruePos\Enums;

enum Currency: string
{
    case TRY = '949';
    case USD = '840';
    case EUR = '978';
    case GBP = '826';

    public function label(): string
    {
        return match ($this) {
            self::TRY => 'Türk Lirası',
            self::USD => 'ABD Doları',
            self::EUR => 'Euro',
            self::GBP => 'İngiliz Sterlini',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::TRY => '₺',
            self::USD => '$',
            self::EUR => '€',
            self::GBP => '£',
        };
    }

    public function isoAlpha(): string
    {
        return $this->name;
    }
}
