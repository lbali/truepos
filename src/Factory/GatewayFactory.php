<?php

declare(strict_types=1);

namespace TruePos\Factory;

use Psr\Http\Client\ClientInterface;
use TruePos\Contracts\GatewayInterface;
use TruePos\Contracts\HashGeneratorInterface;
use TruePos\Contracts\ResponseParserInterface;
use TruePos\Contracts\SerializerInterface;
use TruePos\Enums\Gateway;
use TruePos\Exceptions\InvalidConfigurationException;
use TruePos\Serializers\FormSerializer;
use TruePos\Serializers\JsonSerializer;
use TruePos\Serializers\XmlSerializer;

/**
 * Registry-based gateway factory.
 *
 * Each gateway registers its components (serializer, hash generator, response parser,
 * gateway class) via the static registry. New gateways can be added without modifying
 * this class — just call GatewayFactory::register() from a service provider.
 */
final class GatewayFactory
{
    /**
     * @var array<string, array{
     *     gateway: class-string<GatewayInterface>,
     *     hashGenerator: class-string<HashGeneratorInterface>,
     *     responseParser: class-string<ResponseParserInterface>,
     *     serializer: SerializerInterface|class-string<SerializerInterface>,
     * }>
     */
    private static array $registry = [];

    private static bool $defaultsRegistered = false;

    /**
     * Register a gateway's components.
     */
    public static function register(
        Gateway $gateway,
        string $gatewayClass,
        string $hashGeneratorClass,
        string $responseParserClass,
        SerializerInterface|string $serializer,
    ): void {
        self::$registry[$gateway->value] = [
            'gateway' => $gatewayClass,
            'hashGenerator' => $hashGeneratorClass,
            'responseParser' => $responseParserClass,
            'serializer' => $serializer,
        ];
    }

    public function create(Gateway $gateway, array $config, ClientInterface $httpClient): GatewayInterface
    {
        self::ensureDefaultsRegistered();

        $entry = self::$registry[$gateway->value]
            ?? throw InvalidConfigurationException::gatewayNotConfigured($gateway->value);

        $serializer = $entry['serializer'] instanceof SerializerInterface
            ? $entry['serializer']
            : new $entry['serializer']();

        return new $entry['gateway'](
            config: $config,
            serializer: $serializer,
            hashGenerator: new $entry['hashGenerator'](),
            responseParser: new $entry['responseParser'](),
            httpClient: $httpClient,
        );
    }

    private static function ensureDefaultsRegistered(): void
    {
        if (self::$defaultsRegistered) {
            return;
        }

        self::$defaultsRegistered = true;

        // ─── Bank gateways (XML) ─────────────────────────────
        self::registerXml(Gateway::NestPay, 'CC5Request');
        self::registerXml(Gateway::Garanti, 'GVPSRequest');
        self::registerXml(Gateway::PosNet, 'posnetRequest');
        self::registerXml(Gateway::PayFor, 'PayforRequest');
        self::registerXml(Gateway::Vakifbank, 'VposRequest');
        self::registerXml(Gateway::KuveytTurk, 'KuveytTurkVPosMessage');

        // ─── JSON API gateways ───────────────────────────────
        self::registerJson(Gateway::Iyzico);
        self::registerJson(Gateway::Moka);
        self::registerJson(Gateway::Sipay);
        self::registerJson(Gateway::Param);
        self::registerJson(Gateway::Craftgate);
        self::registerJson(Gateway::EsnekPos);
        self::registerJson(Gateway::Lidio);

        // ─── Special serializers ─────────────────────────────
        self::registerWithSerializer(Gateway::PayTR, new FormSerializer('PayTR'));
        self::registerWithSerializer(Gateway::Tosla, new JsonSerializer('Tosla', 'application/json-patch+json'));
        self::registerWithSerializer(Gateway::Paratika, new FormSerializer('Paratika'));
    }

    private static function registerXml(Gateway $gateway, string $rootElement): void
    {
        $ns = self::gatewayNamespace($gateway);

        self::register(
            gateway: $gateway,
            gatewayClass: $ns . 'Gateway',
            hashGeneratorClass: $ns . 'HashGenerator',
            responseParserClass: $ns . 'ResponseParser',
            serializer: new XmlSerializer($rootElement, $gateway->label()),
        );
    }

    private static function registerJson(Gateway $gateway): void
    {
        $ns = self::gatewayNamespace($gateway);

        self::register(
            gateway: $gateway,
            gatewayClass: $ns . 'Gateway',
            hashGeneratorClass: $ns . 'HashGenerator',
            responseParserClass: $ns . 'ResponseParser',
            serializer: new JsonSerializer($gateway->label()),
        );
    }

    private static function registerWithSerializer(Gateway $gateway, SerializerInterface $serializer): void
    {
        $ns = self::gatewayNamespace($gateway);

        self::register(
            gateway: $gateway,
            gatewayClass: $ns . 'Gateway',
            hashGeneratorClass: $ns . 'HashGenerator',
            responseParserClass: $ns . 'ResponseParser',
            serializer: $serializer,
        );
    }

    /**
     * Derive the namespace for a gateway's classes from its enum case.
     * Gateway::NestPay → TruePos\Gateways\NestPay\NestPay
     */
    private static function gatewayNamespace(Gateway $gateway): string
    {
        $className = match ($gateway) {
            Gateway::NestPay => 'NestPay',
            Gateway::Garanti => 'Garanti',
            Gateway::PosNet => 'PosNet',
            Gateway::PayFor => 'PayFor',
            Gateway::Vakifbank => 'Vakifbank',
            Gateway::KuveytTurk => 'KuveytTurk',
            Gateway::PayTR => 'PayTR',
            Gateway::Iyzico => 'Iyzico',
            Gateway::Moka => 'Moka',
            Gateway::Sipay => 'Sipay',
            Gateway::Param => 'Param',
            Gateway::Tosla => 'Tosla',
            Gateway::Craftgate => 'Craftgate',
            Gateway::EsnekPos => 'EsnekPos',
            Gateway::Paratika => 'Paratika',
            Gateway::Lidio => 'Lidio',
        };

        return "TruePos\\Gateways\\{$className}\\{$className}";
    }
}
