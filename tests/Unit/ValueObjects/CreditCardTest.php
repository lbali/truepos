<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\ValueObjects;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\CardNetwork;
use TruePos\Exceptions\InvalidCardException;
use TruePos\ValueObjects\CreditCard;

final class CreditCardTest extends TestCase
{
    #[Test]
    public function it_creates_a_valid_visa_card(): void
    {
        $card = new CreditCard('4546711234567894', '12', '30', '000');

        $this->assertSame('4546711234567894', $card->number);
        $this->assertSame(CardNetwork::Visa, $card->network);
    }

    #[Test]
    public function it_strips_spaces_and_dashes(): void
    {
        $card = new CreditCard('4546 7112 3456 7894', '12', '30', '000');

        $this->assertSame('4546711234567894', $card->number);
    }

    #[Test]
    public function it_detects_mastercard(): void
    {
        // Valid Mastercard test number
        $card = new CreditCard('5528790000000008', '12', '30', '123');

        $this->assertSame(CardNetwork::Mastercard, $card->network);
    }

    #[Test]
    public function it_returns_bin(): void
    {
        $card = new CreditCard('4546711234567894', '12', '30', '000');

        $this->assertSame('454671', $card->bin());
    }

    #[Test]
    public function it_returns_last_four(): void
    {
        $card = new CreditCard('4546711234567894', '12', '30', '000');

        $this->assertSame('7894', $card->lastFour());
    }

    #[Test]
    public function it_masks_number(): void
    {
        $card = new CreditCard('4546711234567894', '12', '30', '000');

        $this->assertSame('454671******7894', $card->maskedNumber());
    }

    #[Test]
    public function it_formats_expiry_mmyy(): void
    {
        $card = new CreditCard('4546711234567894', '3', '30', '000');

        $this->assertSame('0330', $card->expiryFormatMMYY());
    }

    #[Test]
    public function it_formats_expiry_yymm(): void
    {
        $card = new CreditCard('4546711234567894', '3', '30', '000');

        $this->assertSame('3003', $card->expiryFormatYYMM());
    }

    #[Test]
    public function it_formats_expiry_mmyyyy(): void
    {
        $card = new CreditCard('4546711234567894', '3', '30', '000');

        $this->assertSame('032030', $card->expiryFormatMMYYYY());
    }

    #[Test]
    public function it_rejects_invalid_luhn(): void
    {
        $this->expectException(InvalidCardException::class);

        new CreditCard('4546711234567890', '12', '30', '000');
    }

    #[Test]
    public function it_rejects_invalid_expiry_month(): void
    {
        $this->expectException(InvalidCardException::class);

        new CreditCard('4546711234567894', '13', '30', '000');
    }

    #[Test]
    public function it_rejects_invalid_cvv(): void
    {
        $this->expectException(InvalidCardException::class);

        new CreditCard('4546711234567894', '12', '30', '12');
    }
}
