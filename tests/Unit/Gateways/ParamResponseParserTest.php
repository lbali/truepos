<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\Gateways\Param\ParamResponseParser;

final class ParamResponseParserTest extends TestCase
{
    private ParamResponseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ParamResponseParser();
    }

    #[Test]
    public function it_parses_successful_purchase(): void
    {
        $response = $this->parser->parse([
            'Sonuc' => 1,
            'Sonuc_Str' => 'Başarılı',
            'Dekont_ID' => '123456',
            'Siparis_ID' => 'ORD-001',
            'Banka_Sonuc_Kod' => 'P12345',
        ], TransactionType::Purchase);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(Gateway::Param, $response->gateway);
        $this->assertSame(TransactionType::Purchase, $response->transactionType);
        $this->assertSame('123456', $response->transactionId);
        $this->assertSame('ORD-001', $response->orderId);
        $this->assertSame('P12345', $response->authCode);
        $this->assertSame('00', $response->responseCode);
        $this->assertSame('Başarılı', $response->responseMessage);
        $this->assertNull($response->errorCode);
        $this->assertNull($response->errorMessage);
    }

    #[Test]
    public function it_parses_failed_purchase(): void
    {
        $response = $this->parser->parse([
            'Sonuc' => 0,
            'Sonuc_Str' => 'Hatalı İşlem',
            'Hata_Kodu' => '99',
            'Dekont_ID' => '',
            'Siparis_ID' => 'ORD-002',
        ], TransactionType::Purchase);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(TransactionStatus::Failed, $response->status);
        $this->assertSame(Gateway::Param, $response->gateway);
        $this->assertSame('99', $response->responseCode);
        $this->assertSame('99', $response->errorCode);
        $this->assertSame('Hatalı İşlem', $response->errorMessage);
    }

    #[Test]
    public function it_parses_3d_callback(): void
    {
        $response = $this->parser->parseThreeDCallback([
            'TURKPOS_RETVAL_Sonuc' => 1,
            'TURKPOS_RETVAL_Sonuc_Str' => 'İşlem başarılı',
            'TURKPOS_RETVAL_Dekont_ID' => '789012',
            'TURKPOS_RETVAL_Siparis_ID' => 'ORD-003',
            'mdStatus' => '1',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(TransactionType::Purchase, $response->transactionType);
        $this->assertSame('789012', $response->transactionId);
        $this->assertSame('ORD-003', $response->orderId);
        $this->assertSame('1', $response->mdStatus);
        $this->assertSame('00', $response->responseCode);
        $this->assertNull($response->errorCode);
    }
}
