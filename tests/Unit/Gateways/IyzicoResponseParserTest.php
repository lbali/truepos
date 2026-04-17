<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\Gateways\Iyzico\IyzicoResponseParser;

final class IyzicoResponseParserTest extends TestCase
{
    private IyzicoResponseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new IyzicoResponseParser();
    }

    #[Test]
    public function it_parses_successful_purchase(): void
    {
        $response = $this->parser->parse([
            'status' => 'success',
            'paymentId' => 'IYZ26041001',
            'basketId' => 'TP20260416IYZ001',
            'authCode' => 'X99999',
            'hostReference' => '016016011111',
        ], TransactionType::Purchase);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(Gateway::Iyzico, $response->gateway);
        $this->assertSame('IYZ26041001', $response->transactionId);
        $this->assertSame('TP20260416IYZ001', $response->orderId);
        $this->assertSame('X99999', $response->authCode);
        $this->assertSame('00', $response->responseCode);
        $this->assertSame('success', $response->responseMessage);
        $this->assertSame('016016011111', $response->hostReferenceNumber);
        $this->assertNull($response->errorCode);
        $this->assertNull($response->errorMessage);
    }

    #[Test]
    public function it_parses_failed_purchase(): void
    {
        $response = $this->parser->parse([
            'status' => 'failure',
            'paymentId' => 'IYZ26041002',
            'basketId' => 'TP20260416IYZ002',
            'errorCode' => '10051',
            'errorMessage' => 'Yetersiz bakiye',
        ], TransactionType::Purchase);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(TransactionStatus::Failed, $response->status);
        $this->assertSame('10051', $response->responseCode);
        $this->assertSame('10051', $response->errorCode);
        $this->assertSame('Yetersiz bakiye', $response->errorMessage);
    }

    #[Test]
    public function it_parses_3d_callback(): void
    {
        $response = $this->parser->parseThreeDCallback([
            'status' => 'success',
            'paymentId' => 'IYZ26041003',
            'basketId' => 'TP20260416IYZ003',
            'authCode' => 'Y88888',
            'mdStatus' => '1',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(TransactionType::Purchase, $response->transactionType);
        $this->assertSame('1', $response->mdStatus);
        $this->assertSame('TP20260416IYZ003', $response->orderId);
        $this->assertSame('Y88888', $response->authCode);
        $this->assertNull($response->errorCode);
    }
}
