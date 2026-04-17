<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\Gateways\KuveytTurk\KuveytTurkResponseParser;

final class KuveytTurkResponseParserTest extends TestCase
{
    private KuveytTurkResponseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new KuveytTurkResponseParser();
    }

    #[Test]
    public function it_parses_successful_purchase(): void
    {
        $response = $this->parser->parse([
            'ResponseCode' => '00',
            'ProvisionNumber' => 'KT26041001',
            'MerchantOrderId' => 'TP20260416KT001',
            'ResponseMessage' => 'Basarili',
            'RRN' => '016016098765',
        ], TransactionType::Purchase);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(Gateway::KuveytTurk, $response->gateway);
        $this->assertSame('KT26041001', $response->transactionId);
        $this->assertSame('TP20260416KT001', $response->orderId);
        $this->assertSame('KT26041001', $response->authCode);
        $this->assertSame('00', $response->responseCode);
        $this->assertSame('Basarili', $response->responseMessage);
        $this->assertSame('016016098765', $response->hostReferenceNumber);
        $this->assertNull($response->errorCode);
        $this->assertNull($response->errorMessage);
    }

    #[Test]
    public function it_parses_failed_purchase(): void
    {
        $response = $this->parser->parse([
            'ResponseCode' => '51',
            'MerchantOrderId' => 'TP20260416KT002',
            'ResponseMessage' => 'Yetersiz bakiye',
        ], TransactionType::Purchase);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(TransactionStatus::Failed, $response->status);
        $this->assertSame('51', $response->responseCode);
        $this->assertSame('51', $response->errorCode);
        $this->assertSame('Yetersiz bakiye', $response->errorMessage);
    }

    #[Test]
    public function it_parses_3d_callback(): void
    {
        $response = $this->parser->parseThreeDCallback([
            'ResponseCode' => '00',
            'ProvisionNumber' => 'KT26041003',
            'MerchantOrderId' => 'TP20260416KT003',
            'ResponseMessage' => 'Basarili',
            'MD' => '1',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(TransactionType::Purchase, $response->transactionType);
        $this->assertSame('1', $response->mdStatus);
        $this->assertSame('TP20260416KT003', $response->orderId);
        $this->assertSame('KT26041003', $response->authCode);
        $this->assertNull($response->errorCode);
    }
}
