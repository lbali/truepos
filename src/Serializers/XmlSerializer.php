<?php

declare(strict_types=1);

namespace TruePos\Serializers;

use TruePos\Contracts\SerializerInterface;
use TruePos\Exceptions\GatewayException;

final class XmlSerializer implements SerializerInterface
{
    public function __construct(
        private readonly string $rootElement = 'Request',
        private readonly string $gatewayName = 'Unknown',
    ) {}

    public function serialize(array $data): string
    {
        $xml = new \SimpleXMLElement("<{$this->rootElement}/>");

        $this->arrayToXml($data, $xml);

        return $xml->asXML() ?: '';
    }

    public function deserialize(string $payload): array
    {
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($payload, 'SimpleXMLElement', LIBXML_NONET);

        if ($xml === false) {
            throw GatewayException::unexpectedResponse($this->gatewayName, $payload);
        }

        return $this->xmlToArray($xml);
    }

    public function contentType(): string
    {
        return 'application/xml';
    }

    private function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $child = $xml->addChild((string) $key);
                $this->arrayToXml($value, $child);
            } else {
                $xml->addChild((string) $key, htmlspecialchars((string) $value, ENT_XML1));
            }
        }
    }

    private function xmlToArray(\SimpleXMLElement $xml): array
    {
        $result = [];

        foreach ($xml->children() as $key => $value) {
            if ($value->count() > 0) {
                $result[$key] = $this->xmlToArray($value);
            } else {
                $result[$key] = (string) $value;
            }
        }

        return $result;
    }
}
