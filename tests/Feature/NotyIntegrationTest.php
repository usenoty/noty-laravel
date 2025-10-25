<?php

namespace Noty\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Noty\Laravel\Facades\Noty;
use Noty\Laravel\Jobs\SendNotyEvents;
use Noty\Laravel\Tests\TestCase;

class NotyIntegrationTest extends TestCase
{
    /** @test */
    public function it_captures_event_via_facade(): void
    {
        $result = Noty::captureEvent([
            'title' => 'Test Title',
            'message' => 'Test Message',
        ]);

        $this->assertIsString($result);
    }

    /** @test */
    public function it_resolves_named_channels(): void
    {
        $result = Noty::captureEvent([
            'title' => 'Payment received',
            'channel' => 'payments',
        ]);

        $this->assertIsString($result);
    }

    /** @test */
    public function it_sends_full_featured_event(): void
    {
        $result = Noty::captureEvent([
            'title' => 'Test Event',
            'message' => 'Test message',
            'channel' => 'general',
            'priority' => 'HIGH',
            'actions' => [
                ['name' => 'Action 1', 'url' => 'http://example.com']
            ],
            'tags' => ['user_id' => '123']
        ]);

        $this->assertIsString($result);
    }

    /** @test */
    public function it_uses_helper_function(): void
    {
        $result = noty()->captureEvent([
            'title' => 'Test from helper',
        ]);

        $this->assertIsString($result);
    }

    /** @test */
    public function it_flushes_events(): void
    {
        Noty::captureEvent(['title' => 'Event 1']);
        Noty::captureEvent(['title' => 'Event 2']);

        // Should not throw exception
        Noty::flush();

        $this->assertTrue(true);
    }
}

class NotyQueueIntegrationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Override to use queue transport
        $app['config']->set('noty.transport', 'queue');
        $app['config']->set('noty.queue.batch_size', 2);
        $app['config']->set('noty.queue.connection', null);
        $app['config']->set('noty.queue.queue_name', 'noty-test');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    /** @test */
    public function it_dispatches_to_queue_when_batch_full(): void
    {
        Noty::captureEvent(['title' => 'Event 1']);
        Queue::assertNothingPushed();

        Noty::captureEvent(['title' => 'Event 2']);
        Queue::assertPushed(SendNotyEvents::class, 1);
    }

    /** @test */
    public function it_dispatches_remaining_events_on_flush(): void
    {
        Noty::captureEvent(['title' => 'Event 1']);
        Queue::assertNothingPushed();

        Noty::flush();
        Queue::assertPushed(SendNotyEvents::class, 1);
    }

    /** @test */
    public function it_works_with_multiple_channels_in_queue_mode(): void
    {
        Noty::captureEvent(['title' => 'Auth event', 'channel' => 'auth']);
        Noty::captureEvent(['title' => 'Payment event', 'channel' => 'payments']);

        Queue::assertPushed(SendNotyEvents::class, 1);
    }
}

