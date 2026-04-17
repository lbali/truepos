<?php

declare(strict_types=1);

namespace TruePos\ValueObjects;

use TruePos\Enums\Currency;

final readonly class Money
{
    /**
     * @param  int  $amount  Amount in minor units (kuruş). 1050 = 10.50 TL.
     */
    public function __construct(
        public int $amount,
        public Currency $currency = Currency::TRY,
    ) {
        if ($this->amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative.');
        }
    }

    /**
     * Create from a decimal string or float (e.g., 10.50).
     */
    public static function fromDecimal(string|float $amount, Currency $currency = Currency::TRY): self
    {
        return new self(
            amount: (int) round((float) $amount * 100),
            currency: $currency,
        );
    }

    /**
     * Decimal string with 2 digits — NestPay, Garanti, PayFor.
     * Example: "10.50"
     */
    public function toDecimal(): string
    {
        return number_format($this->amount / 100, 2, '.', '');
    }

    /**
     * Minor units as plain string — PosNet.
     * Example: "1050"
     */
    public function toMinorString(): string
    {
        return (string) $this->amount;
    }

    /**
     * Integer kuruş value — KuveytTürk.
     * Example: 1050
     */
    public function toMinor(): int
    {
        return $this->amount;
    }

    public function format(): string
    {
        return $this->toDecimal() . ' ' . $this->currency->symbol();
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function subtract(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot subtract different currencies.');
        }

        return new self($this->amount - $other->amount, $this->currency);
    }
}
