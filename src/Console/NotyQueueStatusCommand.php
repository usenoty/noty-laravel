<?php

namespace Noty\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class NotyQueueStatusCommand extends Command
{
    protected $signature = 'noty:status 
                            {--connection= : The queue connection to check}';

    protected $description = 'Show Noty queue status';

    public function handle(): int
    {
        $connection = $this->option('connection') ?? config('noty.queue.connection');
        $queueName = config('noty.queue.queue_name', 'noty-events');

        $this->info('Noty Queue Status');
        $this->line('');
        $this->line("Connection: " . ($connection ?: 'default'));
        $this->line("Queue Name: {$queueName}");
        $this->line('');

        try {
            $size = Queue::connection($connection)->size($queueName);
            $this->line("Pending Jobs: {$size}");
        } catch (\Exception $e) {
            $this->error('Could not retrieve queue size: ' . $e->getMessage());
            return 1;
        }

        $this->line('');
        $this->info('Configuration:');
        $this->line("  Batch Size: " . config('noty.queue.batch_size'));
        $this->line("  Retry Times: " . config('noty.queue.retry_times'));
        $this->line("  Retry Delay: " . config('noty.queue.retry_delay') . 's');
        $this->line("  Concurrency: " . config('noty.queue.concurrency'));

        $this->line('');
        $this->comment('To process the queue, run:');
        $this->line("  php artisan queue:work" . ($connection ? " {$connection}" : '') . " --queue={$queueName}");

        return 0;
    }
}

