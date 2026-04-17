<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\Gateways\EsnekPos\EsnekPosResponseParser;

final class EsnekPosResponseParserTest extends TestCase
{
    private EsnekPosResponseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new EsnekPosResponseParser();
    }

    #[Test]
    public function it_parses_successful_payment(): void
    {
        $response = $this->parser->parse([
            'STATUS' => 'SUCCESS',
            'RETURN_CODE' => '0',
            'RETURN_MESSAGE' => 'Approved',
            'REFNO' => 'REF-001',
            'ORDER_REF_NUMBER' => 'ORD-001',
            'BANK_AUTH_CODE' => 'BA001',
        ], TransactionType::Purchase);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(Gateway::EsnekPos, $response->gateway);
        $this->assertSame(TransactionType::Purchase, $response->transactionType);
        $this->assertSame('REF-001', $response->transactionId);
        $this->assertSame('ORD-001', $response->orderId);
        $this->assertSame('BA001', $response->authCode);
        $this->assertSame('0', $response->responseCode);
        $this->assertSame('Approved', $response->responseMessage);
        $this->assertSame('REF-001', $response->hostReferenceNumber);
        $this->assertNull($response->errorCode);
        $this->assertNull($response->errorMessage);
    }

    #[Test]
    public function it_parses_failed_payment(): void
    {
        $response = $this->parser->parse([
            'STATUS' => 'FAILED',
            'RETURN_CODE' => '1001',
            'RETURN_MESSAGE' => 'Insufficient funds',
            'REFNO' => 'REF-002',
            'ORDER_REF_NUMBER' => 'ORD-002',
        ], TransactionType::Purchase);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(TransactionStatus::Failed, $response->status);
        $this->assertSame('1001', $response->responseCode);
        $this->assertSame('1001', $response->errorCode);
        $this->assertSame('Insufficient funds', $response->errorMessage);
    }

    #[Test]
    public function it_parses_3d_callback(): void
    {
        $response = $this->parser->parseThreeDCallback([
            'STATUS' => 'SUCCESS',
            'RETURN_CODE' => '0',
            'RETURN_MESSAGE' => 'Approved',
            'REFNO' => 'REF-003',
            'ORDER_REF_NUMBER' => 'ORD-003',
            'BANK_AUTH_CODE' => 'BA003',
            'IS_NOT_3D_PAYMENT' => '0',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(TransactionType::Purchase, $response->transactionType);
        $this->assertSame('REF-003', $response->transactionId);
        $this->assertSame('ORD-003', $response->orderId);
        $this->assertSame('0', $response->responseCode);
        $this->assertSame('0', $response->mdStatus);
        $this->assertSame('REF-003', $response->hostReferenceNumber);
        $this->assertNull($response->errorCode);
    }
}
