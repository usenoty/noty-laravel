<?php

namespace Noty\Laravel\Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;
use Noty\Laravel\Notifications\Channels\NotyChannel;
use Noty\Laravel\Support\Client;
use Noty\Laravel\Support\Transports\HttpTransport;
use Noty\Laravel\Support\Transports\TransportInterface;
use Noty\Laravel\Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class NotyServiceProviderTest extends TestCase
{
    /** @test */
    public function itBindsHttpTransportByDefault(): void
    {
        $transport = $this->app->make(TransportInterface::class);

        $this->assertInstanceOf(HttpTransport::class, $transport);
    }

    /** @test */
    public function itBindsTransportAndClientAsSingletons(): void
    {
        $transportA = $this->app->make(TransportInterface::class);
        $transportB = $this->app->make(TransportInterface::class);
        $clientA = $this->app->make(Client::class);
        $clientB = $this->app->make(Client::class);

        $this->assertSame($transportA, $transportB);
        $this->assertSame($clientA, $clientB);
    }

    /** @test */
    public function itRegistersNotyNotificationChannel(): void
    {
        /** @var ChannelManager $manager */
        $manager = $this->app->make(ChannelManager::class);

        $channel = $manager->driver('noty');

        $this->assertInstanceOf(NotyChannel::class, $channel);
    }

    /** @test */
    public function terminatingCallbackInvokesClientFlush(): void
    {
        $client = \Mockery::mock(Client::class);
        $client->shouldReceive('flush')->once();

        $this->app->instance(Client::class, $client);

        // Simulate Laravel's request lifecycle terminating phase.
        $this->app->terminate();

        \Mockery::close();
        $this->addToAssertionCount(1);
    }

    /** @test */
    public function terminatingCallbackSwallowsFlushExceptions(): void
    {
        $client = \Mockery::mock(Client::class);
        $client->shouldReceive('flush')
            ->once()
            ->andThrow(new \RuntimeException('flush blew up'))
        ;

        $this->app->instance(Client::class, $client);

        // Must not throw despite the underlying flush failing.
        $this->app->terminate();

        \Mockery::close();
        $this->addToAssertionCount(1);
    }

    /** @test */
    public function itPublishesConfigUnderConfigGroup(): void
    {
        $publishGroups = ServiceProvider::$publishGroups;

        $this->assertArrayHasKey('config', $publishGroups);

        $configPaths = $publishGroups['config'];
        $hasNotyConfig = false;
        foreach ($configPaths as $source => $target) {
            if (str_ends_with($source, '/config/noty.php') && str_ends_with($target, 'noty.php')) {
                $hasNotyConfig = true;

                break;
            }
        }

        $this->assertTrue($hasNotyConfig, 'noty config file should be registered for publishing');
    }

    /** @test */
    public function consoleCommandIsRegistered(): void
    {
        $kernel = $this->app->make(Kernel::class);
        $commands = $kernel->all();

        $this->assertArrayHasKey('noty:status', $commands);
    }
}
