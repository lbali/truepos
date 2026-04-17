<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Serializers;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Exceptions\GatewayException;
use TruePos\Serializers\JsonSerializer;

final class JsonSerializerTest extends TestCase
{
    #[Test]
    public function it_serializes_to_valid_json(): void
    {
        $serializer = new JsonSerializer();
        $data = ['key' => 'value', 'number' => 42];

        $result = $serializer->serialize($data);

        $this->assertJson($result);
        $this->assertSame($data, json_decode($result, true));
    }

    #[Test]
    public function it_deserializes_json_to_array(): void
    {
        $serializer = new JsonSerializer();
        $json = '{"key":"value","number":42}';

        $result = $serializer->deserialize($json);

        $this->assertSame(['key' => 'value', 'number' => 42], $result);
    }

    #[Test]
    public function it_throws_gateway_exception_on_invalid_json(): void
    {
        $serializer = new JsonSerializer('TestGateway');

        $this->expectException(GatewayException::class);

        $serializer->deserialize('not valid json {{{');
    }

    #[Test]
    public function it_returns_application_json_content_type(): void
    {
        $serializer = new JsonSerializer();

        $this->assertSame('application/json', $serializer->contentType());
    }

    #[Test]
    public function it_accepts_custom_content_type_via_constructor(): void
    {
        $serializer = new JsonSerializer(contentType: 'application/vnd.api+json');

        $this->assertSame('application/vnd.api+json', $serializer->contentType());
    }
}
