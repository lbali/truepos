<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\ValueObjects;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\Currency;
use TruePos\ValueObjects\Money;

final class MoneyTest extends TestCase
{
    #[Test]
    public function it_creates_from_minor_units(): void
    {
        $money = new Money(1050);

        $this->assertSame(1050, $money->amount);
        $this->assertSame(Currency::TRY, $money->currency);
    }

    #[Test]
    public function it_creates_from_decimal(): void
    {
        $money = Money::fromDecimal(10.50);

        $this->assertSame(1050, $money->amount);
    }

    #[Test]
    public function it_creates_from_decimal_string(): void
    {
        $money = Money::fromDecimal('250.99', Currency::USD);

        $this->assertSame(25099, $money->amount);
        $this->assertSame(Currency::USD, $money->currency);
    }

    #[Test]
    public function it_converts_to_decimal(): void
    {
        $money = new Money(1050);

        $this->assertSame('10.50', $money->toDecimal());
    }

    #[Test]
    public function it_converts_to_minor_string(): void
    {
        $money = new Money(1050);

        $this->assertSame('1050', $money->toMinorString());
    }

    #[Test]
    public function it_formats_with_symbol(): void
    {
        $money = new Money(1050);

        $this->assertSame('10.50 ₺', $money->format());
    }

    #[Test]
    public function it_rejects_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Money(-100);
    }

    #[Test]
    public function it_detects_zero(): void
    {
        $money = new Money(0);

        $this->assertTrue($money->isZero());
        $this->assertFalse((new Money(1))->isZero());
    }

    #[Test]
    public function it_compares_equality(): void
    {
        $a = new Money(1050, Currency::TRY);
        $b = new Money(1050, Currency::TRY);
        $c = new Money(1050, Currency::USD);

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    #[Test]
    public function it_subtracts(): void
    {
        $a = new Money(1000);
        $b = new Money(300);

        $result = $a->subtract($b);

        $this->assertSame(700, $result->amount);
    }

    #[Test]
    public function it_rejects_subtraction_of_different_currencies(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Money(1000, Currency::TRY))->subtract(new Money(300, Currency::USD));
    }

    #[Test]
    public function it_handles_rounding_correctly(): void
    {
        $money = Money::fromDecimal(19.99);

        $this->assertSame(1999, $money->amount);
        $this->assertSame('19.99', $money->toDecimal());
    }
}
