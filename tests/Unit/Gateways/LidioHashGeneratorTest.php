<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Gateways\Lidio\LidioHashGenerator;

final class LidioHashGeneratorTest extends TestCase
{
    private LidioHashGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new LidioHashGenerator();
    }

    #[Test]
    public function it_verifies_correct_hash(): void
    {
        $parameters = [
            'OrderId' => 'ORD-001',
            'TotalAmount' => '100.50',
            'Result' => '3DSuccess',
            'Email' => 'test@example.com',
        ];
        $credentials = [
            'merchantKey' => 'SECRET_KEY',
        ];

        // Hash formula: Base64(SHA-256(OrderId:MerchantKey:TotalAmount:Result:Email))
        $hashData = 'ORD-001:SECRET_KEY:100.50:3DSuccess:test@example.com';
        $expected = base64_encode(hash('sha256', $hashData, true));

        $this->assertTrue($this->generator->verify($expected, $parameters, $credentials));
    }

    #[Test]
    public function it_rejects_wrong_hash(): void
    {
        $parameters = [
            'OrderId' => 'ORD-001',
            'TotalAmount' => '100.50',
            'Result' => '3DSuccess',
            'Email' => 'test@example.com',
        ];
        $credentials = [
            'merchantKey' => 'SECRET_KEY',
        ];

        $this->assertFalse($this->generator->verify('wrong-hash-value', $parameters, $credentials));
    }

    #[Test]
    public function it_formats_amount_with_two_decimal_places(): void
    {
        $parameters = [
            'OrderId' => 'ORD-002',
            'TotalAmount' => '50',
            'Result' => 'Success',
            'Email' => 'user@example.com',
        ];
        $credentials = [
            'merchantKey' => 'KEY123',
        ];

        // Amount "50" should be formatted as "50.00"
        $hashData = 'ORD-002:KEY123:50.00:Success:user@example.com';
        $expected = base64_encode(hash('sha256', $hashData, true));

        $this->assertTrue($this->generator->verify($expected, $parameters, $credentials));
    }
}
