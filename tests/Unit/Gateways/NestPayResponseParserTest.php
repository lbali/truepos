<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\Gateways\NestPay\NestPayResponseParser;

final class NestPayResponseParserTest extends TestCase
{
    private NestPayResponseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new NestPayResponseParser();
    }

    #[Test]
    public function it_parses_successful_purchase(): void
    {
        $response = $this->parser->parse([
            'Response' => 'Approved',
            'ProcReturnCode' => '00',
            'TransId' => '26041IhKH15201',
            'OrderId' => 'TP20260101ABC123',
            'AuthCode' => 'P90325',
            'HostRefNum' => '025015067890',
        ], TransactionType::Purchase);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(Gateway::NestPay, $response->gateway);
        $this->assertSame('26041IhKH15201', $response->transactionId);
        $this->assertSame('P90325', $response->authCode);
        $this->assertNull($response->errorCode);
    }

    #[Test]
    public function it_parses_declined_purchase(): void
    {
        $response = $this->parser->parse([
            'Response' => 'Declined',
            'ProcReturnCode' => '05',
            'TransId' => '26041IhKH15202',
            'OrderId' => 'TP20260101DEF456',
            'ErrMsg' => 'Genel red',
            'ErrCode' => '05',
        ], TransactionType::Purchase);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(TransactionStatus::Failed, $response->status);
        $this->assertSame('05', $response->errorCode);
        $this->assertSame('Genel red', $response->errorMessage);
    }

    #[Test]
    public function it_parses_3d_callback(): void
    {
        $response = $this->parser->parseThreeDCallback([
            'Response' => 'Approved',
            'ProcReturnCode' => '00',
            'TransId' => '26041IhKH15203',
            'oid' => 'TP20260101GHI789',
            'AuthCode' => 'P90326',
            'mdStatus' => '1',
            'HostRefNum' => '025015067891',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('1', $response->mdStatus);
        $this->assertSame('TP20260101GHI789', $response->orderId);
    }
}
