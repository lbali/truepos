<?php

declare(strict_types=1);

namespace TruePos\Gateways\Vakifbank;

use TruePos\Contracts\SerializerInterface;
use TruePos\Exceptions\GatewayException;

final class VakifbankSerializer implements SerializerInterface
{
    public function serialize(array $data): string
    {
        $xml = new \SimpleXMLElement('<VposRequest/>');

        $this->arrayToXml($data, $xml);

        return $xml->asXML() ?: '';
    }

    public function deserialize(string $payload): array
    {
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($payload);

        if ($xml === false) {
            throw GatewayException::unexpectedResponse('Vakifbank', $payload);
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
