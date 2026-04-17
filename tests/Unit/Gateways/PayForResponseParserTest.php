<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\Gateways\PayFor\PayForResponseParser;

final class PayForResponseParserTest extends TestCase
{
    private PayForResponseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new PayForResponseParser();
    }

    #[Test]
    public function it_parses_successful_purchase(): void
    {
        $response = $this->parser->parse([
            'ProcReturnCode' => '00',
            'TransId' => 'PF26041001',
            'OrderId' => 'TP20260416ABC001',
            'AuthCode' => 'A12345',
            'Response' => 'Approved',
            'HostRefNum' => '016016012345',
        ], TransactionType::Purchase);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(Gateway::PayFor, $response->gateway);
        $this->assertSame('PF26041001', $response->transactionId);
        $this->assertSame('TP20260416ABC001', $response->orderId);
        $this->assertSame('A12345', $response->authCode);
        $this->assertSame('00', $response->responseCode);
        $this->assertSame('Approved', $response->responseMessage);
        $this->assertSame('016016012345', $response->hostReferenceNumber);
        $this->assertNull($response->errorCode);
        $this->assertNull($response->errorMessage);
    }

    #[Test]
    public function it_parses_declined_purchase(): void
    {
        $response = $this->parser->parse([
            'ProcReturnCode' => '05',
            'TransId' => 'PF26041002',
            'OrderId' => 'TP20260416DEF002',
            'Response' => 'Declined',
            'ErrCode' => 'ISO-05',
            'ErrMsg' => 'Genel red',
        ], TransactionType::Purchase);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(TransactionStatus::Failed, $response->status);
        $this->assertSame(Gateway::PayFor, $response->gateway);
        $this->assertSame('05', $response->responseCode);
        $this->assertSame('ISO-05', $response->errorCode);
        $this->assertSame('Genel red', $response->errorMessage);
    }

    #[Test]
    public function it_parses_3d_callback(): void
    {
        $response = $this->parser->parseThreeDCallback([
            'ProcReturnCode' => '00',
            'TransId' => 'PF26041003',
            'OrderId' => 'TP20260416GHI003',
            'AuthCode' => 'B67890',
            'Response' => 'Approved',
            'HostRefNum' => '016016012346',
            '3DStatus' => '1',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(TransactionType::Purchase, $response->transactionType);
        $this->assertSame('1', $response->mdStatus);
        $this->assertSame('TP20260416GHI003', $response->orderId);
        $this->assertSame('B67890', $response->authCode);
        $this->assertNull($response->errorCode);
    }
}
