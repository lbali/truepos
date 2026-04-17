<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\Gateways\Craftgate\CraftgateResponseParser;

final class CraftgateResponseParserTest extends TestCase
{
    private CraftgateResponseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CraftgateResponseParser();
    }

    #[Test]
    public function it_parses_successful_payment(): void
    {
        $response = $this->parser->parse([
            'data' => [
                'paymentStatus' => 'SUCCESS',
                'id' => 12345,
                'conversationId' => 'ORD-001',
                'authCode' => 'AUTH99',
                'hostReference' => 'HREF-001',
            ],
        ], TransactionType::Purchase);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(Gateway::Craftgate, $response->gateway);
        $this->assertSame(TransactionType::Purchase, $response->transactionType);
        $this->assertSame('12345', $response->transactionId);
        $this->assertSame('ORD-001', $response->orderId);
        $this->assertSame('AUTH99', $response->authCode);
        $this->assertSame('00', $response->responseCode);
        $this->assertSame('Success', $response->responseMessage);
        $this->assertSame('HREF-001', $response->hostReferenceNumber);
        $this->assertNull($response->errorCode);
        $this->assertNull($response->errorMessage);
    }

    #[Test]
    public function it_parses_failed_payment_with_errors(): void
    {
        $response = $this->parser->parse([
            'errors' => [
                [
                    'errorCode' => '10051',
                    'errorDescription' => 'Yetersiz bakiye',
                ],
            ],
            'data' => [
                'paymentStatus' => 'FAILURE',
                'id' => 12346,
                'conversationId' => 'ORD-002',
            ],
        ], TransactionType::Purchase);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(TransactionStatus::Failed, $response->status);
        $this->assertSame('10051', $response->errorCode);
        $this->assertSame('Yetersiz bakiye', $response->errorMessage);
        $this->assertSame('10051', $response->responseCode);
        $this->assertSame('Yetersiz bakiye', $response->responseMessage);
    }

    #[Test]
    public function it_parses_3d_callback_success(): void
    {
        $response = $this->parser->parseThreeDCallback([
            'status' => 'SUCCESS',
            'paymentId' => 78901,
            'conversationId' => 'ORD-003',
            'completeStatus' => 'COMPLETE',
            'callbackStatus' => 'SUCCESS',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(TransactionType::Purchase, $response->transactionType);
        $this->assertSame('78901', $response->transactionId);
        $this->assertSame('ORD-003', $response->orderId);
        $this->assertSame('00', $response->responseCode);
        $this->assertSame('SUCCESS', $response->responseMessage);
        $this->assertSame('COMPLETE', $response->mdStatus);
        $this->assertNull($response->errorCode);
        $this->assertNull($response->errorMessage);
    }
}
