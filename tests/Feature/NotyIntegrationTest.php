<?php

namespace Noty\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Noty\Laravel\Facades\Noty;
use Noty\Laravel\Jobs\SendNotyEvents;
use Noty\Laravel\Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class NotyIntegrationTest extends TestCase
{
    /** @test */
    public function itCapturesEventViaFacade(): void
    {
        $result = Noty::captureEvent([
            'title' => 'Test Title',
            'message' => 'Test Message',
        ]);

        $this->assertIsString($result);
    }

    /** @test */
    public function itResolvesNamedChannels(): void
    {
        $result = Noty::captureEvent([
            'title' => 'Payment received',
            'channel' => 'payments',
        ]);

        $this->assertIsString($result);
    }

    /** @test */
    public function itSendsFullFeaturedEvent(): void
    {
        $result = Noty::captureEvent([
            'title' => 'Test Event',
            'message' => 'Test message',
            'channel' => 'general',
            'priority' => 'HIGH',
            'actions' => [
                ['name' => 'Action 1', 'url' => 'http://example.com'],
            ],
            'tags' => ['user_id' => '123'],
        ]);

        $this->assertIsString($result);
    }

    /** @test */
    public function itUsesHelperFunction(): void
    {
        $result = noty()->captureEvent([
            'title' => 'Test from helper',
        ]);

        $this->assertIsString($result);
    }

    /** @test */
    public function itFlushesEvents(): void
    {
        Noty::captureEvent(['title' => 'Event 1']);
        Noty::captureEvent(['title' => 'Event 2']);

        // Should not throw exception
        Noty::flush();

        $this->assertTrue(true);
    }
}

/**
 * @internal
 *
 * @coversNothing
 */
class NotyQueueIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    /** @test */
    public function itDispatchesToQueueWhenBatchFull(): void
    {
        Noty::captureEvent(['title' => 'Event 1']);
        Queue::assertNothingPushed();

        Noty::captureEvent(['title' => 'Event 2']);
        Queue::assertPushed(SendNotyEvents::class, 1);
    }

    /** @test */
    public function itDispatchesRemainingEventsOnFlush(): void
    {
        Noty::captureEvent(['title' => 'Event 1']);
        Queue::assertNothingPushed();

        Noty::flush();
        Queue::assertPushed(SendNotyEvents::class, 1);
    }

    /** @test */
    public function itWorksWithMultipleChannelsInQueueMode(): void
    {
        Noty::captureEvent(['title' => 'Auth event', 'channel' => 'auth']);
        Noty::captureEvent(['title' => 'Payment event', 'channel' => 'payments']);

        Queue::assertPushed(SendNotyEvents::class, 1);
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Override to use queue transport
        $app['config']->set('noty.transport', 'queue');
        $app['config']->set('noty.queue.batch_size', 2);
        $app['config']->set('noty.queue.connection', null);
        $app['config']->set('noty.queue.queue_name', 'noty-test');
    }
}
