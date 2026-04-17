<?php

declare(strict_types=1);

namespace TruePos;

use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use TruePos\Contracts\GatewayInterface;
use TruePos\Decorators\LoggingGateway;
use TruePos\Decorators\RetryGateway;
use TruePos\Enums\Gateway;
use TruePos\Exceptions\InvalidConfigurationException;
use TruePos\Factory\GatewayFactory;

final class TruePosManager
{
    /** @var array<string, GatewayInterface> */
    private array $gateways = [];

    /**
     * @param  array<string, mixed>  $config  Full truepos config array.
     */
    public function __construct(
        private readonly array $config,
        private readonly GatewayFactory $factory,
        private readonly ClientInterface $httpClient,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?CacheInterface $cache = null,
    ) {}

    /**
     * Get a gateway instance by config name (e.g., 'akbank', 'garanti', 'isbank').
     */
    public function gateway(?string $name = null): GatewayInterface
    {
        $name ??= $this->defaultGateway();

        return $this->gateways[$name] ??= $this->resolve($name);
    }

    /**
     * Store the orderId → gateway mapping for 3DS callback resolution.
     */
    public function registerThreeDMapping(string $orderId, string $gatewayName): void
    {
        $ttl = (int) ($this->config['threed_mapping_ttl'] ?? 3600);

        $this->cache?->set("truepos_3d_{$orderId}", $gatewayName, $ttl);
    }

    /**
     * Resolve the gateway name for a 3DS callback by orderId.
     */
    public function resolveThreeDMapping(string $orderId): ?string
    {
        return $this->cache?->get("truepos_3d_{$orderId}");
    }

    private function resolve(string $name): GatewayInterface
    {
        $gatewayConfig = $this->config['gateways'][$name] ?? null;

        if ($gatewayConfig === null) {
            throw InvalidConfigurationException::gatewayNotConfigured($name);
        }

        $driver = $gatewayConfig['driver'] ?? $name;
        $gatewayEnum = Gateway::from($driver);

        $gateway = $this->factory->create(
            gateway: $gatewayEnum,
            config: $gatewayConfig,
            httpClient: $this->httpClient,
        );

        // Apply decorators from config
        $loggingEnabled = $gatewayConfig['logging']
            ?? $this->config['logging']
            ?? false;

        if ($loggingEnabled && $this->logger !== null) {
            $gateway = new LoggingGateway($gateway, $this->logger);
        }

        if ($gatewayConfig['retry'] ?? false) {
            $gateway = new RetryGateway(
                $gateway,
                $gatewayConfig['retry_attempts'] ?? 2,
                $gatewayConfig['retry_delay_ms'] ?? 500,
            );
        }

        return $gateway;
    }

    private function defaultGateway(): string
    {
        $default = $this->config['default'] ?? null;

        if ($default === null) {
            throw InvalidConfigurationException::noDefault();
        }

        return $default;
    }
}
