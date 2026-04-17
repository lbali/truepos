<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Support;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Support\SensitiveDataRedactor;

final class SensitiveDataRedactorTest extends TestCase
{
    #[Test]
    public function it_redacts_card_number_field(): void
    {
        $data = ['CardNumber' => '4111111111111111'];

        $result = SensitiveDataRedactor::redact($data);

        $this->assertSame('41************11', $result['CardNumber']);
    }

    #[Test]
    public function it_redacts_cvv_field(): void
    {
        $data = ['cvv' => '123'];

        $result = SensitiveDataRedactor::redact($data);

        $this->assertSame('***', $result['cvv']);
    }

    #[Test]
    public function it_redacts_password_and_secret_fields(): void
    {
        $data = [
            'Password' => 'mysecretpassword',
            'Secret' => 'topsecretvalue',
        ];

        $result = SensitiveDataRedactor::redact($data);

        $this->assertSame('my************rd', $result['Password']);
        $this->assertSame('to**********ue', $result['Secret']);
    }

    #[Test]
    public function it_keeps_non_sensitive_fields_intact(): void
    {
        $data = [
            'orderId' => 'ORD-001',
            'amount' => '10.50',
            'currency' => 'TRY',
        ];

        $result = SensitiveDataRedactor::redact($data);

        $this->assertSame($data, $result);
    }

    #[Test]
    public function it_handles_nested_arrays(): void
    {
        $data = [
            'payment' => [
                'CardNumber' => '5400000000000001',
                'orderId' => 'ORD-001',
            ],
        ];

        $result = SensitiveDataRedactor::redact($data);

        $this->assertSame('54************01', $result['payment']['CardNumber']);
        $this->assertSame('ORD-001', $result['payment']['orderId']);
    }

    #[Test]
    public function it_handles_short_values_with_stars(): void
    {
        $data = ['pan' => '1234'];

        $result = SensitiveDataRedactor::redact($data);

        $this->assertSame('***', $result['pan']);
    }
}
