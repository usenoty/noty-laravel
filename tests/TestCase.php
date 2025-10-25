<?php

namespace Noty\Laravel\Tests;

use Noty\Laravel\Providers\NotyServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            NotyServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Noty' => \Noty\Laravel\Facades\Noty::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Setup default configuration
        $app['config']->set('noty.dsn', 'http://localhost:3020');
        $app['config']->set('noty.token', 'test_token_123');
        $app['config']->set('noty.channels.default', 'general');
        $app['config']->set('noty.channels.list', [
            'general' => 'channel_general',
            'auth' => 'channel_auth',
            'payments' => 'channel_payments',
        ]);
        $app['config']->set('noty.transport', 'http');
        $app['config']->set('noty.default_priority', 'MEDIUM');
    }
}

