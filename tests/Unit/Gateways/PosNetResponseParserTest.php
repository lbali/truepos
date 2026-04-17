<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\Gateways\PosNet\PosNetResponseParser;

final class PosNetResponseParserTest extends TestCase
{
    private PosNetResponseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new PosNetResponseParser();
    }

    #[Test]
    public function it_parses_successful_purchase(): void
    {
        $response = $this->parser->parse([
            'approved' => '1',
            'hostlogkey' => '021590067890',
            'authCode' => '304919',
            'orderID' => '00000000TP20260101XYZ',
            'respCode' => '',
            'respText' => '',
        ], TransactionType::Purchase);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(Gateway::PosNet, $response->gateway);
        $this->assertSame('021590067890', $response->transactionId);
        $this->assertSame('304919', $response->authCode);
        $this->assertNull($response->errorCode);
    }

    #[Test]
    public function it_parses_declined_purchase(): void
    {
        $response = $this->parser->parse([
            'approved' => '0',
            'respCode' => '0005',
            'respText' => 'Red-Loss/Stolen Card',
            'hostlogkey' => '',
            'authCode' => '',
        ], TransactionType::Purchase);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(TransactionStatus::Failed, $response->status);
        $this->assertSame('0005', $response->errorCode);
        $this->assertSame('Red-Loss/Stolen Card', $response->errorMessage);
    }

    #[Test]
    public function it_parses_3d_callback(): void
    {
        $response = $this->parser->parseThreeDCallback([
            'MerchantPacket' => 'enc_merchant_data',
            'BankPacket' => 'enc_bank_data',
            'Sign' => 'signature',
            'mdStatus' => '1',
            'XID' => '00000000TP20260101ABC',
            'hostlogkey' => '021590067891',
            'authCode' => '304920',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('1', $response->mdStatus);
        $this->assertSame('00000000TP20260101ABC', $response->orderId);
        $this->assertSame(Gateway::PosNet, $response->gateway);
    }
}
