<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Builder\PaymentRequestBuilder;
use TruePos\Enums\PaymentModel;
use TruePos\Enums\TransactionType;
use TruePos\Exceptions\ValidationException;
use TruePos\ValueObjects\CreditCard;
use TruePos\ValueObjects\Customer;
use TruePos\ValueObjects\Money;

final class PaymentRequestBuilderTest extends TestCase
{
    private CreditCard $card;

    protected function setUp(): void
    {
        $this->card = new CreditCard('4546711234567894', '12', '30', '000', 'Test User');
    }

    #[Test]
    public function it_builds_a_basic_payment_request(): void
    {
        $request = PaymentRequestBuilder::create()
            ->card($this->card)
            ->amount(100.00)
            ->build();

        $this->assertSame(10000, $request->amount->amount);
        $this->assertSame($this->card, $request->card);
        $this->assertSame(TransactionType::Purchase, $request->type);
        $this->assertSame(PaymentModel::Regular, $request->paymentModel);
        $this->assertFalse($request->isThreeD());
    }

    #[Test]
    public function it_builds_a_3d_secure_request(): void
    {
        $request = PaymentRequestBuilder::create()
            ->card($this->card)
            ->amount(250.50)
            ->threeD('https://example.com/callback')
            ->build();

        $this->assertSame(PaymentModel::ThreeD, $request->paymentModel);
        $this->assertTrue($request->isThreeD());
        $this->assertSame('https://example.com/callback', $request->callbackUrl);
    }

    #[Test]
    public function it_builds_with_installment(): void
    {
        $request = PaymentRequestBuilder::create()
            ->card($this->card)
            ->amount(600.00)
            ->installment(6)
            ->build();

        $this->assertSame(6, $request->installment);
        $this->assertTrue($request->hasInstallment());
    }

    #[Test]
    public function it_builds_pre_auth_request(): void
    {
        $request = PaymentRequestBuilder::create()
            ->card($this->card)
            ->amount(1000.00)
            ->preAuth()
            ->build();

        $this->assertSame(TransactionType::PreAuth, $request->type);
    }

    #[Test]
    public function it_accepts_money_object(): void
    {
        $money = Money::fromDecimal(99.99);

        $request = PaymentRequestBuilder::create()
            ->card($this->card)
            ->amount($money)
            ->build();

        $this->assertSame(9999, $request->amount->amount);
    }

    #[Test]
    public function it_includes_customer(): void
    {
        $customer = new Customer(ip: '127.0.0.1', email: 'test@example.com');

        $request = PaymentRequestBuilder::create()
            ->card($this->card)
            ->amount(50.00)
            ->customer($customer)
            ->build();

        $this->assertSame('127.0.0.1', $request->customer->ip);
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $builder = PaymentRequestBuilder::create()
            ->card($this->card)
            ->amount(100.00);

        $withInstallment = $builder->installment(3);

        $request1 = $builder->build();
        $request2 = $withInstallment->build();

        $this->assertSame(0, $request1->installment);
        $this->assertSame(3, $request2->installment);
    }

    #[Test]
    public function it_generates_order_id_when_not_provided(): void
    {
        $request = PaymentRequestBuilder::create()
            ->card($this->card)
            ->amount(100.00)
            ->build();

        $this->assertStringStartsWith('TP', $request->orderId);
    }

    #[Test]
    public function it_uses_custom_order_id(): void
    {
        $request = PaymentRequestBuilder::create()
            ->card($this->card)
            ->amount(100.00)
            ->orderId('CUSTOM-123')
            ->build();

        $this->assertSame('CUSTOM-123', $request->orderId);
    }

    #[Test]
    public function it_validates_missing_amount(): void
    {
        $this->expectException(ValidationException::class);

        PaymentRequestBuilder::create()
            ->card($this->card)
            ->build();
    }

    #[Test]
    public function it_validates_missing_card_for_regular_payment(): void
    {
        $this->expectException(ValidationException::class);

        PaymentRequestBuilder::create()
            ->amount(100.00)
            ->build();
    }

    #[Test]
    public function it_allows_no_card_for_3d_host(): void
    {
        $request = PaymentRequestBuilder::create()
            ->amount(100.00)
            ->threeDHost('https://example.com/callback')
            ->build();

        $this->assertNull($request->card);
        $this->assertSame(PaymentModel::ThreeDHost, $request->paymentModel);
    }

    #[Test]
    public function it_validates_3d_requires_callback_url(): void
    {
        $this->expectException(ValidationException::class);

        PaymentRequestBuilder::create()
            ->card($this->card)
            ->amount(100.00)
            ->threeD()
            ->build();
    }
}
