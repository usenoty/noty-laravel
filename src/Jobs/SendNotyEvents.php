<?php

namespace Noty\Laravel\Jobs;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Pool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotyEvents implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;
    public int $backoff;

    /**
     * @param array       $events      Array of event data
     * @param string      $endpoint    API endpoint
     * @param null|string $token       Bearer token
     * @param array       $httpOptions HTTP options
     */
    public function __construct(
        protected array $events,
        protected string $endpoint,
        protected ?string $token = null,
        protected array $httpOptions = []
    ) {
        $this->tries = config('noty.queue.retry_times', 3);
        $this->backoff = config('noty.queue.retry_delay', 60);
    }

    public function handle(): void
    {
        if (empty($this->events)) {
            return;
        }

        $client = new GuzzleClient([
            'timeout' => $this->httpOptions['timeout'] ?? 5.0,
            'connect_timeout' => $this->httpOptions['connect_timeout'] ?? 2.0,
        ]);

        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($this->token) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        // Single event
        if (count($this->events) === 1) {
            try {
                $client->post($this->endpoint, [
                    'headers' => $headers,
                    'json' => $this->events[0],
                ]);
            } catch (GuzzleException $e) {
                $this->handleFailure($e, $this->events[0]);

                throw $e; // Retry will be handled by Laravel Queue
            }

            return;
        }

        // Multiple events - use concurrent requests
        $requests = function () use ($client, $headers) {
            foreach ($this->events as $event) {
                yield function () use ($client, $headers, $event) {
                    return $client->postAsync($this->endpoint, [
                        'headers' => $headers,
                        'json' => $event,
                    ]);
                };
            }
        };

        $pool = new Pool($client, $requests(), [
            'concurrency' => config('noty.queue.concurrency', 5),
            'fulfilled' => function ($response, $index) {
                // Success
            },
            'rejected' => function ($reason, $index) {
                $event = $this->events[$index] ?? null;
                $this->handleFailure($reason, $event);
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();
    }

    public function failed(\Throwable $exception): void
    {
        if (config('noty.queue.log_failures', true)) {
            Log::error('Noty events permanently failed after all retries', [
                'events_count' => count($this->events),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function handleFailure($exception, ?array $event): void
    {
        if (config('noty.queue.log_failures', true)) {
            Log::warning('Noty event failed', [
                'event' => $event,
                'error' => $exception instanceof \Throwable ? $exception->getMessage() : (string) $exception,
                'attempt' => $this->attempts(),
            ]);
        }
    }
}
