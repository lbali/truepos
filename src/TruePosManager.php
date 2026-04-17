<?php

declare(strict_types=1);

namespace TruePos;

use Illuminate\Contracts\Foundation\Application;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
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

    public function __construct(
        private readonly Application $app,
        private readonly GatewayFactory $factory,
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
        $ttl = config('truepos.threed_mapping_ttl', 3600);

        cache()->put("truepos_3d_{$orderId}", $gatewayName, $ttl);
    }

    private function resolve(string $name): GatewayInterface
    {
        $config = config("truepos.gateways.{$name}");

        if ($config === null) {
            throw InvalidConfigurationException::gatewayNotConfigured($name);
        }

        $driver = $config['driver'] ?? $name;
        $gatewayEnum = Gateway::from($driver);

        $gateway = $this->factory->create(
            gateway: $gatewayEnum,
            config: $config,
            httpClient: $this->app->make(ClientInterface::class),
        );

        // Apply decorators from config
        if ($config['logging'] ?? config('truepos.logging', false)) {
            $gateway = new LoggingGateway(
                $gateway,
                $this->app->make(LoggerInterface::class),
            );
        }

        if ($config['retry'] ?? false) {
            $gateway = new RetryGateway(
                $gateway,
                $config['retry_attempts'] ?? 2,
                $config['retry_delay_ms'] ?? 500,
            );
        }

        return $gateway;
    }

    private function defaultGateway(): string
    {
        $default = config('truepos.default');

        if ($default === null) {
            throw InvalidConfigurationException::noDefault();
        }

        return $default;
    }
}
