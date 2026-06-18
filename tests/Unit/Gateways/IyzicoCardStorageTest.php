<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TruePos\Builder\PaymentRequestBuilder;
use TruePos\Contracts\CardStorageInterface;
use TruePos\DataTransferObjects\StoredCardChargeRequest;
use TruePos\Enums\TransactionType;
use TruePos\Gateways\Iyzico\IyzicoGateway;
use TruePos\Gateways\Iyzico\IyzicoHashGenerator;
use TruePos\Gateways\Iyzico\IyzicoResponseParser;
use TruePos\Serializers\JsonSerializer;
use TruePos\ValueObjects\CreditCard;
use TruePos\ValueObjects\Money;

final class IyzicoCardStorageTest extends TestCase
{
    #[Test]
    public function iyzico_gateway_advertises_card_storage_capability(): void
    {
        $this->assertTrue(
            is_subclass_of(IyzicoGateway::class, CardStorageInterface::class),
            'IyzicoGateway CardStorageInterface implement etmeli.'
        );
    }

    #[Test]
    public function parser_surfaces_stored_card_tokens_on_purchase(): void
    {
        $response = (new IyzicoResponseParser)->parse([
            'status' => 'success',
            'paymentId' => '99',
            'basketId' => 'ORD-1',
            'cardUserKey' => 'CUK-abc',
            'cardToken' => 'CTOK-xyz',
        ], TransactionType::Purchase);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('CUK-abc', $response->cardUserKey);
        $this->assertSame('CTOK-xyz', $response->cardToken);
    }

    #[Test]
    public function parser_surfaces_stored_card_tokens_on_threed_callback(): void
    {
        $response = (new IyzicoResponseParser)->parseThreeDCallback([
            'status' => 'success',
            'paymentId' => '99',
            'cardUserKey' => 'CUK-3d',
            'cardToken' => 'CTOK-3d',
        ]);

        $this->assertSame('CUK-3d', $response->cardUserKey);
        $this->assertSame('CTOK-3d', $response->cardToken);
    }

    #[Test]
    public function parser_leaves_tokens_null_when_card_not_stored(): void
    {
        $response = (new IyzicoResponseParser)->parse([
            'status' => 'success',
            'paymentId' => '99',
        ], TransactionType::Purchase);

        $this->assertNull($response->cardUserKey);
        $this->assertNull($response->cardToken);
    }

    #[Test]
    public function builder_propagates_store_card_flag(): void
    {
        $request = PaymentRequestBuilder::create()
            ->amount(Money::fromDecimal(10.0))
            ->orderId('ORD-1')
            ->card(new CreditCard('5528790000000008', '12', '2030', '123', 'Test User'))
            ->storeCard()
            ->regular()
            ->build();

        $this->assertTrue($request->storeCard);
    }

    #[Test]
    public function payment_request_does_not_store_card_by_default(): void
    {
        $request = PaymentRequestBuilder::create()
            ->amount(Money::fromDecimal(10.0))
            ->orderId('ORD-1')
            ->card(new CreditCard('5528790000000008', '12', '2030', '123', 'Test User'))
            ->regular()
            ->build();

        $this->assertFalse($request->storeCard);
    }

    #[Test]
    public function iyzico_emits_pki_authorization_header_for_server_to_server(): void
    {
        $httpClient = new class implements ClientInterface
        {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                throw new \RuntimeException('HTTP çağrısı bu testte kullanılmaz.');
            }
        };

        $gateway = new IyzicoGateway(
            config: ['api_key' => 'KEY', 'secret_key' => 'SECRET', 'payment_url' => 'https://x', 'threed_gateway_url' => 'https://y'],
            serializer: new JsonSerializer,
            hashGenerator: new IyzicoHashGenerator,
            responseParser: new IyzicoResponseParser,
            httpClient: $httpClient,
        );

        $method = new \ReflectionMethod($gateway, 'buildHttpHeaders');
        $method->setAccessible(true);
        $headers = $method->invoke($gateway, ['_authorization' => 'HASH', '_random' => 'RND']);

        $this->assertSame('IYZWS KEY:HASH', $headers['Authorization']);
        $this->assertSame('RND', $headers['x-iyzi-rnd']);
    }

    #[Test]
    public function stored_card_charge_request_holds_tokens(): void
    {
        $request = new StoredCardChargeRequest(
            amount: Money::fromDecimal(50.0),
            orderId: 'SR-1',
            cardUserKey: 'CUK',
            cardToken: 'CTOK',
        );

        $this->assertSame('CUK', $request->cardUserKey);
        $this->assertSame('CTOK', $request->cardToken);
        $this->assertSame('SR-1', $request->orderId);
        $this->assertFalse($request->hasInstallment());
    }
}
