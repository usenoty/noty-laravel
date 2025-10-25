<?php

namespace Noty\Laravel\Tests\Unit;

use Illuminate\Support\Facades\Queue;
use Noty\Laravel\Jobs\SendNotyEvents;
use Noty\Laravel\Support\Event;
use Noty\Laravel\Support\Transports\QueueTransport;
use Noty\Laravel\Tests\TestCase;

class QueueTransportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    /** @test */
    public function it_batches_events_before_dispatching(): void
    {
        $transport = new QueueTransport(
            endpoint: 'http://localhost:3020/api/v1/events',
            queueConfig: ['batch_size' => 3, 'queue_name' => 'test-queue', 'connection' => null],
            token: 'test_token'
        );

        // Send 2 events - should not dispatch yet
        $transport->send(new Event('ch1', 'Event 1'));
        $transport->send(new Event('ch2', 'Event 2'));

        Queue::assertNothingPushed();

        // Send 3rd event - should trigger dispatch
        $transport->send(new Event('ch3', 'Event 3'));

        Queue::assertPushed(SendNotyEvents::class, 1);
    }

    /** @test */
    public function it_dispatches_remaining_events_on_flush(): void
    {
        $transport = new QueueTransport(
            endpoint: 'http://localhost:3020/api/v1/events',
            queueConfig: ['batch_size' => 10, 'queue_name' => 'test-queue', 'connection' => null]
        );

        $transport->send(new Event('ch1', 'Event 1'));
        $transport->send(new Event('ch2', 'Event 2'));

        Queue::assertNothingPushed();

        $transport->flush();

        Queue::assertPushed(SendNotyEvents::class, 1);
    }

    /** @test */
    public function it_uses_correct_queue_connection(): void
    {
        $transport = new QueueTransport(
            endpoint: 'http://localhost:3020/api/v1/events',
            queueConfig: [
                'batch_size' => 1,
                'connection' => 'redis',
                'queue_name' => 'noty-events'
            ]
        );

        $transport->send(new Event('ch1', 'Event 1'));

        Queue::assertPushed(SendNotyEvents::class, function ($job) {
            return $job->connection === 'redis' && $job->queue === 'noty-events';
        });
    }

    /** @test */
    public function it_dispatches_on_destruct(): void
    {
        $transport = new QueueTransport(
            endpoint: 'http://localhost:3020/api/v1/events',
            queueConfig: ['batch_size' => 10, 'queue_name' => 'test-queue', 'connection' => null]
        );

        $transport->send(new Event('ch1', 'Event 1'));

        Queue::assertNothingPushed();

        // Trigger destructor
        unset($transport);

        Queue::assertPushed(SendNotyEvents::class, 1);
    }

    /** @test */
    public function it_returns_channel_id_on_send(): void
    {
        $transport = new QueueTransport(
            endpoint: 'http://localhost:3020/api/v1/events',
            queueConfig: ['batch_size' => 10, 'connection' => null]
        );

        $result = $transport->send(new Event('channel_123', 'Test'));

        $this->assertEquals('channel_123', $result);
    }

    /** @test */
    public function it_handles_multiple_batches(): void
    {
        $transport = new QueueTransport(
            endpoint: 'http://localhost:3020/api/v1/events',
            queueConfig: ['batch_size' => 2, 'connection' => null]
        );

        // First batch
        $transport->send(new Event('ch1', 'Event 1'));
        $transport->send(new Event('ch2', 'Event 2'));

        // Second batch
        $transport->send(new Event('ch3', 'Event 3'));
        $transport->send(new Event('ch4', 'Event 4'));

        Queue::assertPushed(SendNotyEvents::class, 2);
    }
}

