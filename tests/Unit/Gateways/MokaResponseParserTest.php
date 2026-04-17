<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Enums\Gateway;
use TruePos\Enums\TransactionStatus;
use TruePos\Enums\TransactionType;
use TruePos\Gateways\Moka\MokaResponseParser;

final class MokaResponseParserTest extends TestCase
{
    private MokaResponseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new MokaResponseParser();
    }

    #[Test]
    public function it_parses_successful_purchase(): void
    {
        $response = $this->parser->parse([
            'ResultCode' => 'Success',
            'ResultMessage' => 'Basarili',
            'Data' => [
                'VirtualPosOrderId' => 'MK26041001',
                'OtherTrxCode' => 'TP20260416MK001',
                'ApprovalCode' => 'M55555',
            ],
        ], TransactionType::Purchase);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(Gateway::Moka, $response->gateway);
        $this->assertSame('MK26041001', $response->transactionId);
        $this->assertSame('TP20260416MK001', $response->orderId);
        $this->assertSame('M55555', $response->authCode);
        $this->assertSame('00', $response->responseCode);
        $this->assertSame('Basarili', $response->responseMessage);
        $this->assertSame('MK26041001', $response->hostReferenceNumber);
        $this->assertNull($response->errorCode);
        $this->assertNull($response->errorMessage);
    }

    #[Test]
    public function it_parses_failed_purchase(): void
    {
        $response = $this->parser->parse([
            'ResultCode' => 'PaymentDealer.CheckPaymentDealerAuthentication.InvalidRequest',
            'ResultMessage' => 'Gecersiz istek',
            'Data' => [],
        ], TransactionType::Purchase);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(TransactionStatus::Failed, $response->status);
        $this->assertSame('PaymentDealer.CheckPaymentDealerAuthentication.InvalidRequest', $response->responseCode);
        $this->assertSame('PaymentDealer.CheckPaymentDealerAuthentication.InvalidRequest', $response->errorCode);
        $this->assertSame('Gecersiz istek', $response->errorMessage);
    }

    #[Test]
    public function it_parses_3d_callback(): void
    {
        $response = $this->parser->parseThreeDCallback([
            'resultCode' => 'Success',
            'resultMessage' => 'Basarili',
            'trxCode' => 'MK26041003',
            'otherTrxCode' => 'TP20260416MK003',
            'approvalCode' => 'N77777',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(TransactionStatus::Completed, $response->status);
        $this->assertSame(TransactionType::Purchase, $response->transactionType);
        $this->assertSame('MK26041003', $response->transactionId);
        $this->assertSame('TP20260416MK003', $response->orderId);
        $this->assertSame('N77777', $response->authCode);
        $this->assertSame('00', $response->responseCode);
        $this->assertNull($response->errorCode);
    }
}
