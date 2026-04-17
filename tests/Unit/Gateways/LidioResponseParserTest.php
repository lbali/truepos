<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\Gateways\Lidio\LidioResponseParser;

final class LidioResponseParserTest extends TestCase
{
    private LidioResponseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new LidioResponseParser();
    }

    #[Test]
    public function it_parses_successful_payment(): void
    {
        $response = $this->parser->parse([
            'result' => 'Success',
            'resultMessage' => 'Payment completed',
            'paymentInfo' => [
                'systemTransId' => 'SYS-001',
                'orderId' => 'ORD-001',
                'instrumentDetail' => [
                    'acquirerResultDetail' => [
                        'authCode' => 'AUTH01',
                    ],
                ],
            ],
        ], TransactionType::Purchase);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(Gateway::Lidio, $response->gateway);
        $this->assertSame(TransactionType::Purchase, $response->transactionType);
        $this->assertSame('SYS-001', $response->transactionId);
        $this->assertSame('ORD-001', $response->orderId);
        $this->assertSame('AUTH01', $response->authCode);
        $this->assertSame('00', $response->responseCode);
        $this->assertSame('Payment completed', $response->responseMessage);
        $this->assertSame('SYS-001', $response->hostReferenceNumber);
        $this->assertNull($response->errorCode);
        $this->assertNull($response->errorMessage);
    }

    #[Test]
    public function it_parses_3d_callback_success(): void
    {
        $response = $this->parser->parseThreeDCallback([
            'Result' => '3DSuccess',
            'SystemTransId' => 'SYS-002',
            'OrderId' => 'ORD-002',
            'MDStatus' => '1',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Processing, $response->status);
        $this->assertSame(TransactionType::Purchase, $response->transactionType);
        $this->assertSame('SYS-002', $response->transactionId);
        $this->assertSame('ORD-002', $response->orderId);
        $this->assertSame('00', $response->responseCode);
        $this->assertSame('3DSuccess', $response->responseMessage);
        $this->assertSame('1', $response->mdStatus);
        $this->assertNull($response->errorCode);
        $this->assertNull($response->errorMessage);
    }

    #[Test]
    public function it_parses_failed_payment(): void
    {
        $response = $this->parser->parse([
            'result' => 'Refused',
            'resultDetail' => 'InsufficientFunds',
            'resultMessage' => 'Yetersiz bakiye',
        ], TransactionType::Purchase);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(TransactionStatus::Failed, $response->status);
        $this->assertSame('InsufficientFunds', $response->responseCode);
        $this->assertSame('InsufficientFunds', $response->errorCode);
        $this->assertSame('Yetersiz bakiye', $response->errorMessage);
    }
}
