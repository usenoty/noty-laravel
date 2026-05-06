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

    /** @test */
    public function dispatchedJobCarriesResolvedChannelIdsAndConfiguredQueue(): void
    {
        Noty::captureEvent(['title' => 'Auth event', 'channel' => 'auth']);
        Noty::captureEvent(['title' => 'Payment event', 'channel' => 'payments']);

        Queue::assertPushed(SendNotyEvents::class, function (SendNotyEvents $job) {
            $events = (function () { return $this->events; })->call($job);

            return $job->queue === 'noty-test'
                && count($events) === 2
                && $events[0]['channel'] === 'channel_auth'
                && $events[1]['channel'] === 'channel_payments';
        });
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('noty.transport', 'queue');
        $app['config']->set('noty.queue.batch_size', 2);
        $app['config']->set('noty.queue.connection', null);
        $app['config']->set('noty.queue.queue_name', 'noty-test');
    }
}
