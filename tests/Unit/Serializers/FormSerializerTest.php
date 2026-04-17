<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Serializers;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Exceptions\GatewayException;
use TruePos\Serializers\FormSerializer;

final class FormSerializerTest extends TestCase
{
    #[Test]
    public function it_serializes_to_url_encoded_string(): void
    {
        $serializer = new FormSerializer();
        $data = ['key' => 'value', 'name' => 'hello world'];

        $result = $serializer->serialize($data);

        $this->assertSame('key=value&name=hello+world', $result);
    }

    #[Test]
    public function it_deserializes_json_response(): void
    {
        $serializer = new FormSerializer();
        $json = '{"status":"ok","code":"00"}';

        $result = $serializer->deserialize($json);

        $this->assertSame(['status' => 'ok', 'code' => '00'], $result);
    }

    #[Test]
    public function it_deserializes_form_encoded_response(): void
    {
        $serializer = new FormSerializer();
        $form = 'status=ok&code=00';

        $result = $serializer->deserialize($form);

        $this->assertSame(['status' => 'ok', 'code' => '00'], $result);
    }

    #[Test]
    public function it_throws_on_garbage_input(): void
    {
        $serializer = new FormSerializer('TestGateway');

        $this->expectException(GatewayException::class);

        $serializer->deserialize('');
    }
}
