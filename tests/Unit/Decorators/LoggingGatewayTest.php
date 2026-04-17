<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Decorators;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use TruePos\Contracts\GatewayInterface;
use TruePos\DataTransferObjects\PaymentRequest;
use TruePos\DataTransferObjects\PaymentResponse;
use TruePos\Decorators\LoggingGateway;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\ValueObjects\Money;

final class LoggingGatewayTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private GatewayInterface|Mockery\MockInterface $inner;

    private LoggerInterface|Mockery\MockInterface $logger;

    private LoggingGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inner = Mockery::mock(GatewayInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->gateway = new LoggingGateway($this->inner, $this->logger);

        $this->inner->allows('gateway')->andReturn(Gateway::NestPay);
    }

    #[Test]
    public function it_logs_start_and_completion_on_purchase(): void
    {
        $request = new PaymentRequest(
            amount: new Money(1050),
            orderId: 'ORD-001',
        );

        $response = new PaymentResponse(
            isSuccessful: true,
            status: TransactionStatus::Completed,
            gateway: Gateway::NestPay,
            transactionType: TransactionType::Purchase,
            transactionId: 'TXN-001',
            responseCode: '00',
        );

        $this->inner->expects('purchase')
            ->with($request)
            ->once()
            ->andReturn($response);

        $this->logger->expects('info')
            ->with('TruePos: purchase started', Mockery::on(function (array $ctx) {
                return $ctx['gateway'] === 'nestpay'
                    && $ctx['method'] === 'purchase'
                    && $ctx['identifier'] === 'ORD-001'
                    && $ctx['amount'] === '10.50';
            }))
            ->once();

        $this->logger->expects('info')
            ->with('TruePos: purchase completed', Mockery::on(function (array $ctx) {
                return $ctx['success'] === true
                    && $ctx['responseCode'] === '00'
                    && $ctx['transactionId'] === 'TXN-001';
            }))
            ->once();

        $result = $this->gateway->purchase($request);

        $this->assertSame($response, $result);
    }

    #[Test]
    public function it_logs_error_on_exception_during_purchase(): void
    {
        $request = new PaymentRequest(
            amount: new Money(1050),
            orderId: 'ORD-002',
        );

        $exception = new \RuntimeException('Connection timeout');

        $this->inner->expects('purchase')
            ->with($request)
            ->once()
            ->andThrow($exception);

        $this->logger->expects('info')
            ->with('TruePos: purchase started', Mockery::type('array'))
            ->once();

        $this->logger->expects('error')
            ->with('TruePos: purchase failed', Mockery::on(function (array $ctx) {
                return $ctx['exception'] === 'Connection timeout'
                    && $ctx['gateway'] === 'nestpay';
            }))
            ->once();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection timeout');

        $this->gateway->purchase($request);
    }

    #[Test]
    public function it_delegates_gateway_to_inner(): void
    {
        $result = $this->gateway->gateway();

        $this->assertSame(Gateway::NestPay, $result);
    }
}
