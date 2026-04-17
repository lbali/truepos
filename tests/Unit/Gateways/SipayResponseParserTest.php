<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\Gateways\Sipay\SipayResponseParser;

final class SipayResponseParserTest extends TestCase
{
    private SipayResponseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SipayResponseParser();
    }

    #[Test]
    public function it_parses_successful_purchase(): void
    {
        $response = $this->parser->parse([
            'status_code' => 100,
            'status_description' => 'success',
            'data' => [
                'order_id' => 'SP26041001',
                'invoice_id' => 'TP20260416SP001',
                'auth_code' => 'S33333',
            ],
        ], TransactionType::Purchase);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(Gateway::Sipay, $response->gateway);
        $this->assertSame('SP26041001', $response->transactionId);
        $this->assertSame('TP20260416SP001', $response->orderId);
        $this->assertSame('S33333', $response->authCode);
        $this->assertSame('100', $response->responseCode);
        $this->assertSame('success', $response->responseMessage);
        $this->assertNull($response->errorCode);
        $this->assertNull($response->errorMessage);
    }

    #[Test]
    public function it_parses_failed_purchase(): void
    {
        $response = $this->parser->parse([
            'status_code' => 99,
            'status_description' => 'Kart reddedildi',
            'data' => [],
        ], TransactionType::Purchase);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(TransactionStatus::Failed, $response->status);
        $this->assertSame('99', $response->responseCode);
        $this->assertSame('99', $response->errorCode);
        $this->assertSame('Kart reddedildi', $response->errorMessage);
    }

    #[Test]
    public function it_parses_3d_callback(): void
    {
        $response = $this->parser->parseThreeDCallback([
            'status_code' => '100',
            'status_description' => 'success',
            'order_id' => 'SP26041003',
            'invoice_id' => 'TP20260416SP003',
            'auth_code' => 'T44444',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(TransactionType::Purchase, $response->transactionType);
        $this->assertSame('SP26041003', $response->transactionId);
        $this->assertSame('TP20260416SP003', $response->orderId);
        $this->assertSame('T44444', $response->authCode);
        $this->assertSame('100', $response->responseCode);
        $this->assertNull($response->errorCode);
    }
}
