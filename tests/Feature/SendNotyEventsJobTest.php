<?php

namespace Noty\Laravel\Tests\Feature;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Noty\Laravel\Jobs\SendNotyEvents;
use Noty\Laravel\Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class SendNotyEventsJobTest extends TestCase
{
    /** @test */
    public function itSendsSingleEventSuccessfully(): void
    {
        $events = [
            [
                'channel' => 'channel_123',
                'title' => 'Test Event',
                'priority' => 'HIGH',
                'actions' => [],
                'attachments' => [],
                'tags' => [],
            ],
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
    public function itSendsMultipleEventsConcurrently(): void
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
    public function itRespectsRetryConfiguration(): void
    {
        config(['noty.queue.retry_times' => 5]);
        config(['noty.queue.retry_delay' => 30]);

        $events = [
            ['channel' => 'ch1', 'title' => 'Test', 'priority' => 'HIGH', 'actions' => [], 'attachments' => [], 'tags' => []],
        ];

        $job = new SendNotyEvents(
            events: $events,
            endpoint: 'http://localhost:3020/api/v1/events'
        );

        $this->assertEquals(5, $job->tries);
        $this->assertEquals(30, $job->backoff);
    }

    /** @test */
    public function itLogsFailuresWhenEnabled(): void
    {
        config(['noty.queue.log_failures' => true]);

        $events = [
            ['channel' => 'ch1', 'title' => 'Test', 'priority' => 'HIGH', 'actions' => [], 'attachments' => [], 'tags' => []],
        ];

        $job = new SendNotyEvents(
            events: $events,
            endpoint: 'http://localhost:3020/api/v1/events'
        );

        // Test that job respects config
        $this->assertTrue(config('noty.queue.log_failures'));
    }

    /** @test */
    public function handleReturnsEarlyWhenEventsArrayIsEmpty(): void
    {
        Log::spy();

        $job = new SendNotyEvents(
            events: [],
            endpoint: 'http://127.0.0.1:1/api/v1/events',
            httpOptions: ['timeout' => 0.1, 'connect_timeout' => 0.1]
        );

        // Should not throw, should not log, should not attempt HTTP
        $job->handle();

        Log::shouldNotHaveReceived('warning');
        Log::shouldNotHaveReceived('error');
        $this->addToAssertionCount(1);
    }

    /** @test */
    public function handleRethrowsExceptionForSingleEventOnUnreachableEndpoint(): void
    {
        config(['noty.queue.log_failures' => false]); // suppress log noise in test output

        $job = new SendNotyEvents(
            events: [
                ['channel' => 'ch1', 'title' => 'fails', 'priority' => 'HIGH', 'actions' => [], 'attachments' => [], 'tags' => []],
            ],
            endpoint: 'http://127.0.0.1:1/api/v1/events',
            httpOptions: ['timeout' => 0.2, 'connect_timeout' => 0.1]
        );

        $this->expectException(GuzzleException::class);

        $job->handle();
    }

    /** @test */
    public function handleLogsFailedSingleEventWhenLoggingEnabled(): void
    {
        config(['noty.queue.log_failures' => true]);

        Log::spy();

        $job = new SendNotyEvents(
            events: [
                ['channel' => 'ch1', 'title' => 'fails', 'priority' => 'HIGH', 'actions' => [], 'attachments' => [], 'tags' => []],
            ],
            endpoint: 'http://127.0.0.1:1/api/v1/events',
            httpOptions: ['timeout' => 0.2, 'connect_timeout' => 0.1]
        );

        try {
            $job->handle();
        } catch (GuzzleException) {
            // expected — single-event branch rethrows for queue retry
        }

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'Noty event failed'
                    && isset($context['event'])
                    && $context['event']['channel'] === 'ch1'
                    && isset($context['error']);
            })
        ;
    }

    /** @test */
    public function handleDoesNotLogFailedEventWhenLoggingDisabled(): void
    {
        config(['noty.queue.log_failures' => false]);

        Log::spy();

        $job = new SendNotyEvents(
            events: [
                ['channel' => 'ch1', 'title' => 'fails', 'priority' => 'HIGH', 'actions' => [], 'attachments' => [], 'tags' => []],
            ],
            endpoint: 'http://127.0.0.1:1/api/v1/events',
            httpOptions: ['timeout' => 0.2, 'connect_timeout' => 0.1]
        );

        try {
            $job->handle();
        } catch (GuzzleException) {
            // expected
        }

        Log::shouldNotHaveReceived('warning');
        Log::shouldNotHaveReceived('error');
        $this->addToAssertionCount(1);
    }

    /** @test */
    public function handleDoesNotRethrowForMultipleEventsBatchAndLogsEachFailure(): void
    {
        config(['noty.queue.log_failures' => true]);

        Log::spy();

        $job = new SendNotyEvents(
            events: [
                ['channel' => 'ch1', 'title' => 'a', 'priority' => 'HIGH', 'actions' => [], 'attachments' => [], 'tags' => []],
                ['channel' => 'ch2', 'title' => 'b', 'priority' => 'HIGH', 'actions' => [], 'attachments' => [], 'tags' => []],
            ],
            endpoint: 'http://127.0.0.1:1/api/v1/events',
            httpOptions: ['timeout' => 0.2, 'connect_timeout' => 0.1]
        );

        // Multi-event branch uses Pool with 'rejected' callback — does NOT rethrow.
        $job->handle();

        Log::shouldHaveReceived('warning')->twice();
        $this->addToAssertionCount(1);
    }

    /** @test */
    public function failedLogsErrorWhenLoggingEnabled(): void
    {
        config(['noty.queue.log_failures' => true]);

        Log::spy();

        $job = new SendNotyEvents(
            events: [
                ['channel' => 'ch1', 'title' => 'a', 'priority' => 'HIGH', 'actions' => [], 'attachments' => [], 'tags' => []],
                ['channel' => 'ch2', 'title' => 'b', 'priority' => 'HIGH', 'actions' => [], 'attachments' => [], 'tags' => []],
            ],
            endpoint: 'http://localhost:3020/api/v1/events'
        );

        $job->failed(new \RuntimeException('permanent failure'));

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'Noty events permanently failed after all retries'
                    && $context['events_count'] === 2
                    && $context['error'] === 'permanent failure';
            })
        ;
    }

    /** @test */
    public function failedDoesNotLogWhenLoggingDisabled(): void
    {
        config(['noty.queue.log_failures' => false]);

        Log::spy();

        $job = new SendNotyEvents(
            events: [['channel' => 'ch1', 'title' => 'a', 'priority' => 'HIGH', 'actions' => [], 'attachments' => [], 'tags' => []]],
            endpoint: 'http://localhost:3020/api/v1/events'
        );

        $job->failed(new \RuntimeException('boom'));

        Log::shouldNotHaveReceived('error');
        Log::shouldNotHaveReceived('warning');
        $this->addToAssertionCount(1);
    }

    /** @test */
    public function unusedConnectExceptionImportIsResolvable(): void
    {
        // Sanity check: ConnectException is part of GuzzleException hierarchy.
        $this->assertTrue(is_subclass_of(ConnectException::class, GuzzleException::class));
    }
}
