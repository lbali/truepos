<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Gateways;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Gateways\Tosla\ToslaHashGenerator;

final class ToslaHashGeneratorTest extends TestCase
{
    private ToslaHashGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new ToslaHashGenerator();
    }

    #[Test]
    public function it_generates_hash_with_postman_test_credentials(): void
    {
        // From the Postman collection variables
        $credentials = [
            'clientId' => '1000000494',
            'apiUser' => 'POS_ENT_Test_001',
            'apiPass' => 'POS_ENT_Test_001!*!*',
        ];

        $parameters = [
            'clientId' => '1000000494',
            'apiUser' => 'POS_ENT_Test_001',
            'rnd' => 'prep8628',
            'timeSpan' => '20260417120000',
        ];

        // hash = Base64(SHA-512(apiPass + clientId + apiUser + rnd + timeSpan))
        $expectedPayload = 'POS_ENT_Test_001!*!*' . '1000000494' . 'POS_ENT_Test_001' . 'prep8628' . '20260417120000';
        $expectedHash = base64_encode(hash('sha512', $expectedPayload, true));

        $hash = $this->generator->generate($parameters, $credentials);

        $this->assertSame($expectedHash, $hash);
    }

    #[Test]
    public function it_produces_deterministic_output(): void
    {
        $credentials = [
            'clientId' => '1000000494',
            'apiUser' => 'POS_ENT_Test_001',
            'apiPass' => 'POS_ENT_Test_001!*!*',
        ];

        $parameters = [
            'clientId' => '1000000494',
            'apiUser' => 'POS_ENT_Test_001',
            'rnd' => 'fixed_rnd',
            'timeSpan' => '20260101150000',
        ];

        $hash1 = $this->generator->generate($parameters, $credentials);
        $hash2 = $this->generator->generate($parameters, $credentials);

        $this->assertSame($hash1, $hash2);
    }

    #[Test]
    public function it_verifies_matching_hash(): void
    {
        $credentials = [
            'clientId' => '1000000494',
            'apiUser' => 'POS_ENT_Test_001',
            'apiPass' => 'POS_ENT_Test_001!*!*',
        ];

        $parameters = [
            'clientId' => '1000000494',
            'apiUser' => 'POS_ENT_Test_001',
            'rnd' => 'test123',
            'timeSpan' => '20260417150000',
        ];

        $hash = $this->generator->generate($parameters, $credentials);

        $this->assertTrue($this->generator->verify($hash, $parameters, $credentials));
        $this->assertFalse($this->generator->verify('wrong_hash', $parameters, $credentials));
    }
}
