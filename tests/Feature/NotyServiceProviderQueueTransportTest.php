<?php

namespace Noty\Laravel\Tests\Feature;

use Noty\Laravel\Support\Transports\QueueTransport;
use Noty\Laravel\Support\Transports\TransportInterface;
use Noty\Laravel\Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class NotyServiceProviderQueueTransportTest extends TestCase
{
    /** @test */
    public function itBindsQueueTransportWhenConfigured(): void
    {
        $transport = $this->app->make(TransportInterface::class);

        $this->assertInstanceOf(QueueTransport::class, $transport);
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('noty.transport', 'queue');
    }
}
