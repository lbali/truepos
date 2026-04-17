<?php

declare(strict_types=1);

namespace TruePos\Tests\Unit\Serializers;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TruePos\Exceptions\GatewayException;
use TruePos\Serializers\XmlSerializer;

final class XmlSerializerTest extends TestCase
{
    #[Test]
    public function it_serializes_to_valid_xml_with_correct_root_element(): void
    {
        $serializer = new XmlSerializer('Order');
        $data = ['Name' => 'Test', 'Amount' => '100'];

        $result = $serializer->serialize($data);

        $xml = simplexml_load_string($result);
        $this->assertNotFalse($xml);
        $this->assertSame('Order', $xml->getName());
        $this->assertSame('Test', (string) $xml->Name);
        $this->assertSame('100', (string) $xml->Amount);
    }

    #[Test]
    public function it_deserializes_xml_to_array(): void
    {
        $serializer = new XmlSerializer();
        $xml = '<?xml version="1.0"?><Response><Code>00</Code><Message>Success</Message></Response>';

        $result = $serializer->deserialize($xml);

        $this->assertSame(['Code' => '00', 'Message' => 'Success'], $result);
    }

    #[Test]
    public function it_throws_gateway_exception_on_invalid_xml(): void
    {
        $serializer = new XmlSerializer(gatewayName: 'TestGateway');

        $this->expectException(GatewayException::class);

        $serializer->deserialize('this is not xml at all <<<');
    }

    #[Test]
    public function it_returns_application_xml_content_type(): void
    {
        $serializer = new XmlSerializer();

        $this->assertSame('application/xml', $serializer->contentType());
    }

    #[Test]
    public function it_handles_nested_arrays_correctly(): void
    {
        $serializer = new XmlSerializer('Request');
        $data = [
            'Card' => [
                'Number' => '4111111111111111',
                'ExpDate' => '1225',
            ],
            'Amount' => '100',
        ];

        $result = $serializer->serialize($data);
        $deserialized = $serializer->deserialize($result);

        $this->assertSame('4111111111111111', $deserialized['Card']['Number']);
        $this->assertSame('1225', $deserialized['Card']['ExpDate']);
        $this->assertSame('100', $deserialized['Amount']);
    }
}
