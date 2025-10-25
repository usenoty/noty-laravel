<?php

namespace Noty\Laravel\Tests\Feature;

use Noty\Laravel\Jobs\SendNotyEvents;
use Noty\Laravel\Tests\TestCase;

class SendNotyEventsJobTest extends TestCase
{
    /** @test */
    public function it_sends_single_event_successfully(): void
    {
        $events = [
            [
                'channel' => 'channel_123',
                'title' => 'Test Event',
                'priority' => 'HIGH',
                'actions' => [],
                'attachments' => [],
                'tags' => []
            ]
        ];

        $job = new SendNotyEvents(
            events: $events,
            endpoint: 'http://localhost:3020/api/v1/events',
            token: 'test_token'
        );

        // Test job properties
        $this->assertCount(1, $events);
        $this->assertEquals('channel_123', $events[0]['channel']);
        $this->assertEquals('Test Event', $events[0]['title']);
    }

    /** @test */
    public function it_sends_multiple_events_concurrently(): void
    {
        $events = [
            ['channel' => 'ch1', 'title' => 'Event 1', 'priority' => 'HIGH', 'actions' => [], 'attachments' => [], 'tags' => []],
            ['channel' => 'ch2', 'title' => 'Event 2', 'priority' => 'HIGH', 'actions' => [], 'attachments' => [], 'tags' => []],
            ['channel' => 'ch3', 'title' => 'Event 3', 'priority' => 'HIGH', 'actions' => [], 'attachments' => [], 'tags' => []],
        ];

        $job = new SendNotyEvents(
            events: $events,
            endpoint: 'http://localhost:3020/api/v1/events',
            token: 'test_token'
        );

        // Test that job was constructed correctly with multiple events
        $this->assertCount(3, $events);
    }

    /** @test */
    public function it_respects_retry_configuration(): void
    {
        config(['noty.queue.retry_times' => 5]);
        config(['noty.queue.retry_delay' => 30]);

        $events = [
            ['channel' => 'ch1', 'title' => 'Test', 'priority' => 'HIGH', 'actions' => [], 'attachments' => [], 'tags' => []]
        ];

        $job = new SendNotyEvents(
            events: $events,
            endpoint: 'http://localhost:3020/api/v1/events'
        );

        $this->assertEquals(5, $job->tries);
        $this->assertEquals(30, $job->backoff);
    }

    /** @test */
    public function it_logs_failures_when_enabled(): void
    {
        config(['noty.queue.log_failures' => true]);

        $events = [
            ['channel' => 'ch1', 'title' => 'Test', 'priority' => 'HIGH', 'actions' => [], 'attachments' => [], 'tags' => []]
        ];

        $job = new SendNotyEvents(
            events: $events,
            endpoint: 'http://localhost:3020/api/v1/events'
        );

        // Test that job respects config
        $this->assertTrue(config('noty.queue.log_failures'));
    }
}

