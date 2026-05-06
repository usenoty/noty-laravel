<?php

namespace Noty\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Noty\Laravel\Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class NotyQueueStatusCommandTest extends TestCase
{
    /** @test */
    public function itReportsZeroPendingJobsForFakedQueue(): void
    {
        Queue::fake();

        $this->artisan('noty:status')
            ->expectsOutputToContain('Noty Queue Status')
            ->expectsOutputToContain('Queue Name: noty-events')
            ->expectsOutputToContain('Pending Jobs: 0')
            ->expectsOutputToContain('Configuration:')
            ->assertSuccessful()
        ;
    }

    /** @test */
    public function itPrintsConfiguredBatchAndRetryValues(): void
    {
        config([
            'noty.queue.batch_size' => 25,
            'noty.queue.retry_times' => 7,
            'noty.queue.retry_delay' => 90,
            'noty.queue.concurrency' => 4,
        ]);

        Queue::fake();

        $this->artisan('noty:status')
            ->expectsOutputToContain('Batch Size: 25')
            ->expectsOutputToContain('Retry Times: 7')
            ->expectsOutputToContain('Retry Delay: 90s')
            ->expectsOutputToContain('Concurrency: 4')
            ->assertSuccessful()
        ;
    }

    /** @test */
    public function itAcceptsConnectionOptionOverride(): void
    {
        Queue::fake();

        $this->artisan('noty:status', ['--connection' => 'sync'])
            ->expectsOutputToContain('Connection: sync')
            ->assertSuccessful()
        ;
    }
}
