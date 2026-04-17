<?php

declare(strict_types=1);

namespace TruePos\ValueObjects;

use TruePos\Enums\CardNetwork;
use TruePos\Exceptions\InvalidCardException;

final readonly class CreditCard
{
    public string $number;

    public CardNetwork $network;

    public function __construct(
        string $number,
        public string $expiryMonth,
        public string $expiryYear,
        public string $cvv,
        public ?string $holderName = null,
    ) {
        $this->number = preg_replace('/\D/', '', $number) ?? '';

        if (! self::isValidLuhn($this->number)) {
            throw InvalidCardException::invalidNumber();
        }

        if (! self::isValidExpiry($this->expiryMonth, $this->expiryYear)) {
            throw InvalidCardException::invalidExpiry();
        }

        if (! preg_match('/^\d{3,4}$/', $this->cvv)) {
            throw InvalidCardException::invalidCvv();
        }

        $this->network = CardNetwork::fromBin($this->bin());
    }

    public function bin(): string
    {
        return substr($this->number, 0, 6);
    }

    public function lastFour(): string
    {
        return substr($this->number, -4);
    }

    public function maskedNumber(): string
    {
        return $this->bin() . '******' . $this->lastFour();
    }

    /**
     * MM/YY format — NestPay, PayFor, Vakıfbank.
     */
    public function expiryFormatMMYY(): string
    {
        return str_pad($this->expiryMonth, 2, '0', STR_PAD_LEFT)
            . substr(str_pad($this->expiryYear, 2, '0', STR_PAD_LEFT), -2);
    }

    /**
     * YYMM format — PosNet.
     */
    public function expiryFormatYYMM(): string
    {
        return substr(str_pad($this->expiryYear, 2, '0', STR_PAD_LEFT), -2)
            . str_pad($this->expiryMonth, 2, '0', STR_PAD_LEFT);
    }

    /**
     * MM/YYYY format — Garanti.
     */
    public function expiryFormatMMYYYY(): string
    {
        $year = strlen($this->expiryYear) === 2
            ? '20' . $this->expiryYear
            : $this->expiryYear;

        return str_pad($this->expiryMonth, 2, '0', STR_PAD_LEFT) . $year;
    }

    private static function isValidLuhn(string $number): bool
    {
        if (strlen($number) < 13 || strlen($number) > 19) {
            return false;
        }

        $sum = 0;
        $alternate = false;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];

            if ($alternate) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $alternate = ! $alternate;
        }

        return $sum % 10 === 0;
    }

    private static function isValidExpiry(string $month, string $year): bool
    {
        $m = (int) $month;
        if ($m < 1 || $m > 12) {
            return false;
        }

        $y = (int) (strlen($year) === 2 ? '20' . $year : $year);

        $now = new \DateTimeImmutable();
        $currentYear = (int) $now->format('Y');
        $currentMonth = (int) $now->format('m');

        if ($y < $currentYear) {
            return false;
        }

        if ($y === $currentYear && $m < $currentMonth) {
            return false;
        }

        return true;
    }
}
