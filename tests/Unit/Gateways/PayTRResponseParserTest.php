<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\Gateways\PayTR\PayTRResponseParser;

final class PayTRResponseParserTest extends TestCase
{
    private PayTRResponseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new PayTRResponseParser();
    }

    #[Test]
    public function it_parses_successful_purchase(): void
    {
        $response = $this->parser->parse([
            'status' => 'success',
            'trans_id' => 'PTR26041001',
            'merchant_oid' => 'TP20260416PTR001',
            'reason' => 'Basarili',
        ], TransactionType::Purchase);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(Gateway::PayTR, $response->gateway);
        $this->assertSame('PTR26041001', $response->transactionId);
        $this->assertSame('TP20260416PTR001', $response->orderId);
        $this->assertNull($response->authCode);
        $this->assertSame('00', $response->responseCode);
        $this->assertSame('Basarili', $response->responseMessage);
        $this->assertNull($response->errorCode);
        $this->assertNull($response->errorMessage);
    }

    #[Test]
    public function it_parses_failed_purchase(): void
    {
        $response = $this->parser->parse([
            'status' => 'failed',
            'merchant_oid' => 'TP20260416PTR002',
            'err_no' => 'E001',
            'err_msg' => 'Kart reddedildi',
            'reason' => 'Genel hata',
        ], TransactionType::Purchase);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(TransactionStatus::Failed, $response->status);
        $this->assertSame('E001', $response->responseCode);
        $this->assertSame('E001', $response->errorCode);
        $this->assertSame('Kart reddedildi', $response->errorMessage);
    }

    #[Test]
    public function it_parses_3d_callback(): void
    {
        $response = $this->parser->parseThreeDCallback([
            'status' => 'success',
            'trans_id' => 'PTR26041003',
            'merchant_oid' => 'TP20260416PTR003',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(TransactionType::Purchase, $response->transactionType);
        $this->assertSame('PTR26041003', $response->transactionId);
        $this->assertSame('TP20260416PTR003', $response->orderId);
        $this->assertSame('00', $response->responseCode);
        $this->assertNull($response->errorCode);
    }
}
