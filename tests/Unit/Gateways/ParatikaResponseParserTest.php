<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\Gateways\Paratika\ParatikaResponseParser;

final class ParatikaResponseParserTest extends TestCase
{
    private ParatikaResponseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ParatikaResponseParser();
    }

    #[Test]
    public function it_parses_successful_payment(): void
    {
        $response = $this->parser->parse([
            'responseCode' => '00',
            'responseMsg' => 'Approved',
            'pgTranId' => 'PG-001',
            'merchantPaymentId' => 'ORD-001',
            'pgTranApprCode' => 'APPR01',
            'pgTranRefId' => 'HREF-001',
        ], TransactionType::Purchase);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(Gateway::Paratika, $response->gateway);
        $this->assertSame(TransactionType::Purchase, $response->transactionType);
        $this->assertSame('PG-001', $response->transactionId);
        $this->assertSame('ORD-001', $response->orderId);
        $this->assertSame('APPR01', $response->authCode);
        $this->assertSame('00', $response->responseCode);
        $this->assertSame('Approved', $response->responseMessage);
        $this->assertSame('HREF-001', $response->hostReferenceNumber);
        $this->assertNull($response->errorCode);
        $this->assertNull($response->errorMessage);
    }

    #[Test]
    public function it_parses_failed_payment(): void
    {
        $response = $this->parser->parse([
            'responseCode' => '99',
            'responseMsg' => 'Declined',
            'errorMsg' => 'Card limit exceeded',
            'pgTranId' => 'PG-002',
            'merchantPaymentId' => 'ORD-002',
        ], TransactionType::Purchase);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(TransactionStatus::Failed, $response->status);
        $this->assertSame('99', $response->responseCode);
        $this->assertSame('99', $response->errorCode);
        $this->assertSame('Card limit exceeded', $response->errorMessage);
    }

    #[Test]
    public function it_parses_3d_callback(): void
    {
        $response = $this->parser->parseThreeDCallback([
            'responseCode' => '00',
            'responseMsg' => 'Approved',
            'pgTranId' => 'PG-003',
            'merchantPaymentId' => 'ORD-003',
            'pgTranApprCode' => 'APPR03',
            'pgTranRefId' => 'HREF-003',
            'mdStatus' => '1',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(TransactionType::Purchase, $response->transactionType);
        $this->assertSame('PG-003', $response->transactionId);
        $this->assertSame('ORD-003', $response->orderId);
        $this->assertSame('00', $response->responseCode);
        $this->assertSame('1', $response->mdStatus);
        $this->assertSame('HREF-003', $response->hostReferenceNumber);
        $this->assertNull($response->errorCode);
    }
}
