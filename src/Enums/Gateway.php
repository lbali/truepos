<?php

declare(strict_types=1);

namespace TruePos\Enums;

enum Gateway: string
{
    case NestPay = 'nestpay';
    case Garanti = 'garanti';
    case PosNet = 'posnet';
    case PayFor = 'payfor';
    case Vakifbank = 'vakifbank';
    case KuveytTurk = 'kuveytturk';
    case PayTR = 'paytr';
    case Iyzico = 'iyzico';
    case Moka = 'moka';
    case Sipay = 'sipay';
    case Param = 'param';
    case Tosla = 'tosla';
    case Craftgate = 'craftgate';
    case EsnekPos = 'esnekpos';
    case Paratika = 'paratika';
    case Lidio = 'lidio';

    public function label(): string
    {
        return match ($this) {
            self::NestPay => 'NestPay (EST)',
            self::Garanti => 'Garanti BBVA (GVP)',
            self::PosNet => 'Yapı Kredi (PosNet)',
            self::PayFor => 'QNB Finansbank (PayFor)',
            self::Vakifbank => 'Vakıfbank (VPOS)',
            self::KuveytTurk => 'Kuveyt Türk',
            self::PayTR => 'PayTR',
            self::Iyzico => 'iyzico',
            self::Moka => 'Moka',
            self::Sipay => 'Sipay',
            self::Param => 'Param',
            self::Tosla => 'Tosla (Akbank)',
            self::Craftgate => 'Craftgate',
            self::EsnekPos => 'EsnekPOS',
            self::Paratika => 'Paratika (Asseco)',
            self::Lidio => 'Lidio',
        };
    }
}
