<?php

namespace Noty\Laravel\Providers;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;
use Noty\Laravel\Console\NotyQueueStatusCommand;
use Noty\Laravel\Notifications\Channels\NotyChannel;
use Noty\Laravel\Support\Client;
use Noty\Laravel\Support\Transports\HttpTransport;
use Noty\Laravel\Support\Transports\QueueTransport;
use Noty\Laravel\Support\Transports\TransportInterface;

class NotyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/noty.php', 'noty');

        $this->app->singleton(TransportInterface::class, function ($app) {
            $config = $app['config']['noty'];
            $dsn = $config['dsn'];
            $token = $config['token'] ?? null;
            $httpOptions = $config['http'];

            // Build endpoint
            $path = $httpOptions['path'] ?? '/api/v1/events';
            $endpoint = rtrim($dsn, '/') . $path;

            return match ($config['transport'] ?? 'http') {
                'queue' => new QueueTransport(
                    $endpoint,
                    $config['queue'],
                    $token,
                    $httpOptions
                ),
                default => new HttpTransport(
                    $dsn,
                    $httpOptions,
                    $token
                ),
            };
        });

        $this->app->singleton(Client::class, function ($app) {
            return new Client($app->make(TransportInterface::class));
        });
    }

    public function boot(): void
    {
        // Config publish
        $this->publishes([
            __DIR__ . '/../../config/noty.php' => config_path('noty.php'),
        ], 'config');

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                NotyQueueStatusCommand::class,
            ]);
        }

        // Custom notification channel: "noty"
        $this->app->make(ChannelManager::class)->extend('noty', function ($app) {
            return new NotyChannel($app->make(Client::class));
        });

        // Response gönderildikten sonra flush (non-blocking)
        $flushTimeout = (float) config('noty.flush_timeout', 1.0);
        $this->app->terminating(function () use ($flushTimeout) {
            try {
                app(Client::class)->flush($flushTimeout);
            } catch (\Throwable $e) {
                // yut
            }
        });
    }
}
