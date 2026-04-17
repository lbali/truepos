<?php

declare(strict_types=1);

namespace TruePos\Providers;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use TruePos\Contracts\GatewayInterface;
use TruePos\Contracts\TransactionRepositoryInterface;
use TruePos\Factory\GatewayFactory;
use TruePos\Repositories\EloquentTransactionRepository;
use TruePos\Repositories\NullTransactionRepository;
use TruePos\TruePosManager;

/**
 * Laravel service provider for TruePos.
 *
 * This is the only file that bridges the framework-agnostic core
 * with Laravel's container, config, routes, and views.
 * For non-Laravel usage, instantiate TruePosManager directly.
 */
final class TruePosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/truepos.php', 'truepos');

        $this->app->singleton(GatewayFactory::class);

        // Register PSR-18 HTTP client if not already bound
        if (! $this->app->bound(ClientInterface::class)) {
            $this->app->bind(ClientInterface::class, function () {
                $verifySsl = config('truepos.verify_ssl', true);

                if (! $verifySsl && app()->environment('production')) {
                    throw new \RuntimeException('TruePos: verify_ssl cannot be disabled in production.');
                }

                return new Client([
                    'timeout' => config('truepos.http_timeout', 30),
                    'verify' => $verifySsl,
                ]);
            });
        }

        $this->app->singleton(TruePosManager::class, function ($app) {
            return new TruePosManager(
                config: config('truepos'),
                factory: $app->make(GatewayFactory::class),
                httpClient: $app->make(ClientInterface::class),
                logger: $app->make(LoggerInterface::class),
                cache: $app->make(CacheInterface::class),
            );
        });

        $this->app->bind(GatewayInterface::class, function ($app) {
            return $app->make(TruePosManager::class)->gateway();
        });

        $this->app->bind(TransactionRepositoryInterface::class, function () {
            return config('truepos.transaction_logging')
                ? new EloquentTransactionRepository()
                : new NullTransactionRepository();
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/truepos.php' => config_path('truepos.php'),
            ], 'truepos-config');

            $this->publishes([
                __DIR__ . '/../../database/migrations/' => database_path('migrations'),
            ], 'truepos-migrations');

            $this->publishes([
                __DIR__ . '/../../resources/views/' => resource_path('views/vendor/truepos'),
            ], 'truepos-views');
        }

        $this->loadRoutesFrom(__DIR__ . '/../../routes/truepos.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'truepos');
    }
}
