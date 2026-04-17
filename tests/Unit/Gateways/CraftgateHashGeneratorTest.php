<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Gateways\Craftgate\CraftgateHashGenerator;

final class CraftgateHashGeneratorTest extends TestCase
{
    private CraftgateHashGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new CraftgateHashGenerator();
    }

    #[Test]
    public function it_generates_deterministic_output(): void
    {
        $parameters = [
            '_path' => '/payment/v1/card-payments',
            '_rnd' => 'fixed-random-string',
            '_body' => '{"price":100}',
        ];
        $credentials = [
            'apiKey' => 'sandbox-api-key',
            'secretKey' => 'sandbox-secret-key',
            'baseUrl' => 'https://sandbox-api.craftgate.io',
        ];

        $hash1 = $this->generator->generate($parameters, $credentials);
        $hash2 = $this->generator->generate($parameters, $credentials);

        $this->assertSame($hash1, $hash2);
    }

    #[Test]
    public function it_generates_base64_sha256_hash(): void
    {
        $parameters = [
            '_path' => '/payment/v1/card-payments',
            '_rnd' => 'test-rnd',
            '_body' => '{"price":50}',
        ];
        $credentials = [
            'apiKey' => 'my-api-key',
            'secretKey' => 'my-secret-key',
            'baseUrl' => 'https://api.craftgate.io',
        ];

        // hashString = baseUrl + urlDecode(path) + apiKey + secretKey + randomString + requestBody
        $hashString = 'https://api.craftgate.io/payment/v1/card-paymentsmy-api-keymy-secret-keytest-rnd{"price":50}';
        $expected = base64_encode(hash('sha256', $hashString, true));

        $result = $this->generator->generate($parameters, $credentials);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function it_verifies_3ds_callback_hash(): void
    {
        $secretKey = 'my-secret-key';
        $parameters = [
            'status' => 'SUCCESS',
            'completeStatus' => 'COMPLETE',
            'paymentId' => '12345',
            'conversationData' => '',
            'conversationId' => 'ORD-001',
            'callbackStatus' => 'SUCCESS',
        ];
        $credentials = [
            'secretKey' => $secretKey,
        ];

        // hash = SHA-256(hashKey###status###completeStatus###paymentId###conversationData###conversationId###callbackStatus)
        $hashString = $secretKey . '###SUCCESS###COMPLETE###12345######ORD-001###SUCCESS';
        $expected = hash('sha256', $hashString);

        $this->assertTrue($this->generator->verify($expected, $parameters, $credentials));
    }
}
