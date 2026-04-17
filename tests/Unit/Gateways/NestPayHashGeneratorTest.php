<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Gateways\NestPay\NestPayHashGenerator;

final class NestPayHashGeneratorTest extends TestCase
{
    private NestPayHashGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new NestPayHashGenerator();
    }

    #[Test]
    public function it_generates_hash(): void
    {
        $hash = $this->generator->generate(
            parameters: [
                'clientid' => 'test_client',
                'oid' => 'ORDER123',
                'amount' => '100.00',
                'okUrl' => 'https://example.com/ok',
                'failUrl' => 'https://example.com/fail',
                'TranType' => 'Auth',
                'Instalment' => '',
                'rnd' => 'test_random',
            ],
            credentials: ['storeKey' => 'test_key'],
        );

        $this->assertNotEmpty($hash);
        $this->assertIsString($hash);
    }

    #[Test]
    public function it_verifies_matching_hash(): void
    {
        $params = [
            'clientid' => 'test_client',
            'oid' => 'ORDER123',
            'amount' => '100.00',
            'okUrl' => 'https://example.com/ok',
            'failUrl' => 'https://example.com/fail',
            'TranType' => 'Auth',
            'Instalment' => '',
            'rnd' => 'test_random',
        ];
        $credentials = ['storeKey' => 'test_key'];

        $hash = $this->generator->generate($params, $credentials);

        $this->assertTrue($this->generator->verify($hash, $params, $credentials));
    }

    #[Test]
    public function it_rejects_mismatched_hash(): void
    {
        $params = [
            'clientid' => 'test_client',
            'oid' => 'ORDER123',
            'amount' => '100.00',
            'okUrl' => 'https://example.com/ok',
            'failUrl' => 'https://example.com/fail',
            'TranType' => 'Auth',
            'Instalment' => '',
            'rnd' => 'test_random',
        ];
        $credentials = ['storeKey' => 'test_key'];

        $this->assertFalse($this->generator->verify('wrong_hash', $params, $credentials));
    }

    #[Test]
    public function it_produces_deterministic_output(): void
    {
        $params = [
            'clientid' => 'abc',
            'oid' => 'ORD1',
            'amount' => '50.00',
            'okUrl' => 'https://ok.com',
            'failUrl' => 'https://fail.com',
            'TranType' => 'Auth',
            'Instalment' => '3',
            'rnd' => 'fixed_rnd',
        ];
        $credentials = ['storeKey' => 'mykey'];

        $hash1 = $this->generator->generate($params, $credentials);
        $hash2 = $this->generator->generate($params, $credentials);

        $this->assertSame($hash1, $hash2);
    }
}
