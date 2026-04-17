<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Decorators;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Contracts\GatewayInterface;
use TruePos\DataTransferObjects\CancelRequest;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\DataTransferObjects\StatusRequest;
use TruePos\Decorators\RetryGateway;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\Exceptions\GatewayException;
use TruePos\ValueObjects\Money;

final class RetryGatewayTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private GatewayInterface|Mockery\MockInterface $inner;

    private RetryGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inner = Mockery::mock(GatewayInterface::class);
        // Use delayMs=0 to avoid slow tests
        $this->gateway = new RetryGateway($this->inner, maxRetries: 2, delayMs: 0);
    }

    #[Test]
    public function it_does_not_retry_purchase(): void
    {
        $request = new PaymentRequest(
            amount: new Money(1050),
            orderId: 'ORD-001',
        );

        $exception = new GatewayException('Connection failed');

        $this->inner->expects('purchase')
            ->with($request)
            ->once()
            ->andThrow($exception);

        $this->expectException(GatewayException::class);

        $this->gateway->purchase($request);
    }

    #[Test]
    public function it_retries_status_on_gateway_exception(): void
    {
        $request = new StatusRequest(orderId: 'ORD-001');

        $response = new PaymentResponse(
            isSuccessful: true,
            status: TransactionStatus::Completed,
            gateway: Gateway::NestPay,
            transactionType: TransactionType::StatusQuery,
        );

        $callCount = 0;
        $this->inner->expects('status')
            ->with($request)
            ->times(2)
            ->andReturnUsing(function () use (&$callCount, $response) {
                $callCount++;
                if ($callCount === 1) {
                    throw new GatewayException('Timeout');
                }

                return $response;
            });

        $result = $this->gateway->status($request);

        $this->assertSame($response, $result);
    }

    #[Test]
    public function it_throws_after_max_retries_exhausted(): void
    {
        $request = new StatusRequest(orderId: 'ORD-001');

        $this->inner->expects('status')
            ->with($request)
            ->times(3) // 1 initial + 2 retries
            ->andThrow(new GatewayException('Timeout'));

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Timeout');

        $this->gateway->status($request);
    }

    #[Test]
    public function it_retries_cancel_successfully(): void
    {
        $request = new CancelRequest(orderId: 'ORD-001');

        $response = new PaymentResponse(
            isSuccessful: true,
            status: TransactionStatus::Cancelled,
            gateway: Gateway::NestPay,
            transactionType: TransactionType::Cancel,
        );

        $callCount = 0;
        $this->inner->expects('cancel')
            ->with($request)
            ->times(2)
            ->andReturnUsing(function () use (&$callCount, $response) {
                $callCount++;
                if ($callCount === 1) {
                    throw new GatewayException('Temporary failure');
                }

                return $response;
            });

        $result = $this->gateway->cancel($request);

        $this->assertSame($response, $result);
    }
}
