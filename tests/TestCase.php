<?php

namespace Noty\Laravel\Tests;

use Illuminate\Testing\TestResponse;
use Noty\Laravel\Facades\Noty;
use Noty\Laravel\Providers\NotyServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Cross-version shim: testbench-core writes to static::$latestResponse in
     * setUp / tearDown. The property used to live on
     * Illuminate\Foundation\Testing\Concerns\MakesHttpRequests (which Orchestra's
     * TestCase pulls in), but Laravel 12 removed it from that trait — leaving
     * testbench's static access pointing at an undeclared property. Declaring
     * it here gives the chain a stable home across Laravel 10 / 11 / 12.
     *
     * @var null|TestResponse
     */
    public static $latestResponse;

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
            'Noty' => Noty::class,
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
        $app['config']->set('noty.default_priority', 'NORMAL');
    }
}
