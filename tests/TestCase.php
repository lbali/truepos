<?php

declare(strict_types=1);

namespace TruePos\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use TruePos\Providers\TruePosServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [TruePosServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'TruePos' => \TruePos\Facades\TruePos::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('truepos.default', 'test_nestpay');
        $app['config']->set('truepos.gateways.test_nestpay', [
            'driver' => 'nestpay',
            'client_id' => 'test_client',
            'username' => 'test_user',
            'password' => 'test_pass',
            'store_key' => 'test_store_key',
            'store_type' => '3d',
            'payment_url' => 'https://test.example.com/fim/api',
            'threed_gateway_url' => 'https://test.example.com/fim/est3Dgate',
            'lang' => 'tr',
        ]);
        $app['config']->set('truepos.gateways.test_garanti', [
            'driver' => 'garanti',
            'terminal_id' => '30691298',
            'merchant_id' => '7000679',
            'provision_user' => 'PROVAUT',
            'terminal_user' => 'PROVAUT',
            'provision_password' => '123qweASD/',
            'store_key' => '12345678',
            'test_mode' => true,
            'payment_url' => 'https://test.example.com/VPServlet',
            'threed_gateway_url' => 'https://test.example.com/servlet/gt3dengine',
        ]);
    }

    protected function fixturePath(string $path): string
    {
        return __DIR__ . '/Fixtures/' . $path;
    }

    protected function loadFixture(string $path): string
    {
        return file_get_contents($this->fixturePath($path));
    }
}
