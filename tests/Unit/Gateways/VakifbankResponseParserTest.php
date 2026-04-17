<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\Gateways\Vakifbank\VakifbankResponseParser;

final class VakifbankResponseParserTest extends TestCase
{
    private VakifbankResponseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new VakifbankResponseParser();
    }

    #[Test]
    public function it_parses_successful_purchase(): void
    {
        $response = $this->parser->parse([
            'ResultCode' => '0000',
            'TransactionId' => 'VB26041001',
            'OrderId' => 'TP20260416VB001',
            'MerchantOrderId' => 'TP20260416VB001M',
            'AuthCode' => 'C11111',
            'ResultDetail' => 'Basarili',
            'Rrn' => '016016054321',
        ], TransactionType::Purchase);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(Gateway::Vakifbank, $response->gateway);
        $this->assertSame('VB26041001', $response->transactionId);
        $this->assertSame('TP20260416VB001', $response->orderId);
        $this->assertSame('C11111', $response->authCode);
        $this->assertSame('0000', $response->responseCode);
        $this->assertSame('Basarili', $response->responseMessage);
        $this->assertSame('016016054321', $response->hostReferenceNumber);
        $this->assertNull($response->errorCode);
        $this->assertNull($response->errorMessage);
    }

    #[Test]
    public function it_parses_failed_purchase(): void
    {
        $response = $this->parser->parse([
            'ResultCode' => '0005',
            'TransactionId' => 'VB26041002',
            'OrderId' => 'TP20260416VB002',
            'ResultDetail' => 'Red edildi',
        ], TransactionType::Purchase);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(TransactionStatus::Failed, $response->status);
        $this->assertSame('0005', $response->responseCode);
        $this->assertSame('0005', $response->errorCode);
        $this->assertSame('Red edildi', $response->errorMessage);
    }

    #[Test]
    public function it_parses_3d_callback(): void
    {
        $response = $this->parser->parseThreeDCallback([
            'ResultCode' => '0000',
            'TransactionId' => 'VB26041003',
            'MerchantOrderId' => 'TP20260416VB003',
            'AuthCode' => 'D22222',
            'ResultDetail' => 'Basarili',
            'MdStatus' => '1',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(TransactionType::Purchase, $response->transactionType);
        $this->assertSame('1', $response->mdStatus);
        $this->assertSame('TP20260416VB003', $response->orderId);
        $this->assertSame('D22222', $response->authCode);
        $this->assertNull($response->errorCode);
    }
}
