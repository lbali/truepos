<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Validation;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\PaymentModel;
use TruePos\Exceptions\ValidationException;
use TruePos\Validation\ValidationPipeline;
use TruePos\ValueObjects\CreditCard;
use TruePos\ValueObjects\Money;

final class ValidationPipelineTest extends TestCase
{
    private ValidationPipeline $pipeline;

    protected function setUp(): void
    {
        $this->pipeline = ValidationPipeline::default();
    }

    #[Test]
    public function it_passes_valid_data(): void
    {
        $this->pipeline->validate([
            'card' => new CreditCard('4546711234567894', '12', '30', '000'),
            'amount' => Money::fromDecimal(100),
            'installment' => 3,
            'paymentModel' => PaymentModel::Regular,
            'callbackUrl' => null,
        ]);

        $this->assertTrue(true); // No exception thrown
    }

    #[Test]
    public function it_collects_all_errors(): void
    {
        try {
            $this->pipeline->validate([
                'card' => null,
                'amount' => null,
                'installment' => 15,
                'paymentModel' => PaymentModel::Regular,
                'callbackUrl' => null,
            ]);

            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->errors();

            $this->assertArrayHasKey('card', $errors);
            $this->assertArrayHasKey('amount', $errors);
            $this->assertArrayHasKey('installment', $errors);
        }
    }

    #[Test]
    public function it_requires_callback_for_3d(): void
    {
        try {
            $this->pipeline->validate([
                'card' => new CreditCard('4546711234567894', '12', '30', '000'),
                'amount' => Money::fromDecimal(100),
                'installment' => 0,
                'paymentModel' => PaymentModel::ThreeD,
                'callbackUrl' => null,
            ]);

            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('callbackUrl', $e->errors());
        }
    }

    #[Test]
    public function it_allows_no_card_for_3d_host(): void
    {
        $this->pipeline->validate([
            'card' => null,
            'amount' => Money::fromDecimal(100),
            'installment' => 0,
            'paymentModel' => PaymentModel::ThreeDHost,
            'callbackUrl' => 'https://example.com/callback',
        ]);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_rejects_zero_amount(): void
    {
        try {
            $this->pipeline->validate([
                'card' => new CreditCard('4546711234567894', '12', '30', '000'),
                'amount' => new Money(0),
                'installment' => 0,
                'paymentModel' => PaymentModel::Regular,
                'callbackUrl' => null,
            ]);

            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }
    }
}
