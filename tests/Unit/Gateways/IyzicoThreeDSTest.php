<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TruePos\DataTransferObjects\ThreeDSecureData;
use TruePos\Exceptions\ThreeDSecureException;
use TruePos\Gateways\Iyzico\IyzicoGateway;
use TruePos\Gateways\Iyzico\IyzicoHashGenerator;
use TruePos\Gateways\Iyzico\IyzicoResponseParser;
use TruePos\Serializers\JsonSerializer;

final class IyzicoThreeDSTest extends TestCase
{
    #[Test]
    public function iyzico_uses_server_side_three_d_initialize(): void
    {
        $this->assertTrue($this->invoke($this->gateway(), 'threeDUsesServerInitialize'));
    }

    #[Test]
    public function parse_three_d_initialize_decodes_html_content(): void
    {
        $html = '<html><body>3DS</body></html>';
        $result = $this->invoke($this->gateway(), 'parseThreeDInitialize', [
            'status' => 'success',
            'threeDSHtmlContent' => base64_encode($html),
        ]);

        $this->assertInstanceOf(ThreeDSecureData::class, $result);
        $this->assertTrue($result->hasHtmlContent());
        $this->assertSame($html, $result->htmlContent);
    }

    #[Test]
    public function parse_three_d_initialize_throws_on_failure(): void
    {
        $this->expectException(ThreeDSecureException::class);
        $this->invoke($this->gateway(), 'parseThreeDInitialize', [
            'status' => 'failure',
            'errorMessage' => 'Kart hatalı',
        ]);
    }

    #[Test]
    public function three_d_provision_endpoint_targets_3dsecure_auth(): void
    {
        $this->assertSame(
            'https://sandbox-api.iyzipay.com/payment/3dsecure/auth',
            $this->invoke($this->gateway(), 'threeDProvisionEndpoint'),
        );
    }

    #[Test]
    public function validate_callback_requires_payment_id_and_conversation_id(): void
    {
        $gateway = $this->gateway();
        $this->assertTrue($gateway->validateThreeDCallbackPayload(['paymentId' => '123', 'conversationId' => 'ORD-1']));
        $this->assertFalse($gateway->validateThreeDCallbackPayload(['conversationId' => 'ORD-1']));
        $this->assertFalse($gateway->validateThreeDCallbackPayload([]));
    }

    #[Test]
    public function provision_parameters_include_conversation_data_when_present(): void
    {
        $params = $this->invoke($this->gateway(), 'buildThreeDProvisionParameters', [
            'conversationId' => 'ORD-1',
            'paymentId' => '123',
            'conversationData' => 'CDATA',
        ]);

        $this->assertSame('123', $params['paymentId']);
        $this->assertSame('CDATA', $params['conversationData']);
    }

    private function gateway(): IyzicoGateway
    {
        $httpClient = new class implements ClientInterface
        {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                throw new \RuntimeException('HTTP çağrısı bu testte kullanılmaz.');
            }
        };

        return new IyzicoGateway(
            config: [
                'api_key' => 'KEY',
                'secret_key' => 'SECRET',
                'payment_url' => 'https://sandbox-api.iyzipay.com/payment/auth',
                'threed_gateway_url' => 'https://sandbox-api.iyzipay.com/payment/3dsecure/initialize',
            ],
            serializer: new JsonSerializer,
            hashGenerator: new IyzicoHashGenerator,
            responseParser: new IyzicoResponseParser,
            httpClient: $httpClient,
        );
    }

    private function invoke(IyzicoGateway $gateway, string $method, mixed ...$args): mixed
    {
        $ref = new \ReflectionMethod($gateway, $method);
        $ref->setAccessible(true);

        return $ref->invoke($gateway, ...$args);
    }
}
