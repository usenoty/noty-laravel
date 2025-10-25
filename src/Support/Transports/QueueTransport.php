<?php

namespace Noty\Laravel\Support\Transports;

use Illuminate\Support\Facades\Log;
use Noty\Laravel\Jobs\SendNotyEvents;
use Noty\Laravel\Support\Event;

class QueueTransport implements TransportInterface
{
    protected array $batch = [];
    protected int $batchSize;
    protected ?string $connection;
    protected string $queueName;
    protected string $endpoint;
    protected ?string $token;
    protected array $httpOptions;

    public function __construct(
        string $endpoint,
        array $queueConfig,
        ?string $token = null,
        array $httpOptions = []
    ) {
        $this->endpoint = $endpoint;
        $this->token = $token;
        $this->httpOptions = $httpOptions;
        $this->batchSize = $queueConfig['batch_size'] ?? 10;
        $this->connection = $queueConfig['connection'] ?? null;
        $this->queueName = $queueConfig['queue_name'] ?? 'noty-events';
    }

    public function send(Event $event): ?string
    {
        $this->batch[] = $event->toArray();

        // If batch is full, dispatch immediately
        if (count($this->batch) >= $this->batchSize) {
            $this->flushBatch();
        }

        return $event->channel;
    }

    public function flush(float $timeoutSeconds = 1.0): void
    {
        $this->flushBatch();
    }

    protected function flushBatch(): void
    {
        if (empty($this->batch)) {
            return;
        }

        try {
            $job = new SendNotyEvents(
                events: $this->batch,
                endpoint: $this->endpoint,
                token: $this->token,
                httpOptions: $this->httpOptions
            );

            if ($this->connection) {
                $job->onConnection($this->connection);
            }

            $job->onQueue($this->queueName);

            dispatch($job);

            $this->batch = [];
        } catch (\Throwable $e) {
            // Fail silently, but log if enabled
            if (config('noty.queue.log_failures', true)) {
                Log::warning('Failed to dispatch Noty events to queue', [
                    'error' => $e->getMessage(),
                ]);
            }
            $this->batch = [];
        }
    }

    public function __destruct()
    {
        // Ensure remaining events are flushed when object is destroyed
        $this->flushBatch();
    }
}
