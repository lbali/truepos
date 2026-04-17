<?php

declare(strict_types=1);

namespace TruePos\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use TruePos\Contracts\GatewayInterface;
use TruePos\Contracts\TransactionRepositoryInterface;
use TruePos\Repositories\NullTransactionRepository;
use TruePos\Tests\TestCase;
use TruePos\TruePosManager;

final class ServiceProviderTest extends TestCase
{
    #[Test]
    public function it_registers_the_manager_as_singleton(): void
    {
        $manager1 = $this->app->make(TruePosManager::class);
        $manager2 = $this->app->make(TruePosManager::class);

        $this->assertSame($manager1, $manager2);
    }

    #[Test]
    public function it_resolves_default_gateway(): void
    {
        $gateway = $this->app->make(GatewayInterface::class);

        $this->assertInstanceOf(GatewayInterface::class, $gateway);
    }

    #[Test]
    public function it_uses_null_repository_when_logging_disabled(): void
    {
        $this->app['config']->set('truepos.transaction_logging', false);

        $repo = $this->app->make(TransactionRepositoryInterface::class);

        $this->assertInstanceOf(NullTransactionRepository::class, $repo);
    }

    #[Test]
    public function it_loads_config(): void
    {
        $this->assertNotNull(config('truepos.default'));
    }
}
