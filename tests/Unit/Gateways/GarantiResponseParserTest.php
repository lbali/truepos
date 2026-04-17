<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\Gateways\Garanti\GarantiResponseParser;

final class GarantiResponseParserTest extends TestCase
{
    private GarantiResponseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new GarantiResponseParser();
    }

    #[Test]
    public function it_parses_successful_purchase(): void
    {
        $response = $this->parser->parse([
            'Order' => ['OrderID' => 'TP20260101GHI789'],
            'Transaction' => [
                'Response' => [
                    'Code' => '00',
                    'Message' => 'Approved',
                    'ErrorCode' => '',
                    'SysErrMsg' => '',
                ],
                'RetrefNum' => '025015067891',
                'AuthCode' => '304919',
            ],
        ], TransactionType::Purchase);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(Gateway::Garanti, $response->gateway);
        $this->assertSame('025015067891', $response->transactionId);
        $this->assertSame('304919', $response->authCode);
    }

    #[Test]
    public function it_parses_declined_purchase(): void
    {
        $response = $this->parser->parse([
            'Order' => ['OrderID' => 'TP20260101JKL012'],
            'Transaction' => [
                'Response' => [
                    'Code' => '05',
                    'Message' => 'Declined',
                    'ErrorCode' => '05',
                    'SysErrMsg' => 'System error',
                ],
                'RetrefNum' => '',
                'AuthCode' => '',
            ],
        ], TransactionType::Purchase);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(TransactionStatus::Failed, $response->status);
        $this->assertSame('05', $response->errorCode);
    }
}
